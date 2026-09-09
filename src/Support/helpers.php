<?php

use Knackline\Olm\OlmManager;

if (! function_exists('olm')) {
    /**
     * Get the OLM manager or a named driver.
     *
     * @return \Knackline\Olm\OlmManager|\Knackline\Olm\Contracts\DriverInterface
     */
    function olm(?string $driver = null)
    {
        /** @var OlmManager $manager */
        $manager = app('olm');

        if ($driver === null) {
            return $manager;
        }

        return $manager->driver($driver);
    }
}
