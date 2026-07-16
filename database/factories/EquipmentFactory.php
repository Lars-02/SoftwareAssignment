<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Equipment' => fake()->unique()->numerify('##########'),
            'Material' => fake()->bothify('####.###.#####-FET'),
            'MaterialWithoutFet' => fake()->bothify('####.###.#####'),
            'Description' => fake()->words(3, true),
            'Room' => fake()->bothify('ROOM##'),
            'Location' => fake()->bothify('LOC##'),
            'ManufactSerialNumber' => '',
            'UserStatus' => 'USAB',
            'SystemStatus' => 'ESTO',
            'ToolCompetence' => '',
            'NEN3140Int' => 0,
            'MaintInt' => 0,
            'CalInt' => 0,
            'CertInt' => 0,
            'CtrlInt' => 0,
            'current_status' => '',
            'workcenter' => '',
            'material_status' => 'R4',
            'CreatedBy' => 'TESTER',
            'ChangedBy' => 'TESTER',
        ];
    }
}
