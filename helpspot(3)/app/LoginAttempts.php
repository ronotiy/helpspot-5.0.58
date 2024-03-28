<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class LoginAttempts extends Model
{
    protected $table = 'HS_Login_Attempts';

    protected $primaryKey = 'xAttempt';

    public $timestamps = false;

    protected $guarded = [];
}
