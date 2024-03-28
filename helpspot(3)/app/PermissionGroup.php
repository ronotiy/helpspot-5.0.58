<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    protected $table = 'HS_Permission_Groups';

    protected $primaryKey = 'xGroup';

    public $timestamps = false;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class, 'fUserType');
    }
}
