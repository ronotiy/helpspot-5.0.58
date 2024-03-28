<?php

namespace HS\Attachments;

interface Documents
{
    /**
     * Retrieve Document Binary File.
     * @param $xDocumentId
     * @return mixed
     */
    public function getDocument($xDocumentId);

    /**
     * Retrieve Staff User Image Binary File.
     * @param $xPersonPhotoId
     * @return mixed
     */
    public function getPhoto($xPersonPhotoId);

    /**
     * Retrieve Knowledge Book Binary File.
     * @param $xDocumentId
     * @return mixed
     */
    public function getBookFile($xDocumentId);

    /**
     * Retrieve a single binary from the database.
     * @param $table
     * @param $column
     * @param $identifier
     * @param $id
     * @return mixed
     */
    public function getBinary($table, $column, $identifier, $id);

    /**
     * Save a binary Document.
     * @param $xDocumentId
     * @param $binary
     * @return mixed
     */
    public function putDocument($xDocumentId, $binary);

    /**
     * Save a binary staff photo.
     * @param $xPersonPhotoId
     * @param $binary
     * @internal param $binary
     * @return mixed
     */
    public function putPhoto($xPersonPhotoId, $binary);

    /**
     * Save a binary Knowledge Book file.
     * @param $xDocumentId
     * @param $binary
     * @return mixed
     */
    public function putBookFile($xDocumentId, $binary);

    /**
     * Save a binary to the database.
     * @param $table
     * @param $column
     * @param $identifier
     * @param $id
     * @param $binary
     * @return mixed
     */
    public function putBinary($table, $column, $identifier, $id, $binary);

    /**
     * Get documents with binaries saved in the database.
     * @param callable $eachChunk
     * @return array
     */
    public function getDatabaseDocuments(callable $eachChunk);

    /**
     * Set system to use file system for attachments
     * Assumes settings exist already.
     * @param string $filePath
     * @return void
     */
    public function setAttachmentsToFileSystem($filePath);

    /**
     * Add a filesystem location of a document.
     * @param $xDocumentId
     * @param $attachmentPath
     * @return mixed
     */
    public function addLocation($xDocumentId, $attachmentPath);
}
