<?php

namespace Knackline\Olm\Drivers;

class VllmDriver extends OpenAICompatibleDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        parent::__construct('vllm', $config);
    }
}
