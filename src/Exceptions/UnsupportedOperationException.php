<?php

namespace Knackline\Olm\Exceptions;

class UnsupportedOperationException extends OlmException
{
    public static function forDriver(string $driver, string $operation): self
    {
        return new self("Driver [{$driver}] does not support [{$operation}].");
    }
}
