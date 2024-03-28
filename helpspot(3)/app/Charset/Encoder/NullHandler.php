<?php

namespace HS\Charset\Encoder;

class NullHandler implements HandlerInterface
{
    public function encode($string, $to, $from)
    {
        // No encoding done, just pass string
        return $string;
    }
}
