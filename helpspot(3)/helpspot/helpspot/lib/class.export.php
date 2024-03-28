<?php

class Export
{
    protected $path;
    protected $email;
    public $zipFile;

    /**
     * Export constructor.
     * @param $email
     */
    public function __construct($email)
    {
        $this->path = 'export/'.$email;
        $this->email = $email;
        $this->zipFile = storage_path('export/'.$this->email.'.zip');
        $this->createFolder();
    }

    /**
     * Export all requests by an email
     * @return mixed
     */
    public function requestsByEmail()
    {
        $reqs = $this->findByEmail($this->email);
        $out = [];
        while($req = $reqs->FetchRow()) {
            $req = apiGetRequest($req['xRequest']);
            $request = $this->getHistory($req['xRequest']);
            $historyItem = [];
            foreach ($request->history as $note) {
                if ($note->documents) {
                    foreach ($note->documents as $document) {
                        $doc = $document->toArray();
                        $this->moveImage($doc);
                    }
                }
                $historyItem[] = $note->toArray();
            }
            $out[$req['xRequest']] = $req;
            $out[$req['xRequest']]['xRequestHistory'] = $historyItem;
        }
        $this->writeJson($out);
        $this->zipFolder();
        $this->cleanup();
        return $this->zipFile;
    }

    /**
     * Create the storage folders to hold everything
     */
    protected function createFolder()
    {
        mkdir(storage_path('export'));
        mkdir(storage_path($this->path));
    }

    /**
     * @param $item
     * @return bool
     */
    protected function moveImage($item)
    {
        $document = \HS\Domain\Workspace\Document::fromAdminRequest($item['xDocumentId']);
        $file = $document->asFile(1);
        return copy($file->getPathname(), storage_path($this->path.'/'.$file->getFilename()));
    }

    /**
     * @param $content
     * @return bool
     */
    public function writeJson($content)
    {
        $json = json_encode($content, JSON_PRETTY_PRINT);

        $filename = storage_path($this->path.'/export.json');

        if (! $handle = fopen($filename, "w")) {
            echo "Cannot open file ($filename)";
            exit;
        }

        // Write out the json
        $this->fwrite_stream($handle, $json);

        if (fwrite($handle, $json) === FALSE) {
            echo "Cannot write to file ($filename)";
            exit;
        }

        fclose($handle);

        return true;
    }

    /**
     * @param $fp
     * @param $string
     * @return bool|int
     */
    protected function fwrite_stream($fp, $string) {
        for ($written = 0; $written < mb_strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, mb_substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }

    /**
     * Find all requests by email.
     *
     * @param $email
     * @return bool
     */
    protected function findByEmail($email)
    {
        $reqs = $GLOBALS['DB']->Execute('SELECT * FROM HS_Request WHERE sEmail = ? ORDER BY xRequest DESC', [$email]);
        if($reqs == false) {
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database',__FILE__,__LINE__);
            return false;
        } else {
            return $reqs;
        }
    }

    /**
     * @param $reqid
     * @return bool
     */
    protected function getHistory($reqid)
    {
        $ticket = \HS\Domain\Workspace\Request::with('history.documents')->find($reqid);
        
        if (! $ticket) {
            return false;
        }

        return $ticket;
    }

    /**
     * Zip the export folder
     */
    protected function zipFolder()
    {
        // Get real path for our folder
        $rootPath = storage_path($this->path);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($this->zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
    }

    /**
     * Remove export files/folders
     */
    public function cleanup()
    {
        $path = storage_path($this->path);
        array_map('unlink', glob("$path/*"));
        rmdir($path);
    }
}
