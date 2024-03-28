<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class language
{
    public function __construct($page = false, $language = false)
    {
        $language = hs_setting('cHD_LANG', 'english-us');

        // Set PHP's internal default character set
        ini_set('default_charset', 'UTF-8');

        //Load base language files
        require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.charset.php';
        require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.general.php';

        //Load the current page
        $currentPage = cBASEPATH.'/helpspot/lang/'.$language.'/lg.pg.'.$page.'.php';
        if ($page && file_exists($currentPage)) {
            require_once $currentPage;
        }

        //Load portal if we're in it
        if (hs_setting('IN_PORTAL', false)) {
            require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.portal.php';
        }
    }

    //Load other pages language files
    public function load($pages = false)
    {
        $language = hs_setting('cHD_LANG', 'english-us');

        if (is_array($pages)) {
            foreach ($pages as $k=>$pg) {
                require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.pg.'.$pg.'.php';
            }
        } else {
            require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.pg.'.$pages.'.php';
        }

        return $this;
    }
}
