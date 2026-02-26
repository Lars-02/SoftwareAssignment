<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Enums\ImportResult;
use App\Domain\Models\ImportHistory;
use App\Domain\Repositories\ImportHistoryRepositoryInterface;
use Carbon\Carbon;

class ImportHistoryRepository implements ImportHistoryRepositoryInterface
{
    public function hasFileWithResult(string $fileName, ImportResult ...$results): bool
    {
        if ($results === []) {
            return false;
        }

        $resultValues = array_map(static fn (ImportResult $result): string => $result->value, $results);

        return ImportHistory::query()
            ->where('Name', $fileName)
            ->whereIn('Result', $resultValues)
            ->exists();
    }

    public function create(string $fileName, ImportResult $result, ?string $notes = null): void
    {
        ImportHistory::query()->create([
            'Name' => $fileName,
            'Result' => $result->value,
            'Notes' => $notes,
            'ImportedAt' => Carbon::now(),
        ]);
    }
}
