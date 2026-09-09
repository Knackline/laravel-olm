<?php

namespace Knackline\Olm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Knackline\Olm\Contracts\DriverInterface driver(string|null $driver = null)
 * @method static \Knackline\Olm\DTO\ChatResponse chat(array $messages, array $options = [])
 * @method static \Generator streamChat(array $messages, array $options = [])
 * @method static \Knackline\Olm\DTO\ChatResponse complete(string $prompt, array $options = [])
 * @method static \Knackline\Olm\DTO\EmbeddingResponse embed(string|array $input, array $options = [])
 * @method static array models()
 * @method static bool health()
 * @method static array pull(string $model, array $options = [])
 * @method static mixed show(string $model)
 * @method static string getDefaultDriver()
 *
 * @see \Knackline\Olm\OlmManager
 */
class Olm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'olm';
    }
}
