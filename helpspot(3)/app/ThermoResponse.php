<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class ThermoResponse extends Model
{
    protected $guarded = [];

    protected $table = 'HS_Thermostat';

    protected $primaryKey = 'xThermostat';

    public $timestamps = true;

    public function request()
    {
        return $this->belongsTo(\HS\Request::class, 'xRequest');
    }
}
