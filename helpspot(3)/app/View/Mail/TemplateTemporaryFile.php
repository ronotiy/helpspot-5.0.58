<?php

namespace HS\View\Mail;

class TemplateTemporaryFile
{
    protected $filePathname;
    protected $resource;

    public function __construct($template, $content = null)
    {
        $this->filePathname = static::getTmpDir() . DIRECTORY_SEPARATOR . $template . '.blade.php';
        $this->resource = fopen($this->filePathname, 'w+');

        if (! is_null($content)) {
            $this->setContent($content);
        }

        $this->registerShutdown();
    }

    public static function create($template, $content = null)
    {
        new static($template, $content);
    }

    public static function getTmpDir()
    {
        return is_writable(ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
    }

    /**
     * Get the full file path with file name
     * @return mixed
     * @throws \Exception
     */
    public function name()
    {
        return $this->filePathname;
    }

    /**
     * Get the filename without a path
     * @return string
     * @throws \Exception
     */
    public function basename()
    {
        return basename($this->name());
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
        if( is_resource($this->resource)) {
            fclose($this->resource);
        }

        @unlink($this->filePathname);

        return $this;
    }

    /**
     * Close (delete) the temporary file when the application is terminating.
     * Note that this won't run when queue jobs are complete but we,
     * currently do not build any emails within a queue job
     *
     * @return $this
     * @throws \Exception
     */
    public function registerShutdown()
    {
        app()->terminating(function() {
            $this->close();
        });

        return $this;
    }
}
