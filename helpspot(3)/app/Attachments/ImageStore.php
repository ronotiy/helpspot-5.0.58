<?php

namespace HS\Attachments;

use Illuminate\Support\Facades\DB;

class ImageStore
{
    /**
     * @param $file
     * @return string
     */
    public function save($file)
    {
        return $this->saveForRequest($file);
    }

    /**
     * @param $file
     * @return string
     */
    protected function saveForRequest($file)
    {
        if (hs_setting('cHD_ATTACHMENT_LOCATION') == 'file') {
            return $this->saveToFile($file);
        } else {
            return $this->saveToDb($file);
        }
    }

    /**
     * @param $pageid
     * @param $file
     * @return string
     */
    public function saveForKb($pageid, $file)
    {
        return DB::transaction(function() use($pageid, $file) {
            $body = file_get_contents($file['tmp_name']);

            $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Documents(xPage,fDownload,sFilename,sFileMimeType) VALUES (?,?,?,?)',
                [
                    $pageid,
                    0, //this is not a download
                    $file['name'],
                    $file['type'],
                ]
            );

            $id = dbLastInsertID('HS_KB_Documents', 'xDocumentId');
            $GLOBALS['DB']->UpdateBlob('HS_KB_Documents', 'blobFile', $body, ' xDocumentId = '.$id);

            return '/index.php?pg=file&from=2&id='.$id;
        });
    }

    /**
     * @param $file
     * @return string
     */
    protected function saveToFile($file)
    {
        $directory = $this->makeDirectory();

        return $this->storeToDisk($file, $directory);
    }

    /**
     * @param $file
     * @return string
     */
    protected function saveToDB($file)
    {
        $sCID = (isset($file['content-id']) ? ltrim(rtrim($file['content-id'], '>'), '<') : '');
        $docadd = $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sCID) VALUES(?,?,?)', [$file['name'], $file['mimetype'], $sCID]);
        if ($docadd) { //if initial insert OK then do blob
            $docid  = dbLastInsertID('HS_Documents', 'xDocumentId');
            $docadd = $GLOBALS['DB']->UpdateBlob('HS_Documents', 'blobFile', $file['body'], ' xDocumentId = ' . $docid);
            return 'admin.php?pg=file&from=0&id='.$docid;
        } else {
            errorLog('Initial Document Insert Failed' . $GLOBALS['DB']->ErrorMsg(), 'Database');
        }
    }

    /**
     * @param $file
     * @param $directory
     * @return string
     */
    protected function storeToDisk($file, $directory)
    {
        $extension = $this->getExtension($file['type']);
        $file_path = $directory.'/'.md5($file['name'].uniqid('helpspot')).'.'.$extension;
        $relPath = str_replace(hs_setting('cHD_ATTACHMENT_LOCATION_PATH'), '', $file_path);

        // Try and write files to disk
        move_uploaded_file($file['tmp_name'], $file_path);
        $GLOBALS['DB']->Execute('INSERT INTO HS_Documents(sFilename,sFileMimeType,sFileLocation,sCID) VALUES(?,?,?,?)',
            [$file['name'], $file['type'], $relPath, $this->getCid($file)]);

        $id = dbLastInsertID('HS_Documents', 'xDocumentId');

        return 'admin?pg=file&from=0&id='.$id;
    }

    /**
     * Generate a CID from the file
     *
     * @param array $file
     * @return string
     */
    protected function getCid($file)
    {
        return md5(cHOST.$file['name'].time()).'@'.parse_url(cHOST, PHP_URL_HOST);
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

    /**
     * @return string
     */
    protected function makeDirectory()
    {
        $year = date('Y');
        $month = date('n');
        $day = date('j');
        $yr_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year;
        $mo_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month;
        $dy_path = hs_setting('cHD_ATTACHMENT_LOCATION_PATH').'/'.$year.'/'.$month.'/'.$day;

        // Create path to directory location if it doesn't exist
        // Set writable perms (hs_chmod). Sometimes the dir is built by root when running under cron, other times by the Apache user if done via file upload. Esp when root builds it the system is then unable to
        // write uploaded files to the dir's because the apache user doesn't have access.
        if (is_dir($dy_path)) {
            return $dy_path;
        }

        if (! is_dir($yr_path)) {
            @mkdir($yr_path);
            hs_chmod($yr_path, 0777);
        } //make year folder
        if (! is_dir($mo_path)) {
            @mkdir($mo_path);
            hs_chmod($mo_path, 0777);
        } //make month folder
        @mkdir($dy_path); // make day folder
        hs_chmod($dy_path, 0777);

        return $dy_path;
    }
}
