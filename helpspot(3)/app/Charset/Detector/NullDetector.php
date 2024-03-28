<?php

namespace HS\Charset\Detector;

class NullDetector implements DetectorInterface
{
    public function isEncoded($encoding, $string)
    {
    }
}
