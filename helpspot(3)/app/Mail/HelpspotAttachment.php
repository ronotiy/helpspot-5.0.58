<?php

namespace HS\Mail;

use Swift_Image;
use Swift_Attachment;
use Swift_ByteStream_FileByteStream;

use HS\Base\Gettable;
use HS\Domain\Workspace\Document;

class HelpspotAttachment implements Attachment
{
    use Gettable;

    protected $id;
    protected $isEmbed;
    protected $document; // the HS_Document row

    /**
     * Attachment constructor.
     * @param int $xDocumentId
     * @param bool $isEmbed
     */
    public function __construct($xDocumentId, $isEmbed)
    {
        $this->id = $xDocumentId;
        $this->isEmbed = $isEmbed;
    }

    /**
     * @return Document
     */
    protected function document()
    {
        return ($this->document)
            ? $this->document
            : $this->document = Document::findOrFail($this->id);
    }

    /**
     * @return bool
     */
    public function isEmbed()
    {
        return $this->isEmbed && ! empty($this->document()->sCID);
    }

    /**
     * @return Swift_Image|Swift_Attachment
     * @throws \Swift_IoException
     */
    public function toSwift()
    {
        $document = $this->document()->asFile(true);

        if( $this->isEmbed() ) {
            return (new Swift_Image(
                new Swift_ByteStream_FileByteStream($document->getPathname()),
                $this->document()->sFilename,
                $this->document()->sFileMimeType)
            )->setId($this->validCID($this->document()->sCID));
        }

        return new Swift_Attachment(
            new Swift_ByteStream_FileByteStream($document->getPathname()),
            $this->document()->sFilename,
            $this->document()->sFileMimeType
        );
    }

    /**
     * Persist files so queued emails can get retrieve the documents
     */
    public function persist()
    {
        // spl file object from Document takes care of this
        return $this;
    }

    /**
     * Delete the attachment from disk if we
     * persisted a file out of the tmp dir
     * @return $this
     */
    public function cleanup()
    {
        return $this;
    }

    /**
     * Naive-ish stab at generating a cid that
     * Swiftmailer should accept
     * @param $cid
     * @return string
     */
    protected function validCID($cid)
    {
        if(strpos($cid, '@') === false) {
            return $cid . '@' . $this->getHostname();
        }

        return $cid;
    }

    protected function getHostname()
    {
        if( defined('cHOST') ) {
            return parse_url(cHOST, PHP_URL_HOST);
        }

        return 'helpspot.com';
    }
}
