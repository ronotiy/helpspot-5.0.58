<?php

namespace HS\Charset\Encoder;

class MbHandler implements HandlerInterface
{
    public function encode($string, $to, $from)
    {
        return mb_convert_encoding($string, $to, $from);
    }
}
