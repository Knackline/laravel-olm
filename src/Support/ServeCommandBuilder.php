<?php

namespace Knackline\Olm\Support;

use Knackline\Olm\Exceptions\OlmException;

class ServeCommandBuilder
{
    /**
     * Build the shell command used to start the given driver's inference server.
     *
     * @param  array<string, mixed>  $driverConfig
     *
     * @throws OlmException
     */
    public function build(string $driver, array $driverConfig): string
    {
        if ($driver === 'lmstudio') {
            throw new OlmException(
                'LM Studio cannot be started from the CLI. Start the local server in the LM Studio app, or set OLM_DEV=false.'
            );
        }

        $override = $driverConfig['serve'] ?? null;

        if (is_string($override) && $override !== '') {
            return $override;
        }

        return match ($driver) {
            'ollama' => 'ollama serve',
            'mlx' => $this->buildMlx($driverConfig),
            'llamacpp' => $this->buildLlamaCpp($driverConfig),
            'vllm' => $this->buildVllm($driverConfig),
            default => throw new OlmException("Unsupported OLM driver [{$driver}] for olm:serve."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildMlx(array $config): string
    {
        $model = $this->requireModel($config, 'mlx');
        $command = 'mlx_lm.server --model '.escapeshellarg($model);

        [$host, $port] = $this->hostAndPort($config['base_url'] ?? null, '127.0.0.1', 8080);

        if ($host !== '127.0.0.1' || $port !== 8080) {
            $command .= ' --host '.escapeshellarg($host).' --port '.$port;
        }

        return $command;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildLlamaCpp(array $config): string
    {
        [$host, $port] = $this->hostAndPort($config['base_url'] ?? null, '127.0.0.1', 8080);

        $command = 'llama-server --host '.escapeshellarg($host).' --port '.$port;

        $model = $config['model'] ?? null;

        if (is_string($model) && $model !== '') {
            $command .= ' --model '.escapeshellarg($model);
        }

        return $command;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildVllm(array $config): string
    {
        $model = $this->requireModel($config, 'vllm');
        [$host, $port] = $this->hostAndPort($config['base_url'] ?? null, '127.0.0.1', 8000);

        return 'vllm serve '.escapeshellarg($model)
            .' --host '.escapeshellarg($host)
            .' --port '.$port;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function requireModel(array $config, string $driver): string
    {
        $model = $config['model'] ?? null;

        if (! is_string($model) || $model === '') {
            throw new OlmException(
                "Driver [{$driver}] requires a model in config (or a custom serve command) before olm:serve can start."
            );
        }

        return $model;
    }

    /**
     * @return array{0: string, 1: int}
     */
    protected function hostAndPort(?string $baseUrl, string $defaultHost, int $defaultPort): array
    {
        if ($baseUrl === null || $baseUrl === '') {
            return [$defaultHost, $defaultPort];
        }

        $parts = parse_url($baseUrl);

        if ($parts === false) {
            return [$defaultHost, $defaultPort];
        }

        $host = $parts['host'] ?? $defaultHost;
        $port = isset($parts['port']) ? (int) $parts['port'] : $defaultPort;

        return [$host, $port];
    }
}
