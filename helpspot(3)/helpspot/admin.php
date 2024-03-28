<?php

/*
*	This is the primary page which all other pages flow through.
*	Copyright 2004 - UserScape Software
*
*/
/*****************************************
PHP GLOBAL SETTINGS
*****************************************/
ob_start();
/*
if( (!isset($_GET['pg']) || !in_array($_GET['pg'], array('graph','graph_pareto','file','file.staffphoto','excel','excel_filter','ajax_gateway'))) && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && extension_loaded('zlib') && !ini_get('zlib.output_compresasion') && function_exists('ob_gzhandler')){
    $encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));

    if(in_array('gzip', $encodings) || in_array('x-gzip', $encodings)){
        ob_start("ob_gzhandler");
        header("Content-Encoding: " . (in_array('x-gzip', $encodings) ? "x-gzip" : "gzip") );
    }else{
        ob_start();
    }
}else{
    ob_start();
}
*/

/*****************************************
CHECK FOR DB INFO FILE
*****************************************/
// TODO: We load .env in bootstrap, this check should be moved there
if (! file_exists(cBASEPATH.'/../.env')) {
    header('Location: '.cHOST.'/install', true, 301);
    exit();
}

/*****************************************
INCLUDE PATH
*****************************************/
set_include_path(cBASEPATH.'/helpspot/pear');

/*****************************************
HANDLE CUSTOM PAGES
*****************************************/
if (isset($_GET['pg']) && $_GET['pg'] == 'custompg' && isset($_GET['file'])) {
    $fileName = 'custom_pages/'.utf8_trim(clean_filename($_GET['file'])).'.php';
    if (file_exists($fileName)) {
        ob_start();
        include $fileName;
        $custom_pagebody = ob_get_clean();
    } else {
        exit('Path not allowed');
    }
}

/*****************************************
INCLUDES
*****************************************/
include_once cBASEPATH.'/helpspot/lib/error.lib.php';
include_once cBASEPATH.'/helpspot/lib/platforms.lib.php';
include_once cBASEPATH.'/helpspot/lib/display.lib.php';
include_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
include_once cBASEPATH.'/helpspot/lib/api.lib.php';
include_once cBASEPATH.'/helpspot/lib/class.notify.php';
include_once cBASEPATH.'/helpspot/lib/class.userscape.bayesian.classifier.php';
include_once cBASEPATH.'/helpspot/lib/class.license.php';
include_once cBASEPATH.'/helpspot/lib/class.array2recordset.php';
include_once cBASEPATH.'/helpspot/pear/Net/UserAgent/Detect.php';
include_once cBASEPATH.'/helpspot/lib/class.filter.php';
include_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
include_once cBASEPATH.'/helpspot/lib/class.triggers.php';
include_once cBASEPATH.'/helpspot/lib/class.person.status.php';
include_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
include_once cBASEPATH.'/helpspot/lib/class.language.php';
include_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';

/*****************************************
CLEAN EXTERNAL DATA
*****************************************/
clean_data();

//Not in portal
define('IN_PORTAL', false);

//Get License
$licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
$GLOBALS['license'] = $licenseObj->getLicense();

if(! $GLOBALS['license']) {
    return view('utility.support_expired');
}

//Do not allow beta's to run
if ($GLOBALS['license']['Beta']) {
    die('Beta license does not work with this version.');
}

/*****************************************
SET VARS
*****************************************/
$page = isset($_GET['pg']) ? clean_filename($_GET['pg']) : 'workspace';
$GLOBALS['page'] = $page;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$hidePageFrame = 0;
$htmldirect = false;
$formerrors = [];
$errorbox = '';
$pagebody = '';
$pagetitle = '';
$tab = 'nav_hd';
$subtab = '';
$headscript = '';
$onload = '';

/*****************************************
SETUP LANGUAGE
*****************************************/
$GLOBALS['lang'] = new language($page);
include cBASEPATH.'/helpspot/lib/lookup.lib.php';	//include lookups here so we can use lang abstraction

