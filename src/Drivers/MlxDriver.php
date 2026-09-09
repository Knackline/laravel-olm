<?php

namespace Knackline\Olm\Drivers;

class MlxDriver extends OpenAICompatibleDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config)
    {
        parent::__construct('mlx', $config);
    }
}
