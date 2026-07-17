<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\EquipmentFileCorruptException;
use App\Services\EquipmentFileParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SAP IH09 export parser.
 *
 * The fixture below is a synthetic 4-record export mimicking the real
 * file's layout exactly (column alignment, three physical lines per
 * record, header block, separators) without containing any real data.
 * The last two records share an equipment number with different system
 * statuses, mirroring the duplicates found in real exports.
 *
 * NOTE: the fixture is column-aligned; do not let your editor reformat
 * the whitespace inside the nowdoc.
 */
class EquipmentFileParserTest extends TestCase
{
    private const FIXTURE = <<<'TXT'
|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
|Material           Material Description                     Size/dimensions                  Equipment          Stat Stat Location   Room     SLoc Superord.Equipment ManufactSerialNumber           Serial Number                                        |
|Description of Technical Object          Size/dimensions                       Gross Weight     WUn            Length                 Width                Height     Uni MS Plnt Plnt Cost Ctr   Valid From to         PP WkCtr Work ctr WorkCtr         |
|Work ctr        Net Weight     Old material no.   MS PP S Created On Created By   Chngd On   Changed by   Short description                        Short desc.                                                                                            |
|9999.111.22222-FET TEST HARNESS ASSY 2-P LT L/XL            100X200X500                      2000000001         USAB ESTO T05I       B01-T-99 LENT                    S4M99991                       2000000001                                           |
|TEST HARNESS ASSY 2-P LT L/XL            200X500X300                                  0,00  KG  KG             0,000                 0,000                 0,000             DE34 DE34 DE343030   23.12.2020 31.12.9999 10000001 T-0-0001 00000000        |
|                     0,00  KG  9999.111.22222     R4 35   23.12.2020 TESTUSER     09.02.2020 TESTPD  LO - Test Cabin CD                       LO - TEST CABIN CD                                                                                          |
|9999.333.44444-FET SAMPLE CONTROL BOX TOOL                                                   2000000002         USAB ASEQ FE05       ASEQ     FE05 2000000001                                        2000000002                                           |
|SAMPLE CONTROL BOX TOOL                                                           8.000,00  KG  KG             0,000                 0,000                 0,000             DE34 DE34 DE343235   23.12.2020 31.12.9999 10000002 MF   00000000            |
|                     0,00  KG  9999.333.44444     R4 35   23.12.2020 TESTIMP      23.12.2020 TESTIMP      Sample Metro Frame                   SAMPLE METRO FRAME                                                                                         |
|9999.111.22222-FET TEST HARNESS ASSY 2-P LT L/XL            100X200X500                      2000000003         USAB ESTO T05I       B09-01-E LENT                    S4M99993                       2000000003                                           |
|TEST HARNESS ASSY 2-P LT L/XL            200X500X300                                  0,00  KG  KG             0,000                 0,000                 0,000             DE34 DE34 DE343030   23.12.2020 31.12.9999 10000001 T-0-0001 00000000        |
|                     0,00  KG  9999.111.22222     R4 35   23.12.2020 TESTUSER     13.11.2020 TESTX9       LO - Test Cabin CD                       LO - TEST CABIN CD                                                                                     |
|9999.111.22222-FET TEST HARNESS ASSY 2-P LT L/XL            100X200X500                      2000000003         USAB UIIA T05I       B09-01-E LENT                    S4M99993                       2000000003                                           |
|TEST HARNESS ASSY 2-P LT L/XL            200X500X300                                  0,00  KG  KG             0,000                 0,000                 0,000             DE34 DE34 DE343030   23.12.2020 31.12.9999 10000001 T-0-0001 00000000        |
|                     0,00  KG  9999.111.22222     R4 35   23.12.2020 TESTUSER     13.11.2020 TESTX9       LO - Test Cabin CD                       LO - TEST CABIN CD                                                                                     |
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
TXT;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_parses_the_searchable_fields_and_tricky_columns(): void
    {
        $records = (new EquipmentFileParser())->parseContents(self::FIXTURE);

        $this->assertCount(4, $records);

        // The four fields the application searches on.
        $first = $records[0];
        $this->assertSame('2000000001', $first->equipment);
        $this->assertSame('9999.111.22222-FET', $first->material);
        $this->assertSame('TEST HARNESS ASSY 2-P LT L/XL', $first->description);
        $this->assertSame('B01-T-99', $first->room);

        // Dimensions must be split off the description, not mistaken for it.
        $this->assertSame('100X200X500', $first->dimensions);

        // European number format: "8.000,00" is 8000.0, and a record
        // without a size column has no dimensions.
        $second = $records[1];
        $this->assertSame(8000.0, $second->grossWeight);
        $this->assertNull($second->dimensions);
    }

    public function test_parse_unique_keeps_the_last_occurrence_of_a_duplicate(): void
    {
        $unique = (new EquipmentFileParser())->parseUnique($this->fixtureFile(self::FIXTURE));

        $this->assertCount(3, $unique);

        // The duplicate appears first as ESTO, then as UIIA: the last one
        // wins, and the importer persists this deduplicated set.
        $this->assertSame('UIIA', $unique['2000000003']->systemStatus);
    }

    public function test_rejects_an_empty_file_without_returning_records(): void
    {
        $this->expectException(EquipmentFileCorruptException::class);

        (new EquipmentFileParser())->parse($this->fixtureFile("   \n\n  "));
    }

    public function test_rejects_a_file_with_a_truncated_record_block(): void
    {
        $lines = explode("\n", self::FIXTURE);
        array_splice($lines, -2, 1); // drop the last data line, keep the trailing separator

        $this->expectException(EquipmentFileCorruptException::class);

        (new EquipmentFileParser())->parseContents(implode("\n", $lines));
    }

    private function fixtureFile(string $contents): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'equipments_test_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
