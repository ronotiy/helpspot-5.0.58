<?php

//Make sure nothing goes out until the end of the script
ob_start();

//Config
if( ! file_exists(cBASEPATH.'/../.env')){
	header('Location: '.action('Install\InstallerController@getStep1'), true, 301);
	exit();
}

//Portal Logic
$response = require_once(cBASEPATH.'/helpspot/portal/logic.php');

// If Portal Logic returns a redirect response, we'll short circuit this
// and return the response back back to the route handler immediately
if( $response && $response instanceof \Symfony\Component\HttpFoundation\Response) {
    return $response;
}

//Savant
require_once(cBASEPATH.'/helpspot/lib/Savant2.php');

//Helper Class
require_once(cBASEPATH.'/helpspot/portal/class.portalhelper.php');

// For paths last in array is first searched by Savant
$custom_path = (isset($GLOBALS['hs_multiportal']) ? $GLOBALS['hs_multiportal']->sPortalPath : cBASEPATH);
$opts = array(
    'template_path' => array(cBASEPATH.'/helpspot/templates',$custom_path.'/custom_templates', public_path('custom_templates')),
    'resource_path' => array(cBASEPATH.'/helpspot/lib/Savant2', cBASEPATH.'/helpspot/portal'),
    'template' => 'index.tpl.php'
);

$tpl = new Savant2($opts);

//Sterilized Get/Post vars
if($page == 'request.check'){	//Exception to allow this page to get non-numeric ID's. Do check though to make sure in valid format.
	//If not set or contains any incorrect characters then return false
	if(isset($_GET['id']) && preg_match('/^[0-9]{1,11}[a-zA-Z]{6}$/', $_GET['id'])) {
        $tpl->assign('get_id', e($_GET['id'])); // HelpSpot 4 and lower used 6 characters
    }elseif(isset($_GET['id']) && preg_match('/^[0-9]{1,11}[a-zA-Z]{20}$/', $_GET['id'])){
        $tpl->assign('get_id', e($_GET['id'])); // HelpSpot 6 uses 20 characters
	}else{
		$tpl->assign('get_id', false);
	}
}else{
	$tpl->assign('get_id',(isset($_GET['id']) && is_numeric($_GET['id']) ? e($_GET['id']) : false));
}

//If we're in a remote portal do not allow access to KB's and forums which are not part of this portal
if(isset($GLOBALS['hs_multiportal'])){
	$GLOBALS['hs_multiportal']->idCheck($page,$_GET['id']);
}

//Special case if $_GET['login_email'] is set then there's been an error
if(isset($_GET['login_email'])) $GLOBALS['errors']['login_email'] = lg_portal_req_loginfailed;

$tpl->assign('get_page',$page);
$tpl->assign('get_page_css_class',str_replace('.','-',$page));
$tpl->assign('get_start',(isset($_GET['start']) && is_numeric($_GET['start']) ? e($_GET['start']) : 0));
// Do not run through htmlspecialchars since it's already converted from util.lib clean_data
$tpl->assign('get_q',(isset($_GET['q']) && is_string($_GET['q']) ? e($_GET['q']) : ''));
$tpl->assign('get_xTag',(isset($_GET['xTag']) && is_numeric($_GET['xTag']) ? e($_GET['xTag']) : ''));
$tpl->assign('get_area',(isset($_GET['area']) && is_string($_GET['area']) ? hs_htmlspecialchars($_GET['area']) : ''));
$tpl->assign('post_tPost',(isset($_POST['tPost'])) ? hs_htmlspecialchars($_POST['tPost']) : '');
$tpl->assign('post_sTopic',(isset($_POST['sTopic'])) ? hs_htmlspecialchars($_POST['sTopic']) : '');
$tpl->assign('get_login_email',(isset($_GET['login_email'])) ? hs_htmlspecialchars($_GET['login_email']) : '');

