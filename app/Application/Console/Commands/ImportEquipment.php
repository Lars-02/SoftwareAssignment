<?php

namespace App\Application\Console\Commands;

use App\Application\Exceptions\IncompleteFileException;
use App\Application\Exceptions\InvalidFileException;
use App\Domain\Enums\ImportResult;
use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Domain\Repositories\ImportHistoryRepositoryInterface;
use App\Domain\Services\EquipmentParser;
use App\Domain\Services\ImportHistory\ImportHistoryHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportEquipment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:equipment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import equipment data';

    public function __construct(
        private readonly EquipmentParser $equipmentParser,
        private readonly EquipmentRepositoryInterface $equipmentRepository,
        private readonly ImportHistoryRepositoryInterface $importHistoryRepository,
        private readonly ImportHistoryHandler $importHistoryHandler,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $equipmentFilePath = $this->getLatestEquipmentFileFullPath();

        if ($equipmentFilePath === null) {
            $this->error('No equipment file found');

            return self::FAILURE;
        }

        if ($this->importHistoryHandler->isImported($equipmentFilePath)) {
            $this->info("No new equipment file");

            return self::SUCCESS;
        }

        try {
            $equipments = $this->equipmentParser->parseFile($equipmentFilePath);
            $this->equipmentRepository->saveBatch($equipments);
        } catch (InvalidFileException $e) {
            return $this->handleInvalidFile($equipmentFilePath, $e);
        } catch (IncompleteFileException $e) {
            return $this->handleIncompleteFile($equipmentFilePath, $e);
        } catch (\Throwable $e) {
            return $this->handleUnexpectedError($equipmentFilePath, $e);
        }

        $this->importHistoryHandler->markAsImported($equipmentFilePath);
        $this->info(count($equipments) . " rows has been imported");

        return self::SUCCESS;
    }

    private function handleInvalidFile(string $equipmentFilePath, InvalidFileException $e): int
    {
        $this->logImportFailure('Invalid file found', $equipmentFilePath, $e->getMessage());
        $this->importHistoryRepository->create(
            basename($equipmentFilePath),
            ImportResult::INVALID,
            $e->getMessage(),
        );
        $this->error($e->getMessage());

        return self::FAILURE;
    }

    private function handleIncompleteFile(string $equipmentFilePath, IncompleteFileException $e): int
    {
        $this->logImportFailure('Incomplete file found', $equipmentFilePath, $e->getMessage());
        $this->importHistoryRepository->create(
            basename($equipmentFilePath),
            ImportResult::INCOMPLETE,
            $e->getMessage(),
        );
        $this->warn($e->getMessage());

        return self::SUCCESS;
    }

    private function handleUnexpectedError(string $equipmentFilePath, \Throwable $e): int
    {
        $this->logImportFailure('Something went wrong', $equipmentFilePath, $e->getMessage(), $e);
        $this->importHistoryRepository->create(
            basename($equipmentFilePath),
            ImportResult::FAILED,
        );
        $this->error('Unexpected import error ' . $e->getMessage());
        $this->error($e->getTraceAsString());

        return self::FAILURE;
    }

    private function logImportFailure(
        string $message,
        string $equipmentFilePath,
        string $errorMessage,
        ?\Throwable $e = null,
    ): void {
        $context = [
            'file' => $equipmentFilePath,
            'error' => $errorMessage,
        ];

        if ($e !== null) {
            $context['trace'] = $e->getTraceAsString();
        }

        Log::error($message, $context);
    }

    public function getLatestEquipmentFileFullPath(): ?string
    {
        $folder = trim((string) config('equipment.folder'), '/');
        $glob = glob(storage_path("app/{$folder}/*")) ?: [];

        $files = array_values(array_filter($glob, static fn (string $path): bool => is_file($path)));

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));

        return $files[0] ?? null;
    }
}
