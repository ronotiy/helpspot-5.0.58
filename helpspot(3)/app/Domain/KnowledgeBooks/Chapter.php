<?php

namespace HS\Domain\KnowledgeBooks;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $table = 'HS_KB_Chapters';

    protected $primaryKey = 'xChapter';

    public $timestamps = false;

    public function pages()
    {
        return $this->hasMany(\HS\Domain\KnowledgeBooks\Page::class, 'xChapter');
    }

    public function book()
    {
        return $this->belongsTo(\HS\Domain\KnowledgeBooks\Book::class, 'xBook');
    }
}
