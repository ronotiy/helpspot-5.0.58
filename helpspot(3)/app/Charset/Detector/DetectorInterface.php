<?php

namespace HS\Charset\Detector;

/**
 * Test if text is compatible with
 * given encoding.
 *
 * @param string    Encoding, such as UTF-8
 * @param string    String, the text to be tested
 * @return bool
 */
interface DetectorInterface
{
    public function isEncoded($encoding, $string);
}
