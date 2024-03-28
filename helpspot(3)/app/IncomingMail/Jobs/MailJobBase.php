<?php

namespace HS\IncomingMail\Jobs;

class MailJobBase
{
    /**
     * Load the world.
     */
    protected function loadTheWorld()
    {
        ob_start();

        /*****************************************
        INCLUDE PATH
         *****************************************/
        set_include_path(cBASEPATH.'/helpspot/pear');

        /*****************************************
        INCLUDE LIBS
         *****************************************/
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        require_once cBASEPATH.'/helpspot/lib/platforms.lib.php';
        require_once cBASEPATH.'/helpspot/pear/Mail/helpspot_mimeDecode.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/display.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.notify.php';
        require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';
        require_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
        require_once cBASEPATH.'/helpspot/lib/class.mail.rule.php';
        require_once cBASEPATH.'/helpspot/pear/Console/Getopt.php';
        require_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
        require_once cBASEPATH.'/helpspot/lib/class.language.php';
        require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        require_once cBASEPATH.'/helpspot/lib/class.license.php';
        require_once cBASEPATH.'/helpspot/lib/lookup.lib.php';	//include lookups here so we can use lang abstraction
        ob_clean();
    }
}
