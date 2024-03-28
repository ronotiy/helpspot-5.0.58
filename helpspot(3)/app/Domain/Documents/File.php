<?php

namespace HS\Domain\Documents;

interface File
{
    /**
     * @return \SplFileInfo
     */
    public function toSpl();

    /**
     * @return string
     */
    public function getBody();
}
