<?php

namespace HS\Domain\KnowledgeBooks;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'HS_KB_Pages';

    protected $primaryKey = 'xPage';

    public $timestamps = false;

    public function chapter()
    {
        return $this->belongsTo(\HS\Domain\KnowledgeBooks\Chapter::class, 'xChapter');
    }

    public function documents()
    {
        return $this->hasMany('HS\Domain\KnowledgeBooks\Documents', 'xDocumentId');
    }
}
