<?php

namespace HS\Domain\Workspace\Jobs;

class DeleteRequest
{
    //The HS license file.
    public $license;

    //The request ID to delete
    public $id;

    public function __construct($id)
    {
        $this->id = $id;

        ob_start();
        // Boilerplate for HelpSpot internal API
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        include_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.license.php';
        require_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
        require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        require_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        require_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
        ob_clean();

        //Get License
        $licenseObj = new \usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $this->license = $licenseObj->getLicense();
    }

    public function run()
    {
        $request = apiGetRequest($this->id);

        if ($request) {
            apiDeleteRequest($this->id);

            return true;
        } else {
            return false;
        }
    }
}
