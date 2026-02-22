<?php

namespace App\Domain\Services;

use App\Application\Exceptions\InvalidFileException;
use App\Domain\Models\Equipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class EquipmentParser
{
    private const OUTER_PIPE_CHARACTER_MASK = '|';
    private const TRAILING_WHITESPACE_CHARACTER_MASK = "\t \n\r\0\x0B";

    /**
     * @var array<string, array{0: int, 1: int}> 0 = start, 1 = length
     */
    private array $characterPositions = [
        'material' => [0, 19],
        'description' => [19, 41],
        'dimensions' => [60, 33],
        'location' => [122, 11],
        'room' => [133, 9],
        'sloc' => [142, 5],
        'super_eq' => [147, 19],
        'manufact_serial' => [166, 31],
        'serial_number' => [197, 53],
        'ih09_description' => [0, 41],
        'dimensions_alt' => [41, 38],
        'plant_block' => [166, 28],
        'old_material_no' => [31, 19],
        'created_on_raw' => [58, 11],
        'created_by' => [69, 13],
        'changed_on_raw' => [82, 11],
        'short_description' => [105, 41],
        'short_desc' => [146, 103],
        'equipment_block' => [89, 19],
        'status_block' => [108, 14],
        'gross_weight_block' => [79, 17],
        'wkctr_block' => [224, 9],
        'changed_by_block' => [92, 13],
        'valid_from_raw' => [194, 10],
        'valid_to_raw' => [205, 10],
    ];

    public function __construct(
        private readonly EquipmentParserValidator $validator,
    ) {
    }

    /**
     * @return Equipment[]
     */
    public function parseFile(string $equipmentFilePath): array
    {
        try {
            $content = File::get($equipmentFilePath);
        } catch (\Throwable $e) {
            throw new InvalidFileException("Unable to read equipment file: {$equipmentFilePath}");
        }

        $lines      = $this->removeNewLine($content);
        $records    = $this->getRecordPerThreeLines($lines);
        $equipments = [];

        foreach ($records as $index => $recordLines) {
            $equipments[] = $this->parseRecord($recordLines);
        }

        return $equipments;
    }

    private function removeNewLine(string $content): array
    {
        return preg_split('/\r\n|\r|\n/', $content) ?: [];
    }

    /**
     * @param string[] $lines
     * @return array<int, array<int, string>>
     */
    private function getRecordPerThreeLines(array $lines): array
    {
        $lines = array_values(array_filter($lines, fn (string $line): bool => !$this->validator->isHeaderOrFooter($line)));

        return $this->parseRecordPerThreeLines($lines);
    }

    private function parseRecordPerThreeLines(array $lines)
    {
        $records = [];

        for ($i = 0; $i + 2 < count($lines); $i += 3) {
            $records[$i] = [$lines[$i], $lines[$i + 1], $lines[$i + 2]];
        }

        return $records;
    }

    /**
     * @param string[] $lines
     */
    private function parseRecord(array $lines): Equipment
    {
        if (count($lines) !== 3) {
            throw new InvalidFileException("incorrect row: expected 3 lines");
        }

        $line1 = $this->cleanDelimitedLine($lines[0]);
        $line2 = $this->cleanDelimitedLine($lines[1]);
        $line3 = $this->cleanDelimitedLine($lines[2]);

        $line1 = $this->parseFirstLineData($line1);
        $line2 = $this->parseSecondLineData($line2);
        $line3 = $this->parseThirdLineData($line3);

        if (is_null($line1['equipment'])) {
            throw new InvalidFileException("Missing equipment id at record");
        }

        $createdOn    = $this->parseDateForTimestamp($line3['created_on_raw']);
        $createdBy    = $line3['created_by'];
        $changedOn    = $this->parseDateForTimestamp($line3['changed_on_raw']);
        $systemStatus = $line1['system_status'] ?: 'UNKNOWN';
        $userStatus   = $line1['user_status'] ?: 'UNKNOWN';
        $stockType    = $this->deriveStockType($line2['valid_from_raw'], $line2['valid_to_raw']);

        return new Equipment([
            'Equipment' => $line1['equipment'],
            'Material' => $line1['material'],
            'MaterialWithoutFet' => str_replace('-FET', '', (string) ($line1['material'] ?? '')),
            'Description' => $line1['description'],
            'IH09Description' => $line2['ih09_description'],
            'Room' => $line1['room'],
            'Plant' => $line2['plant'],
            'Location' => $line1['location'],
            'Sloc' => $line1['sloc'],
            'SuperEq' => $line1['super_eq'],
            'ManufactSerialNumber' => $line1['manufact_serial'] ?? null,
            'SerNo' => $line1['serial_number'],
            'UserStatus' => $userStatus,
            'SystemStatus' => $systemStatus,
            'Dimensions' => $line1['dimensions'] ?? $line2['dimensions_alt'],
            'CleaningCounter_limit' => 0,
            'CleaningCounter_current' => 0,
            'ToolCompetence' => null,
            'NextCertDate' => null,
            'NextCalDate' => null,
            'NextCtrlDate' => null,
            'NEN3140Int' => null,
            'MaintInt' => null,
            'NextNEN3140Date' => null,
            'CalInt' => null,
            'NextMaintDate' => null,
            'CertInt' => null,
            'CtrlInt' => null,
            'ExempEndDate' => null,
            'Min_CALD_Date' => null,
            'GrossWeight' => $line2['gross_weight'],
            'current_status' => trim($systemStatus.' '.$userStatus),
            'needed_time' => null,
            'return_time' => null,
            'workcenter' => $line2['wkctr'] ?? 'UNKNOWN',
            'material_status' => null,
            'StockType' => $stockType,
            'SpecialStock' => null,
            'CreatedOn' => $createdOn,
            'CreatedBy' => $createdBy ?? 'SYSTEM',
            'ChangedOn' => $changedOn,
            'ChangedBy' => $line3['changed_by'] ?? 'SYSTEM',
        ]);
    }

    /**
     * @return array{
     *     material: ?string,
     *     description: ?string,
     *     dimensions: ?string,
     *     equipment: ?string,
     *     system_status: ?string,
     *     user_status: ?string,
     *     location: ?string,
     *     room: ?string,
     *     sloc: ?string,
     *     super_eq: ?string,
     *     manufact_serial: ?string,
     *     serial_number: ?string
     * }
     */
    private function parseFirstLineData(string $line): array
    {
        $equipment = $this->getEquipment($line);
        [$systemStatus, $userStatus] = $this->getStatuses($line);

        return [
            'material' => $this->getValue($line, 'material'),
            'description' => $this->getValue($line, 'description'),
            'dimensions' => $this->getValue($line, 'dimensions'),
            'equipment' => $equipment,
            'system_status' => $systemStatus,
            'user_status' => $userStatus,
            'location' => $this->getValue($line, 'location'),
            'room' => $this->getValue($line, 'room'),
            'sloc' => $this->getValue($line, 'sloc'),
            'super_eq' => $this->getValue($line, 'super_eq'),
            'manufact_serial' => $this->getValue($line, 'manufact_serial'),
            'serial_number' => $this->getValue($line, 'serial_number'),
        ];
    }

    /**
     * @return array{
     *     ih09_description: ?string,
     *     dimensions_alt: ?string,
     *     gross_weight: ?float,
     *     plant: ?string,
     *     wkctr: ?string,
     *     valid_from_raw: ?string,
     *     valid_to_raw: ?string
     * }
     */
    private function parseSecondLineData(string $line): array
    {
        return [
            'ih09_description' => $this->getValue($line, 'ih09_description'),
            'dimensions_alt' => $this->getValue($line, 'dimensions_alt'),
            'gross_weight' => $this->getGrossWeight($line),
            'plant' => $this->getPlant($line),
            'wkctr' => $this->getWorkCenter($line),
            'valid_from_raw' => $this->getValue($line, 'valid_from_raw'),
            'valid_to_raw' => $this->getValue($line, 'valid_to_raw'),
        ];
    }

    /**
     * @return array{
     *     old_material_no: ?string,
     *     created_on_raw: ?string,
     *     created_by: ?string,
     *     changed_on_raw: ?string,
     *     changed_by: ?string,
     *     short_description: ?string,
     *     short_desc: ?string
     * }
     */
    private function parseThirdLineData(string $line): array
    {
        return [
            'old_material_no' => $this->getValue($line, 'old_material_no'),
            'created_on_raw' => $this->getValue($line, 'created_on_raw'),
            'created_by' => $this->getValue($line, 'created_by'),
            'changed_on_raw' => $this->getValue($line, 'changed_on_raw'),
            'changed_by' => $this->getValue($line, 'changed_by_block'),
            'short_description' => $this->getValue($line, 'short_description'),
            'short_desc' => $this->getValue($line, 'short_desc'),
        ];
    }

    private function cleanDelimitedLine(string $line): string
    {
        $withoutPipes = trim($line, self::OUTER_PIPE_CHARACTER_MASK);

        return rtrim($withoutPipes, self::TRAILING_WHITESPACE_CHARACTER_MASK);
    }

    private function getValue(string $line, string $key): ?string
    {
        if (!isset($this->characterPositions[$key])) {
            return null;
        }

        [$start, $length] = $this->characterPositions[$key];
        $value = trim(rtrim(substr($line, $start, $length)));

        return $value === '' ? null : $value;
    }

    private function parseDateForTimestamp(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d.m.Y', $value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getEquipment(string $line): ?string
    {
        $equipmentValue = $this->getValue($line, 'equipment_block') ?? '';

        // Match from 8-10 digit.
        if (preg_match('/\d{8,10}/', $equipmentValue, $matches) !== 1) {
            return null;
        }

        return $matches[0] ?? null;
    }

    /**
     * @return array{
     *     0: string|null, // system status
     *     1: string|null  // user status
     * }
     */
    private function getStatuses(string $line): array
    {
        $statuses = $this->getValue($line, 'status_block') ?? '';

        // Split statuses by one-or-more whitespace characters.
        $parts = preg_split('/\s+/', trim($statuses)) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    private function getGrossWeight(string $line): ?float
    {
        $grossWeightBlock = $this->getValue($line, 'gross_weight_block');

        // Split by whitespace to remove unit, e.g. "8.000,00 KG".
        $parts        = preg_split('/\s+/', trim((string) $grossWeightBlock)) ?: [];
        $numericToken = $parts[0] ?? null;

        return $this->parseDecimal($numericToken);
    }

    private function parseDecimal(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace('.', '', $value);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = trim($normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function getWorkCenter(string $line): ?string
    {
        $workCenterBlock = $this->getValue($line, 'wkctr_block');

        // Split by whitespace and keep first work-center, bcs there are like 
        // PPAC, BOTM, MF and the other has 9 character except these
        $parts = preg_split('/\s+/', trim((string) $workCenterBlock)) ?: [];

        return $parts[0] ?? null;
    }

    private function deriveStockType(?string $validFromRaw, ?string $validToRaw): ?string
    {
        $validFrom = $this->parseDateForTimestamp($validFromRaw);
        $validTo   = $this->parseDateForTimestamp($validToRaw);
        $today     = Carbon::today();

        if ($validFrom === null || $validTo === null) {
            return 'UNAVAILABLE';
        }

        $validFromDate = Carbon::parse($validFrom);
        $validToDate = Carbon::parse($validTo);

        return $today->betweenIncluded($validFromDate, $validToDate)
            ? 'AVAILABLE'
            : 'UNAVAILABLE';
    }

    private function getPlant(string $line): ?string
    {
        $plants = $this->getValue($line, 'plant_block');

        // Split by white space to extract plants.
        $parts = preg_split('/\s+/', trim((string) $plants)) ?: [];

        if ($parts === []) {
            return null;
        }

        return $parts[0] ?? $parts[1] ?? null;
    }
}
