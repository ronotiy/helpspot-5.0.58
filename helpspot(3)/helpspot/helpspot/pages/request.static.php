<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
require_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
require_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
require_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';
require_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['request']);

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request.static']);
//Make sure /admin doesn't output any headers
$pagebody = '';
$tab = 'nav_workspace';
$subtab = '';
$htmldirect = 1;
$menu = '';
$reqid = isset($_GET['reqid']) ? $_GET['reqid'] : '';
$from_streamview = isset($_GET['from_streamview']) ? $_GET['from_streamview'] : false;
if(isset($_GET['from_history_search'])){
	$from_history_search = $_GET['from_history_search'];
}else{
	$from_history_search = false;
}

// Get Request
$req = apiGetRequest($reqid);

// Secure from guests
if (perm('fCanViewOwnReqsOnly') && !empty($reqid)){
    if($req['xPersonAssignedTo'] != $user['xPerson']){
        return redirect()->action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace']);
    }
}
// If we're in limited access mode make sure only a valid user can see the request
if(perm('fLimitedToAssignedCats')){
    $cats = apiGetUserCats($user['xPerson']);
    if(!in_array($req['xCategory'],$cats)){
        return redirect()->action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace']);
    }
}

//setups
$cats = apiGetAllCategoriesComplete();
$cats = rsToArray($cats, 'xCategory', false);
$fmReportingTags = apiGetRequestRepTags($reqid);

$status = isset($GLOBALS['reqStatus'][$req['xStatus']]) ? $GLOBALS['reqStatus'][$req['xStatus']] : '';
$category = isset($cats[$req['xCategory']]['sCategory']) ? $cats[$req['xCategory']]['sCategory'] : '';

$reptags = '';
foreach ($fmReportingTags as $k=>$v) {
    $reptags .= hs_htmlspecialchars($v).'<br>';
}

//Custom fields
$cfs = '';
//create list of categories so we can only show relevant CF's for this requests category
if (isset($cats[$req['xCategory']])) {
    $category_cfs = hs_unserialize($cats[$req['xCategory']]['sCustomFieldList']);
} else {
    $category_cfs = [];
}

if (is_array($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $fvalue) {
        if ($fvalue['isAlwaysVisible'] || in_array($fvalue['fieldID'], $category_cfs)) { //visibility check
            $fid = 'Custom'.$fvalue['fieldID'];
            if ($fvalue['fieldType'] == 'checkbox') {
                $v = boolShow($req[$fid], lg_checked, lg_notchecked);
            } elseif ($fvalue['fieldType'] == 'date') {
                $v = $req[$fid] == 0 ? '' : hs_showShortDate($req[$fid]);
            } elseif ($fvalue['fieldType'] == 'datetime') {
                $v = $req[$fid] == 0 ? '' : hs_showDate($req[$fid]);
            } elseif ($fvalue['fieldType'] == 'decimal') {
                $v = $req[$fid] == 0 ? '' : $req[$fid];
            } else {
                $v = $req[$fid];
            }
            $cfs .= '<tr valign="top"><td><label class="datalabel">'.hs_htmlspecialchars($fvalue['fieldName']).'</label>
								<span>'.fillEmpty(cfDrillDownFormat(hs_htmlspecialchars($v))).'</span></td></tr>';
        }
    }
}

$requestpush = '';
$pushlist = showPushesByReq($reqid);
if ($pushlist) {
    $requestpush = '
	<table style="width:100%;">
		<tr valign="top">
			<td style="padding-left:20px;padding-right:20px;">
				<div class="nice-line"></div>
			</td>
		</tr>
		<tr valign="top">
			<td>
				<label class="datalabel">'.lg_request_reqpush.'</label>
				<span>'.$pushlist.'</span>
			</td>
		</tr>
	</table>';
}

$thermoResponse = apiGetThermostatResponse($reqid);
$thermostat = '';

if ($thermoResponse) {
    $thermostat = displayContentBoxTop('<img src="'.static_url().'/static/img5/thermostat.png" style="height:20px; position: relative; top: 2px;" />&nbsp;');
    $thermostat .= '<div class="thermo-response thermo-response-'.apiGetResponseType($thermoResponse['iScore'], $thermoResponse['type']).'">
        <div class="thermo-survey-type">'.$thermoResponse['type'].'</div>
		<div class="thermo-response-pill">
			<span>'.$thermoResponse['iScore'].'</span>
		</div>
		<div class="thermo-response-feedback">'.hs_htmlspecialchars($thermoResponse['tFeedback']). '</div>
		<div class="thermo-response-link"><a href="https://thermostat.io/survey/manage/' . $thermoResponse['xSurvey'] . '#results">'.lg_thermostat_label_see_results.'</a></div>
	</div>';
    $thermostat .= displayContentBoxBottom();
}

/*****************************************
JAVASCRIPT
*****************************************/
//Email errors
$headscript = '';
if (isset($_GET['emailerror'])) {
    $headscript = '
		<script type="text/javascript" language="JavaScript">
		$jq().ready(function(){
			hs_alert("'.hs_jshtmlentities($_GET['emailerror']).'");
		});
		</script>';
}

/*****************************************
PAGE OUTPUTS
*****************************************/
if ($from_history_search) {
    $pagebody .= displaySimpleHeader();
} elseif ($from_streamview) {
    //No header
} else {
    //Set onclick for inline images
    $headscript .= '<script type="text/javascript" language="JavaScript">
	$jq().ready(function(){
		$jq(".note-stream-item-inline-img").live("click",function(){
			var url = $jq(this).prop("src") + "&showfullsize=1";
			var modal = initModal({
				footer: false,
				closeMethods: ["overlay", "button", "escape"],
				html: "<img src=\'" + url + "\' style=\'max-width: 100%;\'>"
			});
		});
	});
	</script>
	';
    $pagebody .= displayHeader($reqid.' - '.lg_request_pagetitleedit, 'default', 'nav_workspace', $headscript);
}

