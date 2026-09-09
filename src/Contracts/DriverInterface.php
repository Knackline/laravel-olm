<?php

namespace Knackline\Olm\Contracts;

use Generator;
use Knackline\Olm\DTO\ChatResponse;
use Knackline\Olm\DTO\EmbeddingResponse;
use Knackline\Olm\DTO\ModelInfo;

interface DriverInterface
{
    /**
     * Create a chat completion.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): ChatResponse;

    /**
     * Stream a chat completion (SSE).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @return Generator<int, string>
     */
    public function streamChat(array $messages, array $options = []): Generator;

    /**
     * Create a text completion.
     *
     * @param  array<string, mixed>  $options
     */
    public function complete(string $prompt, array $options = []): ChatResponse;

    /**
     * Create embeddings.
     *
     * @param  string|array<int, string>  $input
     * @param  array<string, mixed>  $options
     */
    public function embed(string|array $input, array $options = []): EmbeddingResponse;

    /**
     * List available models.
     *
     * @return array<int, ModelInfo>
     */
    public function models(): array;

    /**
     * Check whether the backend is reachable.
     */
    public function health(): bool;

    /**
     * Driver name (mlx, ollama, …).
     */
    public function getName(): string;
}
