<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the SAP equipment export fails structural validation.
 * The importer must catch this and abort WITHOUT touching existing data.
 */
class EquipmentFileCorruptException extends RuntimeException
{
    public static function missingFile(string $path): self
    {
        return new self("Equipment export not found or not readable: {$path}");
    }

    public static function emptyFile(string $path): self
    {
        return new self("Equipment export is empty: {$path}");
    }

    public static function missingHeader(string $path): self
    {
        return new self("Equipment export is missing the expected SAP header block: {$path}");
    }

    public static function incompleteRecord(int $dataLineCount): self
    {
        return new self(
            "Equipment export data lines ({$dataLineCount}) are not a multiple of 3; " .
            'a record block is truncated or the file layout changed.'
        );
    }

    public static function unparsableRecord(int $blockIndex, string $firstLine): self
    {
        return new self(
            "Record block #{$blockIndex} could not be parsed (no equipment/status anchor found). " .
            'First line: ' . substr(trim($firstLine), 0, 120)
        );
    }
}
