<?php

namespace Knackline\Olm\DTO;

class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly string $content,
    ) {
    }

    /**
     * @param  array{role: string, content: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'] ?? 'assistant',
            content: (string) ($data['content'] ?? ''),
        );
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