if (! $req) {
    //Invalid reqid page

    $pagebody .= '<div class="unknown_reqid">'.lg_request_unkownid.'</div>';
} else {
    if(\HS\Domain\Workspace\Request::reachedHistoryLimit($reqid)) {
        $pagebody .= displaySystemBox(lg_request_fb_history_limit);
    }
    $tttable = '';
    $rows = apiGetTimeForRequest($reqid);
    if (hs_rscheck($rows) && $rows->RecordCount() > 0) {
        $tttable .= '<div id="time_body">';
        $tttable .= renderTimeTrackerTable($rows, 0, false);
        $tttable .= '</div>';
    }

    $takeIt = '';
    if ((int) $req['xPersonAssignedTo'] == 0 && ($from_history_search or $from_streamview)) {
        $takeIt = '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $req['xRequest'], 'frominbox' => 1, 'rand' => time()]).'" class="btn accent inline-action" style="align-self: center; margin-left: 10px;" id="takeitfilter-'.$req['xRequest'].'">'.lg_lookup_filter_takeit.'</a>';
	}

	$leftHeader = '';
	if ($from_streamview) {
		$leftHeader = '<input type="checkbox" value="1" class="form-checkbox js-select-request" style="width: 30px;height: 30px;" /><span style="margin-left: 10px; font-size: 22px;font-weight:bold;"><a href=/admin?pg=request&reqid='.$reqid.'>' . $reqid . '</a></span> ';
		$menu .= '<span class="prevNext"></span>';
	}
	else
	{
		$leftHeader = '<span style="margin-left: 10px; font-size: 22px;font-weight:bold;">' . $reqid . '</span> ';

	}

    $menu .= '
            '.$takeIt.'
            '.($req['fOpen'] == 0 && ! $from_history_search && ! $from_streamview ? '
                <div class="table-top-menu">
                    <form action="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid]).'" style="align-self: center;" method="post">
						'.csrf_field(). '
						<input type="hidden" name="reopen" value="1" />
                        <button type="submit" class="btn accent inline-action" style="margin-right:15px;">'.lg_request_reopen.'</button>
                    </form>
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'prev' => 'true']).'" class="" ><img src="'.static_url().'/static/img5/navigate-back.svg" style="height:24px;" title="'.lg_prev.'" /></a>
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'next' => 'true']).'" class=""><img src="'.static_url().'/static/img5/navigate-forward.svg" style="height:24px;" title="'.lg_next.'" /></a>
                </div>
                ' : '');

    $pagebody .= renderPageheader($leftHeader  . ($req['fOpen'] == 1 ? '' : '<span class="page-header-label">'.lg_request_closed.'</span>'), $menu);

    $allStaff = apiGetAssignStaff();

    $pagebody .= '
		<div style="display:flex;padding: 20px 0;">
			<div style="flex:3">
				'.renderRequestHistory($reqid, $allStaff, $req, true, false, false, false, $from_streamview).'
				'.$tttable.'
			</div>
			<div style="flex:1;padding-left:17px;">
				<table class="request-static-meta">
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_customer.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($req['sFirstName'].' '.$req['sLastName'])).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_email.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($req['sEmail'])).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_phone.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($req['sPhone'])).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_custid.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($req['sUserId'])).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_contactedvia.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($GLOBALS['openedVia'][$req['fOpenedVia']])).'</span>
						</td>
					</tr>

					<tr valign="top">
						<td style="padding-left:20px;padding-right:20px;">
							<div class="nice-line"></div>
						</td>
					</tr>

					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_status.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($status)).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_category.'</label>
							<span>'.fillEmpty(hs_htmlspecialchars($category)).'</span>
						</td>
					</tr>
					<tr valign="top">
						<td>
							<label class="datalabel">'.lg_request_reportingtags.'</label>
							<span>'.fillEmpty($reptags).'</span>
						</td>
					</tr>

					<tr valign="top">
						<td style="padding-left:20px;padding-right:20px;">
							<div class="nice-line"></div>
						</td>
					</tr>

					'.$cfs.'
				</table>

				'.$requestpush.'
				'.$thermostat.'
			</div>
		</div>
	';
}

if ($from_history_search) {
    $pagebody .= '<form action="" name="requestform" id="requestform">';
    $pagebody .= csrf_field();

    $tcust = isset($req['sUserId']) ? hs_jshtmlentities($req['sUserId']) : '';
    $tfname = isset($req['sFirstName']) ? hs_jshtmlentities($req['sFirstName']) : '';
    $tlname = isset($req['sLastName']) ? hs_jshtmlentities($req['sLastName']) : '';
    $temail = isset($req['sEmail']) ? hs_jshtmlentities($req['sEmail']) : '';
    $tphone = isset($req['sPhone']) ? hs_jshtmlentities($req['sPhone']) : '';
    $pagebody .= '
		<input type="hidden" name="xRequest" id="xRequest" value="'.$reqid.'">
		<input type="hidden" name="sUserId" id="sUserId" value="'.$tcust.'">
		<input type="hidden" name="sFirstName" id="sFirstName" value="'.$tfname.'">
		<input type="hidden" name="sLastName" id="sLastName" value="'.$tlname.'">
		<input type="hidden" name="sEmail" id="sEmail" value="'.$temail.'">
		<input type="hidden" name="sPhone" id="sPhone" value="'.$tphone.'">
	';

    $pagebody .= '</form>';
}

if ($from_history_search) {
    $pagebody .= displaySimpleFooter();
} elseif ($from_streamview) {
    //No footer
} else {
    $pagebody .= displayFooter();
}
