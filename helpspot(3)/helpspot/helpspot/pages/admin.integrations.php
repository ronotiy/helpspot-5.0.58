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
LIBS
*****************************************/
require_once(cBASEPATH.'/helpspot/lib/api.thermostat.lib.php');
/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = cHOST.'/admin?pg=admin.integrations';
$pagetitle = lg_admin_integrations_title;
$tab = 'nav_admin';
$subtab = '';
$error		  = '';
$thermostatToken = (apiGetThermostatToken()) ? lg_admin_thermostat_token_value : '';

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$efb = (session('error'))
    ?  errorBox(session('error'))
    : '';

/*****************************************
ACTIONS
*****************************************/
if( isset($_POST['action']) ) {
    if( $_POST['action'] == 'thermostat' ) {
        apiSetThermostatToken($_POST['cHD_THERMOSTAT_TOKEN']);
        return redirect()->route('admin', [
            'pg' => 'admin.integrations',
        ])->with('feedback', 'Thermostat API Token Added');
    }
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any
$pagebody .= '
    <div class= "padded">
	'.displayContentBoxTop(lg_admin_integrations_header. '<img src="'.static_url().'/static/css/shared/general/zapier-logo.png" height="25" style="margin-bottom: -6px;" />', '', '', '100%', '').'
		<ul>
			<li>Notify your team on new requests via Slack, Twilio, and more.</li>
			<li>Update contacts in your Salesforce, Sugar, or other CRMs.</li>
			<li>Create requests in HelpSpot from forms in Wordpress, Wufoo, and others.</li>
			<li><strong>Connect HelpSpot to your other systems, no coding required!</strong></li>
		</ul>
    '.displayContentBoxBottom('<a href="https://zapier.com/apps/helpspot/integrations" class="btn accent" target="_blank">'.lg_admin_integrations_start_zapier.'</a> <a href="https://support.helpspot.com/index.php?pg=kb.page&id=554" class="btn" target="_blank">'.lg_admin_integrations_docs.'</a>').'
    </div>
';
$pagebody .= '
<form action="'.$basepgurl.'" method="POST">
    '.csrf_field(). '
    <div class= "padded">
    '.displayContentBoxTop(lg_admin_thermostat_header. '<img src="'.static_url().'/static/img5/thermostat.png" style="width:100px;" />', '', '', '100%', 'thermostat-header'). '
    <table class="ft">
        <tbody>
            <tr class="trr">
                <td class="tdl">
                    <label class="datalabel" for="cHD_THERMOSTAT_TOKEN">'.lg_admin_thermostat_label_api_token.'</label>
                </td>
                <td class="tdr">
                    <textarea name="cHD_THERMOSTAT_TOKEN" id="cHD_THERMOSTAT_TOKEN" rows="10" cols="60" '.$thermostatToken.'></textarea>
                </td>
            </tr>
        </tbody>
    </table>
    '.displayContentBoxBottom('<button class="btn accent">'.lg_admin_thermostat_connect.'</button> <a href="https://thermostat.io/account#api" class="btn" target="_blank">'.lg_admin_thermostat_get_token.'</a> <a href="https://thermostat.io" class="btn" target="_blank">'.lg_admin_thermostat_learn_about.'</a>'). '
    </div>
<input type="hidden" name="action" value="thermostat" />
</form>
';
?>
