<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken as BasePersonalAccessToken;

class PersonalAccessToken extends BasePersonalAccessToken
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'HS_Person_Access_Tokens';
}
