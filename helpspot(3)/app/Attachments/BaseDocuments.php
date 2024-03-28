<?php

namespace HS\Attachments;

use Facades\HS\Cache\Manager;
use Illuminate\Database\Connection;

abstract class BaseDocuments implements Documents
{
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $conn;

    /**
     * @var bool
     */
    protected $isSqlSrv = false;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Retrieve Document Binary File.
     * @param $xDocumentId
     * @return mixed
     */
    public function getDocument($xDocumentId)
    {
        return $this->getBinary('HS_Documents', 'blobFile', 'xDocumentId', $xDocumentId);
    }

    /**
     * Retrieve Staff User Image Binary File.
     * @param $xPersonPhotoId
     * @return mixed
     */
    public function getPhoto($xPersonPhotoId)
    {
        return $this->getBinary('HS_PersonPhotos', 'blobPhoto', 'xPersonPhotoId', $xPersonPhotoId);
    }

    /**
     * Retrieve Knowledge Book Binary File.
     * @param $xDocumentId
     * @return mixed
     */
    public function getBookFile($xDocumentId)
    {
        return $this->getBinary('HS_KB_Documents', 'blobFile', 'xDocumentId', $xDocumentId);
    }

    /**
     * Retrieve a single binary from the database.
     * @param $table
     * @param $column
     * @param $identifier
     * @param $id
     * @return mixed
     */
    public function getBinary($table, $column, $identifier, $id)
    {
        return $this->conn->table($table)
            ->select($column)
            ->where($identifier, $id)
            ->first()
            ->$column; // Return the binary directly
    }

    /**
     * Save a binary Document.
     * @param $xDocumentId
     * @param $binary
     * @return mixed
     */
    public function putDocument($xDocumentId, $binary)
    {
        return $this->putBinary('HS_Documents', 'blobFile', 'xDocumentId', $xDocumentId, $binary);
    }

    /**
     * Save a binary staff photo.
     * @param $xPersonPhotoId
     * @param $binary
     * @internal param $binary
     * @return mixed
     */
    public function putPhoto($xPersonPhotoId, $binary)
    {
        return $this->putBinary('HS_PersonPhotos', 'blobPhoto', 'xPersonPhotoId', $xPersonPhotoId, $binary);
    }

    /**
     * Save a binary Knowledge Book file.
     * @param $xDocumentId
     * @param $binary
     * @return mixed
     */
    public function putBookFile($xDocumentId, $binary)
    {
        return $this->putBinary('HS_KB_Documents', 'blobFile', 'xDocumentId', $xDocumentId, $binary);
    }

    /**
     * Save a binary to the database.
     * @param $table
     * @param $column
     * @param $identifier
     * @param $id
     * @param $binary
     * @return mixed
     */
    public function putBinary($table, $column, $identifier, $id, $binary)
    {
        return $this->conn->table($table)
            ->where($identifier, $id)
            ->update([$column => $binary]);
    }

    /**
     * Get the PDO object.
     * @return \PDO
     */
    protected function pdo()
    {
        return $this->conn->getPdo();
    }

    /**
     * Test if we're dealing with SqlSrv,
     * the special case in our queries.
     * @return bool
     */
    protected function isSqlSrv()
    {
        return $this->conn->getDriverName() === 'sqlsrv';
    }

    /**
     * Get documents with binaries saved in the database.
     * @param callable $eachChunk
     * @return bool
     */
    public function getDatabaseDocuments(callable $eachChunk)
    {
        return $this->conn->table('HS_Documents')
            ->select(['HS_Request_History.dtGMTChange', 'HS_Documents.xDocumentId', 'HS_Documents.sFilename', 'HS_Documents.sFileLocation'])
            ->join('HS_Request_History', 'HS_Documents.xRequestHistory', '=', 'HS_Request_History.xRequestHistory')
            ->whereNull('HS_Documents.sFileLocation')

            // ignore files saved as responses for now, as it needs `HS_Request_History.dtGMTChange`
            // to create the dir structure on the file system
            // See HS\Attachments\Jobs\SaveAttachmentsToDisk::createDirectoryForAttachment()
            ->whereNotNull('HS_Documents.xRequestHistory')

            ->orderBy('HS_Documents.xDocumentId', 'asc')
            ->chunk(5000, $eachChunk);
    }

    /**
     * Set system to use file system for attachments
     * Assumes settings exist already.
     * @param string $filePath
     * @return void
     */
    public function setAttachmentsToFileSystem($filePath)
    {
        $this->conn->table('HS_Settings')
            ->where(['sSetting' => 'cHD_ATTACHMENT_LOCATION'])
            ->update(['tValue' => 'file']);

        $this->conn->table('HS_Settings')
            ->where(['sSetting' => 'cHD_ATTACHMENT_LOCATION_PATH'])
            ->update(['tValue' => $filePath]);

        Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
    }

    /**
     * Add a filesystem location of a document.
     * @param $xDocumentId
     * @param $attachmentPath
     * @return mixed
     */
    public function addLocation($xDocumentId, $attachmentPath)
    {
        return $this->conn->table('HS_Documents')
            ->where('xDocumentId', $xDocumentId)
            ->update(['sFileLocation' => $attachmentPath]);
    }
}
