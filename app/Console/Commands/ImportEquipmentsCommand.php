<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\CorruptEquipmentsFileException;
use App\Repositories\EquipmentRepository;
use App\Services\EquipmentsFileParser;
use Illuminate\Console\Command;

class ImportEquipmentsCommand extends Command
{
    protected $signature = 'equipments:import {file? : Path to a specific equipments export file}';

    protected $description = 'Import equipments from the latest export file into the Equipments table';

    public function __construct(
        private readonly EquipmentsFileParser $parser,
        private readonly EquipmentRepository $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('file') ?? $this->latestFile();

        if (!$path) {
            $this->warn('No equipments file found to import.');

            return self::SUCCESS;
        }

        $this->info("Importing equipments from {$path}...");

        try {
            $rows = $this->parser->parse(file_get_contents($path));
        } catch (CorruptEquipmentsFileException $e) {
            $this->error("Equipments file is corrupt: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->repository->replaceAll($rows);

        $this->comment('Imported '.count($rows)." equipments from {$path}.");

        return self::SUCCESS;
    }

    private function latestFile(): ?string
    {
        $directory = config('import.equipments_path');

        if (!is_dir($directory)) {
            return null;
        }

        $files = glob($directory.'/EQUIPMENTS_*.txt');

        if (!$files) {
            return null;
        }

        sort($files);

        return end($files);
    }
}
