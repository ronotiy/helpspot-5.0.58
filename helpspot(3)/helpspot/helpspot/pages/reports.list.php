<?php

use HS\Domain\Reports\SavedReports;
// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! perm('fModuleReports')) {
    die();
}

//no time limit
set_time_limit(0);

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['reports', 'conditional.ui']);

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/class.reports.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.conditional.ui.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list']);
$pagetitle = lg_reports_title;
$tab = 'nav_reports';
$show = isset($_GET['show']) ? $_GET['show'] : 'report_time_events';
$paginate = isset($_GET['paginate']) && is_numeric($_GET['paginate']) ? $_GET['paginate'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$efb = (session('error'))
    ?  errorBox(session('error'))
    : '';

// Set feedback text
$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

if(isset($_GET['xReport']) and $_GET['xReport'] > 0){
    $report = SavedReports::find((int) $_GET['xReport']);
} else {
    $report = new SavedReports;
}

$ui = new hs_conditional_ui_auto();
$rule = new hs_auto_rule();
$rowid = $ui->generateID('condition');

/*****************************************
PER REPORT OPTIONS
*****************************************/
//Defaults
$option_defaultConditions = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>'-1'];
$option_hide_filters = false;
$option_note = false;
$option_dates = false;
$option_option_box = false;
$option_response_by = false;
$option_unique_by = false;
$option_limit = false;
$option_billable = false;
$option_helpful_type = false;
$fm['fType'] = '1';
$fm['fPermissionGroup'] = 0;
$fm['xPerson'] = auth()->user()->xPerson;
$user = auth()->user()->toArray();
$button = lg_reports_save;
$formaction = 'add';
$resourceid = 0;

// Create a saved report
if($action == 'add' || $action == 'edit'){
	$fm = $_POST; // Probably not ideal but we need all the conditions.
	if (isset($_POST['saveAs']) and $_POST['saveAs'] != '') {
		$action = 'add';
		$fm['title'] = $_POST['saveAs'];
	} else {
		$fm['title'] = $_POST['fm_report'];
	}
	$fm['sFolder'] = $_POST['sFilterFolder'];
	$fm['xReport'] = isset($_GET['xReport']) ? $_GET['xReport'] : null;
	$fm['fType'] = isset($_POST['fType']) ? $_POST['fType'] : 2;
	$fm['sPage'] = isset($_POST['sPage']) ? $_POST['sPage'] : 'reports';
	$fm['sShow'] = isset($_POST['sShow']) ? $_POST['sShow'] : '';
	$fm['fPermissionGroup'] = isset($_POST['fPermissionGroup']) ? $_POST['fPermissionGroup'] : 0;
	$fm['sPersonList'] = isset($_POST['sPersonList']) ? $_POST['sPersonList'] : array();

	// Error checks
	if(hs_empty($fm['title'])) {
		$errors['sReportTitle'] = lg_reports_er_title;
	}

	if(! empty($errors)){
		$formerrors = $errors;
		setErrors($formerrors);
		if(empty($formerrors['errorBoxText'])){
			$formerrors['errorBoxText'] = lg_errorbox;
		}
	} else {
        if ($action == 'add') {
            $id = $report->add($user, $fm);
        } elseif ($action == 'edit') {
            $id = $report->edit($user, $fm);
        }
        return redirect()->route('admin', [
            'pg' => 'reports.list',
            'show' => $fm['sShow'],
            'xReport' => $id,
        ])->with('feedback', lg_reports_reportsaved);
	}
}

