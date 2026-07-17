<?php

namespace App\Console\Commands;

use App\Exceptions\EquipmentFileCorruptException;
use App\Services\EquipmentFileParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportEquipments extends Command
{
    protected $signature = 'equipments:import
        {file? : Path to the export file (defaults to newest in import dir)}
        {--force : Skip the partial-export check}';

    protected $description = 'Import the SAP equipment export into the Equipments table';

    public function handle(EquipmentFileParser $parser): int
    {
        try {
            $path = $this->argument('file') ?? $this->latestExportFile();
            $records = $parser->parseUnique($path);
        } catch (EquipmentFileCorruptException $e) {
            $this->error($e->getMessage());

            return self::FAILURE; // existing data untouched
        }

        if (! $this->option('force') && $this->isPartialExport(count($records))) {
            return self::FAILURE; // existing data untouched, wait for next file
        }

        DB::transaction(function () use ($records) {
            // A valid export is a complete snapshot of SAP, so replace the
            // table contents: equipment that disappeared from the export is
            // removed here too. delete() rather than truncate(), because
            // TRUNCATE is DDL in MySQL and would commit the transaction.
            DB::table('Equipments')->delete();

            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('Equipments')->insert(
                    array_map(fn ($r) => $r->toDatabaseRow(), $chunk)
                );
            }
        });

        $this->info(sprintf('Imported %d equipments from %s', count($records), basename($path)));

        return self::SUCCESS;
    }

    private function latestExportFile(): string
    {
        $directory = rtrim((string) config('equipments.import_path'), '/');

        $files = glob($directory . '/EQUIPMENTS_*.txt') ?: [];

        if ($files === []) {
            throw EquipmentFileCorruptException::missingFile($directory . '/EQUIPMENTS_*.txt');
        }

        // The timestamp in the filename (EQUIPMENTS_yyyymmddHHMMSS.txt) sorts
        // lexicographically, so the last name is the newest export.
        sort($files);

        return end($files);
    }

    /**
     * A structurally valid export can still be incomplete (many tools missing).
     * If the new file holds far fewer records than the database currently
     * does, ignore it and wait for the next daily export.
     */
    private function isPartialExport(int $newCount): bool
    {
        $currentCount = DB::table('Equipments')->count();

        if ($currentCount === 0) {
            return false; // first import: nothing to compare against
        }

        $threshold = (float) config('equipments.partial_threshold', 0.5);

        if ($newCount >= (int) ceil($currentCount * $threshold)) {
            return false;
        }

        $this->warn(sprintf(
            'Export looks partial: %d records vs %d in the database (below %d%% threshold). Ignoring this file.',
            $newCount,
            $currentCount,
            (int) round($threshold * 100),
        ));

        return true;
    }
}
