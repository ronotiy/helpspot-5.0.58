<?php

namespace HS\Domain\Forum;

use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    protected $table = 'HS_Forums';

    protected $primaryKey = 'xForumId';

    public $timestamps = false;

    public function topics()
    {
        return $this->hasMany(\HS\Domain\Forum\Topic::class, 'xForumId');
    }
}
