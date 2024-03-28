<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    protected $table = 'HS_Person_Status';

    protected $primaryKey = 'xPersonStatus';

    public $timestamps = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'xPersonStatus');
    }
}
