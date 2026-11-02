<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The default on-device / local LLM driver used when none is specified.
    | Supported: "mlx", "ollama", "lmstudio", "llamacpp", "vllm"
    |
    */
    'default' => env('OLM_DRIVER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | Local Development
    |--------------------------------------------------------------------------
    |
    | When enabled, laravel-olm registers `php artisan olm:serve` with Laravel's
    | DevCommands (Laravel 13+) so it runs as part of `composer run dev` /
    | `php artisan dev`. Set OLM_DEV=false to skip registration.
    |
    */
    'dev' => [
        'enabled' => (bool) env('OLM_DEV', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    |
    | Configure each local inference backend. Most speak an OpenAI-compatible
    | HTTP API. Ollama additionally supports native model pull/list/show.
    |
    | Optional `serve` overrides the shell command used by `php artisan olm:serve`.
    |
    */
    'drivers' => [

        'mlx' => [
            'base_url' => env('OLM_MLX_BASE_URL', 'http://127.0.0.1:8080'),
            'api_key' => env('OLM_MLX_API_KEY'),
            'model' => env('OLM_MLX_MODEL'),
            'timeout' => (int) env('OLM_MLX_TIMEOUT', 120),
            'serve' => env('OLM_MLX_SERVE'),
        ],

        'ollama' => [
            'base_url' => env('OLM_OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'api_key' => env('OLM_OLLAMA_API_KEY'),
            'model' => env('OLM_OLLAMA_MODEL', 'llama3.2'),
            'timeout' => (int) env('OLM_OLLAMA_TIMEOUT', 120),
            'serve' => env('OLM_OLLAMA_SERVE'),
        ],

        'lmstudio' => [
            'base_url' => env('OLM_LMSTUDIO_BASE_URL', 'http://127.0.0.1:1234'),
            'api_key' => env('OLM_LMSTUDIO_API_KEY', 'lm-studio'),
            'model' => env('OLM_LMSTUDIO_MODEL'),
            'timeout' => (int) env('OLM_LMSTUDIO_TIMEOUT', 120),
            'serve' => env('OLM_LMSTUDIO_SERVE'),
        ],

        'llamacpp' => [
            'base_url' => env('OLM_LLAMACPP_BASE_URL', 'http://127.0.0.1:8080'),
            'api_key' => env('OLM_LLAMACPP_API_KEY'),
            'model' => env('OLM_LLAMACPP_MODEL'),
            'timeout' => (int) env('OLM_LLAMACPP_TIMEOUT', 120),
            'serve' => env('OLM_LLAMACPP_SERVE'),
        ],

        'vllm' => [
            'base_url' => env('OLM_VLLM_BASE_URL', 'http://127.0.0.1:8000'),
            'api_key' => env('OLM_VLLM_API_KEY'),
            'model' => env('OLM_VLLM_MODEL'),
            'timeout' => (int) env('OLM_VLLM_TIMEOUT', 120),
            'serve' => env('OLM_VLLM_SERVE'),
        ],

    ],

];
