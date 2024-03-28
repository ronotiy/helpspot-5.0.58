<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'HS_Request';

    protected $primaryKey = 'xRequest';

    public $timestamps = false;

    protected $guarded = [];

    public function merged()
    {
        return $this->hasMany(Merged::class);
    }
}
