<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Equipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\BuildsEquipmentsFixtures;
use Tests\TestCase;

class ImportEquipmentsCommandTest extends TestCase
{
    use BuildsEquipmentsFixtures;
    use DatabaseTransactions;

    private string $importDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDir = storage_path('framework/testing/equipments-'.uniqid());
        mkdir($this->importDir, recursive: true);
        config(['import.equipments_path' => $this->importDir]);

        Equipment::query()->delete();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->importDir.'/*') ?: []);
        rmdir($this->importDir);

        parent::tearDown();
    }

    public function test_it_imports_equipments_from_the_latest_file(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [
                ['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001', 'Room' => 'ROOMA'],
                [],
                [],
            ],
            [
                ['Material' => '2222.222.22222-FET', 'Equipment' => '3000000002', 'Room' => 'ROOMB'],
                [],
                [],
            ],
        ]));

        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $content);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame(2, Equipment::count());
        $this->assertSame('ROOMA', Equipment::find('3000000001')->Room);
        $this->assertSame('ROOMB', Equipment::find('3000000002')->Room);
    }

    public function test_it_picks_the_most_recently_named_file(): void
    {
        $older = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001', 'Room' => 'OLD'], [], []],
        ]));
        $newer = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001', 'Room' => 'NEW'], [], []],
        ]));

        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $older);
        file_put_contents($this->importDir.'/EQUIPMENTS_20260212150034.txt', $newer);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame('NEW', Equipment::find('3000000001')->Room);
    }

    public function test_it_replaces_existing_data_on_each_import(): void
    {
        Equipment::factory()->create(['Equipment' => '9999999999']);

        $content = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001'], [], []],
        ]));
        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $content);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame(1, Equipment::count());
        $this->assertNull(Equipment::find('9999999999'));
        $this->assertNotNull(Equipment::find('3000000001'));
    }

    public function test_it_does_not_change_data_when_the_file_is_corrupt(): void
    {
        Equipment::factory()->create(['Equipment' => '9999999999']);

        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', "not a valid export file\n");

        $this->artisan('equipments:import')->assertFailed();

        $this->assertSame(1, Equipment::count());
        $this->assertNotNull(Equipment::find('9999999999'));
    }

    public function test_it_succeeds_without_changing_anything_when_no_file_is_present(): void
    {
        Equipment::factory()->create(['Equipment' => '9999999999']);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame(1, Equipment::count());
    }

    public function test_it_rejects_a_file_with_fewer_rows_than_current_data(): void
    {
        Equipment::factory()->count(3)->create();

        $content = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001'], [], []],
            [['Material' => '2222.222.22222-FET', 'Equipment' => '3000000002'], [], []],
        ]));
        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $content);

        $this->artisan('equipments:import')->assertFailed();

        $this->assertSame(3, Equipment::count());
        $this->assertNull(Equipment::find('3000000001'));
    }

    public function test_it_allows_a_file_with_the_same_row_count_as_current_data(): void
    {
        Equipment::factory()->count(2)->create();

        $content = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001'], [], []],
            [['Material' => '2222.222.22222-FET', 'Equipment' => '3000000002'], [], []],
        ]));
        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $content);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame(2, Equipment::count());
        $this->assertNotNull(Equipment::find('3000000001'));
    }

    public function test_it_allows_the_first_import_into_an_empty_table_regardless_of_size(): void
    {
        $content = $this->equipmentsFile($this->equipmentsRecords([
            [['Material' => '1111.111.11111-FET', 'Equipment' => '3000000001'], [], []],
        ]));
        file_put_contents($this->importDir.'/EQUIPMENTS_20260101000000.txt', $content);

        $this->artisan('equipments:import')->assertSuccessful();

        $this->assertSame(1, Equipment::count());
    }
}
