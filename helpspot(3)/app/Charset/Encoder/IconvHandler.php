<?php

namespace HS\Charset\Encoder;

class IconvHandler implements HandlerInterface
{
    public function encode($string, $to, $from)
    {
        return iconv($from, $to.'//IGNORE', $string);
    }
}
