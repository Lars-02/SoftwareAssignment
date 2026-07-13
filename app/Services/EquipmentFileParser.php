<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\EquipmentRecord;
use App\Exceptions\EquipmentFileCorruptException;
use DateTimeImmutable;

/**
 * Parser for the SAP IH09 equipment export (EQUIPMENTS_yyyymmddHHMMSS.txt).
 *
 * File layout
 * -----------
 * The export is a pipe-bordered, column-aligned report. After a 3-line header
 * block, every equipment record spans exactly THREE physical lines:
 *
 *   line 1: Material | Description | Size | Equipment | UserStat | SysStat |
 *           Location | Room | SLoc | SuperEq | ManufactSerial | SerialNo
 *   line 2: IH09 description | dims | gross weight | plants | cost ctr |
 *           valid from/to | PP work center | work ctr
 *   line 3: net weight | old material no | material status | created on/by |
 *           changed on/by | short description
 *
 * Gotchas this parser handles deliberately:
 *  - Column positions on line 1 SHIFT depending on description/size width,
 *    so we anchor on the `<equipment> <STAT> <STAT>` pattern instead of
 *    absolute offsets. The tail AFTER that anchor is reliably fixed-width.
 *  - European number formats: "8.000,00" means 8000.00, "0,139" means 0.139.
 *  - Dates are dd.mm.yyyy ("31.12.9999" is SAP's forever-sentinel).
 *  - Dimensions like "100X200X500" must not be confused with descriptions
 *    that merely contain an X ("WRENCH HEX SCH CRV 3/8X6").
 *  - The same equipment number can appear multiple times (different status
 *    combinations). parse() returns every row; parseUnique() deduplicates.
 */
class EquipmentFileParser
{
    private const LINES_PER_RECORD = 3;

    /**
     * `<equipment number> <4-char user status> <4-char system status> ` —
     * the only reliable landmark on line 1.
     */
    private const ANCHOR_PATTERN = '/(\d{9,10})\s{2,}([A-Z0-9]{4}) ([A-Z0-9]{4}) /';

    /**
     * Fixed field widths of the line-1 tail, measured from the character
     * right after the system status: Location(11) Room(9) SLoc(5)
     * SuperEq(19) ManufactSerial(31) SerialNo(rest).
     */
    private const TAIL_WIDTHS = [11, 9, 5, 19, 31];

    /** Width of the "Description of Technical Object" column on line 2. */
    private const IH09_DESCRIPTION_WIDTH = 41;

    private const HEADER_PATTERNS = [
        '/^\|Material\s+Material Description/',
        '/^\|Description of Technical Object/',
        '/^\|Work ctr\s+Net Weight/',
    ];

    /**
     * Parse the export file into equipment records, in file order.
     * Duplicate equipment numbers are preserved as-is.
     *
     * @return list<EquipmentRecord>
     *
     * @throws EquipmentFileCorruptException when the file is missing, empty,
     *         has no header block, or contains truncated/unparsable records.
     */
    public function parse(string $path): array
    {
        if (! is_readable($path)) {
            throw EquipmentFileCorruptException::missingFile($path);
        }

        $contents = (string) file_get_contents($path);

        if (trim($contents) === '') {
            throw EquipmentFileCorruptException::emptyFile($path);
        }

        return $this->parseContents($contents, $path);
    }

    /**
     * Parse and deduplicate: when the same equipment number occurs more than
     * once, the LAST occurrence in the file wins (the importer persists this
     * deduplicated set, `Equipment` being the primary key).
     *
     * @return array<string, EquipmentRecord> keyed by equipment number
     */
    public function parseUnique(string $path): array
    {
        $unique = [];

        foreach ($this->parse($path) as $record) {
            $unique[$record->equipment] = $record;
        }

        return $unique;
    }

    /**
     * @return list<EquipmentRecord>
     */
    public function parseContents(string $contents, string $sourceName = '(string)'): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $headerLineCount = 0;
        $dataLines = [];

