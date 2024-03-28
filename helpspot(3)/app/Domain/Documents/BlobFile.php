<?php

namespace HS\Domain\Documents;

use HS\File\NamedTemporaryFile;

class BlobFile implements File
{
    /**
     * @var int
     */
    private $documentId;

    protected $file;

    /**
     * BlobFile constructor.
     * @param int $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
        $this->file = $this->getRow();
    }

    /**
     * @return mixed
     */
    protected function getRow()
    {
        return $GLOBALS['DB']->GetRow(
            'SELECT xDocumentId, blobFile FROM HS_Documents WHERE xDocumentId=?',
            [$this->documentId]
        );
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->file['blobFile'];
    }

    /**
     * @return \SplFileInfo
     */
    public function toSpl()
    {
        return with($tmpFile = new NamedTemporaryFile($this->file['blobFile']))->persist()->toSpl();
    }
}
