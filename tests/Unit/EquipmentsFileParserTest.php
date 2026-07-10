<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\CorruptEquipmentsFileException;
use App\Services\EquipmentsFileParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\BuildsEquipmentsFixtures;

class EquipmentsFileParserTest extends TestCase
{
    use BuildsEquipmentsFixtures;

    public function test_it_parses_a_clean_record(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [
                [
                    'Material' => '1111.111.11111-FET',
                    'Description' => 'CLEAN WIDGET',
                    'Dimensions' => '10X10X10',
                    'Equipment' => '2000000001',
                    'UserStatus' => 'USAB',
                    'SystemStatus' => 'ESTO',
                    'Location' => 'LOC1',
                    'Room' => 'ROOMA',
                    'Sloc' => 'SLC1',
                    'ManufactSerialNumber' => 'MSN001',
                    'SerNo' => '2000000001',
                ],
                [
                    'IH09Description' => 'CLEAN WIDGET TECH',
                    'GrossWeight' => '12,50',
                    'Plant' => 'DE34',
                    'workcenter' => 'WCONE',
                ],
                [
                    'material_status' => 'R4',
                    'CreatedOn' => '05.01.2020',
                    'CreatedBy' => 'ALICE',
                    'ChangedOn' => '06.01.2020',
                    'ChangedBy' => 'BOB',
                ],
            ],
        ]));

        $rows = (new EquipmentsFileParser())->parse($content);

        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('2000000001', $row['Equipment']);
        $this->assertSame('1111.111.11111-FET', $row['Material']);
        $this->assertSame('1111.111.11111', $row['MaterialWithoutFet']);
        $this->assertSame('CLEAN WIDGET', $row['Description']);
        $this->assertSame('10X10X10', $row['Dimensions']);
        $this->assertSame('USAB', $row['UserStatus']);
        $this->assertSame('ESTO', $row['SystemStatus']);
        $this->assertSame('LOC1', $row['Location']);
        $this->assertSame('ROOMA', $row['Room']);
        $this->assertSame('SLC1', $row['Sloc']);
        $this->assertSame('MSN001', $row['ManufactSerialNumber']);
        $this->assertSame('2000000001', $row['SerNo']);
        $this->assertSame('CLEAN WIDGET TECH', $row['IH09Description']);
        $this->assertEqualsWithDelta(12.50, $row['GrossWeight'], 0.001);
        $this->assertSame('DE34', $row['Plant']);
        $this->assertSame('WCONE', $row['workcenter']);
        $this->assertSame('R4', $row['material_status']);
        $this->assertSame('2020-01-05', $row['CreatedOn']);
        $this->assertSame('ALICE', $row['CreatedBy']);
        $this->assertSame('2020-01-06', $row['ChangedOn']);
        $this->assertSame('BOB', $row['ChangedBy']);
    }

    public function test_it_corrects_a_shifted_record(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [
                [
                    'Material' => '2222.222.22222-FET',
                    'Description' => 'SHIFTED WIDGET',
                    'Dimensions' => '',
                    'eqCol' => 89,
                    'Equipment' => '2000000002',
                    'UserStatus' => 'DCDA',
                    'SystemStatus' => 'ESTO',
                    'Location' => 'LOC2',
                    'Room' => 'ROOMB',
                    'Sloc' => 'SLC2',
                ],
                ['workcenter' => 'WCTWO'],
                ['material_status' => 'U2'],
            ],
        ]));

        $rows = (new EquipmentsFileParser())->parse($content);

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame('2000000002', $row['Equipment']);
        $this->assertSame('ROOMB', $row['Room']);
        $this->assertSame('LOC2', $row['Location']);
        $this->assertSame('DCDA', $row['UserStatus']);
        $this->assertSame('ESTO', $row['SystemStatus']);
    }

    public function test_it_throws_when_a_record_has_no_locatable_equipment_number(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [
                ['Material' => 'NO EQUIPMENT NUMBER HERE', 'Equipment' => ''],
                [],
                [],
            ],
        ]));

        $this->expectException(CorruptEquipmentsFileException::class);

        (new EquipmentsFileParser())->parse($content);
    }

    public function test_it_deduplicates_by_keeping_the_last_occurrence(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [
                ['Material' => '1111.111.11111-FET', 'Equipment' => '2000000001', 'Room' => 'ROOMA'],
                [],
                [],
            ],
            [
                ['Material' => '1111.111.11111-FET', 'Equipment' => '2000000001', 'Room' => 'ROOMZ'],
                [],
                ['ChangedBy' => 'EVE'],
            ],
        ]));

        $rows = (new EquipmentsFileParser())->parse($content);

        $this->assertCount(1, $rows);
        $this->assertSame('ROOMZ', $rows[0]['Room']);
        $this->assertSame('EVE', $rows[0]['ChangedBy']);
    }

    #[DataProvider('malformedFileProvider')]
    public function test_it_rejects_malformed_files(string $content): void
    {
        $this->expectException(CorruptEquipmentsFileException::class);

        (new EquipmentsFileParser())->parse($content);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function malformedFileProvider(): array
    {
        $border = '|'.str_repeat('-', 250).'|';
        $footer = str_repeat('-', 252);
        $notABorder = str_pad('not a border', 252);
        $headerText = [
            '|'.str_pad('header 1', 250).'|',
            '|'.str_pad('header 2', 250).'|',
            '|'.str_pad('header 3', 250).'|',
        ];
        $goodRecordLine = '|'.str_pad('x', 250).'|';

        return [
            'missing top border' => [
                implode("\n", [$notABorder, ...$headerText, $border, $goodRecordLine, $goodRecordLine, $goodRecordLine, $footer]),
            ],
            'missing bottom header border' => [
                implode("\n", [$border, ...$headerText, $notABorder, $goodRecordLine, $goodRecordLine, $goodRecordLine, $footer]),
            ],
            'missing footer' => [
                implode("\n", [$border, ...$headerText, $border, $goodRecordLine, $goodRecordLine, $goodRecordLine, 'not a footer']),
            ],
            'body not a multiple of three' => [
                implode("\n", [$border, ...$headerText, $border, $goodRecordLine, $goodRecordLine, $footer]),
            ],
            'too short to contain a header and body' => [
                implode("\n", [$border, $border]),
            ],
        ];
    }
}
