<?php

namespace Knackline\Olm\Tests;

use Illuminate\Support\Facades\Artisan;
use Knackline\Olm\Exceptions\OlmException;
use Knackline\Olm\Facades\Olm;
use Knackline\Olm\Olm as OlmDev;
use Knackline\Olm\Providers\OlmServiceProvider;
use Knackline\Olm\Support\ServeCommandBuilder;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class ServeCommandTest extends TestCase
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
        $app['config']->set('olm.dev.enabled', true);
        $app['config']->set('olm.drivers.ollama', [
            'base_url' => 'http://127.0.0.1:11434',
            'api_key' => null,
            'model' => 'llama3.2',
            'timeout' => 30,
            'serve' => null,
        ]);
        $app['config']->set('olm.drivers.mlx', [
            'base_url' => 'http://127.0.0.1:8080',
            'api_key' => null,
            'model' => 'mlx-community/test',
            'timeout' => 30,
            'serve' => null,
        ]);
        $app['config']->set('olm.drivers.lmstudio', [
            'base_url' => 'http://127.0.0.1:1234',
            'api_key' => 'lm-studio',
            'model' => 'local-model',
            'timeout' => 30,
            'serve' => null,
        ]);
        $app['config']->set('olm.drivers.llamacpp', [
            'base_url' => 'http://127.0.0.1:8080',
            'api_key' => null,
            'model' => '/models/model.gguf',
            'timeout' => 30,
            'serve' => null,
        ]);
        $app['config']->set('olm.drivers.vllm', [
            'base_url' => 'http://127.0.0.1:8000',
            'api_key' => null,
            'model' => 'vllm-model',
            'timeout' => 30,
            'serve' => null,
        ]);
    }

    public function test_olm_serve_command_is_registered(): void
    {
        $this->assertArrayHasKey('olm:serve', Artisan::all());
    }

    public function test_builder_ollama_default(): void
    {
        $command = (new ServeCommandBuilder)->build('ollama', config('olm.drivers.ollama'));

        $this->assertSame('ollama serve', $command);
    }

    public function test_builder_mlx_default(): void
    {
        $command = (new ServeCommandBuilder)->build('mlx', config('olm.drivers.mlx'));

        $this->assertSame('mlx_lm.server --model '.escapeshellarg('mlx-community/test'), $command);
    }

    public function test_builder_mlx_custom_host_port(): void
    {
        $command = (new ServeCommandBuilder)->build('mlx', [
            'base_url' => 'http://0.0.0.0:9090',
            'model' => 'mlx-community/test',
        ]);

        $this->assertSame(
            'mlx_lm.server --model '.escapeshellarg('mlx-community/test')
            .' --host '.escapeshellarg('0.0.0.0').' --port 9090',
            $command
        );
    }

    public function test_builder_llamacpp_with_model(): void
    {
        $command = (new ServeCommandBuilder)->build('llamacpp', config('olm.drivers.llamacpp'));

        $this->assertSame(
            'llama-server --host '.escapeshellarg('127.0.0.1').' --port 8080'
            .' --model '.escapeshellarg('/models/model.gguf'),
            $command
        );
    }

    public function test_builder_vllm_default(): void
    {
        $command = (new ServeCommandBuilder)->build('vllm', config('olm.drivers.vllm'));

        $this->assertSame(
            'vllm serve '.escapeshellarg('vllm-model')
            .' --host '.escapeshellarg('127.0.0.1').' --port 8000',
            $command
        );
    }

    public function test_builder_respects_serve_override(): void
    {
        $command = (new ServeCommandBuilder)->build('ollama', [
            'serve' => 'custom-ollama-wrapper',
            'model' => 'llama3.2',
        ]);

        $this->assertSame('custom-ollama-wrapper', $command);
    }

    public function test_builder_rejects_lmstudio(): void
    {
        $this->expectException(OlmException::class);
        $this->expectExceptionMessage('LM Studio cannot be started from the CLI');

        (new ServeCommandBuilder)->build('lmstudio', config('olm.drivers.lmstudio'));
    }

    public function test_builder_requires_model_for_mlx(): void
    {
        $this->expectException(OlmException::class);
        $this->expectExceptionMessage('requires a model');

        (new ServeCommandBuilder)->build('mlx', [
            'base_url' => 'http://127.0.0.1:8080',
            'model' => null,
        ]);
    }

    public function test_builder_requires_model_for_vllm(): void
    {
        $this->expectException(OlmException::class);
        $this->expectExceptionMessage('requires a model');

        (new ServeCommandBuilder)->build('vllm', [
            'base_url' => 'http://127.0.0.1:8000',
            'model' => '',
        ]);
    }

    public function test_artisan_olm_serve_fails_for_lmstudio(): void
    {
        $this->app['config']->set('olm.default', 'lmstudio');

        $this->artisan('olm:serve')
            ->expectsOutputToContain('LM Studio cannot be started from the CLI')
            ->assertFailed();
    }

    public function test_register_dev_commands_is_noop_without_dev_commands_class(): void
    {
        if (class_exists(\Illuminate\Foundation\DevCommands::class)) {
            $this->markTestSkipped('DevCommands is available in this Laravel version.');
        }

        OlmDev::registerDevCommands();

        $this->assertTrue(true);
    }

    public function test_register_dev_commands_skips_when_disabled(): void
    {
        if (! class_exists(\Illuminate\Foundation\DevCommands::class)) {
            $this->markTestSkipped('DevCommands is not available.');
        }

        $this->clearDevCommands();
        $this->app['config']->set('olm.dev.enabled', false);
        $this->app['config']->set('olm.default', 'ollama');

        OlmDev::registerDevCommands();

        $names = array_column(\Illuminate\Foundation\DevCommands::commands(), 'name');

        $this->assertNotContains('olm', $names);
    }

    public function test_register_dev_commands_skips_lmstudio(): void
    {
        if (! class_exists(\Illuminate\Foundation\DevCommands::class)) {
            $this->markTestSkipped('DevCommands is not available.');
        }

        $this->clearDevCommands();
        $this->app['config']->set('olm.default', 'lmstudio');
        $this->app['config']->set('olm.dev.enabled', true);

        OlmDev::registerDevCommands();

        $names = array_column(\Illuminate\Foundation\DevCommands::commands(), 'name');

        $this->assertNotContains('olm', $names);
    }

    public function test_register_dev_commands_registers_olm_process(): void
    {
        if (! class_exists(\Illuminate\Foundation\DevCommands::class)) {
            $this->markTestSkipped('DevCommands is not available.');
        }

        $this->clearDevCommands();
        $this->app['config']->set('olm.default', 'ollama');
        $this->app['config']->set('olm.dev.enabled', true);

        OlmDev::registerDevCommands();

        $commands = \Illuminate\Foundation\DevCommands::commands();
        $olm = collect($commands)->firstWhere('name', 'olm');

        $this->assertNotNull($olm);
        $this->assertStringContainsString('olm:serve', $olm['command']);
    }

    protected function clearDevCommands(): void
    {
        $reflection = new ReflectionClass(\Illuminate\Foundation\DevCommands::class);
        $property = $reflection->getProperty('commands');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
