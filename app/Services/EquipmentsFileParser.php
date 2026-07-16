<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CorruptEquipmentsFileException;

class EquipmentsFileParser
{
    private const HEADER_LINES = 5;

    private const CONTENT_WIDTH = 250;

    private const LINE_WIDTH = 252;

    // Nominal (unshifted) column boundaries within a record's first line,
    // relative to the 250-char content between the leading/trailing '|'.
    private const NOMINAL_EQUIPMENT_START = 93;

    private const LINE1_BOUNDS = [
        'Material' => [0, 19],
        'Description' => [19, 60],
        // Dimensions ends where the Equipment field would start; widened/narrowed by the shift delta.
        'UserStatus' => [112, 117],
        'SystemStatus' => [117, 122],
        'Location' => [122, 133],
        'Room' => [133, 142],
        'Sloc' => [142, 147],
        'SuperEq' => [147, 166],
        'ManufactSerialNumber' => [166, 197],
        'SerNo' => [197, 250],
    ];

    /**
     * @return array<int, array<string, mixed>> Deduplicated rows keyed by column name, last occurrence wins.
     */
    public function parse(string $content): array
    {
        if (empty(trim($content))) {
            throw new CorruptEquipmentsFileException('Equipments file is empty.');
        }

        $lines = explode("\n", rtrim($content, "\n"));

        $this->validateFrame($lines);

        $body = array_slice($lines, self::HEADER_LINES, count($lines) - self::HEADER_LINES - 1);

        $rows = [];

        foreach (array_chunk($body, 3) as $record) {
            if (count($record) !== 3) {
                throw new CorruptEquipmentsFileException('Equipments file body is not a multiple of 3 lines.');
            }

            $row = $this->parseRecord($record);

            $rows[$row['Equipment']] = $row;
        }

        return array_values($rows);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function validateFrame(array $lines): void
    {
        if (count($lines) < self::HEADER_LINES + 2) {
            throw new CorruptEquipmentsFileException('Equipments file is too short to contain a valid header and body.');
        }

        foreach ($lines as $number => $line) {
            if (strlen($line) !== self::LINE_WIDTH) {
                throw new CorruptEquipmentsFileException(
                    'Equipments file line '.($number + 1).' is not '.self::LINE_WIDTH.' characters wide.'
                );
            }
        }

        if (!$this->isBorderLine($lines[0]) || !$this->isBorderLine($lines[4])) {
            throw new CorruptEquipmentsFileException('Equipments file header borders are missing or malformed.');
        }

        $this->validateHeaderLabels($lines[1], $lines[2], $lines[3]);

        $footer = end($lines);

        if (!preg_match('/^-{'.self::LINE_WIDTH.'}$/', $footer)) {
            throw new CorruptEquipmentsFileException('Equipments file footer is missing or malformed.');
        }

        $bodyCount = count($lines) - self::HEADER_LINES - 1;

        if ($bodyCount <= 0 || $bodyCount % 3 !== 0) {
            throw new CorruptEquipmentsFileException('Equipments file body line count is not a multiple of 3.');
        }
    }

    /**
     * Sanity-checks that this is the expected SAP export layout, not some
     * other fixed-width file that happens to share the same border style.
     */
    private function validateHeaderLabels(string $line1, string $line2, string $line3): void
    {
        $expected = [
            $line1 => ['Material', 'Equipment', 'Room'],
            $line2 => ['Gross Weight', 'Plnt'],
            $line3 => ['Created On', 'Created By', 'Chngd On', 'Changed by'],
        ];

        foreach ($expected as $line => $labels) {
            foreach ($labels as $label) {
                if (!str_contains($line, $label)) {
                    throw new CorruptEquipmentsFileException("Equipments file header is missing the expected \"{$label}\" column.");
                }
            }
        }
    }

    private function isBorderLine(string $line): bool
    {
        return (bool) preg_match('/^\|-{'.self::CONTENT_WIDTH.'}\|$/', $line);
    }

    /**
     * @param  array<int, string>  $record
     * @return array<string, mixed>
     */
    private function parseRecord(array $record): array
    {
        return array_merge(
            $this->defaults(),
            $this->parseLine1($record[0]),
            $this->parseLine2($record[1]),
            $this->parseLine3($record[2]),
        );
    }

    /**
     * Fallback values for NOT NULL columns without a database default that the
     * file never populates.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'ToolCompetence' => '',
            'current_status' => '',
            'NEN3140Int' => 0,
            'MaintInt' => 0,
            'CalInt' => 0,
            'CertInt' => 0,
            'CtrlInt' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLine1(string $line): array
    {
        $content = $this->stripBorders($line);

        $delta = $this->detectShift($content);

        $fields = [
            'Material' => $this->slice($content, self::LINE1_BOUNDS['Material'][0], self::LINE1_BOUNDS['Material'][1]),
            'Description' => $this->slice($content, self::LINE1_BOUNDS['Description'][0], self::LINE1_BOUNDS['Description'][1]),
            'Dimensions' => $this->slice($content, 60, self::NOMINAL_EQUIPMENT_START + $delta),
            'Equipment' => $this->slice($content, self::NOMINAL_EQUIPMENT_START + $delta, 112 + $delta),
            'UserStatus' => $this->slice($content, 112 + $delta, 117 + $delta),
            'SystemStatus' => $this->slice($content, 117 + $delta, 122 + $delta),
            'Location' => $this->slice($content, 122 + $delta, 133 + $delta),
            'Room' => $this->slice($content, 133 + $delta, 142 + $delta),
            'Sloc' => $this->slice($content, 142 + $delta, 147 + $delta),
            'SuperEq' => $this->slice($content, 147 + $delta, 166 + $delta),
            'ManufactSerialNumber' => $this->slice($content, 166 + $delta, 197 + $delta),
            'SerNo' => $this->slice($content, 197 + $delta, self::CONTENT_WIDTH),
        ];

        if ($fields['Equipment'] === null || !preg_match('/^\d{9,11}$/', $fields['Equipment'])) {
            throw new CorruptEquipmentsFileException('Could not locate a valid Equipment number in file record.');
        }

        $fields['MaterialWithoutFet'] = preg_replace('/-FET$/', '', (string) $fields['Material']);
        $fields['ManufactSerialNumber'] ??= '';

        return $fields;
    }

    private function detectShift(string $content): int
    {
        if (!preg_match('/\d{9,11}/', $content, $matches, PREG_OFFSET_CAPTURE, 75)) {
            throw new CorruptEquipmentsFileException('Could not locate the Equipment number anchor in file record.');
        }

        return $matches[0][1] - self::NOMINAL_EQUIPMENT_START;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLine2(string $line): array
    {
        $content = $this->stripBorders($line);

        $ih09Description = trim(substr($content, 0, 41)) ?: null;

        $grossWeight = null;
        if (preg_match('/(\d[\d.]*,\d{2,3})\s+KG\s+KG/', $content, $matches)) {
            $grossWeight = $this->toFloat($matches[1]);
        }

        $plant = null;
        if (preg_match('/(?:0,000\s+){3}(\S+)(?:\s+\S+)*?\s+\d{2}\.\d{2}\.\d{4}/', $content, $matches)) {
            $plant = $matches[1];
        }

        $workcenter = '';
        if (preg_match('/\d{2}\.\d{2}\.\d{4}\s+\d{2}\.\d{2}\.\d{4}\s+\d+\s+(\S+)\s+\d+\s*$/', $content, $matches)) {
            $workcenter = $matches[1];
        }

        return [
            'IH09Description' => $ih09Description,
            'GrossWeight' => $grossWeight,
            'Plant' => $plant,
            'workcenter' => $workcenter,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseLine3(string $line): array
    {
        $content = $this->stripBorders($line);

        $materialStatus = '';
        if (preg_match('/\d,\d{2,3}\s+KG\s+\S+\s+(\S+)/', $content, $matches)) {
            $materialStatus = $matches[1];
        }

        $createdOn = null;
        $createdBy = '';
        $changedOn = null;
        $changedBy = '';

        if (preg_match('/(\d{2}\.\d{2}\.\d{4})\s+(\S+)\s+(\d{2}\.\d{2}\.\d{4})\s+(\S+)/', $content, $matches)) {
            $createdOn = $this->toDate($matches[1]);
            $createdBy = $matches[2];
            $changedOn = $this->toDate($matches[3]);
            $changedBy = $matches[4];
        }

        return [
            'material_status' => $materialStatus,
            'CreatedOn' => $createdOn,
            'CreatedBy' => $createdBy,
            'ChangedOn' => $changedOn,
            'ChangedBy' => $changedBy,
        ];
    }

    private function stripBorders(string $line): string
    {
        return substr($line, 1, self::CONTENT_WIDTH);
    }

    private function slice(string $content, int $start, int $end): ?string
    {
        if ($end <= $start) {
            return null;
        }

        $value = trim(substr($content, $start, $end - $start));

        return $value === '' ? null : $value;
    }

    private function toFloat(string $number): float
    {
        // Parse SAP notation to usable floats
        return (float) str_replace(',', '.', str_replace('.', '', $number));
    }

    private function toDate(string $ddMmYyyy): string
    {
        [$day, $month, $year] = explode('.', $ddMmYyyy);

        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            throw new CorruptEquipmentsFileException("Equipments file contains an invalid date \"{$ddMmYyyy}\".");
        }

        return "{$year}-{$month}-{$day}";
    }
}
