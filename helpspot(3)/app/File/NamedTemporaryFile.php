<?php

namespace HS\File;

use Symfony\Component\Finder\SplFileInfo;

class NamedTemporaryFile extends SplFileInfo
{
    protected $resource;

    public function __construct($content = null)
    {
        $tmpdir = is_writable(ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : sys_get_temp_dir();

        $tmpFile = tempnam($tmpdir, 'hs-'); // Prefix required in Windows

        $this->resource = fopen($tmpFile, 'r+');

        if (! is_null($content)) {
            $this->setContent($content);
        }
    }

    /**
     * Get the filename.
     * @return mixed
     * @throws \Exception
     */
    public function name()
    {
        $meta = $this->meta();

        if (isset($meta['uri']) && ! empty($meta['uri'])) {
            return $meta['uri'];
        }

        throw new \Exception('No tmp file path found');
    }

    /**
     * Set tmp file content.
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $string = (gettype($content) === 'resource') ? stream_get_contents($content) : $content;

        fwrite($this->resource, $string);
        fseek($this->resource, 0);

        return $this;
    }

    /**
     * Return file as SplFileInfo object, compatible
     * for use with Symfony\Component\HttpFoundation\BinaryFileResponse.
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function toSpl()
    {
        return new \SplFileInfo($this->name());
    }

    /**
     * Close & delete the temporary file.
     * @return $this
     */
    public function close()
    {
        $file = $this->name();

        fclose($this->resource);
        unlink($file);

        return $this;
    }

    /**
     * Get file meta, namely 'uri',
     * which returns the local file path.
     * @return array
     */
    protected function meta()
    {
        return stream_get_meta_data($this->resource);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Add the NamedTemporaryFile into the app container so we keep
     * a reference to it until the end of the request.
     *
     * Otherwise PHP's garbage collector may destroys the object
     * before the BinaryFileResponse can get the content
     *
     * This is as dirty as it looks
     *
     * @return $this
     */
    public function persist()
    {
        app()[$this->name()] = $this;

        return $this;
    }
}
