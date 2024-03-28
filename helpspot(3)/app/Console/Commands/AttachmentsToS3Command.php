<?php

namespace HS\Console\Commands;

use Aws\S3\S3Client;
use HS\Domain\Workspace\Document;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttachmentsToS3Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:tos3 {bucket : S3 Bucket Name} {--path= : Attachment Path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save file-based attachments to S3 based on age.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = ($this->option('path') && is_dir($this->option('path')))
            ? $this->option('path')
            : hs_setting('cHD_ATTACHMENT_LOCATION_PATH', storage_path('documents'));


        $fileDocs = $this->getFileDocuments();
        $s3 = $this->getS3();

        $updated = [];
        $notFound = [];
        $count = 0;
        foreach ($fileDocs as $file) {
            // There may be duplicates here due to the
            // relationship of documents and imperfect creation of
            // extra HS_Request_History rows
            if (in_array($file->xDocumentId, $updated)) {
                continue;
            }

            if (! file_exists($path.'/'.$file->sFileLocation)) {
                $notFound[] = $file;

                continue;
            }

            // Append "s3://bucket" to file path
            $result = $s3->putObject([
                'Bucket' => $this->argument('bucket'),
                'Key' => substr($file->sFileLocation, 1, strlen($file->sFileLocation)), // Strip leading slash
                'SourceFile' => $path.'/'.$file->sFileLocation,
            ]);

            // $result['ObjectURL']
            Document::where('xDocumentId', $file->xDocumentId)->update([
                    'sFileLocation' => 's3://'.$this->argument('bucket').$file->sFileLocation, ]
            );

            // Track document's we've updated already
            $updated[] = $file->xDocumentId;

            // Delete the file when done uploading
            @unlink($path.'/'.$file->sFileLocation);

            $count++;

            usleep(500000); // sleep .5 seconds to reduce hitting API rate limit
        }

        foreach ($notFound as $missing) {
            $this->error(
                sprintf("Can't find file '%s' on local file system", $path.'/'.$missing->sFileLocation)
            );
        }

        if (config('app.debug') && function_exists('xdebug_peak_memory_usage')) {
            $this->info("\nMemory Usage: ".round(xdebug_peak_memory_usage() / 1048576, 2).'MB');
        }
        $this->info($count . ' attachments out of ' . count($fileDocs) . ' found documents were successfully moved from the file system to S3.');
    }

    /**
     * Get documents with files saved to the disk.
     * @return Collection
     */
    protected function getFileDocuments()
    {
        return DB::table('HS_Documents')
            ->select(['xDocumentId', 'sFilename', 'sFileLocation'])
            ->whereNotNull('sFileLocation')
            ->where('sFileLocation', 'NOT LIKE', 's3://%')
            ->get();
    }

    /**
     * @return S3Client
     */
    public function getS3()
    {
        return new S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
        ]);
    }
}
