<?php

namespace HS\IncomingMail\Parsers;

use HS\Charset\Converter;

class BaseParser
{
    /**
     * Convert any strange charsets
     * See bug #652, and do try to not re-introduce this bug someday.
     *
     * @param $charset
     * @return string
     */
    public function convertCharset($charset)
    {
        return (new Converter())->convert($charset);
    }

    /**
     * Parse the text attachments.
     * @param $body
     * @param $charset
     * @return string
     */
    public function textAttachment($body, $charset)
    {
        //Convert to HS charset and store
        if (empty($charset) || $charset == 'default') {
            return hs_check_charset_and_convert($body, 'UTF-8');
        } else {
            return hs_charset_convert($charset, 'UTF-8', $body);
        }
    }

    /**
     * * Parse the html attachments.
     * @param $body
     * @param $charset
     * @return string
     */
    protected function htmlAttachment($body, $charset)
    {
        if (empty($charset) || $charset == 'default') {
            return hs_check_charset_and_convert($body, 'UTF-8');
        } else {
            return hs_charset_convert($charset, 'UTF-8', $body);
        }
    }
}
