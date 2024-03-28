<?php

namespace HS\Domain\Workspace;

use HS\Domain\Documents\File;
use HS\Domain\Documents\S3File;
use HS\File\NamedTemporaryFile;
use HS\Domain\Documents\BlobFile;
use HS\Domain\Documents\PathFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use HS\Domain\Document as DocumentInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Document extends Model implements DocumentInterface
{
    protected $table = 'HS_Documents';

    protected $primaryKey = 'xDocumentId';

    public $timestamps = false;

    protected $indexed;

    public function history()
    {
        return $this->belongsTo(History::class, 'xRequestHistory', 'xRequestHistory');
    }

    public function scopeNoBlob($query)
    {
        return $query->select(['xDocumentId', 'sFilename', 'sFileMimeType', 'sCID', 'sFileLocation', 'xRequestHistory', 'xResponse']);
    }

    /**
     * @param $id
     * @param bool $showFullSize
     * @return Document
     */
    public static function fromAdminRequest($id, $showFullSize = false)
    {
        $document = static::noBlob()
            ->where('xDocumentId', $id)
            ->first();

        if (! $document) {
            throw (new ModelNotFoundException)->setModel(get_class($document));
        }

        return $document;
    }

    /**
     * @param $id
     * @param $requestId
     * @return Document
     */
    public static function fromPortalRequest($id, $requestId)
    {
        $document = self::noBlob()->with('history')
            ->where('HS_Documents.xDocumentId', $id)
            ->whereHas('history', function(Builder $q) use ($requestId) {
                $q->where('xRequest', $requestId);
            })
            ->first();

        if (! $document) {
            throw (new ModelNotFoundException)->setModel(get_class($document));
        }

        return $document;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function asBase64()
    {
        if ($this->blobFile) {
            $file = (new BlobFile($this->xDocumentId))->getBody();
        } elseif (strpos($this->sFileLocation, 's3://') !== false) {
            $file = (new S3File($this->sFileLocation))->getBody();
        } else {
            $file = (new PathFile($this->sFileLocation))->getBody();
        }

        return base64_encode($file);
    }

    /**
     * @param bool $showFullSize
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function asFile($showFullSize = false)
    {
        $file = $this->getFile();

        // Admin area gets resized image preview
        if ( !isHosted() && $this->isImage() && ! $showFullSize) {
            // NOTE: $file may be a string (file path) here
            $file = $this->resizeImage($file->toSpl());
        }

        return (is_string($file))
            ? $file
            : $file->toSpl();
    }

    /**
     * @return File
     * @throws \Exception
     */
    public function getFile()
    {
        if (empty($this->sFileLocation)) {
            $file = (new BlobFile($this->xDocumentId));
        } else {
            if (strpos($this->sFileLocation, 's3://') !== false) {
                $file = (new S3File($this->sFileLocation));
            } else {
                $file = (new PathFile($this->sFileLocation));
            }
        }

        return $file;
    }

    /**
     * Resize image if we're embedding it in admin
     * area history timeline view.
     * @param $file
     * @return NamedTemporaryFile|string
     */
    public function resizeImage($file)
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }

        $data = file_get_contents($file);

        if (strlen($data) > hs_setting('cHD_IMAGE_THUMB_MAXBYTES')) {
            return public_path('static/img5/space.gif');
        }

        $resized = smart_resize_image($data, hs_setting('cHD_IMAGE_THUMB_SIZE'), 0, true, 'return');

        return (new NamedTemporaryFile($resized))
            ->persist();
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
