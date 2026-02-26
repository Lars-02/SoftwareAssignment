<?php

namespace App\Domain\Repositories;

use App\Domain\Enums\ImportResult;

interface ImportHistoryRepositoryInterface
{
    public function hasFileWithResult(string $fileName, ImportResult ...$results): bool;

    public function create(string $fileName, ImportResult $result, ?string $notes = null): void;
}
