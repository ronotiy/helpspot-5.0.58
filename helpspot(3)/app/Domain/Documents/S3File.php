<?php

namespace HS\Domain\Documents;

use Aws\S3\S3Client;
use HS\File\NamedTemporaryFile;
use Illuminate\Support\Facades\Log;

class S3File implements File
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $parsedPath;

    /**
     * PathFile constructor.
     *
     * @param string $s3Path
     * @throws \Exception
     */
    public function __construct($s3Path)
    {
        $this->path = $s3Path;
        $this->parsedPath = $this->parseS3Uri($this->path);
    }

    /**
     * Get the object from the bucket.
     *
     * @return \Aws\Result
     */
    protected function getObject()
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);

        try {
            return $s3->getObject([
                'Bucket' => $this->parsedPath['bucket'],
                'Key'    => $this->parsedPath['key'],
            ]);
        } catch(\Exception $e) {
            Log::error($e);
            return ['Body' => null];
        }
    }

    /**
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function toSpl()
    {
        $object = $this->getObject();

        return (new NamedTemporaryFile($object['Body']) )->persist()->toSpl();
    }

    /**
     * @return string
     */
    public function getBody()
    {
        $object = $this->getObject();

        return $object['Body'];
    }

    /**
     * Delete an object from the bucket.
     *
     * @return \Aws\Result
     */
    public function delete()
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
        ]);

        try {
            return $s3->deleteObject([
                'Bucket' => $this->parsedPath['bucket'],
                'Key'    => $this->parsedPath['key'],
            ]);
        } catch(\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * Parse URI in format s3://bucket-name/path/to/file.ext.
     * @param string $uri
     * @return array
     * @throws \Exception
     */
    public function parseS3Uri($uri)
    {
        if (strpos($uri, 's3://') !== 0) {
            throw new \Exception("S3 uri '$uri' must begin with 's3://'");
        }

        $uri = str_replace('s3://', '', $uri);

        $parts = explode('/', $uri);

        return [
            'bucket' => $parts[0],
            'key' => implode('/', array_slice($parts, 1)),
        ];
    }
}
