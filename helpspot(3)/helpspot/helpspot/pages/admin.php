<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

/*****************************************
LANG
*****************************************/

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/api.forums.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin']);
$pagetitle = lg_home_admin_title;
$tab = 'nav_admin';
$subtab = '';

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$efb = (session('error'))
    ?  errorBox(session('error'))
    : '';

$staff = apiGetAllUsers();

$supportexpired = ($GLOBALS['license']['SupportEnds'] < date('U'));

/*****************************************
PERFORM ACTIONS
*****************************************/

//LICENSE
if (isset($_FILES['license']['tmp_name']) && is_uploaded_file($_FILES['license']['tmp_name'])) {
    $license_string = file_get_contents($_FILES['license']['tmp_name']);
    $licenseObj = new usLicense(hs_setting('cHD_CUSTOMER_ID'), $license_string, hs_setting('SSKEY'));
    $license = $licenseObj->getLicense();

    $activestaff = apiGetAllUsers();
    if (isset($license['Users']) && trim($license['Users']) != 'unlimited' && $activestaff->RecordCount() > $license['Users']) {
        $error = lg_home_admin_toomanyusers;
    } elseif (isset($license['CustomerID'])) {
        storeGlobalVar('cHD_LICENSE', $license_string);
    } else {
        $error = lg_home_admin_licnotvalid;
    }

    $redirectParams = ['pg' => 'admin'];

    if (! empty($error)) {
        $redirectName = 'error';
        $redirectMsg = $error;
    } else {
        $redirectName = 'feedback';
        $redirectMsg = lg_home_admin_licuploadok;
    }

    return redirect()
        ->route('admin', $redirectParams)
        ->with($redirectName, $redirectMsg);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript .= '
<script type="text/javascript">

</script>
';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($fb)) {
    $pagebody .= $fb;
}

if (! empty($efb)) {
    $pagebody .= $efb;
}

//Check if files appear to be writable, if so warn
if (! hs_file_perm_ok('index.php')) {
    $pagebody .= errorBox(lg_home_admin_permwarning);
}

if (isset($GLOBALS['license']['trial'])) {
    $pagebody .= displaySystemBox('<div style="display:flex;justify-content:space-between;align-items:center;width: 100%;"><div>'.lg_trialexpires.' <b>'.hs_showShortDate($GLOBALS['license']['trial']).'</b></div><a href="'.createStoreLink().'" class="btn inline-action" target="_blank" style="">'.hs_htmlspecialchars(lg_purchase).'</a></div>');
}

if (! hs_empty(hs_setting('cHD_NEWVERSION')) && version_compare(hs_setting('cHD_NEWVERSION'), hs_setting('cHD_VERSION'), '>')) {
    $pagebody .= displaySystemBox('<span style="font-weight:bold;">'.lg_home_admin_newhelpspot.':</span> <b style="color:red;">'.hs_setting('cHD_NEWVERSION').'</b> -
								  <a href="https://store.helpspot.com/files" target="_blank">'.lg_home_admin_download.'</a> -
								  <a href="https://support.helpspot.com/index.php?pg=kb.page&id=14" target="_blank">'.lg_home_admin_instructions.'</a> -
								  <a href="https://www.helpspot.com/releases/category/helpspot-helpdesk" target="_blank">'.lg_home_admin_releasenotes.'</a>');
}

$pagebody .= renderPageheader(lg_home_admin_install.' ('.$GLOBALS['license']['CustomerName'].')');

$pagebody .= '
<div class="card padded">
<div class="yui-g" style="margin-bottom:50px;">
		<div class="yui-g first">
			<div class="yui-u first">
				<label class="datalabel">'.lg_home_admin_customerid.'</label>
				<span class="big-number">'.hs_setting('cHD_CUSTOMER_ID').'</span>
			</div>
			<div class="yui-u">
				<label class="datalabel">'.lg_home_admin_licusers.'</label>
				<span class="big-number">'.($GLOBALS['license']['Users'] == 'unlimited' ? lg_home_admin_unlimited : $staff->RecordCount().'/'.$GLOBALS['license']['Users']).'</span>
				'.(licenseAtUserLimit() ? '<br /><a href="'.createStoreLink().'" class="btn" target="_blank" style="margin-top: 10px;margin-bottom:0px;">'.hs_htmlspecialchars(lg_home_admin_addlicenses).'</a>' : '').'
			</div>
		</div>
		<div class="yui-g first">
			<div class="yui-u first">
				<label class="datalabel">'.lg_home_admin_licsupport.'</label>
				<span class="big-number" style="'.($supportexpired ? 'color:red;' : '').'">'.
                    (subscription()->isFree() ? '<a href="https://discuss.helpspot.com" target="_blank">Forum Only</a>' : hs_showShortDate($GLOBALS['license']['SupportEnds']))
                .'</span>
				'.($supportexpired ? '<br /><a href="'.createStoreLink(true).'" class="btn" target="_blank" style="margin-top: 10px;margin-bottom:0px;">'.hs_htmlspecialchars(lg_home_admin_renew).'</a>' : '').'
			</div>
			<div class="yui-u">
				<label class="datalabel">'.lg_home_admin_version.'</label>
				<span class="big-number" '.(! hs_empty(hs_setting('cHD_NEWVERSION')) && hs_setting('cHD_NEWVERSION') > hs_setting('cHD_VERSION') ? 'style="color:red;"' : '').'>'.hs_setting('cHD_VERSION').'</span>
			</div>
		</div>
</div>';

$pagebody .= '
<form action="'.$basepgurl.'" method="POST" name="license_form" id="license_form" enctype="multipart/form-data">
'.csrf_field().'

    <div class="hr"></div>

    <div class="fr">
        <div class="label">
            <label class="" for="">'.lg_home_admin_licupload.'</label>
        </div>
        <div class="control">
            '.(strpos(cHOST, '://trials.userscape.com') ? '<b>Licenses cannot be uploaded on the hosted trial server.</b>' : '<input type="file" name="license" id="license" style="" onchange="$(\'license_form\').submit();">').'
        </div>
    </div>

    <div class="hr"></div>
</form>

<form action="'.route('maintenance').'" method="POST" onsubmit="return hs_confirm_submit(event, \''.hs_jshtmlentities(lg_home_admin_maintenance_button_conf).'\');">
'.csrf_field().'
<input type="hidden" name="status" value="down" />

    <div class="fr">
        <div class="label">
            <label class="" for="orderBy">'.lg_home_admin_maintenance.'</label>
            <div class="info">'.lg_home_admin_maintenance_desc.'</div>
        </div>
        <div class="control">
            <button class="btn" type="submit" id="maintenance_button">'.lg_home_admin_maintenance_button.'</button>
        </div>
    </div>
</form>';

$pagebody .= '</div>';