$tpl->assign('request_sUserId',(isset($_REQUEST['sUserId'])) ? hs_htmlspecialchars($_REQUEST['sUserId']) : '');
$tpl->assign('request_fullname',(isset($_REQUEST['fullname'])) ? hs_htmlspecialchars($_REQUEST['fullname']) : '');
$tpl->assign('request_sFirstName',(isset($_REQUEST['sFirstName'])) ? hs_htmlspecialchars($_REQUEST['sFirstName']) : '');
$tpl->assign('request_sLastName',(isset($_REQUEST['sLastName'])) ? hs_htmlspecialchars($_REQUEST['sLastName']) : '');
$tpl->assign('request_sEmail',(isset($_REQUEST['sEmail'])) ? hs_htmlspecialchars($_REQUEST['sEmail']) : '');
$tpl->assign('request_sPhone',(isset($_REQUEST['sPhone'])) ? hs_htmlspecialchars($_REQUEST['sPhone']) : '');
$tpl->assign('request_sTitle',(isset($_REQUEST['sTitle'])) ? hs_htmlspecialchars($_REQUEST['sTitle']) : '');
$tpl->assign('request_fUrgent',(isset($_REQUEST['fUrgent'])) && is_numeric($_REQUEST['fUrgent']) ? e($_REQUEST['fUrgent']) : 0);
$tpl->assign('request_xCategory',(isset($_REQUEST['xCategory'])) && is_numeric($_REQUEST['xCategory']) ? e($_REQUEST['xCategory']) : 0);
$tpl->assign('request_did',(isset($_REQUEST['did'])) ? hs_htmlspecialchars($_REQUEST['did']) : '');
$tpl->assign('request_expected',(isset($_REQUEST['expected'])) ? hs_htmlspecialchars($_REQUEST['expected']) : '');
$tpl->assign('request_actual',(isset($_REQUEST['actual'])) ? hs_htmlspecialchars($_REQUEST['actual']) : '');
$tpl->assign('request_simple',(isset($_REQUEST['simple'])) ? hs_htmlspecialchars($_REQUEST['simple']) : '');
$tpl->assign('request_update',(isset($_REQUEST['update'])) ? hs_htmlspecialchars($_REQUEST['update']) : '');
$tpl->assign('request_additional',(isset($_REQUEST['additional'])) ? hs_htmlspecialchars($_REQUEST['additional']) : '');

//Set custom field variables
$customFields = apiGetCustomFields();
if(is_array($customFields) && !empty($customFields)){
	foreach($customFields AS $v){
		$tpl->assign('Custom'.$v['fieldID'],(isset($_REQUEST['Custom'.$v['fieldID']])) ? hs_htmlspecialchars($_REQUEST['Custom'.$v['fieldID']]) : '');
	}
}

