<?php

namespace Knackline\Olm\Exceptions;

class DriverException extends OlmException
{
    public static function unknown(string $driver): self
    {
        return new self("Driver [{$driver}] is not supported.");
    }
}
