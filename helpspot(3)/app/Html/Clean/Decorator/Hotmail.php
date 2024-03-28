<?php

namespace HS\Html\Clean\Decorator;

class Hotmail extends AbstractCleanerDecorator
{
    /**
     * Hotmail in some rare cases sends through this odd tag "<?xml: ...", so we
     * need to replace it here or else HTML_Safe strips the entire
     * email body. See bug: 1053.
     *
     * @param  string  $input    Raw email HTML
     * @param  bool $stripImg Strip images
     * @param  bool $skipTidy Skip HTML Tidy
     * @return string            XML-Stripped string
     */
    public function clean($input, $stripImg = false)
    {
        $stripped = str_replace('<?xml:', '<', $input);

        return $this->wrappedCleaner->clean($stripped, $stripImg);
    }
}