//Set variables which are global to all templates
$tpl->assign('hd_portalFormFormat',trim(hs_setting('cHD_PORTAL_FORMFORMAT')));
$tpl->assign('hd_allowFileAttachments',trim(hs_setting('cHD_PORTAL_ALLOWUPLOADS')));
$tpl->assign('hd_useCaptcha',trim(hs_setting('cHD_PORTAL_CAPTCHA')));
$tpl->assign('hd_hostURL',trim(hs_setting('cHOST')));
$tpl->assign('hd_feedCopyright',utf8_trim(hs_setting('cHD_FEEDCOPYRIGHT')));
$tpl->assign('hd_feedCharSet',trim('UTF-8'));
$tpl->assign('hd_CharSet', '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">');
$tpl->assign('hd_requestCheckAuthType',hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE'));
$tpl->assign('cf_url',trim(hs_setting('cHOST')));
$tpl->assign('cf_path',trim(hs_setting('cBASEPATH')));
$tpl->assign('cf_version',trim(hs_setting('cHD_VERSION')));
$tpl->assign('hd_theme',(hs_setting('cHD_THEME_PORTAL') && hs_setting('cHD_THEME_PORTAL') != 'classic' ? 'css.'.trim(hs_setting('cHD_THEME_PORTAL')) : 'css'));
$tpl->assign('hd_theme_ie',(hs_setting('cHD_THEME_PORTAL') && hs_setting('cHD_THEME_PORTAL') != 'classic' ? 'ie.css.'.trim(hs_setting('cHD_THEME_PORTAL')) : 'ie.css'));
$tpl->assign('hd_portalLoginSearchType',trim(hs_setting('cHD_PORTAL_LOGIN_SEARCHONTYPE')));
$tpl->assign('hd_reCAPTCHATheme',trim(hs_setting('cHD_RECAPTCHA_THEME')));
$tpl->assign('hd_reCAPTCHALang',trim(hs_setting('cHD_RECAPTCHA_LANG')));
$tpl->assign('hd_allowCc',trim(hs_setting('cHD_PORTAL_ALLOWCC')));
$tpl->assign('hd_allowSubject',trim(hs_setting('cHD_PORTAL_ALLOWSUBJECT')));

//Set multiportal specific ones or else deafult to built in
if(isset($GLOBALS['hs_multiportal'])){
	$tpl->assign('cf_primaryurl',trim(config('app.url'))); //set the primary host URL so we can point back to the main install for js and images
	$tpl->assign('hd_name',utf8_trim((empty($GLOBALS['hs_multiportal']->sPortalName) ? hs_setting('cHD_ORGNAME') : $GLOBALS['hs_multiportal']->sPortalName)));
	$tpl->assign('hd_phone',utf8_trim((empty($GLOBALS['hs_multiportal']->sPortalPhone) ? "" : $GLOBALS['hs_multiportal']->sPortalPhone)));
	$tpl->assign('hd_portalHomepageMsg',utf8_trim((empty($GLOBALS['hs_multiportal']->tPortalMsg) ? hs_setting('cHD_PORTAL_MSG') : $GLOBALS['hs_multiportal']->tPortalMsg)));
    $tpl->assign('sPortalTerms',utf8_trim($GLOBALS['hs_multiportal']->sPortalTerms));
    $tpl->assign('sPortalPrivacy',utf8_trim($GLOBALS['hs_multiportal']->sPortalPrivacy));
}else{
	$tpl->assign('cf_primaryurl',trim(hs_setting('cHOST'))); //set the primary host URL so we can point back to the main install for js and images
	$tpl->assign('hd_name',utf8_trim(hs_setting('cHD_ORGNAME')));
	$tpl->assign('hd_logo',trim(hs_setting('cHD_ORGLOGO')));
	$tpl->assign('hd_phone',trim(hs_setting('cHD_PORTAL_PHONE')));
	$tpl->assign('hd_portalHomepageMsg',utf8_trim(hs_setting('cHD_PORTAL_MSG')));
    $tpl->assign('sPortalTerms',utf8_trim(hs_setting('cHD_PORTAL_TERMS')));
    $tpl->assign('sPortalPrivacy',utf8_trim(hs_setting('cHD_PORTAL_PRIVACY')));
}

//Page variables
$tpl->assign('pg_title',utf8_trim(hs_setting('cHD_ORGNAME')));

//Create and assign helper
$hs_helper = new PortalHelper(array('page'=>$tpl->get_page,'id'=>$tpl->get_id));
$tpl->assign('helper',$hs_helper);

// Do we require login to view the portal?
$requireAuth = false;
if(isset($GLOBALS['hs_multiportal']) && $GLOBALS['hs_multiportal']->fRequireAuth) {
	$requireAuth = true;
} elseif (hs_setting('cHD_PORTAL_REQUIRE_AUTH', 0) == 1) {
	$requireAuth = true;
}
$tpl->assign('requireAuth', $requireAuth);
$redirectPages = ['home', 'request', 'request.history', 'kb', 'kb.book', 'kb.chapter', 'kb.page', 'kb.printer.friendly', 'search', 'tag.search'];
if (($requireAuth && ! $tpl->splugin('Request_Check', 'isLoggedIn')) && in_array($page, $redirectPages)) {
	$hs_helper->redirect(trim(hs_setting('cHOST')).'/index.php?pg=request.check');
}

/*****************************************
CRUMB LOGIC
*****************************************/
switch($page){
	case "kb":
		$GLOBALS['navOn'] = 'kb';
		break;
	case "request":
		$GLOBALS['navOn'] = 'request';
		break;
	case "request.check":
		$GLOBALS['navOn'] = 'check';
		break;
	case "request.history":
		$GLOBALS['navOn'] = 'check';
		break;
	case "kb.book":
		$GLOBALS['navOn'] = 'kb'.$tpl->get_id;
		break;
	//If not a standard HS template then just use template name
	default:
		$GLOBALS['navOn'] = $page;
		break;
}


//Get template results
$display = $tpl->fetch();

//Required so IE doesn't fall back to incorrect version
// of document mode
header('X-UA-Compatible: IE=Edge');

hs_nocache_headers();

//Log any errors
errorCleanup();

//Clean output buffer so we have full control and can perform cleanup as needed below
ob_end_clean();

return utf8_trim($display);
?>
