<?php

namespace HS\Domain\Forum;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'HS_Forums_Posts';

    protected $primaryKey = 'xPostId';

    public $timestamps = false;

    public function topic()
    {
        return $this->belongsTo(\HS\Domain\Forum\Topic::class, 'xTopicId');
    }
}
