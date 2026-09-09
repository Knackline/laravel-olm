<?php

namespace Knackline\Olm\Contracts;

use Knackline\Olm\DTO\ModelInfo;

interface SupportsModelPull
{
    /**
     * Pull / download a model (when supported by the backend).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function pull(string $model, array $options = []): array;

    /**
     * Show details for a model.
     *
     * @return array<string, mixed>|ModelInfo
     */
    public function show(string $model): array|ModelInfo;
}
