<?php

namespace Knackline\Olm\DTO;

class ChatResponse
{
    /**
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly string $content,
        public readonly string $role = 'assistant',
        public readonly ?string $finishReason = null,
        public readonly array $usage = [],
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromOpenAi(array $payload): self
    {
        $choice = $payload['choices'][0] ?? [];
        $message = $choice['message'] ?? $choice['delta'] ?? [];

        $content = $message['content']
            ?? $choice['text']
            ?? '';

        return new self(
            id: (string) ($payload['id'] ?? ''),
            model: (string) ($payload['model'] ?? ''),
            content: (string) $content,
            role: (string) ($message['role'] ?? 'assistant'),
            finishReason: isset($choice['finish_reason']) ? (string) $choice['finish_reason'] : null,
            usage: is_array($payload['usage'] ?? null) ? $payload['usage'] : [],
            raw: $payload,
        );
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