        foreach ($lines as $line) {
            if (trim($line) === '' || $this->isSeparatorLine($line)) {
                continue;
            }

            if ($this->isHeaderLine($line)) {
                $headerLineCount++;
                continue;
            }

            $dataLines[] = $line;
        }

        if ($headerLineCount < count(self::HEADER_PATTERNS)) {
            throw EquipmentFileCorruptException::missingHeader($sourceName);
        }

        if (count($dataLines) % self::LINES_PER_RECORD !== 0) {
            throw EquipmentFileCorruptException::incompleteRecord(count($dataLines));
        }

        $records = [];

        foreach (array_chunk($dataLines, self::LINES_PER_RECORD) as $blockIndex => $block) {
            $records[] = $this->parseRecord($block, $blockIndex);
        }

        return $records;
    }

    /**
     * @param array{0: string, 1: string, 2: string} $block
     */
    private function parseRecord(array $block, int $blockIndex): EquipmentRecord
    {
        [$line1, $line2, $line3] = $block;

        if (! preg_match(self::ANCHOR_PATTERN, $line1, $anchor, PREG_OFFSET_CAPTURE)) {
            throw EquipmentFileCorruptException::unparsableRecord($blockIndex, $line1);
        }

        $equipment = $anchor[1][0];
        $userStatus = $anchor[2][0];
        $systemStatus = $anchor[3][0];

        // --- line 1: material + description + size (left of the anchor) ---
        $head = rtrim(substr($line1, 1, (int) $anchor[0][1] - 1));
        [$material, $description, $dimensions] = $this->parseHead($head);

        // --- line 1: fixed-width tail (right of the anchor) ---
        $tail = rtrim(rtrim(substr($line1, (int) $anchor[0][1] + strlen($anchor[0][0]))), '|');
        [$location, $room, $sloc, $superEquipment, $manufactSerial, $serialNumber] = $this->sliceTail($tail);

        // --- line 2 ---
        $ih09Description = $this->emptyToNull(substr($line2, 1, self::IH09_DESCRIPTION_WIDTH));

        $grossWeight = null;
        if (preg_match('/\s([\d.]*\d,\d+)\s+KG\s/', $line2, $m)) {
            $grossWeight = $this->parseEuropeanNumber($m[1]);
        }

        $plant = $costCenter = $workCenter = null;
        $validFrom = $validTo = null;
        if (preg_match(
            '/(DE\d{2})\s+(?:DE\d{2}\s+)?(DE\d{6,})?\s*(\d{2}\.\d{2}\.\d{4}) (\d{2}\.\d{2}\.\d{4})\s+(\d+)\s+(\S+)/',
            $line2,
            $m
        )) {
            $plant = $m[1];
            $costCenter = $this->emptyToNull($m[2]);
            $validFrom = $this->parseDate($m[3]);
            $validTo = $this->parseDate($m[4]);
            $workCenter = $m[6];
        }

        // --- line 3 ---
        $oldMaterialNumber = $materialStatus = null;
        $createdOn = $changedOn = null;
        $createdBy = $changedBy = $shortDescription = null;

        $hasOldMaterial = preg_match('/KG\s+(\S+)/', $line3, $om, PREG_OFFSET_CAPTURE) === 1;
        if ($hasOldMaterial) {
            $oldMaterialNumber = $om[1][0];
        }

        if (preg_match(
            '/(\d{2}\.\d{2}\.\d{4})\s+(\S+)\s+(\d{2}\.\d{2}\.\d{4})\s+(\S+)\s+(.*?)\s*\|?\s*$/',
            $line3,
            $dm,
            PREG_OFFSET_CAPTURE
        )) {
            $createdOn = $this->parseDate($dm[1][0]);
            $createdBy = $dm[2][0];
            $changedOn = $this->parseDate($dm[3][0]);
            $changedBy = $dm[4][0];

            // "Short description" and "Short desc." are printed side by side;
            // splitting on runs of 2+ spaces keeps only the first.
            $descColumns = preg_split('/\s{2,}/', trim($dm[5][0])) ?: [];
            $shortDescription = $this->emptyToNull($descColumns[0] ?? '');

            // Material status (R4, U1, U2, ...) lives between the old
            // material number and the created-on date.
            if ($hasOldMaterial) {
                $segmentStart = (int) $om[1][1] + strlen($om[1][0]);
                $segment = substr($line3, $segmentStart, (int) $dm[1][1] - $segmentStart);

                if (preg_match('/\b([A-Z]\d)\b/', $segment, $ms)) {
                    $materialStatus = $ms[1];
                }
            }
        }

        return new EquipmentRecord(
            equipment: $equipment,
            material: $material,
            materialWithoutFet: preg_replace('/-FET$/', '', $material ?? '') ?? '',
            description: $description,
            dimensions: $dimensions,
            ih09Description: $ih09Description,
            userStatus: $userStatus,
            systemStatus: $systemStatus,
            location: $location,
            room: $room,
            sloc: $sloc,
            superEquipment: $superEquipment,
            manufactSerialNumber: $manufactSerial,
            serialNumber: $serialNumber,
            plant: $plant,
            costCenter: $costCenter,
            validFrom: $validFrom,
            validTo: $validTo,
            workCenter: $workCenter,
            grossWeight: $grossWeight,
            oldMaterialNumber: $oldMaterialNumber,
            materialStatus: $materialStatus,
            createdOn: $createdOn,
            createdBy: $createdBy,
            changedOn: $changedOn,
            changedBy: $changedBy,
            shortDescription: $shortDescription,
        );
    }

    /**
     * Split "3220.731.56770-FET BODY HARNESS ASSY 2-P LT L/XL  100X200X500"
     * into material, description and dimensions. Dimensions must be a
     * standalone whitespace-delimited token like 100X200X500 or 60X35X12 —
     * "3/8X6" inside a description does NOT qualify.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function parseHead(string $head): array
    {
        $parts = preg_split('/\s+/', trim($head), 2) ?: [];

        $material = $this->emptyToNull($parts[0] ?? '');
        $rest = trim($parts[1] ?? '');
        $dimensions = null;

        if (preg_match('/(?:^|\s)(\d+X\d+(?:X\d+)?)$/', $rest, $m)) {
            $dimensions = $m[1];
            $rest = trim(substr($rest, 0, strlen($rest) - strlen($m[1])));
        }

        return [$material, $this->emptyToNull($rest), $dimensions];
    }

    /**
     * Slice the fixed-width tail after the status anchor into
     * [location, room, sloc, superEq, manufactSerial, serialNumber].
     *
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string, 5: ?string}
     */
    private function sliceTail(string $tail): array
    {
        $fields = [];
        $offset = 0;

        foreach (self::TAIL_WIDTHS as $width) {
            $fields[] = $this->emptyToNull(substr($tail, $offset, $width));
            $offset += $width;
        }

        $fields[] = $this->emptyToNull(substr($tail, $offset));

        return $fields;
    }

    /**
     * "8.000,00" -> 8000.0 and "0,139" -> 0.139 (SAP/European formatting:
     * dot = thousand separator, comma = decimal separator).
     */
    private function parseEuropeanNumber(string $value): float
    {
        return (float) str_replace(',', '.', str_replace('.', '', $value));
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $value);

        return $date === false ? null : $date;
    }

    private function isSeparatorLine(string $line): bool
    {
        $stripped = trim(trim($line), '|');

        return $stripped !== '' && trim($stripped, '-') === '';
    }

    private function isHeaderLine(string $line): bool
    {
        foreach (self::HEADER_PATTERNS as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    private function emptyToNull(string|false $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
