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
$GLOBALS['lang']->load(['conditional.ui', 'reports']);

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/class.reports.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.conditional.ui.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl	  = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.matrix']);
$pagetitle 	  = lg_reports_title;
$tab	   	  = 'nav_reports';
$show 		  = 'report_matrix';
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
$dt_from = \Carbon\Carbon::now();
$dt_to = \Carbon\Carbon::now();

/*****************************************
PER REPORT OPTIONS
*****************************************/
//Defaults
$option_defaultConditions = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>'-1'];
$option_bizhours = true;
$option_based_on = false;
$option_extra_groups = false;
$option_grouping = true;
$option_label_limit = false;
$option_note = false;
$option_grouping_reqhistory = false;
$option_hide_filters = false;
$option_grouping_tracker = false;
$option_speedby = false;
$option_billable = false;
$secondary_button = save_as_button(lg_saveas, 'Select a name for the new report', 'saveAs', 'fm_report', '280', 'bottomMiddle', '', '$jq(\'#report_form\').submit()');
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
	$fm['yaxis'] = isset($_POST['yaxis']) ? $_POST['yaxis'] : '';
	$fm['xaxis'] = isset($_POST['xaxis']) ? $_POST['xaxis'] : '';
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
            'pg' => 'reports.matrix',
            'show' => $fm['sShow'],
            'xReport' => $id,
        ])->with('feedback', lg_reports_reportsaved);
	}
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
if(isset($_GET['xReport'])){ //for saved reports
    $saved_report = $report;
	$saved_report['tData'] = hs_unserialize($saved_report['tData']);
	$rule->SetAutoRule($saved_report['tData']);
	$conditionhtml  = $ui->createConditionsUI($rule);

	//Setup other saved values
	$button = lg_reports_resave;
	$formaction = 'edit';
	$resourceid = $_GET['xReport'];
	$fm_report = $saved_report['sReport'];
	$fm_yaxis_grouping = $saved_report['tData']['yaxis'];
	$fm_xaxis_grouping = $saved_report['tData']['xaxis'];
	$fm_usebizhours = $saved_report['tData']['usebizhours'];
	$fm_date_type = $saved_report['tData']['date_type'];
	$fm_option_group_above = $saved_report['tData']['option_group_above'];
	$fm_speedby = $saved_report['tData']['speedby'];
	$fm_billable = $saved_report['tData']['billable'];
	$fm_active_only = $saved_report['tData']['active_only'];
	$fm['sReport'] = $saved_report['sReport'];
	$fm['fType'] = $saved_report['tData']['fType'];
	$fm['sFolder'] = $saved_report['sFolder'];
	$fm['fPermissionGroup'] = $saved_report['tData']['fPermissionGroup'];
	$fm['sPersonList'] = $saved_report['tData']['sPersonList'];
	$fm['xPerson'] = $saved_report['xPerson'];
}else{
	$rule->SetAutoRule($option_defaultConditions);
	$conditionhtml  = $ui->createConditionsUI($rule);

	//Setup other values
	$button = lg_reports_save;
	$delbutton = '';
	$formaction = 'add';
	$resourceid = 0;
	$fm_yaxis_grouping = 'xCategory';
	$fm_xaxis_grouping = 'day';
	$fm_usebizhours = 1;
	$fm_date_type = 'open';
	$fm_option_group_above = 20;
	$fm_speedby = 'hour';
	$fm_billable = 'all';
	$fm_active_only = 'yes';
	$fm['fType'] = '1';
	$fm['fPermissionGroup'] = 0;
	$fm['xPerson'] = 0;
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script src="'.static_url().'/static/js/highcharts/js/highcharts.js"></script>
<script src="'.static_url().'/static/js/highcharts/js/modules/heatmap.js"></script>
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

	function viewRequests(){
		//Change form action to search page
		$jq("#report_form").attr("action","'.cHOST.'/admin?pg=search&reports=1");

		//Submit
		allowSubmit = true;
		$jq("#report_form").submit();
	}

	function runReport(){
		$jq("#graph_wrap_bar").html("<div class=loading style=\'text-align: center;font-size: 24px;color: rgba(51,51,51,0.3);\'>'.lg_loading.'</div>");

		$jq.ajax({
			url: "admin?pg=ajax_gateway&action=report_data&show='.$show.'",
			type: "post",
			data: $jq("#report_form").serialize(),
			cache: false,
			error: function(xhr){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).': "+xhr.statusText,{title:lg_js_error});
			},
			success: function(response){

				displayChart(response, "#graph_wrap_bar", "'.lg_reports_matrix.'");
				buildDataTable(response, "#data_tab_report", "'.lg_reports_matrix.'", "'.$show.'");

			}
		});
	}

	function displayChart(response, element, title){

		// Set default height of graph element (in case something below changes this)
		$jq("#graph_wrap_bar").height("700px");

		// If one of the axes have no data show a message
		if(response.x_categories.length == 0)
		{
			$jq("#graph_wrap_bar").height("300px");
			$jq(element).html("<div class=matrix_no_results>'.lg_no.' '.lg_reports_xaxis.'</div>");

			return;
		}
		else if(response.x_categories.length == 0)
		{
			$jq("#graph_wrap_bar").height("300px");
			$jq(element).html("<div class=matrix_no_results>'.lg_no.' '.lg_reports_yaxis.'</div>");

			return;
		}

		$jq(element).highcharts({
	        chart: {
	            type: "heatmap",
	            backgroundColor: "transparent",
		        style: {
		            fontFamily: "Inter,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji"
		        },
	        },
	        title: {
	            text: "",
	            style: {
	            	color: "'.(inDarkMode() ? '#cfd0d1' : '#737373').'"
	            },
	            align: "left",
	        },
	        legend: {
	            enabled: false
	        },
	        credits: {
	        	enabled: false
	        },
	        colorAxis: {
	            min: 0,
	            minColor: "#FFFFFF",
	            maxColor: Highcharts.getOptions().colors[0]
	        },
	        xAxis: {
				categories: response.x_categories,
				labels: {
					style: {
						color: "'.(inDarkMode() ? '#cfd0d1' : '#737373').'"
					}
				 },
	            title: {
	                enabled: false
	            }
	        },
	        yAxis: {
				categories: response.y_categories,
				labels: {
					style: {
						color: "'.(inDarkMode() ? '#cfd0d1' : '#737373').'"
					}
				 },
	            title: {
	                enabled: false
	            }
	        },
	        tooltip: {
	            formatter: function () {
	                return "<b>" + this.point.value + "</b> Requests match<br><b>" +
	                    this.series.xAxis.categories[this.point.x] + "</b> and <b>" + this.series.yAxis.categories[this.point.y] + "</b>";
	            },
				backgroundColor: "#fff",
				shadow: false,
				borderRadius: 1,
				borderColor: "#737373",
				style: {
					color: "#3a2d23",
					padding: 12
				}
	        },
	        series: [{
	        	data: response.data_points,
	        	dataLabels: {
                	enabled: true,
                	color: "#222"
            	}
	        }],
	    });
	}

	function buildDataTable(response, element, title, show){
		var table = "";

		table += \'<table cellpadding="0" cellspacing="0" border="0" class="tablebody" id="report_table">\';

		//Headers
		table += \'<tr class="tableheaders">\';
			table += \'<td style="min-width:150px;">&nbsp;</td>\';
			for(i=0;i< response.x_categories.length;i++)
			{
				table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.x_categories[i]+\'</td>\';
			}
			table += \'<td>'.lg_reports_total.'</td>\';
		table += \'</tr>\';

		//Data
		var j=0;
		for(var y=response.grid_points.length-1;y >= 0;y--){
			table += \'<tr class="\'+(j % 2 ? "tablerowon" : "tablerowoff")+\'">\';
			table += \'<td class="tcell" style="min-width:150px;">\'+ response.y_categories[y] +\'</td>\';

			for(var x=0;x < response.grid_points[y].length;x++){
				table += \'<td class="tcell tcell-center report-col-width">\'+ response.grid_points[y][x] +\'</td>\';
			}

			table += \'<td><b>\'+ response.totals["y"][y] +\'</b></td>\';

			table += \'</tr>\';
			j++;
		}

		// Output the totals row
		table += \'<tr class="\'+(j % 2 ? "tablerowon" : "tablerowoff")+\'">\';
		table += \'<td class="tcell" style="min-width:150px;"><b>'.lg_reports_total.'</b></td>\';

		for(var x=0;x < response.totals["x"].length;x++){
			table += \'<td class="tcell tcell-center report-col-width"><b>\'+ response.totals["x"][x] +\'</b></td>\';
		}

		table += \'<td></td>\';

		table += \'</tr>\';

		//Close table
		table += "</table>";

		$jq(element).html(table);
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

function cf_grouping($grouping)
{
    $cf_grouping = '';

    if (is_array($GLOBALS['customFields'])) {
        $cf_options = '';
        foreach ($GLOBALS['customFields'] as $fvalue) {
            if ($fvalue['fieldType'] != 'checkbox' and $fvalue['fieldType'] != 'datetime' and $fvalue['fieldType'] != 'lrgtext') {
                $cf_options .= '<option value="Custom'.$fvalue['fieldID'].'" '.selectionCheck('Custom'.$fvalue['fieldID'], $grouping).'>'.$fvalue['fieldName'].'</option>';
            }
        }
        if ($cf_options) {
            $cf_grouping = '<optgroup label="'.lg_reports_grouping_customfields.'">'.$cf_options.'</optgroup>';
        }
    }

    return $cf_grouping;
}

function reptags_grouping($grouping)
{
    $reptags_grouping = '';
    $categories = apiGetAllCategories(0, false);

    if ($categories) {
        $reptags_options = '';
        while ($cat = $categories->FetchRow()) {
            $reptags_options .= '<option value="category_tags_'.$cat['xCategory'].'" '.selectionCheck('category_tags_'.$cat['xCategory'], $grouping).'>'.(! hs_empty($cat['sCategoryGroup']) ? $cat['sCategoryGroup'].': ' : '').$cat['sCategory'].'</option>';
        }
        if ($reptags_options) {
            $reptag_grouping = '<optgroup label="'.lg_reports_grouping_catreptags.'">'.$reptags_options.'</optgroup>';
        }
    }

    return $reptag_grouping;
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

$pagebody .= renderPageheader((isset($saved_report['sReport']) ? $saved_report['sReport'].' ('.lg_reports_matrix.')' : lg_reports_matrix), '<div id="report_desc"></div>');

$pagebody .= '<form action="'.$basepgurl.'&amp;action='.$formaction.'&amp;xReport='.$resourceid.'" method="POST" name="report_form" id="report_form" enctype="multipart/form-data" accept-charset="UTF-8">';
$pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any
$pagebody .= csrf_field().'<input type="hidden" id="report_show" name="report" value="'.$show.'">';

$pagebody .= '
<div id="report_wrap">
	<div id="report_desc"></div>
	<div class="tab_wrap">
		<ul class="tabs" id="reporttabs">
			<li><a href="#graph_tab" class="active"><span>'.lg_reports_graph.'</span></a></li>
			<li><a href="#data_tab"><span>'.lg_reports_data. '</span></a></li>
			<li><a href="#save_tab"><span>' . lg_reports_customize . '</span></a></li>
		</ul>

		<div id="graph_tab" name="graph_tab" class="padded" style="margin-top: 14px;">
			<div id="graph_wrap">
				<div id="graph_wrap_bar" style="height:700px;">
					<div class="loading">'.lg_loading.'</div>
				</div>
			</div>

			' . ($option_note ? '<div style="margin-top:15px;">' . displaySystemBox($option_note, 'hdsystembox-report') . '</div>' : '') . '

			<div class="card padded">
				<div style="display:flex;flex-direction:column;">
					<div style="display:flex;justify-content:flex-start;align-items:center;margin-bottom:14px;" class="graph_options">
						<div>
							<label class="datalabel">'.lg_reports_from.'</label>
							'.calinput('from', reports::repCreateFromDT($dt_from->subDays(14)->setTime(0, 0, 0)->timestamp)).'
						</div>
						<div>
							<label class="datalabel">'.lg_reports_to.'</label>
							'.calinput('to', reports::repCreateToDT($dt_to->modify('today')->setTime(23, 59, 59)->timestamp)).'
						</div>
						<div class="time_or"><label class="datalabel">&nbsp;</label>'.lg_or.'</div>
						<div>
							<label class="datalabel">&nbsp;</label>
							'.hs_ShowMonthQuickDrop('from', 'to').'
						</div>

					</div>


					<div style="display:flex;justify-content:flex-start;align-items:center;" class="graph_options">
								<div>
									<label for="yaxis" class="datalabel">'.lg_reports_yaxis.'</label>
									<select name="yaxis" id="yaxis">
										<option value="hour" '.selectionCheck('hour', $fm_yaxis_grouping).'>'.lg_reports_grouping_opendate_hour.'</option>
										<option value="day" '.selectionCheck('day', $fm_yaxis_grouping).'>'.lg_reports_grouping_opendate_day.'</option>
										<option value="month" '.selectionCheck('month', $fm_yaxis_grouping).'>'.lg_reports_grouping_opendate_month.'</option>
										<option value="year" '.selectionCheck('year', $fm_yaxis_grouping).'>'.lg_reports_grouping_opendate_year.'</option>
										<optgroup label="'.lg_reports_grouping_reqdetails.'">
											<option value="xCategory" '.selectionCheck('xCategory', $fm_yaxis_grouping).'>'.lg_reports_grouping_category.'</option>
											<option value="xStatus" '.selectionCheck('xStatus', $fm_yaxis_grouping).'>'.lg_reports_grouping_status.'</option>
											<option value="xPersonAssignedTo" '.selectionCheck('xPersonAssignedTo', $fm_yaxis_grouping).'>'.lg_reports_grouping_assigned.'</option>
											<option value="fOpenedVia" '.selectionCheck('fOpenedVia', $fm_yaxis_grouping).'>'.lg_reports_grouping_contactvia.'</option>
											<option value="xOpenedViaId" '.selectionCheck('xOpenedViaId', $fm_yaxis_grouping).'>'.lg_conditional_at_mailbox.'</option>
											<option value="xPortal" '.selectionCheck('xPortal', $fm_yaxis_grouping).'>'.lg_conditional_at_portal.'</option>
										</optgroup>
										'.cf_grouping($fm_yaxis_grouping).reptags_grouping($fm_yaxis_grouping).'
									</select>
								</div>
								<div>
									<label for="xaxis" class="datalabel">'.lg_reports_xaxis.'</label>
									<select name="xaxis" id="xaxis">
										<option value="hour" '.selectionCheck('hour', $fm_xaxis_grouping).'>'.lg_reports_grouping_opendate_hour.'</option>
										<option value="day" '.selectionCheck('day', $fm_xaxis_grouping).'>'.lg_reports_grouping_opendate_day.'</option>
										<option value="month" '.selectionCheck('month', $fm_xaxis_grouping).'>'.lg_reports_grouping_opendate_month.'</option>
										<option value="year" '.selectionCheck('year', $fm_xaxis_grouping).'>'.lg_reports_grouping_opendate_year.'</option>
										<optgroup label="'.lg_reports_grouping_reqdetails.'">
											<option value="xCategory" '.selectionCheck('xCategory', $fm_xaxis_grouping).'>'.lg_reports_grouping_category.'</option>
											<option value="xStatus" '.selectionCheck('xStatus', $fm_xaxis_grouping).'>'.lg_reports_grouping_status.'</option>
											<option value="xPersonAssignedTo" '.selectionCheck('xPersonAssignedTo', $fm_xaxis_grouping).'>'.lg_reports_grouping_assigned.'</option>
											<option value="fOpenedVia" '.selectionCheck('fOpenedVia', $fm_xaxis_grouping).'>'.lg_reports_grouping_contactvia.'</option>
											<option value="xOpenedViaId" '.selectionCheck('xOpenedViaId', $fm_xaxis_grouping).'>'.lg_conditional_at_mailbox.'</option>
											<option value="xPortal" '.selectionCheck('xPortal', $fm_xaxis_grouping).'>'.lg_conditional_at_portal.'</option>
										</optgroup>
										'.cf_grouping($fm_xaxis_grouping).reptags_grouping($fm_xaxis_grouping).'
									</select>
								</div>
								<div>
									<label for="active_only" class="datalabel">'.lg_reports_active_only.'  <a href="" id="active_exp_link" class="actionlink" onclick="return false;">[?]</a></label>
									<select name="active_only" id="yaxis">
										<option value="1" '.selectionCheck('1', $fm_active_only).'>'.lg_yes.'</option>
										<option value="0" '.selectionCheck('0', $fm_active_only).'>'.lg_no. '</option>
									</select>
								</div>
					</div>
				</div>


				<div class="">
				' . ($option_note ? '<div style="margin-top:15px;">' . displaySystemBox($option_note, 'hdsystembox-report') . '</div>' : '') . '

				' . (!$option_hide_filters ? '
					' . displayContentBoxTop(lg_reports_filters, '', '', '100%', '', 'box_body_solid') . '
						<div id="cond_wrapper">' . $conditionhtml . '</div>

						<div class="condition-menu" style="padding-left:0px;">
							<img src="' . static_url() . '/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="' . lg_conditional_addcon . '" title="' . lg_conditional_addcon . '"
								onClick="' . hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_condition') . '">
						</div>
					' . displayContentBoxBottom() . '
				' : '<br />') . '
				</div>
			</div>

			<div class="button-bar">
				<button type="button" id="runreport_button" name="" class="btn accent" onclick="runReport();">' . lg_reports_run . '</button>
				<button name="export" id="export" class="btn">' . hs_htmlspecialchars(lg_reports_export) . '</button>
			</div>
		</div>

		<div id="data_tab" name="data_tab" class="padded" style="display:none;margin-top: 14px;">
			<div id="data_tab_report" class="card padded"></div>
		</div>
		<div id="save_tab" class="padded" name="save_tab" style="display:none;margin-top: 14px;">
			' . chartSave($resourceid, $fm, $report->folders($user), $buttons, $_GET['pg'], $_GET['show'], $report) . '
		</div>
	</div>

</div>';

$pagebody .= '
<div id="active_exp" style="display:none;padding:20px;">
	'.lg_reports_active_only_exp.'
</div>
<script type="text/javascript">
	$jq("#active_exp_link").click(function(){
		hs_overlay("active_exp", {width:"500px"});
	});
 </script>
';

$pagebody .= '</form>';