/*****************************************
RSS/FEED CONVERT TO USE LOGIN
*****************************************/
if (hs_setting('cHD_FEEDSENABLED') && $page == 'feed_filter' || $page == 'feed_forum') {
    list($feeduser, $feedpass) = getBasicAuth();

    if (! $feeduser && ! $feedpass) {
        //Send HTTP basic auth header
        header('WWW-Authenticate: Basic realm="HelpSpot RSS Feed Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo '';
        exit;
    }

    if (! auth()->attempt(['sEmail' => $feeduser, 'password' => $feedpass])) {
        //Send HTTP basic auth header
        header('WWW-Authenticate: Basic realm="HelpSpot RSS Feed Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo '';
        exit;
    }

    //Used by auth class
    $_POST['username'] = $feeduser;
    $_POST['password'] = $feedpass;
}

/*****************************************
FILE DOWNLOADS FOR IE
see: http://joseph.randomnetworks.com/archives/2004/10/01/making-ie-accept-file-downloads/
*****************************************/
$downloadUrls = ['file', 'excel', 'excel_filter'];
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') and isset($_GET['pg']) and in_array($_GET['pg'], $downloadUrls)) {
    session_cache_limiter('public');
}

// TODO: Eventually move everything above this to middleware/bootstrap
//       So admin can be a proper route behind auth middleware
if (auth()->check()) {
    if (auth()->user()->fDeleted == 1) {
        auth()->logout();
        return (request()->ajax())
            ? response([], 401) // No "X-HelpSpot-Session" header, we don't want to trigger the login modal on a deleted account
            : redirect()->route('login');
    }
    /*****************************************
    GET USER SETTINGS
    *****************************************/
    if (auth()->user()->sUserName) {
        $user = apiGetUserByAuth(auth()->user()->sUsername, auth()->user()->sEmail);
    } else {
        $user = apiGetUserByEmail(auth()->user()->sEmail);
    }

    if ( ! $user) {
        auth()->logout();
        // todo: redirect to login? (I think this will do it above)
        exit();
    }

    // todo:Replace global $user usage
    $GLOBALS['user'] = $user;
    // Set vars that depend on user settings
    $style = 'blank';

    /*****************************************
    PROTECT SYSTEM WHEN UPLOADING FILES BUT FORGETTING TO RUN /INSTALL
    *****************************************/
    //Make sure files match DB version, if not redirect to message
    if (trim(file_get_contents(cBASEPATH.'/helpspot/version.txt')) !== hs_setting('cHD_VERSION')) {
        return view('errors.dbupgrade')->render();
    }

    /*****************************************
    LICENSE SECURITY - only check when it's an admin to reduce load
    *****************************************/
    if (isAdmin()) {
        //Check usage limit
		if(licenseOverUserLimit()){
			Request::session()->flash('status', 'The installation has more staff members than licensed for');
			return view('utility.support_expired');
		}
    }

    // Is this license expired?
    if (subscription()->expired()) {
        return view('utility.support_expired');
    }

    /*****************************************
    INCLUDE PAGE
    *****************************************/
    $pagepath = cBASEPATH.'/helpspot/pages/'.$page.'.php';
    if (file_exists($pagepath)) {

        //Set persons status
        //TODO: Have flag in each page for ones that "should" be tracked rather than trying to pick out each that should not be
        if (! $htmldirect && ! in_array($_GET['pg'], ['graph', 'graph_pareto', 'file', 'file.staffphoto', 'excel', 'excel_filter', 'ajax_gateway'])) {
            if (($page == 'request' || ($page == 'request.static' and ! isset($_GET['from_streamview']))) && isset($_GET['reqid'])) {
                $reqid = filter_var($_GET['reqid'], FILTER_SANITIZE_NUMBER_INT);
                $details = lg_ps_viewingrequest;
                $ftype = 1;
            } else {
                $reqid = 0;
                $details = '';
                $ftype = 0;
            }

            $person_status = new person_status();
            $person_status->update_status($user, $page, $reqid, $ftype, $details);
        }

        $pageResponse = include $pagepath;

        if( $pageResponse instanceof \Symfony\Component\HttpFoundation\Response ) {
            ob_end_clean();
            return $pageResponse;
        }
    } else {
        exit();
    }

    /*****************************************
    TRIAL EXPIRATION
    *****************************************/
    if (isset($GLOBALS['license']['trial']) && $GLOBALS['license']['trial'] < time() && ! isset($_FILES['license']['tmp_name'])) {
        //If in trial mode and beyond trial limit then show license upload screen
        $hidePageFrame = 1;
        return view('utility.support_expired');
    }

    /*****************************************
    MAIN BODY
    *****************************************/
    $sentHeaders = headers_list();
    $shouldSendHeaders = true;

    if (is_array($sentHeaders)) {
        foreach ($sentHeaders as $header) {
            $header = strtolower($header);
            if (strpos($header, 'cache-control') !== false) {
                $shouldSendHeaders = false;
            }
        }
    }

    //Required so IE doesn't fall back to incorrect version
    // of document mode
    header('X-UA-Compatible: IE=Edge');

    //Send Cache headers
    if ($shouldSendHeaders) {
        hs_nocache_headers();
    }

    //if pagebody is set then build page, otherwise don't output anything
    //page is probably a processing page or one that outputs alt mime types
    if ($htmldirect) {
        echo $pagebody;
    } else {
        if (! empty($pagebody)) {

            //Output page
            if (! $hidePageFrame) {
                echo displayHeader($pagetitle, $style, $tab, $headscript, $onload, $subtab);
            } else {
                echo displaySimpleHeader($pagetitle, $style, $tab, $headscript, $onload);
            }

            echo $pagebody;

            if (! $hidePageFrame) {
                echo displayFooter($tab);
            } else {
                echo displaySimpleFooter();
            }
        }
    }

    //Script is over. Flush the output buffer.
    return ob_get_clean();
} else {
    if ($page != 'ajax_gateway') {
        session()->put('url.intended', url()->full());
    }

    return (request()->ajax())
        ? response([], 401, ['X-HelpSpot-Session' => 'Expired'])
        : redirect()->route('login');
}
