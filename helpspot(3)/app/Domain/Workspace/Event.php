<?php

namespace HS\Domain\Workspace;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'HS_Request_Events';

    protected $primaryKey = 'xEvent';

    public $timestamps = false;

    protected $fillable = [
        // 'xRequestHistory',
        'xRequest',
        'xPerson',
        'sColumn',
        'dtLogged',
        'iSecondsInState',
        'iValue',
        'sValue',
        'sLabel',
        'sDescription',
    ];

    // String Calues
    public function setSValueAttribute($value)
    {
        $this->attributes['sValue'] = \utf8_substr($value, 0, 255);
    }

    // Decimal Values (stored as string)
    public function setDValueAttribute($value)
    {
        $this->attributes['dValue'] = \utf8_substr($value, 0, 255);
    }

    // Label Values
    public function setSLabelAttribute($value)
    {
        $this->attributes['sLabel'] = \utf8_substr($value, 0, 255);
    }

    // Description Values
    public function setSDescriptionAttribute($value)
    {
        $this->attributes['sDescription'] = \utf8_substr($value, 0, 255);
    }

    public function request()
    {
        return $this->belongsTo(\HS\Domain\Workspace\History::class, 'xRequestHistory');
    }
}
