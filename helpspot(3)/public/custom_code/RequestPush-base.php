<?php
/*
Request Push API information can be found at:
https://support.helpspot.com/index.php?pg=kb.chapter&id=27
*/

// SECURITY: This prevents this script from being called from outside the context of HelpSpot
if (! defined('cBASEPATH')) {
    die();
}

class RequestPush_base
{
    public $errorMsg = '';

    public function push($request)
    {
        /* Perform actions with request data here */

        /*
        return "Unique ID"; //Optionally return a unique ID to later retrieve updates
        */
    }

    public function details($id)
    {
        /* Retrieve update about push using $id here */

        /*
        return "HTML"; //Return HTML to be displayed within HelpSpot
        */
    }
}
