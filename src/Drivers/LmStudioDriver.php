<?php

namespace Knackline\Olm\Drivers;

class LmStudioDriver extends OpenAICompatibleDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        parent::__construct('lmstudio', $config);
    }
}
