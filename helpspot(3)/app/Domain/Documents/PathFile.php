<?php

namespace HS\Domain\Documents;

class PathFile implements File
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * PathFile constructor.
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return file_get_contents(hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$this->filePath);
    }

    /**
     * @return \SplFileInfo
     */
    public function toSpl()
    {
        return new \SplFileInfo(hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$this->filePath);
    }
}
