<?php

namespace HS\Domain\KnowledgeBooks;

use Illuminate\Database\Eloquent\Model;
use HS\Domain\Document as DocumentInterface;

class Document extends Model implements DocumentInterface
{
    protected $table = 'HS_KB_Documents';

    protected $primaryKey = 'xDocumentId';

    public $timestamps = false;

    protected $indexed;

    public function page()
    {
        return $this->belongsTo('HS\Domain\KnowledgeBooks\page', 'xPage');
    }

    /**
     * Determine if the file is an image.
     * @return bool
     */
    public function isImage()
    {
        return in_array($this->sFileMimeType, ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg']);
    }
}