//Overrides
if ($_GET['show'] == 'report_time_events') {
	$title = lg_reports_tt_events;
    $option_dates = true;
    $option_option_box = true;
    $option_billable = true;
} elseif ($_GET['show'] == 'report_searches_no_results') {
	$title = lg_report_searches_no_results;
    $option_dates = true;
    $option_hide_filters = true;
} elseif ($_GET['show'] == 'report_searches') {
	$title = lg_report_searches_agg;
    $option_dates = true;
    $option_hide_filters = true;
} elseif ($_GET['show'] == 'report_responses') {
	$title = lg_reports_responses;
    $option_dates = true;
    $option_option_box = true;
    $option_response_by = true;
    $option_hide_filters = true;
} elseif ($_GET['show'] == 'report_customer_activity') {
	$title = lg_reports_customer_activity;
    $option_dates = true;
    $option_option_box = true;
    $option_unique_by = true;
    $option_limit = true;
} elseif ($_GET['show'] == 'report_kb_helpful') {
	$title = lg_reports_kb_helpful;
    $option_helpful_type = true;
    $option_hide_filters = true;
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
if (isset($_GET['xReport'])) { //for saved reports
    $saved_report = $report;
	$saved_report['tData'] = hs_unserialize($saved_report['tData']);
	$rule->SetAutoRule($saved_report['tData']);
	$conditionhtml  = $ui->createConditionsUI($rule);

    $resourceid = $report->xReport;
	$fm_responses_by = $saved_report['tData']['responses_by'];
	$fm_unique_by = $saved_report['tData']['unique_by'];
	$fm_limit = $saved_report['tData']['limit'];
	$fm_billable = $saved_report['tData']['billable'];
	$fm['sReport'] = $saved_report['sReport'];
	$fm['fType'] = $saved_report['tData']['fType'];
	$fm['sFolder'] = $saved_report['sFolder'];
	$fm['fPermissionGroup'] = $saved_report['tData']['fPermissionGroup'];
	$fm['sPersonList'] = $saved_report['tData']['sPersonList'];
	$fm['xPerson'] = $saved_report['xPerson'];
	$formaction = 'edit';
} else {
	$rule->SetAutoRule($option_defaultConditions);
	$conditionhtml  = $ui->createConditionsUI($rule);

	$fm_responses_by = '';
	$fm_unique_by = 'email';
	$fm_limit = '20';
	$fm_billable = 'all';
	$fm['fType'] = '1';
	$fm['fPermissionGroup'] = 0;
	$fm['xPerson'] = 0;
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript .= '
<script src="'.static_url(). '/static/js/highcharts/js/highcharts.js"></script>
<script type="text/javascript" language="JavaScript">
	$jq().ready(function(){
		new Control.Tabs("reporttabs");
		runReport();
		$jq("#export").click(function(e){
		    $jq("#report_form").attr("action","'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'excel', 'show' => $_GET['show']]).'");
			$jq["#report_form"].submit();
			e.preventDefault();
		});
	});

	function runReport(){
		$jq("#graph_wrap_bar").html("<div class=\"loading\">'.lg_loading.'</div>");
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => $_GET['show']]).'",
			type: "post",
			data: $jq("#report_form").serialize(),
			cache: false,
			error: function(){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).'",{title:lg_js_error});
			},
			success: function(response){
				//Set desc
				$jq("#graph_wrap_bar").html(response);
			}
		});
	}

	var allowSubmit = false;
	function checkform(){
		if(allowSubmit === true){
			return true;
		}else{
			return false;
		}
	}
</script>';

/*****************************************
SELECTS
*****************************************/
$allStaff = apiGetAllUsersComplete();
$staff_select = '<option value="" '.selectionCheck($v['xPerson'], $fm_responses_by).'>'.hs_htmlspecialchars(lg_reports_response_anystaff).'</option>';
foreach ($allStaff as $k=>$v) {
    $staff_select .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $fm_responses_by).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
}


$buttons = '
	<div class="button-bar space">
		<button type="submit" name="submit" id="submit" class="btn accent">' . $button . '</button>';
if (isset($_GET['xReport']) && $report->canEdit($user, $fm)) {
	$buttons .= '<button type="button" id="delete_btn" class="btn altbtn" onclick="deleteReport(' . $_GET['xReport'] . ');">' . lg_reports_deletereport . '</button>';
}
$buttons .= '</div>';

/*****************************************
PAGE OUTPUTS
*****************************************/
if(!empty($formerrors)) {
    $efb = $efb . errorBox($formerrors['errorBoxText']);
}

$pagebody .= renderPageheader((isset($saved_report['sReport']) ? $saved_report['sReport'].' ('.$title.')' : $title), '<div id="report_desc"></div>');

$pagebody .= '<form action="'.$basepgurl.'&amp;action='.$formaction.'&amp;xReport='.$resourceid.'" method="POST" name="report_form" id="report_form" enctype="multipart/form-data" accept-charset="UTF-8">';
$pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any
$pagebody .= csrf_field().'<input type="hidden" name="report_list" value="true">';

