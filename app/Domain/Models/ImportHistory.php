<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $table = 'ImportHistory';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $guarded = [];
}
