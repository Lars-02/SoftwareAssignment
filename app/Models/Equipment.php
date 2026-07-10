<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'Equipments';

    protected $primaryKey = 'Equipment';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'Equipment',
        'Material',
        'MaterialWithoutFet',
        'Description',
        'IH09Description',
        'Room',
        'Plant',
        'Location',
        'Sloc',
        'SuperEq',
        'ManufactSerialNumber',
        'SerNo',
        'UserStatus',
        'SystemStatus',
        'Dimensions',
        'CleaningCounter_limit',
        'CleaningCounter_current',
        'ToolCompetence',
        'NextCertDate',
        'NextCalDate',
        'NextCtrlDate',
        'NEN3140Int',
        'MaintInt',
        'NextNEN3140Date',
        'CalInt',
        'NextMaintDate',
        'CertInt',
        'CtrlInt',
        'ExempEndDate',
        'Min_CALD_Date',
        'GrossWeight',
        'current_status',
        'needed_time',
        'return_time',
        'workcenter',
        'material_status',
        'StockType',
        'SpecialStock',
        'CreatedOn',
        'CreatedBy',
        'ChangedOn',
        'ChangedBy',
    ];

    protected function casts(): array
    {
        return [
            'CleaningCounter_limit' => 'integer',
            'CleaningCounter_current' => 'integer',
            'NextCertDate' => 'date',
            'NextCalDate' => 'date',
            'NextCtrlDate' => 'date',
            'NEN3140Int' => 'integer',
            'MaintInt' => 'integer',
            'NextNEN3140Date' => 'date',
            'CalInt' => 'integer',
            'NextMaintDate' => 'date',
            'CertInt' => 'integer',
            'CtrlInt' => 'integer',
            'ExempEndDate' => 'date',
            'Min_CALD_Date' => 'date',
            'GrossWeight' => 'float',
            'needed_time' => 'datetime',
            'return_time' => 'datetime',
            'CreatedOn' => 'datetime',
            'ChangedOn' => 'datetime',
        ];
    }
}
