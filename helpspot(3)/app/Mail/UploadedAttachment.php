<?php

namespace HS\Mail;

use Swift_Image;
use Swift_Attachment;
use HS\Base\Gettable;

class UploadedAttachment implements Attachment
{
    use Gettable;

    protected $path;
    protected $fileName;
    protected $contentType;
    protected $cid;
    protected $persistedPath;

    /**
     * Attachment constructor.
     * @param string $path
     * @param string $fileName
     * @param string $contentType
     * @param string|null $cid
     */
    public function __construct($path, $fileName, $contentType, $cid=null)
    {
        $this->path = $path;
        $this->fileName = $fileName;
        $this->contentType = $contentType;
        $this->cid = $cid;
    }

    /**
     * @return bool
     */
    public function isEmbed()
    {
        return ! is_null($this->cid);
    }

    /**
     * @return Swift_Image|Swift_Attachment
     */
    public function toSwift()
    {
        $path = $this->persistedPath ?? $this->path;

        if( $this->isEmbed() ) {
            return with(Swift_Image::fromPath($path), function(Swift_Image $image) {
                $image->setFilename($this->fileName);
                $image->setContentType($this->contentType);
                $image->setId($this->cid);

                return $image;
            });
        }

        return with(Swift_Attachment::fromPath($path, $this->contentType), function(Swift_Attachment $attachment) {
            $attachment->setFilename($this->fileName);

            return $attachment;
        });
    }

    /**
     * Persist files so queued emails can get retrieve the documents
     */
    public function persist()
    {
        $extension = $this->getExtension($this->contentType);
        $attachmentPath = storage_path('attachments/'.md5($this->fileName.uniqid('helpspot')).'.'.$extension);

        // todo: Do we need to stream the file to new location, or can we rely on move_uploaded_file()?
        rename($this->path, $attachmentPath);

        $this->persistedPath = $attachmentPath;

        return $this;
    }

    /**
     * Delete the attachment from disk if we
     * persisted a file out of the tmp dir
     * @return $this
     */
    public function cleanup()
    {
        if( $this->persistedPath && file_exists($this->persistedPath) ) {
            unlink($this->persistedPath);
        }

        return $this;
    }

    /**
     * @param $mime
     * @return string
     */
    protected function getExtension($mime)
    {
        $ext = hs_lookup_mime($mime);

        return $ext ? $ext : 'txt';
    }
}
