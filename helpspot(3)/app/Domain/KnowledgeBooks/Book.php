<?php

namespace HS\Domain\KnowledgeBooks;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $table = 'HS_KB_Books';

    protected $primaryKey = 'xBook';

    public $timestamps = false;

    public function chapters()
    {
        return $this->hasMany(\HS\Domain\KnowledgeBooks\Chapter::class, 'xBook');
    }
}
