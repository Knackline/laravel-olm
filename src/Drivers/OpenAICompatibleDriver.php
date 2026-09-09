<?php

namespace Knackline\Olm\Drivers;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Knackline\Olm\Contracts\DriverInterface;
use Knackline\Olm\DTO\ChatResponse;
use Knackline\Olm\DTO\EmbeddingResponse;
use Knackline\Olm\DTO\ModelInfo;
use Knackline\Olm\Exceptions\OlmException;
use Knackline\Olm\Exceptions\RequestException;

class OpenAICompatibleDriver implements DriverInterface
{
    protected string $name;

    protected string $baseUrl;

    protected ?string $apiKey;

    protected ?string $defaultModel;

    protected int $timeout;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->apiKey = isset($config['api_key']) && $config['api_key'] !== ''
            ? (string) $config['api_key']
            : null;
        $this->defaultModel = isset($config['model']) && $config['model'] !== ''
            ? (string) $config['model']
            : null;
        $this->timeout = (int) ($config['timeout'] ?? 120);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function chat(array $messages, array $options = []): ChatResponse
    {
        $payload = array_merge([
            'model' => $this->resolveModel($options),
            'messages' => $messages,
            'stream' => false,
        ], $this->withoutReserved($options));

        $response = $this->http()->post('/v1/chat/completions', $payload);
        $this->ensureSuccessful($response, 'chat');

        return ChatResponse::fromOpenAi($response->json() ?? []);
    }

    public function streamChat(array $messages, array $options = []): Generator
    {
        $payload = array_merge([
            'model' => $this->resolveModel($options),
            'messages' => $messages,
            'stream' => true,
        ], $this->withoutReserved($options));

        $response = $this->http()
            ->withOptions(['stream' => true])
            ->post('/v1/chat/completions', $payload);

        if ($response->failed()) {
            throw RequestException::fromResponse($response, 'streamChat');
        }

        $body = $response->toPsrResponse()->getBody();

        $buffer = '';
        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));

                if ($data === '[DONE]') {
                    return;
                }

                $json = json_decode($data, true);

                if (! is_array($json)) {
                    continue;
                }

                $delta = $json['choices'][0]['delta']['content']
                    ?? $json['choices'][0]['text']
                    ?? null;

                if (is_string($delta) && $delta !== '') {
                    yield $delta;
                }
            }
        }
    }

    public function complete(string $prompt, array $options = []): ChatResponse
    {
        $payload = array_merge([
            'model' => $this->resolveModel($options),
            'prompt' => $prompt,
            'stream' => false,
        ], $this->withoutReserved($options));

        $response = $this->http()->post('/v1/completions', $payload);
        $this->ensureSuccessful($response, 'complete');

        return ChatResponse::fromOpenAi($response->json() ?? []);
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponse
    {
        $payload = array_merge([
            'model' => $this->resolveModel($options),
            'input' => $input,
        ], $this->withoutReserved($options));

        $response = $this->http()->post('/v1/embeddings', $payload);
        $this->ensureSuccessful($response, 'embed');

        return EmbeddingResponse::fromOpenAi($response->json() ?? []);
    }

    public function models(): array
    {
        $response = $this->http()->get('/v1/models');
        $this->ensureSuccessful($response, 'models');

        $data = $response->json('data') ?? [];

        return array_map(
            static fn (array $item) => ModelInfo::fromOpenAi($item),
            is_array($data) ? $data : []
        );
    }

    public function health(): bool
    {
        try {
            $response = $this->http()->get('/v1/models');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function http(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'User-Agent' => 'knackline/laravel-olm',
            ]);

        if ($this->apiKey !== null) {
            $request = $request->withToken($this->apiKey);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function resolveModel(array $options): string
    {
        $model = $options['model'] ?? $this->defaultModel;

        if ($model === null || $model === '') {
            throw new OlmException(
                "No model specified for driver [{$this->name}]. Pass options['model'] or set the driver default model in config."
            );
        }

        return (string) $model;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function withoutReserved(array $options): array
    {
        unset($options['model'], $options['messages'], $options['prompt'], $options['input'], $options['stream']);

        return $options;
    }

    protected function ensureSuccessful(Response $response, string $context): void
    {
        if ($response->failed()) {
            throw RequestException::fromResponse($response, $context);
        }
    }
}
