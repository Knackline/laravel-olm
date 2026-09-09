<?php

namespace Knackline\Olm\DTO;

class ModelInfo
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $ownedBy = null,
        public readonly ?int $created = null,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromOpenAi(array $payload): self
    {
        return new self(
            id: (string) ($payload['id'] ?? $payload['name'] ?? ''),
            ownedBy: isset($payload['owned_by']) ? (string) $payload['owned_by'] : null,
            created: isset($payload['created']) ? (int) $payload['created'] : null,
            raw: $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromOllama(array $payload): self
    {
        return new self(
            id: (string) ($payload['name'] ?? $payload['model'] ?? ''),
            ownedBy: 'ollama',
            created: isset($payload['modified_at']) ? strtotime((string) $payload['modified_at']) ?: null : null,
            raw: $payload,
        );
    }
}
