<?php

namespace Knackline\Olm\Drivers;

use Knackline\Olm\Contracts\SupportsModelPull;
use Knackline\Olm\DTO\ModelInfo;

class OllamaDriver extends OpenAICompatibleDriver implements SupportsModelPull
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        parent::__construct('ollama', $config);
    }

    public function models(): array
    {
        // Prefer native tags endpoint (richer metadata); fall back to OpenAI path.
        $response = $this->http()->get('/api/tags');

        if ($response->successful()) {
            $models = $response->json('models') ?? [];

            return array_map(
                static fn (array $item) => ModelInfo::fromOllama($item),
                is_array($models) ? $models : []
            );
        }

        return parent::models();
    }

    public function health(): bool
    {
        try {
            $response = $this->http()->get('/');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function pull(string $model, array $options = []): array
    {
        $payload = array_merge([
            'name' => $model,
            'stream' => false,
        ], $options);

        $response = $this->http()->post('/api/pull', $payload);
        $this->ensureSuccessful($response, 'pull');

        return $response->json() ?? [];
    }

    public function show(string $model): array|ModelInfo
    {
        $response = $this->http()->post('/api/show', [
            'name' => $model,
        ]);
        $this->ensureSuccessful($response, 'show');

        $data = $response->json() ?? [];

        return ModelInfo::fromOllama(array_merge(['name' => $model], is_array($data) ? $data : []));
    }
}
