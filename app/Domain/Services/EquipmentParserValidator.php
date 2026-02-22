<?php

namespace App\Domain\Services;

class EquipmentParserValidator
{

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
}
