<?php

namespace HS\Charset;

class Converter
{
    /**
     * Some emails have a weird charset and this converts them into something usable.
     *
     * See:
     * - http://php.net/manual/en/mbstring.supported-encodings.php
     * - http://stackoverflow.com/questions/13798461/how-to-normalize-encoding-names-like-ks-c-5601-1987-to-cp949
     * - https://github.com/mikel/mail/issues/436
     *
     * @param $charset
     * @return string
     */
    public function convert($charset)
    {
        if (strtolower(trim($charset)) == 'ks_c_5601-1987') { // Korean
            return 'CP949';
        }

        return $charset;
    }
}
