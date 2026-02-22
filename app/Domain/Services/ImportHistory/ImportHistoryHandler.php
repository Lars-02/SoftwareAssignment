<?php

namespace App\Domain\Services\ImportHistory;

use App\Domain\Enums\ImportResult;
use App\Domain\Repositories\ImportHistoryRepositoryInterface;

class ImportHistoryHandler
{
    public function __construct(
        private readonly ImportHistoryRepositoryInterface $importHistoryRepository,
    ) {
    }

    public function isImported(string $equipmentFilePath): bool
    {
        return $this->importHistoryRepository->hasFileWithResult(
            basename($equipmentFilePath),
            ImportResult::SUCCESS,
            ImportResult::INCOMPLETE,
        );
    }

    public function markAsImported(string $equipmentFilePath): void
    {
        $this->importHistoryRepository->create(basename($equipmentFilePath), ImportResult::SUCCESS);
    }
}
