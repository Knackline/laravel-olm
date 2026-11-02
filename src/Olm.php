<?php

namespace Knackline\Olm;

class Olm
{
    /**
     * Register the OLM process with Laravel's DevCommands (Laravel 13+).
     *
     * Called automatically from the service provider when available.
     * Apps can also call this explicitly, or opt out with OLM_DEV=false /
     * DevCommands::except('olm').
     */
    public static function registerDevCommands(): void
    {
        if (! class_exists(\Illuminate\Foundation\DevCommands::class)) {
            return;
        }

        if (! config('olm.dev.enabled', true)) {
            return;
        }

        if (config('olm.default') === 'lmstudio') {
            return;
        }

        \Illuminate\Foundation\DevCommands::artisan('olm:serve', 'olm');
    }
}
