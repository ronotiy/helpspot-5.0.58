<?php

namespace HS\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Response;
use HS\File\NamedTemporaryFile;
use HS\Domain\Workspace\History;
use HS\Domain\Workspace\Document;
use Illuminate\Http\RedirectResponse;

use HS\Domain\Workspace\Request as Ticket;
use Symfony\Component\HttpFoundation\Request;
use HS\Domain\KnowledgeBooks\Document as KBDocument;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController
{
    const FROM_ADMIN = 0;

    const FROM_KB = 2;

    const FROM_PORTAL = 3;

    const FROM_REQUEST = 4;

    protected $headers = [];

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function downloadFile()
    {
        $from = $this->request->input('from');

        $handler = $this->getHandler($from);

        return $this->$handler();
    }

    protected function adminFileHandler()
    {
        try {
            $document = Document::fromAdminRequest($this->request->input('id', 0));
        } catch (ModelNotFoundException $e) {
            return $this->fileNotFound();
        }

        $file = $document->asFile($this->request->has('showfullsize'));

        $response = new BinaryFileResponse(
            $file,
            200,
            ['Content-Type' => $document->sFileMimeType]
        );

        // Cache "forever" if we resized an emedded image
        if ($document->isImage() && ! $this->request->has('showfullsize')) {
            $response->setCache([
                'last_modified' => new \DateTime(gmdate('D, d M Y H:i:s', 1165793644)),
                'max_age' => 315360000,
                'public' => true,
            ]);
            $response->setExpires(new \DateTime(gmdate('D, d M Y H:i:s', time() + 315360000)));
            $response->setVary('Accept-Encoding');
        }

        $ext = pathinfo($document->sFilename, PATHINFO_EXTENSION);
        $response->setContentDisposition($this->getDisposition($document), $this->cleanFileName($document->sFilename), $this->cleanFileName($this->toAscii('document.'.$ext)));

        return $response;
    }

    protected function knowledgebookFileHandler()
    {
        $docId = $this->request->input('id', 0);
        $document = KBDocument::select(['xDocumentId', 'xPage', 'fDownload', 'sFilename', 'sFileMimeType'])
            ->where('xDocumentId', $docId)
            ->first();

        if (! $document) {
            return $this->fileNotFound();
        }

        // If in portal, don't send documents from private pages/books/chapters
        if (IN_PORTAL) {
            // todo: legacy refactor
            $kb = apiInPrivateBook(['xPage' => $document->xPage]);

            if ($kb['fPrivate'] == 1) {
                return new RedirectResponse('admin?pg=file&from='.self::FROM_KB.'&id='.$docId);
            }
        }

        // KB files are always blobs from the database
        $fileBlob = $GLOBALS['DB']->GetRow(
            'SELECT xDocumentId, blobFile FROM HS_KB_Documents WHERE xDocumentId=?',
            [$document->xDocumentId]
        );
        $file = with(new NamedTemporaryFile($fileBlob['blobFile']))->persist()->toSpl();

        $response = new BinaryFileResponse(
            $file,
            200,
            ['Content-Type' => $document->sFileMimeType]
        );

        $ext = pathinfo($document->sFilename, PATHINFO_EXTENSION);
        $response->setContentDisposition($this->getDisposition($document), $this->cleanFileName($document->sFilename), $this->cleanFileName($this->toAscii('document.'.$ext)));

        return $response;
    }

    protected function portalFileHandler()
    {
        // todo: legacy refactor
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $pkey = parseAccessKey($this->request->input('reqid', 0));

        if (! isset($pkey['xRequest']) || ! is_numeric($pkey['xRequest'])) {
            return $this->fileNotFound();
        }

        $request = Ticket::find($pkey['xRequest']);

        if (! $request || $request->sRequestPassword != $pkey['sRequestPassword']) {
            return $this->fileNotFound();
        }

        try {
            $document = Document::fromPortalRequest($this->request->input('id', 0), $pkey['xRequest']);
        } catch (ModelNotFoundException $e) {
            return $this->fileNotFound();
        }

        if ($document->history->fPublic == 0) {
            return $this->fileNotFound();
        }

        $file = $document->asFile($this->request->has('showfullsize'));

        $response = new BinaryFileResponse(
            $file,
            200,
            ['Content-Type' => $document->sFileMimeType]
        );

        $ext = pathinfo($document->sFilename, PATHINFO_EXTENSION);
        $response->setContentDisposition($this->getDisposition($document), $this->cleanFileName($document->sFilename), $this->cleanFileName($this->toAscii('document.'.$ext)));

        return $response;
    }

    /**
     * Used when no valid "from"
     * or invalid attempt to obtain file.
     * @return Response
     */
    protected function fileNotFound()
    {
        return new Response('File not found', 404);
    }

    /**
     * Determine which handler class method
     * to use to retrieve a file.
     * @param $from
     * @return string
     */
    protected function getHandler($from)
    {
        if ($from == self::FROM_ADMIN && ! IN_PORTAL) {
            return 'adminFileHandler';
        }

        if ($from == self::FROM_KB) {
            return 'knowledgebookFileHandler';
        }

        if ($from == self::FROM_PORTAL) {
            return 'portalFileHandler';
        }

        if ($from == self::FROM_REQUEST) {
            return 'requestFileHandler';
        }

        return 'fileNotFound';
    }

    /**
     * Set a cheery disposition header depending on file type.
     * @param $document
     * @return string
     */
    protected function getDisposition($document)
    {
        // audio and images can always be inline as long as we aren't forcing a download.
        if (($this->isAudio($document) or $document->isImage()) and ! $this->request->has('download')) {
            return 'inline';
        } elseif ($document->sFileMimeType == 'application/pdf' and $this->request->has('showfullsize') and ! $this->request->has('download')) {
            return 'inline';
        }

        return 'attachment';
    }

    /**
     * Is Audio file check.
     * @param Document $document
     * @return bool
     */
    protected function isAudio($document)
    {
        return Str::contains($document->sFileMimeType, 'audio/');
    }

    /**
     * Convert string to ASCII.
     * @link https://github.com/nette/utils/blob/master/src/Utils/Strings.php
     * @param $s
     * @return string
     */
    protected function toAscii($s)
    {
        static $transliterator = null;
        if ($transliterator === null && class_exists('Transliterator', false)) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
        }
        $s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u', '', $s);
        $s = strtr($s, '`\'"^~?', "\x01\x02\x03\x04\x05\x06");
        $s = str_replace(
            ["\xE2\x80\x9E", "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9A", "\xE2\x80\x98", "\xE2\x80\x99", "\xC2\xB0"],
            ["\x03", "\x03", "\x03", "\x02", "\x02", "\x02", "\x04"], $s
        );
        if ($transliterator !== null) {
            $s = $transliterator->transliterate($s);
        }
        if (ICONV_IMPL === 'glibc') {
            $s = str_replace(
                ["\xC2\xBB", "\xC2\xAB", "\xE2\x80\xA6", "\xE2\x84\xA2", "\xC2\xA9", "\xC2\xAE"],
                ['>>', '<<', '...', 'TM', '(c)', '(R)'], $s
            );
            $s = iconv('UTF-8', 'WINDOWS-1250//TRANSLIT//IGNORE', $s);
            $s = strtr($s, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e"
                ."\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3"
                ."\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8"
                ."\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe"
                ."\x96\xa0\x8b\x97\x9b\xa6\xad\xb7",
                'ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt- <->|-.');
            $s = preg_replace('#[^\x00-\x7F]++#', '', $s);
        } else {
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        }
        $s = str_replace(['`', "'", '"', '^', '~', '?'], '', $s);

        return strtr($s, "\x01\x02\x03\x04\x05\x06", '`\'"^~?');
    }

    protected function cleanFileName($name)
    {
        $name = str_replace('\\', '-', $name);
        $name = str_replace('%', '-', $name);
        $name = str_replace('/', '-', $name);

        return $name;
    }
}
