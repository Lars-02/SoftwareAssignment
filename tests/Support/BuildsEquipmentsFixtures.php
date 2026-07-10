<?php

declare(strict_types=1);

namespace Tests\Support;

trait BuildsEquipmentsFixtures
{
    /**
     * Wrap a set of body lines in a valid header and footer.
     *
     * @param  array<int, string>  $bodyLines
     */
    protected function equipmentsFile(array $bodyLines): string
    {
        $lines = [
            $this->borderLine(),
            $this->paddedLine('Material  Material Description  Size/dimensions  Equipment  Stat Stat Location  Room  SLoc  Superord.Equipment ManufactSerialNumber  Serial Number'),
            $this->paddedLine('Description of Technical Object  Size/dimensions  Gross Weight'),
            $this->paddedLine('Work ctr  Net Weight  Old material no.  MS PP S Created On Created By  Chngd On  Changed by'),
            $this->borderLine(),
            ...$bodyLines,
            str_repeat('-', 252),
        ];

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records  Each record is a [line1 fields, line2 fields, line3 fields] tuple.
     * @return array<int, string>
     */
    protected function equipmentsRecords(array $records): array
    {
        $lines = [];

        foreach ($records as [$line1, $line2, $line3]) {
            $lines[] = $this->equipmentsLine1($line1);
            $lines[] = $this->equipmentsLine2($line2);
            $lines[] = $this->equipmentsLine3($line3);
        }

        return $lines;
    }

    private function borderLine(): string
    {
        return '|'.str_repeat('-', 250).'|';
    }

    private function paddedLine(string $content): string
    {
        return '|'.str_pad(substr($content, 0, 250), 250).'|';
    }

    /**
     * Builds a record's first line at exact column offsets. Pass `eqCol` to
     * simulate the shifted rows found in the real export (nominal column 93).
     *
     * @param  array<string, mixed>  $fields
     */
    private function equipmentsLine1(array $fields): string
    {
        $content = str_repeat(' ', 250);
        $eqCol = $fields['eqCol'] ?? 93;

        $content = $this->place($content, 0, $fields['Material'] ?? '');
        $content = $this->place($content, 19, $fields['Description'] ?? '');
        $content = $this->place($content, 60, $fields['Dimensions'] ?? '');
        $content = $this->place($content, $eqCol, $fields['Equipment']);
        $content = $this->place($content, $eqCol + 19, $fields['UserStatus'] ?? 'USAB');
        $content = $this->place($content, $eqCol + 24, $fields['SystemStatus'] ?? 'ESTO');
        $content = $this->place($content, $eqCol + 29, $fields['Location'] ?? '');
        $content = $this->place($content, $eqCol + 40, $fields['Room'] ?? '');
        $content = $this->place($content, $eqCol + 49, $fields['Sloc'] ?? '');
        $content = $this->place($content, $eqCol + 54, $fields['SuperEq'] ?? '');
        $content = $this->place($content, $eqCol + 73, $fields['ManufactSerialNumber'] ?? '');
        $content = $this->place($content, $eqCol + 104, $fields['SerNo'] ?? '');

        return $this->paddedLine($content);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function equipmentsLine2(array $fields): string
    {
        $content = str_repeat(' ', 250);
        $content = $this->place($content, 0, $fields['IH09Description'] ?? '');

        $plant = $fields['Plant'] ?? 'DE34';
        $tail = sprintf(
            '%s  KG  KG             0,000                 0,000                 0,000             %s %s   %s %s   %s %s %s',
            $fields['GrossWeight'] ?? '0,00',
            $plant,
            $plant,
            $fields['ValidFrom'] ?? '01.01.2020',
            $fields['ValidTo'] ?? '31.12.9999',
            $fields['WkCtr'] ?? '10000000',
            $fields['workcenter'] ?? 'WC01',
            $fields['WorkCtrNum'] ?? '00000000',
        );
        $content = $this->place($content, 79, $tail);

        return $this->paddedLine($content);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function equipmentsLine3(array $fields): string
    {
        $content = str_repeat(' ', 250);

        $tail = sprintf(
            '0,00  KG  %s     %s      %s %s     %s %s',
            $fields['OldMaterial'] ?? 'X',
            $fields['material_status'] ?? 'R4',
            $fields['CreatedOn'] ?? '01.01.2020',
            $fields['CreatedBy'] ?? 'TESTER',
            $fields['ChangedOn'] ?? '02.01.2020',
            $fields['ChangedBy'] ?? 'TESTER2',
        );
        $content = $this->place($content, 0, $tail);

        return $this->paddedLine($content);
    }

    private function place(string $content, int $start, mixed $value): string
    {
        $value = (string) $value;

        return substr($content, 0, $start).$value.substr($content, $start + strlen($value));
    }
}
