<?php

namespace Knackline\Olm;

use Illuminate\Support\Manager;
use Knackline\Olm\Contracts\DriverInterface;
use Knackline\Olm\Contracts\SupportsModelPull;
use Knackline\Olm\Drivers\LlamaCppDriver;
use Knackline\Olm\Drivers\LmStudioDriver;
use Knackline\Olm\Drivers\MlxDriver;
use Knackline\Olm\Drivers\OllamaDriver;
use Knackline\Olm\Drivers\VllmDriver;
use Knackline\Olm\Exceptions\DriverException;
use Knackline\Olm\Exceptions\UnsupportedOperationException;

/**
 * @method \Knackline\Olm\DTO\ChatResponse chat(array $messages, array $options = [])
 * @method \Generator streamChat(array $messages, array $options = [])
 * @method \Knackline\Olm\DTO\ChatResponse complete(string $prompt, array $options = [])
 * @method \Knackline\Olm\DTO\EmbeddingResponse embed(string|array $input, array $options = [])
 * @method array models()
 * @method bool health()
 */
class OlmManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->container['config']->get('olm.default', 'ollama');
    }

    public function driver($driver = null): DriverInterface
    {
        return parent::driver($driver);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function pull(string $model, array $options = []): array
    {
        $driver = $this->driver();

        if (! $driver instanceof SupportsModelPull) {
            throw UnsupportedOperationException::forDriver($driver->getName(), 'pull');
        }

        return $driver->pull($model, $options);
    }

    public function show(string $model): mixed
    {
        $driver = $this->driver();

        if (! $driver instanceof SupportsModelPull) {
            throw UnsupportedOperationException::forDriver($driver->getName(), 'show');
        }

        return $driver->show($model);
    }

    protected function createMlxDriver(): DriverInterface
    {
        return new MlxDriver($this->container['config']->get('olm.drivers.mlx', []));
    }

    protected function createOllamaDriver(): DriverInterface
    {
        return new OllamaDriver($this->container['config']->get('olm.drivers.ollama', []));
    }

    protected function createLmstudioDriver(): DriverInterface
    {
        return new LmStudioDriver($this->container['config']->get('olm.drivers.lmstudio', []));
    }

    protected function createLlamacppDriver(): DriverInterface
    {
        return new LlamaCppDriver($this->container['config']->get('olm.drivers.llamacpp', []));
    }

    protected function createVllmDriver(): DriverInterface
    {
        return new VllmDriver($this->container['config']->get('olm.drivers.vllm', []));
    }

    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (\InvalidArgumentException $e) {
            throw DriverException::unknown((string) $driver);
        }
    }
}
