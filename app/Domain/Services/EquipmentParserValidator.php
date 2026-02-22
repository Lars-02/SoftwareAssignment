<?php

namespace App\Domain\Services;

use App\Application\Exceptions\IncompleteFileException;
use App\Application\Exceptions\InvalidFileException;

class EquipmentParserValidator
{
    private const MINIMUM_RECORD = 50;
    private const EXPECTED_DATA_LINE_LENGTH = 252;

    /**
     * @param string[] $lines
     */
    public function validateHeader(array $lines): void
    {
        $requiredHeadersPerLine = [
            0 => [
                'Material',
                'Material Description',
                'Size/dimensions',
                'Equipment',
                'Stat',
                'Location',
                'Room',
                'SLoc',
                'Superord.Equipment',
                'ManufactSerialNumber',
                'Serial Number',
            ],
            1 => [
                'Description of Technical Object',
                'Size/dimensions',
                'Gross Weight',
                'WUn',
                'Length',
                'Width',
                'Height',
                'Uni',
                'MS',
                'Plnt',
                'Plnt Cost Ctr',
                'Valid From to',
                'PP WkCtr',
                'Work ctr',
                'WorkCtr',
            ],
            2 => [
                'Work ctr',
                'Net Weight',
                'Old material no.',
                'MS PP S',
                'Created On',
                'Created By',
                'Chngd On',
                'Changed by',
                'Short description',
                'Short desc.',
            ],
        ];

        $headerLines = $this->getHeaderLines($lines);

        foreach ($requiredHeadersPerLine as $headerLineIndex => $requiredHeaders) {
            $headerLine = $headerLines[$headerLineIndex];

            foreach ($requiredHeaders as $requiredHeader) {
                if (!str_contains($headerLine, $requiredHeader)) {
                    throw new InvalidFileException('Invalid header');
                }
            }
        }
    }

    /**
     * @param string[] $lines
     */
    private function getHeaderLines(array $lines): array
    {
        $headerLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (!str_starts_with($trimmed, '|')) {
                continue;
            }

            if (str_starts_with($trimmed, '|---')) {
                continue;
            }

            $headerLines[] = $trimmed;

            if (count($headerLines) === 3) {
                break;
            }
        }

        if (count($headerLines) < 3) {
            throw new InvalidFileException('Invalid headers');
        }

        return $headerLines;
    }

    public function isHeaderOrFooter(string $line): bool
    {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '|---') || str_starts_with($trimmed, '---')) {
            return true;
        }

        if (!str_starts_with($trimmed, '|')) {
            return true;
        }

        if (
            str_contains($trimmed, 'Material Description')
            || str_contains($trimmed, 'Description of Technical Object')
            || str_contains($trimmed, 'Work ctr')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, mixed> $equipments
     */
    public function validateMinimumRecord(array $equipments): void
    {
        if (count($equipments) < self::MINIMUM_RECORD) {
            throw new IncompleteFileException('Total rows as less than expected');
        }
    }

    /**
     * @param string[] $lines
     */
    public function validateLineLength(array $lines): void
    {
        foreach ($lines as $line) {
            if (strlen($line) !== self::EXPECTED_DATA_LINE_LENGTH) {
                throw new InvalidFileException('Lines does not contain the expected total characters');
            }
        }
    }
}
