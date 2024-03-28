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
$basepgurl	  = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.dual']);
$pagetitle 	  = lg_reports_title;
$tab	   	  = 'nav_reports';
$sortby	   	  = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord   	  = isset($_GET['sortord']) ? $_GET['sortord'] : '';
$show1		  = isset($_GET['show']) ? $_GET['show'] : 'report_productivity_replyspeed';
$show2		  = isset($_GET['show2']) ? $_GET['show2'] : 'report_productivity_resolution';
$sortorder    = (isset($_GET['sortby']) ? $_GET['sortby'] : '').(isset($_GET['sortord']) ? ' '.$_GET['sortord'] : '');
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

	if (isset($_POST['saveAs']) and trim($_POST['saveAs']) != '') {
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
            'pg' => 'reports.dual',
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
    $resourceid = $report->xReport;
	$formaction = 'edit';
	$fm_graph_grouping = $saved_report['tData']['graph_grouping'];
	$fm_usebizhours = $saved_report['tData']['usebizhours'];
	$fm_date_type = $saved_report['tData']['date_type'];
	$fm_option_group_above = $saved_report['tData']['option_group_above'];
	$fm_speedby = $saved_report['tData']['speedby'];
	$fm_billable = $saved_report['tData']['billable'];
	$fm_report = $report->sReport;
	$fm['sReport'] = $report->sReport;
	$fm['fType'] = $saved_report['tData']['fType'];
    $fm['fPermissionGroup'] = $saved_report['tData']['fPermissionGroup'];
    $fm['sPersonList'] = $saved_report['tData']['sPersonList'];
    $fm['sFolder'] = $report->sFolder;
    $fm['xPerson'] = $report->xPerson;

}else{
	$rule->SetAutoRule($option_defaultConditions);
	$conditionhtml  = $ui->createConditionsUI($rule);

	//Setup other values
	$fm_graph_grouping = 'date_day';
	$fm_usebizhours = 1;
	$fm_date_type = 'open';
	$fm_option_group_above = 20;
	$fm_speedby = 'hour';
	$fm_billable = 'all';
	$fm['fType'] = '1';
	$fm['fPermissionGroup'] = 0;
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript .= '
<script src="'.static_url().'/static/js/highcharts/js/highcharts.js"></script>
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
		$jq.ajax({
			url: "admin?pg=ajax_gateway&action=report_data&show='.$show1.'",
			type: "post",
			data: $jq("#report_form").serialize(),
			cache: false,
			error: function(xhr){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).': "+xhr.statusText,{title:lg_js_error});
			},
			success: function(response){

				displayChart(response, "#graph_wrap_bar1", "'.lg_reports_speed_to_first_grouped.'");
				buildDataTable(response, "#data_tab_report1", "'.lg_reports_speed_to_first_grouped.'", "'.$show1.'");

			}
		});

		// Report #2
		$jq.ajax({
			url: "admin?pg=ajax_gateway&action=report_data&show='.$show2.'",
			type: "post",
			data: $jq("#report_form").serialize(),
			cache: false,
			error: function(xhr){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).': "+xhr.statusText,{title:lg_js_error});
			},
			success: function(response){

				displayChart(response, "#graph_wrap_bar2", "'.lg_reports_resolution_speed_grouped.'");
				buildDataTable(response, "#data_tab_report2", "'.lg_reports_resolution_speed_grouped.'", "'.$show2.'");

			}
		});
	}

	function displayChart(response, element, title){
		$jq(element).highcharts({
	        chart: {
	            type: "area",
	            backgroundColor: "transparent",
		        style: {
		            fontFamily: "Inter,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji"
		        },
		        marginLeft: 0
	        },
	        title: {
	            text: title,
	            style: {
	            	color: "#304D71"
	            },
	            align: "left",
	        },
	        colors: [
	        	"#2e8b57",
	        	"#527ac2",
	        	"#d2527f",
	        	"#708090",
	        	"#8d6708",
	        	"#d35400",
	        	"#dc2a2a",
	        ],
	        legend: {
	        	enabled: false
	        },
	        credits: {
	        	enabled: false
	        },
	        subtitle: {
	            text: ""
	        },
	        xAxis: {
	            categories: response.dates,
	            minPadding:0,
	            maxPadding:0,
	            min: 0.5,
	            max: (response.dates.length -1.5),
	            tickmarkPlacement: "on",
	            title: {
	                text: "'.lg_reports_basedon_open.'"
	            }
	        },
	        yAxis: {
	            title: {
	                text: "'.lg_reports_chartlabel_requests.'"
	            }
	        },
	        tooltip: {
	            //pointFormat: "<span style=\"color: {series.color};\">{series.name}</span> <b>{point.percentage:.1f}%</b> ({point.y:,.0f} requests)<br/>",
				 formatter: function() {
					var s = "<b>"+this.points[0].x+"</b>";

					$jq.each(this.points, function(i, point) {
						var tiplabel = point.series.name;
						//Remove count data, used by dash_requests
						if(tiplabel.search(/\(/) && tiplabel.search(/\)/)){
							var parts = tiplabel.split("(");
							tiplabel = parts[0];
						}

						s += "<br/><span style=\"font-family:monospace\;color:"+point.series.color+"\">"+ $jq.strPad(tiplabel,18,".") +" <b>"+ Math.floor(point.percentage) +"%</b> ["+point.y+" requests]</span>";
					});

					return s;
				 },
	            shared: true,
				backgroundColor: "#fff",
				shadow: false,
				borderRadius: 1,
				borderColor: "#737373",
				style: {
					color: "#3a2d23",
					padding: 12
				}
	        },
	        plotOptions: {
	            area: {
	                stacking: "normal", //percent
	                lineColor: "#ffffff",
	                lineWidth: 1,
	                marker: {
	                    lineWidth: 1,
	                    lineColor: "#ffffff"
	                }
	            }
	        },
	        series: [
	        			response.series1,
	        			response.series2,
	        			response.series3,
	        			response.series4,
	        			response.series5,
	        			response.series6,
	        			response.series7,
	            	]
	    });
	}

	function buildDataTable(response, element, title, show){
		var table = "";

		table += \'<div style="display:flex;justify-content:space-between;"><span class="table-title">\'+ '.(isset($saved_report['sReport']) ? '"'.$saved_report['sReport'].'" + " (" + title + ")"' : 'title').' +\'</span> <button type="button" onclick="downloadReport(\\\'\'+show+\'\\\');" name="" class="btn tiny">'.hs_htmlspecialchars(lg_reports_export).'</button></div>\';

		table += \'<table cellpadding="0" cellspacing="0" border="0" class="tablebody" id="report_table">\';

		//Headers
		table += \'<tr class="tableheaders">\';
			table += \'<td style="min-width:150px;">'.hs_jshtmlentities(lg_reports_col_grouping).'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series1.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series2.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series3.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series4.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series5.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series6.name+\'</td>\';
			table += \'<td class="tcell-center report-col-width" style="font-size:10px;">\'+response.series7.name+\'</td>\';
		table += \'</tr>\';

		//Data
		var j=0;
		for(var i=0;i < response.series1.data.length;i++){
			table += \'<tr class="\'+(j % 2 ? "tablerowon" : "tablerowoff")+\'">\';
			table += \'<td class="tcell" style="min-width:150px;">\'+ response.dates[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series1.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series2.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series3.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series4.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series5.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series6.data[i] +\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+ response.series7.data[i] +\'</td>\';
			table += \'</tr>\';
			j++;
		}

		//Close table
		table += "</table>";

		$jq(element).html(table);
	}

	function downloadReport(show){
		form2form("#report_form", "#" + show + "_form");
		$jq("#" + show + "_form").submit();
	}

	function form2form(formA, formB) {
	    $jq(":input[name]", formA).each(function() {

			$jq("<input>").attr({
			    type: "hidden",
			    value: $jq(this).val(),
			    name: $jq(this).attr("name")
			}).appendTo(formB);

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
$cf_grouping = '';
if (is_array($GLOBALS['customFields'])) {
    $cf_options = '';
    foreach ($GLOBALS['customFields'] as $fvalue) {
        if ($fvalue['fieldType'] == 'select' or $fvalue['fieldType'] == 'drilldown') {
            $cf_options .= '<option value="Custom'.$fvalue['fieldID'].'" '.selectionCheck('Custom'.$fvalue['fieldID'], $fm_graph_grouping).'>'.$fvalue['fieldName'].'</option>';
        }
    }
    if ($cf_options) {
        $cf_grouping = '<optgroup label="'.lg_reports_grouping_customfields.'">'.$cf_options.'</optgroup>';
    }
}

//Rep tag category grouping
$reptags_grouping = '';
$categories = apiGetAllCategories(0, false);
if ($categories) {
    $reptags_options = '';
    while ($cat = $categories->FetchRow()) {
        $reptags_options .= '<option value="category_tags_'.$cat['xCategory'].'" '.selectionCheck('category_tags_'.$cat['xCategory'], $fm_graph_grouping).'>'.(! hs_empty($cat['sCategoryGroup']) ? $cat['sCategoryGroup'].': ' : '').$cat['sCategory'].'</option>';
    }
    if ($reptags_options) {
        $reptag_grouping = '<optgroup label="'.lg_reports_grouping_catreptags.'">'.$reptags_options.'</optgroup>';
    }
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
$pagebody .= renderPageheader((isset($saved_report['sReport']) ? $saved_report['sReport'].' ('.lg_reports_productivity.')' : lg_reports_productivity), '<div id="report_desc"></div>');

$pagebody .= '<form action="'.$basepgurl.'&amp;action='.$formaction.'&amp;xReport='.$resourceid.'" method="POST" name="report_form" id="report_form" enctype="multipart/form-data" accept-charset="UTF-8">';
$pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any
$pagebody .= csrf_field().'<input type="hidden" id="report_show1" name="report" value="'.$show1.'"><input type="hidden" id="report_show2" name="report" value="'.$show2.'">';

$pagebody .= '
<div id="report_wrap">
	<div id="report_desc"></div>
	<div class="tab_wrap">
		<ul class="tabs" id="reporttabs">
			<li><a href="#graph_tab" class="active"><span>'.lg_reports_graph.'</span></a></li>
			<li><a href="#data_tab"><span>'.lg_reports_data. '</span></a></li>
			<li><a href="#save_tab"><span>' . lg_reports_customize . '</span></a></li>
		</ul>

		<div id="graph_tab" name="graph_tab" class="padded">

			<div id="graph_tab" class="yui-g">
				<div id="graph_wrap_bar1" class="yui-u first" style="height:500px;padding: 25px 0;">
					<div class="loading" style="text-align: center;font-size: 24px;color: rgba(51,51,51,0.3);">'.lg_loading.'</div>
				</div>
				<div id="graph_wrap_bar2" class="yui-u" style="height:500px;padding: 25px 0;">
					<div class="loading" style="text-align: center;font-size: 24px;color: rgba(51,51,51,0.3);">'.lg_loading.'</div>
				</div>
			</div>

			<div class="card padded">
				<div style="display:flex;flex-direction:column;">
					<div style="display:flex;justify-content:flex-start;align-items:center;margin-bottom:14px;" class="graph_options">
						<div>
							<label class="datalabel">'.lg_reports_from.'</label>
							'.calinput('from', reports::repCreateFromDT($dt_from->subMonths(3)->modify('first day of this month')->setTime(0, 0, 0)->timestamp)).'
						</div>
						<div>
							<label class="datalabel">'.lg_reports_to.'</label>
							'.calinput('to', reports::repCreateToDT($dt_to->modify('last day of this month')->setTime(23, 59, 59)->timestamp)).'
						</div>
						<div class="time_or"><label class="datalabel">&nbsp;</label>'.lg_or.'</div>
						<div>
							<label class="datalabel">&nbsp;</label>
							'.hs_ShowMonthQuickDrop('from', 'to').'
						</div>
					</div>

					<div style="display:flex;justify-content:flex-start;align-items:center;" class="graph_options">
						'.($option_grouping ? '
						<div>
							<label for="graph_grouping" class="datalabel">'.lg_reports_grouping.'</label>
							<select name="graph_grouping" id="graph_grouping">
								<option value="date_day" '.selectionCheck('date_day', $fm_graph_grouping).'>'.lg_reports_grouping_date_day.'</option>
								<option value="date_month" '.selectionCheck('date_month', $fm_graph_grouping).' selected>'.lg_reports_grouping_date_month.'</option>
								<option value="date_year" '.selectionCheck('date_year', $fm_graph_grouping).'>'.lg_reports_grouping_date_year.'</option>
							</select>
						</div>' : '').'

						'.($option_bizhours ? '
						<div>
							<label for="usebizhours" class="datalabel">'.lg_reports_hourcalcon.' <a href="" id="bizhour_exp_link" class="actionlink" onclick="return false;">[?]</a></label>
							<select name="usebizhours" id="usebizhours">
								<option value="1" '.selectionCheck(1, $fm_usebizhours).'>'.lg_reports_usebizhours.'</option>
								<option value="0" '.selectionCheck(0, $fm_usebizhours).'>'.lg_reports_allhours.'</option>
							</select>
						</div>' : ''). '
					</div>
				</div>

				<div class="">

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

			<div class="button-bar space">
				<button type="button" id="runreport_button" name="" class="btn accent" onclick="runReport();">' . lg_reports_run . '</button>
			</div>

		</div>
		<div id="data_tab" name="data_tab" style="display:none;margin-top: 14px;" class="">
			<div id="data_tab_report1" class="card padded" style="margin-bottom:14px;"></div>
			<div id="data_tab_report2" class="card padded"></div>
		</div>
		<div id="save_tab" class="padded" name="save_tab" style="display:none;">
			' . chartSave($resourceid, $fm, $report->folders($user), $buttons, $_GET['pg'], $_GET['show'], $report) . '
		</div>
	</div>

</div>';

$pagebody .= '
<div id="bizhour_exp" style="display:none;">
	'.displayContentBoxTop(lg_reports_allhours).'
	'.lg_reports_allhours_exp.'
	'.displayContentBoxBottom().'

	'.displayContentBoxTop(lg_reports_usebizhours).'
	'.lg_reports_usebizhours_exp.'
	'.displayContentBoxBottom().'
</div>
<script type="text/javascript">
	$jq("#bizhour_exp_link").click(function(){
		hs_overlay("bizhour_exp");
	});
 </script>
';

$pagebody .= '</form>';

// These forms are used to submit the excel downloads for the individual reports
$pagebody .= '
<form action="'.cHOST.'/admin?pg=excel&productivity=1&show='.$show1.'" method="post" id="'.$show1.'_form"></form>
<form action="'.cHOST.'/admin?pg=excel&productivity=1&show='.$show2.'" method="post" id="'.$show2.'_form"></form>';
