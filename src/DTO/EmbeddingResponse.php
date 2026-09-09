<?php

namespace Knackline\Olm\DTO;

class EmbeddingResponse
{
    /**
     * @param  array<int, array<int, float>>  $embeddings
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $model,
        public readonly array $embeddings,
        public readonly array $usage = [],
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromOpenAi(array $payload): self
    {
        $data = $payload['data'] ?? [];
        $embeddings = [];

        foreach ($data as $item) {
            if (isset($item['embedding']) && is_array($item['embedding'])) {
                $embeddings[] = $item['embedding'];
            }
        }

        return new self(
            model: (string) ($payload['model'] ?? ''),
            embeddings: $embeddings,
            usage: is_array($payload['usage'] ?? null) ? $payload['usage'] : [],
            raw: $payload,
        );
    }

    /**
     * First embedding vector, or empty array.
     *
     * @return array<int, float>
     */
    public function first(): array
    {
        return $this->embeddings[0] ?? [];
    }
}
