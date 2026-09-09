<?php

namespace Knackline\Olm\Exceptions;

use Illuminate\Http\Client\Response;

class RequestException extends OlmException
{
    public function __construct(
        string $message,
        public readonly ?Response $response = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(Response $response, string $context = 'request'): self
    {
        $body = $response->body();
        $status = $response->status();

        return new self(
            "OLM {$context} failed with status {$status}: {$body}",
            $response,
            $status,
        );
    }
}
