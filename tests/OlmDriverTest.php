<?php

namespace Knackline\Olm\Tests;

use Illuminate\Support\Facades\Http;
use Knackline\Olm\Drivers\LlamaCppDriver;
use Knackline\Olm\Drivers\LmStudioDriver;
use Knackline\Olm\Drivers\MlxDriver;
use Knackline\Olm\Drivers\OllamaDriver;
use Knackline\Olm\Drivers\VllmDriver;
use Knackline\Olm\Exceptions\UnsupportedOperationException;
use Knackline\Olm\Facades\Olm;
use Knackline\Olm\Providers\OlmServiceProvider;
use Orchestra\Testbench\TestCase;

class OlmDriverTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [OlmServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Olm' => Olm::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('olm.default', 'ollama');
        $app['config']->set('olm.drivers.ollama', [
            'base_url' => 'http://ollama.test',
            'api_key' => null,
            'model' => 'llama3.2',
            'timeout' => 30,
        ]);
        $app['config']->set('olm.drivers.mlx', [
            'base_url' => 'http://mlx.test',
            'api_key' => null,
            'model' => 'mlx-community/test',
            'timeout' => 30,
        ]);
        $app['config']->set('olm.drivers.lmstudio', [
            'base_url' => 'http://lmstudio.test',
            'api_key' => 'lm-studio',
            'model' => 'local-model',
            'timeout' => 30,
        ]);
        $app['config']->set('olm.drivers.llamacpp', [
            'base_url' => 'http://llamacpp.test',
            'api_key' => null,
            'model' => 'gguf-model',
            'timeout' => 30,
        ]);
        $app['config']->set('olm.drivers.vllm', [
            'base_url' => 'http://vllm.test',
            'api_key' => null,
            'model' => 'vllm-model',
            'timeout' => 30,
        ]);
    }

    public function test_chat_via_openai_compatible_driver(): void
    {
        Http::fake([
            'mlx.test/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-1',
                'model' => 'mlx-community/test',
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Hello from MLX'],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => ['total_tokens' => 10],
            ], 200),
        ]);

        $response = Olm::driver('mlx')->chat([
            ['role' => 'user', 'content' => 'Hi'],
        ]);

        $this->assertSame('Hello from MLX', $response->content);
        $this->assertSame('mlx-community/test', $response->model);
    }

    public function test_all_openai_compatible_drivers_chat(): void
    {
        $cases = [
            'lmstudio' => 'http://lmstudio.test/v1/chat/completions',
            'llamacpp' => 'http://llamacpp.test/v1/chat/completions',
            'vllm' => 'http://vllm.test/v1/chat/completions',
        ];

        Http::fake([
            'lmstudio.test/v1/chat/completions' => Http::response($this->chatPayload('local-model', 'from lmstudio'), 200),
            'llamacpp.test/v1/chat/completions' => Http::response($this->chatPayload('gguf-model', 'from llamacpp'), 200),
            'vllm.test/v1/chat/completions' => Http::response($this->chatPayload('vllm-model', 'from vllm'), 200),
        ]);

        foreach (array_keys($cases) as $driver) {
            $response = Olm::driver($driver)->chat([
                ['role' => 'user', 'content' => 'Hi'],
            ]);
            $this->assertStringContainsString($driver, $response->content);
        }
    }

    public function test_models_openai_compatible(): void
    {
        Http::fake([
            'vllm.test/v1/models' => Http::response([
                'data' => [
                    ['id' => 'model-a', 'owned_by' => 'vllm', 'created' => 1],
                ],
            ], 200),
        ]);

        $models = Olm::driver('vllm')->models();

        $this->assertCount(1, $models);
        $this->assertSame('model-a', $models[0]->id);
    }

    public function test_embeddings(): void
    {
        Http::fake([
            'ollama.test/v1/embeddings' => Http::response([
                'model' => 'llama3.2',
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3]],
                ],
                'usage' => [],
            ], 200),
        ]);

        $response = Olm::embed('hello world');

        $this->assertSame([0.1, 0.2, 0.3], $response->first());
    }

    public function test_complete(): void
    {
        Http::fake([
            'llamacpp.test/v1/completions' => Http::response([
                'id' => 'cmpl-1',
                'model' => 'gguf-model',
                'choices' => [
                    ['text' => 'completed text', 'finish_reason' => 'stop'],
                ],
            ], 200),
        ]);

        $response = Olm::driver('llamacpp')->complete('Once upon');

        $this->assertSame('completed text', $response->content);
    }

    public function test_ollama_models_uses_native_tags(): void
    {
        Http::fake([
            'ollama.test/api/tags' => Http::response([
                'models' => [
                    ['name' => 'llama3.2:latest', 'modified_at' => '2024-01-01T00:00:00Z'],
                ],
            ], 200),
        ]);

        $models = Olm::driver('ollama')->models();

        $this->assertCount(1, $models);
        $this->assertSame('llama3.2:latest', $models[0]->id);
        $this->assertSame('ollama', $models[0]->ownedBy);
    }

    public function test_ollama_pull_and_show(): void
    {
        Http::fake([
            'ollama.test/api/pull' => Http::response(['status' => 'success'], 200),
            'ollama.test/api/show' => Http::response([
                'modelfile' => 'FROM llama3.2',
                'parameters' => '',
            ], 200),
        ]);

        $pull = Olm::pull('llama3.2');
        $this->assertSame('success', $pull['status']);

        $show = Olm::show('llama3.2');
        $this->assertSame('llama3.2', $show->id);
    }

    public function test_pull_unsupported_on_mlx(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $this->app['config']->set('olm.default', 'mlx');
        $this->app->forgetInstance('olm');
        Olm::clearResolvedInstances();

        Olm::pull('some-model');
    }

    public function test_health_checks(): void
    {
        Http::fake([
            'ollama.test/' => Http::response('Ollama is running', 200),
            'mlx.test/v1/models' => Http::response(['data' => []], 200),
        ]);

        $this->assertTrue(Olm::driver('ollama')->health());
        $this->assertTrue(Olm::driver('mlx')->health());
    }

    public function test_helper_returns_manager_and_driver(): void
    {
        $this->assertSame('ollama', olm()->getDefaultDriver());
        $this->assertInstanceOf(OllamaDriver::class, olm('ollama'));
        $this->assertInstanceOf(MlxDriver::class, olm('mlx'));
        $this->assertInstanceOf(LmStudioDriver::class, olm('lmstudio'));
        $this->assertInstanceOf(LlamaCppDriver::class, olm('llamacpp'));
        $this->assertInstanceOf(VllmDriver::class, olm('vllm'));
    }

    /**
     * @return array<string, mixed>
     */
    private function chatPayload(string $model, string $content): array
    {
        return [
            'id' => 'chatcmpl-test',
            'model' => $model,
            'choices' => [
                [
                    'message' => ['role' => 'assistant', 'content' => $content],
                    'finish_reason' => 'stop',
                ],
            ],
        ];
    }
}
