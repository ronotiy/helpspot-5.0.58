<?php

namespace HS\Charset\Detector;

class MbDetector implements DetectorInterface
{
    public function isEncoded($encoding, $string)
    {
        return mb_check_encoding($string, $encoding);
    }
}
