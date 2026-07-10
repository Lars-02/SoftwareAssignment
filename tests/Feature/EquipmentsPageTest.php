<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Equipment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EquipmentsPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Equipment::query()->delete();
    }

    public function test_it_shows_an_empty_state_when_there_are_no_equipments(): void
    {
        $response = $this->get(route('equipments.index'));

        $response->assertOk();
        $response->assertSee('No equipments imported yet.');
    }

    public function test_it_lists_equipments(): void
    {
        Equipment::factory()->create(['Equipment' => '4000000001']);
        Equipment::factory()->create(['Equipment' => '4000000002']);

        $response = $this->get(route('equipments.index'));

        $response->assertOk();
        $response->assertSee('4000000001');
        $response->assertSee('4000000002');
    }

    public function test_it_filters_by_search_term(): void
    {
        Equipment::factory()->create(['Equipment' => '4000000001', 'Description' => 'FIRST WIDGET']);
        Equipment::factory()->create(['Equipment' => '4000000002', 'Description' => 'SECOND WIDGET']);

        $response = $this->get(route('equipments.index', ['search' => 'FIRST']));

        $response->assertOk();
        $response->assertSee('4000000001');
        $response->assertDontSee('4000000002');
    }

    public function test_it_shows_a_search_specific_empty_state_when_nothing_matches(): void
    {
        Equipment::factory()->create(['Equipment' => '4000000001']);

        $response = $this->get(route('equipments.index', ['search' => 'NOTHING WILL MATCH THIS']));

        $response->assertOk();
        $response->assertSee('No equipments match');
        $response->assertDontSee('4000000001');
    }

    public function test_it_rejects_search_terms_over_the_max_length(): void
    {
        $response = $this->get(route('equipments.index', ['search' => str_repeat('a', 192)]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('search');
    }

    public function test_it_accepts_search_terms_at_the_max_length(): void
    {
        $response = $this->get(route('equipments.index', ['search' => str_repeat('a', 191)]));

        $response->assertOk();
        $response->assertSessionHasNoErrors();
    }
}
