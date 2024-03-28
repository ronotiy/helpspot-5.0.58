<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class person_status
{
    //Timeout for collision
    public $timeout = '';

    //Constructor
    public function __construct()
    {

        // Don't return results for people on a page more than 6 hours. We'll assume they've left with the page open.
        $this->timeout = (time() - 21600);
    }

    public function update_status($user, $page, $xrequest = 0, $ftype = 0, $details = '')
    {
        if (isset($user['sPage']) && ! hs_empty($user['sPage'])) { //update existing record
            $GLOBALS['DB']->Execute('UPDATE HS_Person_Status SET xRequest=?,dtGMTEntered=?,sPage=?,fType=?,sDetails=? WHERE xPersonStatus = ?',
                                          [$xrequest,
                                          time(),
                                          hs_truncate($page, 255),
                                          $ftype,
                                          hs_truncate($details, 255),
                                          $user['xPerson'], ]);
        } else { //build new record
            $GLOBALS['DB']->Execute('INSERT INTO HS_Person_Status(xPersonStatus,xRequest,dtGMTEntered,sPage,fType,sDetails) VALUES (?,?,?,?,?,?)',
                                           [$user['xPerson'],
                                           $xrequest,
                                           time(),
                                           hs_truncate($page, 255),
                                           $ftype,
                                           hs_truncate($details, 255), ]);
        }
    }

    public function update_status_details($xperson, $spage, $ftype, $details)
    {
        $GLOBALS['DB']->Execute('UPDATE HS_Person_Status SET fType=?,sDetails=? WHERE xPersonStatus = ? AND sPage = ?', [$ftype, $details, $xperson, $spage]);
    }

    public function get_all_viewing()
    {
        $res = $GLOBALS['DB']->Execute('SELECT * FROM HS_Person_Status WHERE HS_Person_Status.xRequest > 0 AND HS_Person_Status.dtGMTEntered > ?', [$this->timeout]);

        return rsToArray($res, 'xRequest', false);
    }

    public function remove_status($xperson)
    {
        $GLOBALS['DB']->Execute('DELETE FROM HS_Person_Status WHERE xPersonStatus = ?', [$xperson]);
    }

    // Return all people on the requests page other than the person who's on it currently
    public function get_request_page($reqid, $xperson)
    {
        $status = $GLOBALS['DB']->Execute('SELECT * FROM HS_Person_Status, HS_Person WHERE HS_Person_Status.xPersonStatus = HS_Person.xPerson AND HS_Person_Status.xRequest = ? AND HS_Person_Status.xPersonStatus <> ? AND HS_Person_Status.dtGMTEntered > ? ORDER BY sDetails ASC', [$reqid, $xperson, $this->timeout]);
        return rsToArray($status, 'xPersonStatus', false);
    }
}
