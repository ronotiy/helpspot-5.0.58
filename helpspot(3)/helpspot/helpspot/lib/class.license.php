<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class usLicense
{
    //License string
    public $license;

    //Customer ID
    public $usCustomerID;

    //Secret Key
    public $sskey;

    //Constructor
    public function __construct($custid, $licstring, $key)
    {
        $this->usCustomerID = trim($custid);
        $this->license = $licstring;
        $this->sskey = $key;
        $this->sskey .= $this->usCustomerID;
        $this->sskey = md5($this->sskey); //hash again now with customer id
    }

    public function getLicense()
    {
        $lics = base64_decode($this->license);

        $rc4 = new Crypt_RC4;
        $rc4->setKey($this->sskey);		//uses constant sskey
        $rc4->decrypt($lics);

        $licensearray = @hs_unserialize($lics);

        if (is_array($licensearray)) {
            //Check customer id inside license, though it should be the same
            if (trim($licensearray['CustomerID']) == trim($this->usCustomerID)) {
                return $licensearray;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
