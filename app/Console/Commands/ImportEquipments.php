<?php

namespace App\Console\Commands;

use App\Exceptions\EquipmentFileCorruptException;
use App\Services\EquipmentFileParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportEquipments extends Command
{
    protected $signature = 'equipments:import {file? : Path to the export file (defaults to newest in import dir)}';
    protected $description = 'Import the SAP equipment export into the Equipments table';

    private function latestExportFile(): string
    {
        $directory = rtrim(config('equipments.import_path'), '/');

        $files = glob($directory . '/EQUIPMENTS_*.txt') ?: [];

        if ($files === []) {
            throw EquipmentFileCorruptException::missingFile($directory . '/EQUIPMENTS_*.txt');
        }

        // The timestamp in the filename (EQUIPMENTS_yyyymmddHHMMSS.txt) sorts
        // lexicographically, so the last name is the newest export.
        sort($files);

        return end($files);
    }

    public function handle(EquipmentFileParser $parser): int
    {
        $path = $this->argument('file') ?? $this->latestExportFile();

        try {
            $records = $parser->parseUnique($path);
        } catch (EquipmentFileCorruptException $e) {
            $this->error($e->getMessage());
            return self::FAILURE; // existing data untouched
        }

        DB::transaction(function () use ($records) {
            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('Equipments')->upsert(
                    array_map(fn ($r) => $r->toDatabaseRow(), $chunk),
                    ['Equipment']
                );
            }
        });

        $this->info(sprintf('Imported %d equipments from %s', count($records), basename($path)));
        return self::SUCCESS;
    }
}
