<?php

namespace HS\Http\Controllers;

use language;
use usLicense;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WidgetController extends Controller
{
    /**
     * Show the widget tab and handle form submission
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $this->enviro();
        $response = require_once cBASEPATH.'/tab.php';

        if ($response instanceof Response) {
            return $response;
        }

        return response($response);
    }

    /**
     * Replaces public/widgets/enviro.php
     */
    protected function enviro()
    {
        set_include_path(cBASEPATH.'/helpspot/pear');

        include_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        include_once cBASEPATH.'/helpspot/lib/util.lib.php';
        include_once cBASEPATH.'/helpspot/lib/error.lib.php';
        include_once cBASEPATH.'/helpspot/lib/platforms.lib.php';
        include_once cBASEPATH.'/helpspot/lib/display.lib.php';
        include_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
        include_once cBASEPATH.'/helpspot/lib/api.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.notify.php';
        include_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
        include_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';

        include_once cBASEPATH.'/helpspot/pear/Serializer.php';
        include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        include_once cBASEPATH.'/helpspot/lib/api.forums.lib.php';
        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
        include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.language.php';
        include_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        include_once cBASEPATH.'/helpspot/lib/class.license.php';

        if (!defined('IN_PORTAL')) {
            define('IN_PORTAL', true);
        }

        $licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $GLOBALS['license'] = $licenseObj->getLicense();
        $GLOBALS['lang'] = new language('widget');
        require_once cBASEPATH.'/helpspot/lib/lookup.lib.php';
    }
}
