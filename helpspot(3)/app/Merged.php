<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class Merged extends Model
{
    protected $table = 'HS_Request_Merged';

    protected $primaryKey = 'xMergedRequest';

    public $timestamps = false;

    protected $guarded = [];
}
