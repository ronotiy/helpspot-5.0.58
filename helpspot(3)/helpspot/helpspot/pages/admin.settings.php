<?php

// SECURITY: Don't allow direct calls
use Illuminate\Support\Facades\Artisan;

if (! defined('cBASEPATH') || ! isAdmin()) {
	die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/TZ.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
include cBASEPATH.'/helpspot/lib/class.api.base.php';
include cBASEPATH.'/helpspot/lib/class.api.public.php';

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
 *****************************************/
$sm = hs_unserialize(hs_setting('cHD_MAIL_SMTPCONN'), []);

// Fresh install with no cHD_MAIL_SMTPCONN yet
$freshInstall = count($sm) == 0;

$sm['cHD_MAIL_SMTPPROTOCOL'] = isset($sm['cHD_MAIL_SMTPPROTOCOL']) ? $sm['cHD_MAIL_SMTPPROTOCOL'] : '';
$sm['cHD_MAIL_SMTPAUTH'] = isset($sm['cHD_MAIL_SMTPAUTH']) ? $sm['cHD_MAIL_SMTPAUTH'] : '';
$sm['cHD_MAIL_SMTPHOST'] = isset($sm['cHD_MAIL_SMTPHOST']) ? $sm['cHD_MAIL_SMTPHOST'] : '';
$sm['cHD_MAIL_SMTPHELO'] = isset($sm['cHD_MAIL_SMTPHELO']) ? $sm['cHD_MAIL_SMTPHELO'] : '';
$sm['cHD_MAIL_SMTPUSER'] = isset($sm['cHD_MAIL_SMTPUSER']) ? $sm['cHD_MAIL_SMTPUSER'] : '';
$sm['cHD_MAIL_SMTPPASS'] = isset($sm['cHD_MAIL_SMTPPASS']) ? $sm['cHD_MAIL_SMTPPASS'] : '';
$sm['cHD_MAIL_SMTPPORT'] = isset($sm['cHD_MAIL_SMTPPORT']) ? $sm['cHD_MAIL_SMTPPORT'] : '25';

if ($freshInstall && $sm['cHD_MAIL_SMTPAUTH'] === '') {
    $sm['cHD_MAIL_SMTPAUTH'] = '1';
}

$ldap = hs_unserialize(hs_setting('cAUTHTYPE_LDAP_OPTIONS', ''), []);
$ldap['cHD_LDAP_ACCOUNT_SUFFIX'] = isset($ldap['account_suffix']) ? $ldap['account_suffix'] : '';
$ldap['cHD_LDAP_BASE_DN'] = isset($ldap['base_dn']) ? $ldap['base_dn'] : '';
$ldap['cHD_LDAP_USERNAME'] = isset($ldap['ad_username']) ? $ldap['ad_username'] : '';
$ldap['cHD_LDAP_PASSWORD'] = isset($ldap['ad_password']) ? $ldap['ad_password'] : '';
$ldap['cHD_LDAP_DN_CONTROL'] = isset($ldap['domain_controllers'][0]) ? $ldap['domain_controllers'][0] : ''; //stored as an array
$ldap['cHD_LDAP_USESSL'] = isset($ldap['use_ssl']) ? $ldap['use_ssl'] : false;
$ldap['cHD_LDAP_USETLS'] = isset($ldap['use_tls']) ? $ldap['use_tls'] : false;

$saml = hs_unserialize(hs_setting('cAUTHTYPE_SAML_OPTIONS', ''), []);
$saml['cHD_SAML_ENTITYID'] = $saml['entity_id'] ?? '';
$saml['cHD_SAML_LOGINURL'] = $saml['login_url'] ?? '';
$saml['cHD_SAML_LOGOUTURL'] = $saml['logout_url'] ?? '';
$saml['cHD_SAML_CERT'] = $saml['x509_cert'] ?? '';

$live_lookup_searches = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'), []);

// Advanced SMTP Security Options
$listedTransports = smtpSecurityProtocols();
$advancedTransports = '';
if (count($listedTransports)) {
	$advancedTransports = '<optgroup label="'.lg_admin_settings_smtpprotolabeladvanced.'">';
	foreach ($listedTransports as $transport) {
		$advancedTransports .= '<option value="'.$transport.'" '.selectionCheck($transport, $sm['cHD_MAIL_SMTPPROTOCOL']).'>'.$transport.'</option>';
	}
	$advancedTransports .= '</optgroup>';
} else {
	$advancedTransports = '<optgroup label="'.lg_admin_settings_smtpprotolabeladvanced.'">
	<option disabled>None Available</option>
</optgroup>';
}
/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
	<script type="text/javascript" language="JavaScript">
		function checkForm(){
			var er = "";

			//check business hours
			var days = ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"];

			for(i=0;i < 7;i++){
				if($F("bh_"+days[i]+"_nohours") != 1){
					if(parseInt($F("bh_"+days[i]+"_start")) > parseInt($F("bh_"+days[i]+"_end"))){
						er = "'.lg_admin_settings_bizhours_error.'";
					}
				}
			}

			//Check that email prefix is letters only
			if(!$("cHD_EMAILPREFIX").value == ""){
				if(!/^[a-zA-Z]+$/.test($("cHD_EMAILPREFIX").value)){
					er = "'.lg_admin_settings_prefix_error.'";
				}
			}

			if(er == ""){
				return true;
			}else{
				hs_alert(er);

				return false;
			}
		}

		function htmlSwitch(){
			stripindex = document.settingsform.cHD_STRIPHTML.selectedIndex;
			if(document.settingsform.cHD_STRIPHTML[stripindex].value == 1){
				$jq("#htmlallowed").show();
			}else{
				$jq("#htmlallowed").hide();
			}
		}

		function attachPathSwitch(){
			if( ! $("attachment_location_path")) {
				return;
			}
			if($F("cHD_ATTACHMENT_LOCATION") == "file"){
				$("attachment_location_path").show();
			}else{
				$("attachment_location_path").hide();
			}
		}

		function captchaSwitch(){
			if($F("cHD_PORTAL_CAPTCHA") == "1"){
				$("captcha_text_wrap").show();
				$("captcha_re_wrap").hide();
			}else if($F("cHD_PORTAL_CAPTCHA") == "2"){
				$("captcha_text_wrap").hide();
				$("captcha_re_wrap").show();
			}else{
				$("captcha_text_wrap").hide();
				$("captcha_re_wrap").hide();
			}
		}

		function authSwitch(){
			if($F("cAUTHTYPE") == "internal"){
				Element.hide("authblock");
				Element.hide("ldapblock");
				Element.hide("samlblock");
				Element.hide("saml_options");
				Element.hide("ldap_ad_options");
			}else if($F("cAUTHTYPE") == "ldap_ad"){
				Element.hide("authblock");
				Element.hide("samlblock");
				Element.hide("saml_options");
				Element.show("ldapblock");
				Element.show("ldap_ad_options");
			}else if($F("cAUTHTYPE") == "blackbox"){
				Element.hide("ldap_ad_options");
				Element.hide("ldapblock");
				Element.hide("samlblock");
				Element.hide("saml_options");
				Element.show("authblock");
			}else if($F("cAUTHTYPE") == "saml"){
				Element.hide("ldap_ad_options");
				Element.hide("authblock");
				Element.hide("ldapblock");
				Element.show("samlblock");
				Element.show("saml_options");
			}
		}

		function emailSwitch(){
			if($F("cHD_MAIL_OUTTYPE") == "smtp"){
				Element.show("emailblock");
			}else{
				Element.hide("emailblock");
			}
		}

		function switchDate(field,select){
			ind = document.getElementById(select).selectedIndex;
			document.getElementById(field).value = document.getElementById(select).options[ind].value;
		}

		function setWSEdit(id){
			//Close all other boxes
			var boxes = $$(".wscols");
			boxes.each(function(s){s.hide();});

			//Change bg on links
			var links = $$(".wscolslnk");
			links.each(function(s){s.style.backgroundColor="";});

			$("wscolslnk_"+id).style.backgroundColor = "#FDE74C";
			$("wscols_"+id).show();
		}

		function addNewLiveLookupSource(){
			Effect.BlindDown("new_livelookup");
			$("new_livelookup_link").hide();
			return false;
		}

		function showPortalAuthNote(){
			if($F("cHD_PORTAL_LOGIN_AUTHTYPE") == "blackbox"){
				$("samlportal_note").hide();
				$("blackboxportal_note").show();
			}else if($F("cHD_PORTAL_LOGIN_AUTHTYPE") == "saml"){
				$("blackboxportal_note").hide();
				$("samlportal_note").show();
			}else{
				$("samlportal_note").hide();
				$("blackboxportal_note").hide();
			}
		}

		function bizhours_nohours(){
			//Regular days
			var days = ["monday","tuesday","wednesday","thursday","friday","saturday","sunday"];

			for(i=0;i < 7;i++){
				if($F("bh_"+days[i]+"_nohours") == 1){
					$("bh_"+days[i]+"_start").disable();
					$("bh_"+days[i]+"_end").disable();
				}else{
					$("bh_"+days[i]+"_start").enable();
					$("bh_"+days[i]+"_end").enable();
				}
			}

			//New holiday
			if($F("bh_newholiday_nohours") == 1){
				$("bh_newholiday_start").disable();
				$("bh_newholiday_end").disable();
			}else{
				$("bh_newholiday_start").enable();
				$("bh_newholiday_end").enable();
			}
		}

		function showSetting(id){
			$jq("#admin-navigation li.active").removeClass("active");
			$jq("#settings-nav-" + id).addClass("active");
			$jq(".settings-box").css("display","none");
			$jq("#box_id_" + id).css("display","block");
			$jq("#admin_settings_page").val(id);

			return false;
		}

		function testLDAP(){
			$("test_ldap_results").innerHTML = ajaxLoading("'.hs_jshtmlentities(lg_admin_settings_ldap_testing).'");

			var url  = "'.route('admin').'";
			var pars = "pg=ajax_gateway&action=test_ldap&username="+$F("ldap_test_username")+"&password="+$F("ldap_test_password")+"&account_suffix="+$F("cHD_LDAP_ACCOUNT_SUFFIX")+"&base_dn="+$F("cHD_LDAP_BASE_DN")+"&domain_controllers="+$F("cHD_LDAP_DN_CONTROL")+"&ad_username="+$F("cHD_LDAP_USERNAME")+"&ad_password="+$F("cHD_LDAP_PASSWORD")+"&use_ssl="+$F("cHD_LDAP_USESSL")+"&use_tls="+$F("cHD_LDAP_USETLS")+"&rand=" + ajaxRandomString();

			var call = new Ajax.Request(
				url,
				{
					method: 	 "get",
					parameters:  pars,
					onComplete:  function(){
						$("test_ldap_results").innerHTML = arguments[0].responseText;
					}
				});

			return false;
		}
	</script>
';

$onload = 'authSwitch();emailSwitch();htmlSwitch();showPortalAuthNote();attachPathSwitch();captchaSwitch();bizhours_nohours();';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.settings']);
$pagetitle = lg_admin_settings_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_settings';

if (session('feedback')) {
	$feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$efb = (session('error'))
	?  errorBox(session('error'))
	: '';

$feedbackArea = '';
$error = '';

//Setup api info for column editing panel
$api_methods = ['customer.getRequests'	=>['xRequest'=>true, 'fOpenedVia'=>true, 'xOpenedViaId'=>true, 'xPersonOpenedBy'=>false, 'xPersonAssignedTo'=>false, 'fOpen'=>true, 'xStatus'=>true, 'fUrgent'=>true, 'xCategory'=>true, 'dtGMTOpened'=>true, 'dtGMTClosed'=>false, 'sRequestPassword'=>true, 'sTitle'=>true, 'sUserId'=>true, 'sFirstName'=>true, 'sLastName'=>true, 'sEmail'=>true, 'sPhone'=>true, 'iLastReplyBy'=>false, 'fTrash'=>false, 'dtGMTTrashed'=>false, 'fullname'=>true, 'sStatus'=>true, 'sCategory'=>true],
					   'request.getCategories'	=>['xCategory'=>true, 'sCategory'=>true, 'sCustomFieldList'=>true],
					   'request.getCustomFields'=>['xCustomField'=>true, 'fieldName'=>true, 'isRequired'=>true, 'fieldType'=>true, 'iOrder'=>true, 'sTxtSize'=>true, 'lrgTextRows'=>true, 'listItems'=>true, 'iDecimalPlaces'=>true, 'sRegex'=>true, 'isAlwaysVisible'=>true],
					   'request.get'			=>['xRequest'=>true, 'fOpenedVia'=>true, 'xOpenedViaId'=>true, 'xPersonOpenedBy'=>false, 'xPersonAssignedTo'=>false, 'fOpen'=>true, 'xStatus'=>true, 'fUrgent'=>true, 'xCategory'=>true, 'dtGMTOpened'=>true, 'dtGMTClosed'=>true, 'sRequestPassword'=>true, 'sTitle'=>true, 'sUserId'=>true, 'sFirstName'=>true, 'sLastName'=>true, 'sEmail'=>true, 'sPhone'=>true, 'iLastReplyBy'=>false, 'fTrash'=>false, 'dtGMTTrashed'=>false, 'fullname'=>true, 'sStatus'=>true, 'sCategory'=>true, 'sAssignedToFirstName'=>true, 'sAssignedToLastName'=>false, 'request_history.xRequestHistory'=>true, 'request_history.xRequest'=>true, 'request_history.xPerson'=>true, 'request_history.firstname'=>true, 'request_history.lastname'=>false, 'request_history.dtGMTChange'=>true, 'request_history.fInitial'=>true, 'request_history.tNote'=>true, 'request_history.fNoteIsHTML'=>true, 'request_history.fMergedFromRequest'=>false, 'request_history.file.sFilename'=>true, 'request_history.file.sFileMimeType'=>true, 'request_history.file.url'=>true, 'request_history.file.xDocumentId'=>true],
					   'kb.list'				=>['xBook'=>true, 'sBookName'=>true, 'iOrder'=>true, 'tDescription'=>true],
					   'kb.get'					=>['xBook'=>true, 'sBookName'=>true, 'iOrder'=>true, 'tDescription'=>true],
					   'kb.getBookTOC'			=>['name'=>true, 'xChapter'=>true, 'sChapterName'=>true, 'iOrder'=>true, 'fAppendix'=>true, 'xBook'=>false],
					   'kb.getPage'				=>['name'=>true, 'xPage'=>true, 'xChapter'=>true, 'sPageName'=>true, 'tPage'=>true, 'iOrder'=>true, 'fHighlight'=>true, 'iHelpful'=>false, 'iNotHelpful'=>false],
					   'kb.search'				=>['desc'=>true, 'sBookName'=>true, 'sPageName'=>true, 'xPage'=>true, 'link'=>true], ];

//Append custom fields to request methods
foreach ($GLOBALS['customFields'] as $k=>$v) {
	$api_methods['request.get']['Custom'.$k] = false;
	$api_methods['customer.getRequests']['Custom'.$k] = false;
}

$excluded_columns = hs_unserialize(hs_setting('cHD_WSPUBLIC_EXCLUDECOLUMNS'), []);

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($_GET['restart_queues']) {
    Artisan::call('queue:restart');
    return redirect()
        ->route('admin', ['pg' => 'admin.settings', 'admin_settings_page' => md5(lg_admin_settings_workers)])
        ->with('feedback', lg_admin_settings_workers_restarted);
}

if (isset($_POST['vmode'])) {
	//AUTH TYPE
	if ($_POST['cAUTHTYPE'] == 'blackbox') {
		if (file_exists(customCodePath('BlackBox.php'))) {
			if (! hs_empty($user['sUsername'])) {
				$authtype = 'blackbox';
			} else {
				$authtype = 'internal';
				$error = lg_admin_settings_usernamenotset;
			}
		} else {
			$authtype = 'internal';
			$error = lg_admin_settings_blackboxwarn;
		}
	} elseif ($_POST['cAUTHTYPE'] == 'ldap_ad') {
		$authtype = 'ldap_ad';

		$ldap_ad_options = [
			'account_suffix' => $_POST['cHD_LDAP_ACCOUNT_SUFFIX'],
			'base_dn' => $_POST['cHD_LDAP_BASE_DN'],
			'domain_controllers' => [$_POST['cHD_LDAP_DN_CONTROL']],
			'ad_username' => $_POST['cHD_LDAP_USERNAME'],
			'ad_password' => $_POST['cHD_LDAP_PASSWORD'],
			'use_ssl' => $_POST['cHD_LDAP_USESSL'],
			'use_tls' => $_POST['cHD_LDAP_USETLS'],
		];
		$ldap_ad_options = hs_serialize($ldap_ad_options);
		storeGlobalVar('cAUTHTYPE_LDAP_OPTIONS', $ldap_ad_options);
	} elseif ($_POST['cAUTHTYPE'] == 'saml') {
		$authtype = 'saml';
		$saml_options = [
			'entity_id' => $_POST['cHD_SAML_ENTITYID'],
			'login_url' => $_POST['cHD_SAML_LOGINURL'],
			'logout_url' => $_POST['cHD_SAML_LOGOUTURL'],
			'x509_cert' => $_POST['cHD_SAML_CERT'],
		];
		$saml_options = hs_serialize($saml_options);
		storeGlobalVar('cAUTHTYPE_SAML_OPTIONS', $saml_options);
	} else {
		$authtype = 'internal';
	}

	storeGlobalVar('cAUTHTYPE', $authtype);

	//STATUS
	$allstatusrs = apiGetAllStatus();
	//Adding new
	if (trim($_POST['newStatus']) != '') {
		$GLOBALS['DB']->Execute('INSERT INTO HS_luStatus(sStatus,fDeleted,fOrder) VALUES (?,?,?)', [$_POST['newStatus'], 0, 1]);
	}

	//Updating
	while ($stat = $allstatusrs->FetchRow()) {
		if (isset($_POST['status_name_'.$stat['xStatus']])) {
			$del = isset($_POST['status_del_'.$stat['xStatus']]) ? 1 : 0;
			$GLOBALS['DB']->Execute('UPDATE HS_luStatus SET sStatus=?, fDeleted=?, fOrder=? WHERE xStatus=?',
																  [$_POST['status_name_'.$stat['xStatus']], $del,
																		$_POST['status_order_'.$stat['xStatus']], $stat['xStatus'], ]);
		}
	}

	$allstatusrs->Move(0);

	//Restoring
	while ($stat = $allstatusrs->FetchRow()) {
		if (isset($_POST['status_restore_'.$stat['xStatus']])) {
			apiDeleteResource('HS_luStatus', 'xStatus', $stat['xStatus'], 'undelete');
		}
	}

	//BUSINESS HOURS
	$new_bizhours = hs_unserialize(cHD_BUSINESS_HOURS);
	foreach ([1=>'monday', 2=>'tuesday', 3=>'wednesday', 4=>'thursday', 5=>'friday', 6=>'saturday', 0=>'sunday'] as $k=>$v) {
		$new_bizhours['bizhours'][$k] = (! isset($_POST['bh_'.$v.'_nohours']) ? ['start'=>$_POST['bh_'.$v.'_start'], 'end'=>$_POST['bh_'.$v.'_end']] : false);
	}
	storeGlobalVar('cHD_BUSINESS_HOURS', hs_serialize($new_bizhours));

	//SYSTEM
	storeGlobalVar('cHD_ORGNAME', $_POST['cHD_ORGNAME']);
	storeGlobalVar('cHD_ORGLOGO', $_POST['cHD_ORGLOGO']);
	storeGlobalVar('cHD_LANG', $_POST['cHD_LANG']);
	storeGlobalVar('cHD_CONTACTVIA', $_POST['cHD_CONTACTVIA']);
	storeGlobalVar('cHD_DEFAULT_HISTORYSEARCH', $_POST['cHD_DEFAULT_HISTORYSEARCH']);
	storeGlobalVar('cHD_TIMEZONE_OVERRIDE', $_POST['cHD_TIMEZONE_OVERRIDE']);
	storeGlobalVar('cHD_DATEFORMAT', $_POST['cHD_DATEFORMAT']);
	storeGlobalVar('cHD_SHORTDATEFORMAT', $_POST['cHD_SHORTDATEFORMAT']);
	storeGlobalVar('cHD_POPUPCALDATEFORMAT', $_POST['cHD_POPUPCALDATEFORMAT']);
	storeGlobalVar('cHD_POPUPCALSHORTDATEFORMAT', $_POST['cHD_POPUPCALSHORTDATEFORMAT']);
	storeGlobalVar('cHD_DEFAULTMAILBOX', $_POST['cHD_DEFAULTMAILBOX']);

	if (validateEmail($_POST['cHD_NOTIFICATIONEMAILACCT'])) {
		storeGlobalVar('cHD_NOTIFICATIONEMAILACCT', $_POST['cHD_NOTIFICATIONEMAILACCT']);
		storeGlobalVar('cHD_NOTIFICATIONEMAILNAME', $_POST['cHD_NOTIFICATIONEMAILNAME']);
	} else {
		$error = lg_admin_settings_emailnotvalid;
	}

	storeGlobalVar('cHD_CUSTCONNECT_ACTIVE', $_POST['cHD_CUSTCONNECT_ACTIVE']);
	storeGlobalVar('cHD_BATCHCLOSE', $_POST['cHD_BATCHCLOSE']);
	storeGlobalVar('cHD_BATCHRESPOND', $_POST['cHD_BATCHRESPOND']);
	storeGlobalVar('cHD_FEEDSENABLED', $_POST['cHD_FEEDSENABLED']);
	storeGlobalVar('cHD_FEEDCOPYRIGHT', $_POST['cHD_FEEDCOPYRIGHT']);
	storeGlobalVar('cHD_MAXSEARCHRESULTS', $_POST['cHD_MAXSEARCHRESULTS']);
	storeGlobalVar('cHD_VIRTUAL_ARCHIVE', $_POST['cHD_VIRTUAL_ARCHIVE']);
	storeGlobalVar('cHD_STRIPHTML', $_POST['cHD_STRIPHTML']);
	storeGlobalVar('cHD_SERIOUS', $_POST['cHD_SERIOUS']);
	storeGlobalVar('cHD_HTMLALLOWED', $_POST['cHD_HTMLALLOWED']);
	storeGlobalVar('cHD_EMBED_MEDIA', $_POST['cHD_EMBED_MEDIA']);
	storeGlobalVar('cHD_DAYS_TO_LEAVE_TRASH', $_POST['cHD_DAYS_TO_LEAVE_TRASH']);
	storeGlobalVar('cHD_SAVE_DRAFTS_EVERY', $_POST['cHD_SAVE_DRAFTS_EVERY']);

	//Don't want to allow hosted to set this so keep the original
	if (! isHosted()) {
		storeGlobalVar('cHD_ATTACHMENT_LOCATION', $_POST['cHD_ATTACHMENT_LOCATION']);
		storeGlobalVar('cHD_ATTACHMENT_LOCATION_PATH', rtrim($_POST['cHD_ATTACHMENT_LOCATION_PATH'], ' \/'));

		//Check for error in write path of location path. If it's not writable output an error
		if ($_POST['cHD_ATTACHMENT_LOCATION'] == 'file') {
			if (! writeFile(rtrim($_POST['cHD_ATTACHMENT_LOCATION_PATH'], ' \/').'/helpspot-test.txt', 'This file tests if the folder is writable')) {
				$error = lg_admin_settings_saveattach_pather;
			}
		}
	}

	//System security
	storeGlobalVar('cHD_PORTAL_SPAM_LINK_CT', $_POST['cHD_PORTAL_SPAM_LINK_CT']);
	storeGlobalVar('cHD_PORTAL_SPAM_AUTODELETE', $_POST['cHD_PORTAL_SPAM_AUTODELETE']);
	storeGlobalVar('cHD_PORTAL_SPAM_FORMVALID_ENABLED', $_POST['cHD_PORTAL_SPAM_FORMVALID_ENABLED']);
	storeGlobalVar('cHD_PORTAL_CAPTCHA', $_POST['cHD_PORTAL_CAPTCHA']);
	storeGlobalVar('cHD_PORTAL_CAPTCHA_WORDS', $_POST['cHD_PORTAL_CAPTCHA_WORDS']);
	storeGlobalVar('cHD_RECAPTCHA_PUBLICKEY', $_POST['cHD_RECAPTCHA_PUBLICKEY']);
	storeGlobalVar('cHD_RECAPTCHA_PRIVATEKEY', $_POST['cHD_RECAPTCHA_PRIVATEKEY']);
	storeGlobalVar('cHD_RECAPTCHA_THEME', $_POST['cHD_RECAPTCHA_THEME']);
	storeGlobalVar('cHD_RECAPTCHA_LANG', $_POST['cHD_RECAPTCHA_LANG']);

	//Time tracker
	storeGlobalVar('cHD_TIMETRACKER', $_POST['cHD_TIMETRACKER']);

	//EMAIL INTEGRATION
	$exts = str_replace(' ', '', $_POST['cHD_EXCLUDEMIMETYPES']);
	storeGlobalVar('cHD_EXCLUDEMIMETYPES', $exts);
	storeGlobalVar('cHD_MAIL_ALLOWMAILATTACHMENTS', $_POST['cHD_MAIL_ALLOWMAILATTACHMENTS']);
	storeGlobalVar('cHD_MAIL_MAXATTACHSIZE', $_POST['cHD_MAIL_MAXATTACHSIZE']);
	storeGlobalVar('cHD_TASKSDEBUG', $_POST['cHD_TASKSDEBUG']);
	storeGlobalVar('cHD_SPAMFILTER', $_POST['cHD_SPAMFILTER']);
	storeGlobalVar('cHD_SPAM_WHITELIST', $_POST['cHD_SPAM_WHITELIST']);
	storeGlobalVar('cHD_SPAM_BLACKLIST', $_POST['cHD_SPAM_BLACKLIST']);
	storeGlobalVar('cHD_EMAILLOOP_TIME', $_POST['cHD_EMAILLOOP_TIME']);
	storeGlobalVar('cHD_EMAIL_DAYS_AFTER_CLOSE', $_POST['cHD_EMAIL_DAYS_AFTER_CLOSE']);
	storeGlobalVar('cHD_EMAILS_MAX_TO_IMPORT', $_POST['cHD_EMAILS_MAX_TO_IMPORT']);
	storeGlobalVar('cHD_EMAILPREFIX', trim($_POST['cHD_EMAILPREFIX']));
	storeGlobalVar('cHD_EMAIL_LOOPCHECK_TIME', $_POST['cHD_EMAIL_LOOPCHECK_TIME']);
	storeGlobalVar('cHD_EMAIL_LOOPCHECK_CTMAX', $_POST['cHD_EMAIL_LOOPCHECK_CTMAX']);
	storeGlobalVar('cHD_EMAIL_GLOBALBCC', $_POST['cHD_EMAIL_GLOBALBCC']);
	storeGlobalVar('cHD_EMAIL_GLOBALBCC_TYPE', $_POST['cHD_EMAIL_GLOBALBCC_TYPE']);
	storeGlobalVar('cHD_EMAIL_REPLYABOVE', $_POST['cHD_EMAIL_REPLYABOVE']);
	storeGlobalVar('cSTAFFREPLY_AS_PUBLIC', $_POST['cSTAFFREPLY_AS_PUBLIC']);

	$smtp = [];
	$smtp['cHD_MAIL_SMTPTIMEOUT'] = $_POST['cHD_MAIL_SMTPTIMEOUT'];
	$smtp['cHD_MAIL_SMTPPROTOCOL'] = $_POST['cHD_MAIL_SMTPPROTOCOL'];
	$smtp['cHD_MAIL_SMTPAUTH'] = $_POST['cHD_MAIL_SMTPAUTH'];
	$smtp['cHD_MAIL_SMTPHOST'] = $_POST['cHD_MAIL_SMTPHOST'];
	$smtp['cHD_MAIL_SMTPHELO'] = $_POST['cHD_MAIL_SMTPHELO'];
	$smtp['cHD_MAIL_SMTPUSER'] = $_POST['cHD_MAIL_SMTPUSER'];
	$smtp['cHD_MAIL_SMTPPASS'] = (isset($_POST['cHD_MAIL_SMTPPASS']) && $_POST['cHD_MAIL_SMTPPASS'] != '') ? $_POST['cHD_MAIL_SMTPPASS'] : $sm['cHD_MAIL_SMTPPASS'];
	$smtp['cHD_MAIL_SMTPPORT'] = $_POST['cHD_MAIL_SMTPPORT'];
	$smtpvars = hs_serialize($smtp);
	storeGlobalVar('cHD_MAIL_SMTPCONN', $smtpvars);

	storeGlobalVar('cHD_MAIL_OUTTYPE', $_POST['cHD_MAIL_OUTTYPE']);

	storeGlobalVar('cHD_HTMLEMAILS', $_POST['cHD_HTMLEMAILS']);
	storeGlobalVar('cHD_HTMLEMAILS_EDITOR', $_POST['cHD_HTMLEMAILS_EDITOR']);
	storeGlobalVar('cHD_HTMLEMAILS_FILTER_IMG', $_POST['cHD_HTMLEMAILS_FILTER_IMG']);

	//LIVE LOOKUP
	storeGlobalVar('cHD_LIVELOOKUP', $_POST['cHD_LIVELOOKUP']);
	storeGlobalVar('cHD_LIVELOOKUPAUTO', $_POST['cHD_LIVELOOKUPAUTO']);

	//Find all LL passed in and save them
	$llsearches = [];
	$j = 0;
	for ($i = 1; $i <= $_POST['livelookup_count']; $i++) {
		if (! empty($_POST['livelookup_'.$i.'_name']) && ! empty($_POST['livelookup_'.$i.'_path'])) {
			$llsearches[$j]['name'] = $_POST['livelookup_'.$i.'_name'];
			$llsearches[$j]['type'] = $_POST['livelookup_'.$i.'_type'];
			$llsearches[$j]['path'] = $_POST['livelookup_'.$i.'_path'];
			$j++;
		}
	}

	//Sort by name
	asort($llsearches);

	//Serialize to store
	storeGlobalVar('cHD_LIVELOOKUP_SEARCHES', hs_serialize($llsearches));

	//WEB SERVICE API
	storeGlobalVar('cHD_WSPUBLIC', $_POST['cHD_WSPUBLIC']);
	storeGlobalVar('cHD_WSPRIVATE', $_POST['cHD_WSPRIVATE']);

	//build exclude cols list for each method
	$exclude_array = [];
	foreach ($api_methods as $k=>$v) {
		$post_array_to_exclude = isset($_POST['excludecols_'.str_replace('.', '_', $k)]) ? $_POST['excludecols_'.str_replace('.', '_', $k)] : [];
		$exclude_array[$k] = array_diff(array_keys($v), $post_array_to_exclude);
	}

	storeGlobalVar('cHD_WSPUBLIC_EXCLUDECOLUMNS', hs_serialize($exclude_array));

	//KB
	storeGlobalVar('cHD_WYSIWYG', $_POST['cHD_WYSIWYG']);
	storeGlobalVar('cHD_WYSIWYG_STYLES', $_POST['cHD_WYSIWYG_STYLES']);

	//PORTAL
	storeGlobalVar('cHD_PORTAL_PHONE', $_POST['cHD_PORTAL_PHONE']);
	storeGlobalVar('cHD_PORTAL_MSG', $_POST['cHD_PORTAL_MSG']);
	storeGlobalVar('cHD_PORTAL_FORMFORMAT', $_POST['cHD_PORTAL_FORMFORMAT']);
	storeGlobalVar('cHD_PORTAL_AUTOREPLY', $_POST['cHD_PORTAL_AUTOREPLY']);
	storeGlobalVar('cHD_PORTAL_REQUIRE_AUTH', $_POST['cHD_PORTAL_REQUIRE_AUTH']);
	storeGlobalVar('cHD_PORTAL_LOGIN_SEARCHONTYPE', $_POST['cHD_PORTAL_LOGIN_SEARCHONTYPE']);
	storeGlobalVar('cHD_PORTAL_LOGIN_AUTHTYPE', $_POST['cHD_PORTAL_LOGIN_AUTHTYPE']);
	storeGlobalVar('cHD_PORTAL_ALLOWUPLOADS', $_POST['cHD_PORTAL_ALLOWUPLOADS']);
	storeGlobalVar('cHD_PORTAL_EXCLUDEMIMETYPES', $_POST['cHD_PORTAL_EXCLUDEMIMETYPES']);
	storeGlobalVar('cHD_PORTAL_ALLOWCC', $_POST['cHD_PORTAL_ALLOWCC']);
	storeGlobalVar('cHD_PORTAL_ALLOWSUBJECT', $_POST['cHD_PORTAL_ALLOWSUBJECT']);

	$redirectParams = ['pg' => 'admin.settings'];

    if (isset($_POST['admin_settings_page']) && ! empty($_POST['admin_settings_page'])) {
        $redirectParams['admin_settings_page'] = $_POST['admin_settings_page'];
    }

	if (! empty($error)) {
		$redirectName = 'error';
		$redirectMsg = $error;
	} else {
		$redirectName = 'feedback';
		$redirectMsg = lg_admin_settings_saved;
	}

	return redirect()
		->route('admin', $redirectParams)
		->with($redirectName, $redirectMsg);
}

if (isHelpspotEmail($sm['cHD_MAIL_SMTPUSER'])) {
	$headscript .= '
		<script type="text/javascript" language="JavaScript">
			$jq().ready(function(){
				$jq(".testOutbound").remove();
			});
		</script>';
} else {
	$headscript .= '
		<script type="text/javascript" language="JavaScript">
		function testOutboundEmail(e){
			e.preventDefault();

			var formvals = $jq("#settingsform input[name^=\"cHD_MAIL\"],#settingsform select[name^=\"cHD_MAIL\"]").serialize();
			hs_overlay({href:"'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'test_outbound_email_ui']).'&"+formvals,
						onComplete:function(){
							$jq("#test_email_to").focus();
						},
						buttons: []
			});
			return false;
		}
		function sendTestEmail(){
			var formvals = $jq("#test_email_to,#cHD_NOTIFICATIONEMAILACCT,#settingsform input[name^=\"cHD_MAIL\"],#settingsform select[name^=\"cHD_MAIL\"]").serializeArray();
			var p = ($F("cHD_MAIL_SMTPPASS") == "") ? "'.$sm['cHD_MAIL_SMTPPASS'].'" : $F("cHD_MAIL_SMTPPASS");
			// Replace out the password
			$jq.each(formvals, function(index, item) {
				if (item.name == "cHD_MAIL_SMTPPASS") {
					item.value = p;
				}
			});
			hs_overlay({href:"'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'test_outbound_email']).'&"+$jq.param(formvals)});
		}
		</script>
	';
}

// get mailboxes - used in JS so no line breaks
$mailboxesSelect = '<option value="0" '.selectionCheck(0, hs_setting('cHD_DEFAULTMAILBOX')).'>'.hs_setting('cHD_NOTIFICATIONEMAILNAME').' - '.hs_setting('cHD_NOTIFICATIONEMAILACCT').'</option>';
$mailboxesres = apiGetAllMailboxes(0, '');
if (is_object($mailboxesres) && $mailboxesres->RecordCount() > 0) {
	while ($box = $mailboxesres->FetchRow()) {
		if (! hs_empty($box['sReplyEmail'])) {
			$mailboxesSelect .= '<option value="'.$box['xMailbox'].'" '.selectionCheck($box['xMailbox'], hs_setting('cHD_DEFAULTMAILBOX')).'>'.replyNameDisplay($box['sReplyName']).' - '.$box['sReplyEmail'].'</option>';
		}
	}
}
$mailboxesSelect .= '<option value="-1" '.selectionCheck(-1, hs_setting('cHD_DEFAULTMAILBOX')).'>'.lg_dontemail.'</option>';

$langopt = '';
if ($hd = opendir(cBASEPATH.'/helpspot/lang/')) {
	while (false !== ($file = readdir($hd))) {
		if ($file[0] != '.' && $file != 'core') {
			$f = str_replace('.php', '', $file);
			$langopt .= '<option value="'.$f.'" '.selectionCheck($f, hs_setting('cHD_LANG')).'>'.$f.'</option>';
		}
	}
	closedir($hd);
}

$contactopt = '<option value="0">'.lg_admin_settings_staffselectcontact.'</option>';
foreach ($GLOBALS['openedVia'] as $k=>$v) {
	if ($k != 6 && $k != 7) {
		$contactopt .= '<option value="'.$k.'" '.selectionCheck($k, hs_setting('cHD_CONTACTVIA')).'>'.$v.'</option>';
	}
}

$dateformats = ['%b %e %Y, %I:%M %p',
					 '%B %e %Y, %I:%M %p',
					 '%a, %B %e, %Y, %I:%M %p',
					 '%b %e, %Y, %I:%M %p',
					 '%m/%e/%Y, %I:%M %p',
					 '%e %b %Y, %I:%M %p',
					 '%e %b %Y, %I:%M %p %Z', ];
$dateselect = '';
foreach ($dateformats as $v) {
	$dateselect .= '<option value="'.$v.'" '.selectionCheck($v, hs_setting('cHD_DATEFORMAT')).'>'.hs_showCustomDate(date('U'), $v).'</option>';
}

$shortdateformats = ['%B %e, %Y',
					 '%b %e, %Y',
					 '%m/%e/%y',
					 '%e %b %Y', ];
$shortdateselect = '';
foreach ($shortdateformats as $v) {
	$shortdateselect .= '<option value="'.$v.'" '.selectionCheck($v, hs_setting('cHD_SHORTDATEFORMAT')).'>'.hs_showCustomDate(date('U'), $v).'</option>';
}

$llinst = lg_admin_settings_livelookupex;
$llinst .= ' <a href="'.$GLOBALS['userscapesupport'].'/index.php?pg=kb.page&id=6" target="_blank">'.lg_admin_settings_livelookupexinst.'</a>';

$wsinst = lg_admin_settings_wsex;
$wsinst .= ' <a href="'.$GLOBALS['userscapesupport'].'/index.php?pg=kb.chapter&id=28" target="_blank">'.lg_admin_settings_wsexinst.'</a>';

$allstatus = apiGetAllStatus();
$statusdelcount = 0;
while ($tas = $allstatus->FetchRow()) {
	if ($tas['fDeleted']) {
		$statusdelcount++;
	}
}
$allstatus->Move(0);

$updatebtn = '
	<div class="button-bar space">
		<button type="submit" name="submit" class="btn accent">'.lg_admin_settings_savebutton.'</button>
	</div>';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($efb)) {
	$feedbackArea = $efb;
}


$bh = new business_hours;

$pagebody .= '
<form action="'.$basepgurl.'" method="POST" class="" name="settingsform" id="settingsform" onSubmit="return checkForm();" enctype="multipart/form-data">
'.csrf_field().'
<input type="hidden" name="admin_settings_page" id="admin_settings_page" value="" />';

$pagebody .= $feedbackArea;

$pagebody .= view('admin.settings.system', ['mailboxesSelect' => $mailboxesSelect, 'langopt' => $langopt, 'contactopt' => $contactopt])->render();
$pagebody .= view('admin.settings.bizhours', ['bh' => $bh])->render();
$pagebody .= view('admin.settings.datetime', ['dateselect' => $dateselect, 'shortdateselect' => $shortdateselect, 'timezoneSelect' => $timezoneSelect])->render();
$pagebody .= view('admin.settings.timetracking')->render();
$pagebody .= view('admin.settings.emailintegration', ['sm' => $sm])->render();
$pagebody .= view('admin.settings.htmlemails')->render();
$pagebody .= view('admin.settings.livelookup', ['live_lookup_searches' => $live_lookup_searches, 'llsearchct' => count($live_lookup_searches) + 1])->render();
$pagebody .= view('admin.settings.api', ['api_methods' => $api_methods, 'excluded_columns' => $excluded_columns])->render();
$pagebody .= view('admin.settings.auth', ['saml' => $saml, 'ldap' => $ldap,])->render();
$pagebody .= view('admin.settings.kb')->render();
$pagebody .= view('admin.settings.portal')->render();
$pagebody .= view('admin.settings.workers')->render();

$pagebody .= '<input type="hidden" name="cHD_RECAPTCHA_THEME" value="clean">
	<input type="hidden" name="vmode" value="update">
</form>';
