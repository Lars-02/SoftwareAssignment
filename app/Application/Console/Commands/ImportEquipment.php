<?php

namespace App\Application\Console\Commands;

use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Domain\Services\EquipmentParser;
use Illuminate\Console\Command;

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
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $equipmentFilePath = $this->getEquipmentFileFullPath();

        if ($equipmentFilePath === null) {
            $this->error('No equipment file found');

            return self::FAILURE;
        }
        
        $equipments = $this->equipmentParser->parseFile($equipmentFilePath);
        $this->equipmentRepository->saveBatch($equipments);

        $this->info(count($equipments) . " rows has been imported");

        return self::SUCCESS;
    }

    public function getEquipmentFileFullPath(): ?string
    {
        $folder = trim((string) config('equipment.folder'), '/');
        $glob = glob(storage_path("app/{$folder}/*")) ?: [];

        $files = array_values(array_filter($glob, static fn (string $path): bool => is_file($path)));

        if ($files === []) {
            return null;
        }

        return $files[0] ?? null;
    }
}
