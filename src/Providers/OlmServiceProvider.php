<?php

namespace Knackline\Olm\Providers;

use Illuminate\Support\ServiceProvider;
use Knackline\Olm\OlmManager;

class OlmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/olm.php',
            'olm'
        );

        $this->app->singleton('olm', function ($app) {
            return new OlmManager($app);
        });

        $this->app->alias('olm', OlmManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/olm.php' => config_path('olm.php'),
            ], 'olm-config');
        }
    }
}
