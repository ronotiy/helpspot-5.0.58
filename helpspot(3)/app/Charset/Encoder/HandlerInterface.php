<?php

namespace HS\Charset\Encoder;

interface HandlerInterface
{
    public function encode($string, $to, $from);
}
