<?php

namespace App\Services\Finance\Diagnostics\Contracts;

use App\Services\Finance\Diagnostics\DTO\IntegrityFindingCollection;

interface IntegrityCheckInterface
{
    /**
     * Run the specific integrity check for the entire database or optionally scoped to a user.
     */
    public function run(?int $userId = null): IntegrityFindingCollection;
}
