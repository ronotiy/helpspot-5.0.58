<?php

namespace HS\Domain\Forum;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'HS_Forums_Topics';

    protected $primaryKey = 'xTopicId';

    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(\HS\Domain\Forum\Post::class, 'xTopicId');
    }

    public function forum()
    {
        return $this->belongsTo(\HS\Domain\Forum\Forum::class, 'xForumId');
    }
}
