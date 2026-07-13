<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use DateTimeImmutable;

/**
 * One equipment record from the SAP IH09 export.
 *
 * Immutable value object: the parser produces these, the importer consumes them.
 * All nullable fields are null when the export left the column blank.
 */
final readonly class EquipmentRecord
{
    public function __construct(
        public string $equipment,
        public ?string $material,
        public string $materialWithoutFet,
        public ?string $description,
        public ?string $dimensions,
        public ?string $ih09Description,
        public string $userStatus,
        public string $systemStatus,
        public ?string $location,
        public ?string $room,
        public ?string $sloc,
        public ?string $superEquipment,
        public ?string $manufactSerialNumber,
        public ?string $serialNumber,
        public ?string $plant,
        public ?string $costCenter,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validTo,
        public ?string $workCenter,
        public ?float $grossWeight,
        public ?string $oldMaterialNumber,
        public ?string $materialStatus,
        public ?DateTimeImmutable $createdOn,
        public ?string $createdBy,
        public ?DateTimeImmutable $changedOn,
        public ?string $changedBy,
        public ?string $shortDescription,
    ) {
    }

    /**
     * Map to the columns of the `Equipments` table.
     *
     * Columns that have no source in the export (cleaning counters, cert/cal
     * dates, intervals, ...) are intentionally omitted, so the database
     * defaults apply. NOT NULL varchar columns without a source are set to ''.
     *
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'Equipment'            => $this->equipment,
            'Material'             => $this->material,
            'MaterialWithoutFet'   => $this->materialWithoutFet,
            'Description'          => $this->description,
            'IH09Description'      => $this->ih09Description,
            'Room'                 => $this->room,
            'Plant'                => $this->plant,
            'Location'             => $this->location,
            'Sloc'                 => $this->sloc,
            'SuperEq'              => $this->superEquipment,
            'ManufactSerialNumber' => $this->manufactSerialNumber ?? '',
            'SerNo'                => $this->serialNumber,
            'UserStatus'           => $this->userStatus,
            'SystemStatus'         => $this->systemStatus,
            'Dimensions'           => $this->dimensions,
            'ToolCompetence'       => '',
            'GrossWeight'          => $this->grossWeight,
            'current_status'       => '',
            'workcenter'           => $this->workCenter ?? '',
            'material_status'      => $this->materialStatus ?? '',
            'CreatedOn'            => $this->createdOn?->format('Y-m-d H:i:s'),
            'CreatedBy'            => $this->createdBy ?? '',
            'ChangedOn'            => $this->changedOn?->format('Y-m-d H:i:s'),
            'ChangedBy'            => $this->changedBy ?? '',
        ];
    }
}