$pagebody .= '
<div id="report_wrap">
	<div class="tab_wrap">
		<ul class="tabs" id="reporttabs">
			<li><a href="#graph_tab" class="active"><span>' . lg_reports_graph . '</span></a></li>
			<li><a href="#save_tab"><span>' . lg_reports_customize . '</span></a></li>
		</ul>

		<div id="graph_tab" class="padded" name="graph_tab">
			<div id="graph_wrap_bar" class="reportlist" style="margin-top: 14px;"></div>

			' . ($option_note ? '<div style="margin-top:15px;">' . displaySystemBox($option_note) . '</div>' : '') . '

			<div class="card padded">
				<div style="display:flex;flex-direction:column;">
					<div style="display:flex;justify-content:flex-start;align-items:center;" class="graph_options">
					' . ($option_dates ? '
							<div>
								<label class="datalabel">'.lg_reports_from.'</label>
								'.calinput('from', reports::repCreateFromDT()).'
							</div>
							<div>
								<label class="datalabel">'.lg_reports_to.'</label>
								'.calinput('to', reports::repCreateToDT()).'
							</div>
							<div class="time_or"><label class="datalabel">&nbsp;</label>'.lg_or.'</div>
							<div>
								<label class="datalabel">&nbsp;</label>
								'.hs_ShowMonthQuickDrop('from', 'to').'
							</div>

						' : '') . '

						' . ($option_helpful_type ? '
						<div>
							<label for="helpful_type" class="datalabel">' . lg_reports_kb_helpful_orderby . '</label>
							<select name="helpful_type" id="helpful_type" onchange="">
								<option value="helpful" ' . selectionCheck('helpful', $fm_helpful_type) . '>' . lg_reports_kb_helpful_type_helpful . '</option>
								<option value="not_helpful" ' . selectionCheck('not_helpful', $fm_helpful_type) . '>' . lg_reports_kb_helpful_type_not . '</option>
							</select>
						</div>
						' : '') . '
					</div>

				' . ($option_option_box ? '
					<div style="display:flex;justify-content:flex-start;align-items:center;margin-top:14px;" class="graph_options">
								' . ($option_response_by ? '
								<div>
									<label for="responses_by" class="datalabel">' . lg_reports_response_by . '</label>
									<select name="responses_by" id="responses_by">
										' . $staff_select . '
									</select>
								</div>
								' : '') . '

								' . ($option_unique_by ? '
								<div>
									<label for="unique_by" class="datalabel">' . lg_reports_customer_uniqueby . '</label>
									<select name="unique_by" id="unique_by">
										<option value="sEmail" ' . selectionCheck('sEmail', $fm_unique_by) . '>' . lg_reports_customer_unique_email . '</option>
										<option value="sUserId" ' . selectionCheck('sUserId', $fm_unique_by) . '>' . lg_reports_customer_unique_id . '</option>
										<option value="fullname" ' . selectionCheck('fullname', $fm_unique_by) . '>' . lg_reports_customer_unique_name . '</option>
									</select>
								</div>
								' : '') . '

								' . ($option_limit ? '
								<div>
									<label for="limit" class="datalabel">' . lg_reports_limit . '</label>
									<select name="limit" id="limit">
										<option value="20" ' . selectionCheck('20', $fm_limit) . '>20</option>
										<option value="50" ' . selectionCheck('50', $fm_limit) . '>50</option>
										<option value="200" ' . selectionCheck('200', $fm_limit) . '>200</option>
										<option value="500" ' . selectionCheck('500', $fm_limit) . '>500</option>
									</select>
								</div>
								' : '') . '

								' . ($option_billable ? '
								<div>
									<label for="billable" class="datalabel">' . lg_reports_billable . '</label>
									<select name="billable" id="billable" onchange="">
										<option value="all" ' . selectionCheck('all', $fm_billable) . '>' . lg_reports_billable_all . '</option>
										<option value="billable" ' . selectionCheck('billable', $fm_billable) . '>' . lg_reports_billable_is . '</option>
										<option value="not_billable" ' . selectionCheck('not_billable', $fm_billable) . '>' . lg_reports_billable_isnt . '</option>
									</select>
								</div>
								' : '') . '

					</div>
					' : '') . '
				</div>

				' . (!$option_hide_filters ? '
					<div>
					' . displayContentBoxTop(lg_reports_filters, '', '', '100%', '', 'box_body_solid') . '
						<div id="cond_wrapper">' . $conditionhtml . '</div>

						<div class="condition-menu" style="padding-left:0px;">
							<img src="' . static_url() . '/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="' . lg_conditional_addcon . '" title="' . lg_conditional_addcon . '"
								onClick="' . hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_condition') . '">
						</div>
					' . displayContentBoxBottom() . '
					</div>
				' : '') . '
			</div>

			<div class="button-bar">
				<button type="button" name="" class="btn accent" onclick="runReport();">' . lg_reports_run . '</button>
				<button name="export" id="export" class="btn">' . hs_htmlspecialchars(lg_reports_export) . '</button>
			</div>

		</div>
		<div id="save_tab" class="padded" name="save_tab" style="display:none;">
			' . chartSave($resourceid, $fm, $report->folders($user), $buttons, $_GET['pg'], $_GET['show'], $report) . '
		</div>
	</div>
</div>
';

$pagebody .= '</form>';
