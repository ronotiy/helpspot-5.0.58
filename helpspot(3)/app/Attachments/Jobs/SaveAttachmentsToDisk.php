<?php

namespace HS\Attachments\Jobs;

use HS\Attachments\Manager;
use Illuminate\Bus\Queueable;
use Illuminate\Filesystem\Filesystem;

class SaveAttachmentsToDisk
{
    use Queueable;

    /**
     * File path to save attachments.
     * @var string
     */
    private $attachmentSaveDirectory;

    /**
     * Create a new job instance.
     *
     * @param $attachmentSaveDirectory
     */
    public function __construct($attachmentSaveDirectory)
    {
        $this->attachmentSaveDirectory = $attachmentSaveDirectory;
    }

    /**
     * @param Manager $attachments
     * @param Filesystem $filesystem
     * @throws \Exception
     */
    public function handle(Manager $attachments, Filesystem $filesystem)
    {
        $saveDir = $this->attachmentSaveDirectory;

        // Ensure save location is valid
        if (! $filesystem->isDirectory($saveDir) || ! $filesystem->isWritable($saveDir)) {
            throw new \Exception('Path "'.$saveDir.'" is not writable or is not a directory.');
        }

        $attachments = $attachments->connection(null); // Get default connection

        $attachments->setAttachmentsToFileSystem($saveDir);

        $attachments->getDatabaseDocuments(function ($attachmentRows) use ($attachments, $saveDir, $filesystem) {
            foreach ($attachmentRows as $attachment) {
                // Create save sub-directory if applicable
                $datePath = $this->createDirectoryForAttachment($filesystem, $attachment, $saveDir);
                // Get/build file information
                $extension = $this->fileExtension($attachment->sFilename);
                $fileName = $this->fileName($attachment->sFilename, $extension);
                $blobFile = $attachments->getDocument($attachment->xDocumentId);
                // Save file to filesystem
                $fullFilePath = $saveDir.DIRECTORY_SEPARATOR.$datePath.DIRECTORY_SEPARATOR.$fileName;
                $saveResult = $filesystem->put($fullFilePath, $blobFile);
                if ($saveResult !== false) {
                    // Update with file location in HS_Documents table
                    $attachments->addLocation($attachment->xDocumentId, DIRECTORY_SEPARATOR.$datePath.DIRECTORY_SEPARATOR.$fileName);
                    // Delete blobFile - SqlServer will also need PDO updating :/
                    $attachments->putDocument($attachment->xDocumentId, null);
                }
            }
        });
    }

    /**
     * Create directories as needed for saving the attachment.
     * @param Filesystem $filesystem
     * @param $attachment
     * @param $baseDir
     * @return string
     */
    protected function createDirectoryForAttachment(Filesystem $filesystem, $attachment, $baseDir)
    {
        $year = date('Y', $attachment->dtGMTChange);
        $month = date('n', $attachment->dtGMTChange);
        $day = date('j', $attachment->dtGMTChange);

        $datePath = $year.DIRECTORY_SEPARATOR.$month.DIRECTORY_SEPARATOR.$day;
        $fullPath = $baseDir.DIRECTORY_SEPARATOR.$datePath;

        if (! $filesystem->exists($fullPath)) {
            $filesystem->makeDirectory($fullPath, 0755, true);
        }

        return $datePath;
    }

    /**
     * Retrieve file extension.
     * @param $fileName
     * @return string
     */
    protected function fileExtension($fileName)
    {
        $ext = explode('.', $fileName);
        $id = count($ext) - 1;

        return str_replace('/', '_', ($ext[$id] ? $ext[$id] : 'txt'));
    }

    /**
     * Create file name given original filename and extension.
     * @param $filename
     * @param $extension
     * @return string
     */
    protected function fileName($filename, $extension)
    {
        return md5($filename.uniqid('helpspot')).'.'.$extension;
    }
}
