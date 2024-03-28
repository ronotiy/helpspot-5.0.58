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
$basepgurl	  = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports']);
$pagetitle 	  = lg_reports_title;
$tab	   	  = 'nav_reports';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$sortby	   	  = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord   	  = isset($_GET['sortord']) ? $_GET['sortord'] : '';
$show		  = isset($_GET['show']) ? $_GET['show'] : 'report_over_time';
$sortorder    = (isset($_GET['sortby']) ? $_GET['sortby'] : '').(isset($_GET['sortord']) ? ' '.$_GET['sortord'] : '');

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

$savedReports = new SavedReports();
$ui = new hs_conditional_ui_auto();
$rule = new hs_auto_rule();
$rowid = $ui->generateID('condition');

/*****************************************
PER REPORT OPTIONS
*****************************************/
//Defaults
$option_defaultConditions = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>'-1'];
$option_bizhours = false;
$option_based_on = false;
$option_extra_groups = false;
$option_grouping = false;
$option_label_limit = false;
$option_note = false;
$option_grouping_reqhistory = false;
$option_hide_filters = false;
$option_grouping_tracker = false;
$option_speedby = false;
$option_billable = false;
$user = auth()->user()->toArray();
$from = '';
$to = '';

// Create a saved report
if($action == 'add' || $action == 'edit'){
    $fm = $_POST; // Probably not ideal but we need all the conditions.
    $fm['xPerson'] = auth()->user()->xPerson;
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
	$fm['sDataRange'] = isset($_POST['sDataRange']) ? $_POST['sDataRange'] : '';
	$fm['fPermissionGroup'] = isset($_POST['fPermissionGroup']) ? $_POST['fPermissionGroup'] : 0;
	$fm['fEmail'] = isset($_POST['fEmail']) ? $_POST['fEmail'] : 0;
	$fm['sPersonList'] = isset($_POST['sPersonList']) ? $_POST['sPersonList'] : array();

	// Error checks
	if(hs_empty($fm['title'])) {
		$errors['sReportTitle'] = lg_reports_er_title;
	}
	if (! hs_empty($fm['fSendToExternal'])) {
	    $report->fSendToExternal = $fm['fSendToExternal'];
        $parser = new HS\Mail\EmailsFromString($fm['fSendToExternal']);
        if (count($parser->skipped()) > 0) {
            $errors['fSendToExternal'] = lg_reports_email_external_error . implode(', ', $parser->skipped());
        } else {
            // Convert to commas
            $fm['fSendToExternal'] = '';
            foreach ($parser->emails() as $email) {
                $fm['fSendToExternal'] .= $email.',';
            }
        }
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
            'pg' => 'reports',
            'show' => $fm['sShow'],
            'xReport' => $id,
        ])->with('feedback', lg_reports_reportsaved);
	}
}

//Overrides
if ($_GET['show'] == 'report_over_time') {
	$title = lg_reports_reqs_over_time;
    $option_extra_groups = true;
    $option_based_on = true;
    $option_grouping = true;
} elseif ($_GET['show'] == 'report_first_response') {
	$title = lg_reports_speed_to_first;
    $option_bizhours = true;
    $option_grouping = true;
    $option_speedby = true;
    $option_allhours_exp = lg_reports_allhours_exp;
    $option_usebizhours_exp = lg_reports_usebizhours_exp;
    $option_note = lg_reports_speed_to_first_spec;
} elseif ($_GET['show'] == 'report_first_assignment') {
	$title = lg_reports_speed_to_first_assignment;
    $option_bizhours = true;
    $option_grouping = true;
    $option_speedby = true;
    $option_allhours_exp = lg_reports_assignment_allhours_exp;
    $option_usebizhours_exp = lg_reports_assignment_usebizhours_exp;
    $option_note = lg_reports_speed_to_first_assignment_spec;
} elseif ($_GET['show'] == 'report_replies_by_count') {
	$title = lg_reports_replies_to_close;
    $option_label_limit = 20;
    $option_note = lg_reports_replies_to_close_spec;
} elseif ($_GET['show'] == 'report_interactions') {
	$title = lg_reports_interactions;
    $option_grouping = true;
    $option_hide_filters = true;
    $option_grouping_reqhistory = true;
} elseif ($_GET['show'] == 'report_resolution_speed') {
	$title = lg_reports_resolution_speed;
    $option_defaultConditions = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>'0'];
    $option_bizhours = true;
    $option_grouping = true;
    $option_based_on = true;
    $option_allhours_exp = lg_reports_resolution_allhours_exp;
    $option_usebizhours_exp = lg_reports_resolution_usebizhours_exp;
    $option_extra_groups = true;
} elseif ($_GET['show'] == 'report_tt_over_time') {
	$title = lg_reports_tt_over_time;
    $option_grouping = true;
    $option_grouping_tracker = true;
    $option_billable = true;
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
if (isset($_GET['xReport'])) { //for saved reports
    $saved_report = $report;
    $saved_report['tData'] = hs_unserialize($saved_report['tData']);
	$rule->SetAutoRule($saved_report['tData']);
	$conditionhtml  = $ui->createConditionsUI($rule);

	// Dynamic to/from.
    $range = $report->calculateDateRange($saved_report['tData']['sDataRange']);
    $from = $range['start'];
    $to = $range['end'];

	//Setup other saved values
	$button = lg_reports_resave;
	$formaction = 'edit';
	$resourceid = $_GET['xReport'];
	$fm_report = $saved_report['sReport'];
    $fm['sReport'] = $saved_report['sReport'];
	$fm_graph_grouping = $saved_report['tData']['graph_grouping'];
	$fm_usebizhours = $saved_report['tData']['usebizhours'];
	$fm_date_type = $saved_report['tData']['date_type'];
	$fm_option_group_above = $saved_report['tData']['option_group_above'];
	$fm_speedby = $saved_report['tData']['speedby'];
	$fm_billable = $saved_report['tData']['billable'];
	$fm['fType'] = $saved_report['tData']['fType'];
	$fm['sFolder'] = $saved_report['sFolder'];
	$fm['fPermissionGroup'] = $saved_report['tData']['fPermissionGroup'];
	$fm['sPersonList'] = $saved_report['tData']['sPersonList'];
	$fm['xPerson'] = $saved_report['xPerson'];
	$fm['fEmail'] = $saved_report['fEmail'];
} else {
	$rule->SetAutoRule($option_defaultConditions);
	$conditionhtml  = $ui->createConditionsUI($rule);

	//Setup other values
	$button = lg_reports_save;
	$formaction = 'add';
	$resourceid = 0;
	$fm_graph_grouping = 'date_day';
	$fm_usebizhours = 1;
	$fm_date_type = 'open';
	$fm_option_group_above = 20;
	$fm_speedby = 'hour';
	$fm_billable = 'all';
	$fm['fType'] = '1';
	$fm['fPermissionGroup'] = 0;
	$fm['xPerson'] = 0;
	$fm['fEmail'] = 0;
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

	function runReport(){
		'.($option_grouping ? '
		//adjust chart size if using non-date grouping
		if($jq("#graph_grouping").val().search("date_") != -1){
			$jq("#graph_wrap_bar").css({height:320});
		}else{
			$jq("#graph_wrap_bar").css({height:500});
		}
		' : '').'

		//show loading
		if($jq.type(mainchart) == "object") mainchart.showLoading();

		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => $_GET['show']]).'",
			type: "post",
			data: $jq("#report_form").serialize(),
			cache: false,
			error: function(xhr){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).': "+xhr.statusText,{title:lg_js_error});
			},
			success: function(response){
				//Set desc
				$jq("#report_desc").html(response.meta.desc);

				//Hide loading
				if($jq.type(mainchart) == "object") mainchart.hideLoading();

				//Create data table(s)
				if(response.series1){
					buildDataTable(response.meta.series_title,
									 response.series1.data,
									 response.series1.meta,
								 	 (typeof(response.series2) != "undefined" ? response.series2.data : false),
								 	 (typeof(response.series2) != "undefined" ? response.series2.meta : false),
								 	 (typeof(response.series3) != "undefined" ? response.series3.data : false),
								 	 (typeof(response.series3) != "undefined" ? response.series3.meta : false));

					buildChart(response.meta.series_title,
								 response.meta.series_subtitle,
								 response.series1.data,
								 response.series1.meta,
								 (typeof(response.series2) != "undefined" ? response.series2.data : false),
								 (typeof(response.series2) != "undefined" ? response.series2.meta : false),
								 (typeof(response.series3) != "undefined" ? response.series3.data : false),
								 (typeof(response.series3) != "undefined" ? response.series3.meta : false));
				}
			}
		});
	}

	function buildDataTable(title,data1,data1meta,data2,data2meta,data3,data3meta){
		var table = "";

		//Get col count
		var colct = 1;
		if(data2) colct = 2;
		if(data3) colct = 3;

		table += \'<table cellpadding="0" cellspacing="0" border="0" class="tablebody" id="report_table">\';

		//Headers
		table += \'<tr class="tableheaders">\';
			table += \'<td>'.hs_jshtmlentities(lg_reports_col_grouping).'</td>\';
			table += \'<td class="tcell-center report-col-width">\'+data1meta.ylabel+\'</td>\';
			if(data2) table += \'<td class="tcell-center report-col-width">\'+data2meta.ylabel+\'</td>\';
			if(data3) table += \'<td class="tcell-center report-col-width">\'+data3meta.ylabel+\'</td>\';
		table += \'</tr>\';

		//Data
		var j=0;
		$jq.each(data1,function(key,value){
			var label = ($jq.type(key) == "string" ? key.split("|") : key);
			table += \'<tr class="\'+(j % 2 ? "tablerowon" : "tablerowoff")+\'">\';
			table += \'<td class="tcell">\'+(label[1] ? label[1] : label[0])+\'</td>\';
			table += \'<td class="tcell tcell-center report-col-width">\'+(value > 0 ? value : "<span>-</span>")+\'</td>\';

			if(data2){
				table += \'<td class="tcell tcell-center report-col-width">\'+(data2[key] > 0 ? data2[key] : "<span>-</span>")+\'</td>\';
			}

			if(data3){
				table += \'<td class="tcell tcell-center report-col-width">\'+(data3[key] > 0 ? data3[key] : "<span>-</span>")+\'</td>\';
			}

			table += \'</tr>\';
			j++;
		});

		//Close table
		table += "</table>";

		$jq("#data_tab_report").html(table);

		//Build stats
		$jq("#report-stats-desc").html(data1meta.ylabel);
		$jq("#stats_median").html(data1meta.median);
		$jq("#stats_avg").html(data1meta.average);
		$jq("#stats_min").html(data1meta.min);
		$jq("#stats_max").html(data1meta.max_stats);
		$jq("#stats_sum").html(data1meta.sum);

		if(data2meta.max_stats){
			$jq("#series2_stats_div").show();
			$jq("#series2_report-stats-desc").html(data2meta.ylabel);
			$jq("#series2_stats_median").html(data2meta.median);
			$jq("#series2_stats_avg").html(data2meta.average);
			$jq("#series2_stats_min").html(data2meta.min);
			$jq("#series2_stats_max").html(data2meta.max_stats);
			$jq("#series2_stats_sum").html(data2meta.sum);
		}

		if(data3meta.max_stats){
			$jq("#series3_stats_div").show();
			$jq("#series3_report-stats-desc").html(data3meta.ylabel);
			$jq("#series3_stats_median").html(data3meta.median);
			$jq("#series3_stats_avg").html(data3meta.average);
			$jq("#series3_stats_min").html(data3meta.min);
			$jq("#series3_stats_max").html(data3meta.max_stats);
			$jq("#series3_stats_sum").html(data3meta.sum);
		}
	}

	var mainchart = "";
	function buildChart(title,subtitle,data1,data1meta,data2,data2meta,data3,data3meta){
		'.chartSetup().'

		//Set label style for longer names
		if($jq("#graph_grouping").length){
			if($jq("#graph_grouping").val().search("date_") != -1){
				var xlabelStyle = {};
			}else{
				var xlabelStyle = {
						rotation: 90,
						align: "left"
					 }
			}
		}else{
			var xlabelStyle = {};
		}

		//SERIES 1: decodes json as strings, cast to int
		var i=0;
		$jq.each(data1,function(k,v){
			var label = ($jq.type(k) == "string" ? k.split("|") : k);
			series1_data[i] = {y: parseFloat(v),
							   name: (label[1] ? label[1] : label[0]),
							   color: series1_color};
			series1_labels[i] = label[0];
			i++;
		});

		//SERIES 2
		var i=0;
		$jq.each(data2,function(k,v){
			var label = ($jq.type(k) == "string" ? k.split("|") : k);
			series2_data[i] = {y:parseFloat(v),
							   name:(label[1] ? label[1] : label[0]),
							   color: series2_color};
			//series2_labels[i] = label[0];
			i++;
		});

		//SERIES 3
		var i=0;
		$jq.each(data3,function(k,v){
			var label = ($jq.type(k) == "string" ? k.split("|") : k);
			series3_data[i] = {y:parseFloat(v),
							   name:(label[1] ? label[1] : label[0]),
							   color: series3_color};
			//series3_labels[i] = label[0];
			i++;
		});

		//ADD SERIES
		if(series1_data.length){
			//Plotlines
			if(!series2_data.length && data1meta.plotLineData){
				plotLineData = [ {
								label: {
									text:"'.lg_reports_average.': "+data1meta.average,
									align: "right",
									style: {
										color: series3_color
									},
									x:0,
									y: 15
								},
								color: series3_color,
								dashStyle: "DashDot",
								width: 2,
								value: data1meta.average
							},{
								label: {
									text:"'.lg_reports_median.': "+(data1meta.median === false ? "'.lg_reports_na.'" : data1meta.median),
									align: "left",
									style: {
										color: series2_color
									},
									x:0
								},
								color: series2_color,
								dashStyle: "DashDot",
								width: 2,
								value: data1meta.median
							}];
						}
			//Data
			final_series_group.push({
						name: data1meta.tooltip,
						type: data1meta.type,
			  			data: series1_data
			  		   });

			 //Y axis
			 yAxisGroup.push('.chartYAxisDefault().');
		}

		if(series2_data.length){
			//Data
			final_series_group.push({
						name: data2meta.tooltip,
			  		   	color: series2_color,
			  		   	type: data2meta.type,
			  		   	yAxis: (!data2meta.stacked ? 1 : 0),
			  		   	data: series2_data
			  		   });

			//Y axis, secondary axis
			if(!data2meta.stacked){
				yAxisGroup.push({
						 min: 0,
						 gridLineWidth: 0,
						 //allowDecimals: false,
						 title: {
							text: data2meta.ylabel,
							style: {
							   color: series2_color
							}
						 },
						 labels: {
							formatter: function() {
							   return this.value;
							},
							style: {
							   color: series2_color
							}
						 },
						 opposite: true
					  });
			}
		}

		if(series3_data.length){
			final_series_group.push({
						name: data3meta.tooltip,
			  		   	color: series3_color,
			  		   	type: data3meta.type,
			  		   	yAxis: (!data3meta.stacked ? 2 : 0),
			  		   	data: series3_data
			  		   });

			//Y axis, third axis
			if(!data3meta.stacked){
				yAxisGroup.push({ // Third yAxis
						 min: 0,
						 gridLineWidth: 0,
						 //allowDecimals: false,
						 title: {
							text: data3meta.ylabel,
							style: {
							   color: series3_color
							}
						 },
						 labels: {
							formatter: function() {
							   return this.value;
							},
							style: {
							   color: series3_color
							}
						 },
						 opposite: true
					  });
			}
		}

		mainchart = new Highcharts.Chart({
			  chart: {
				renderTo: "graph_wrap_bar",
				backgroundColor: "transparent",
				style: {
				    fontFamily: "Inter,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji"
				},
				marginLeft: 0
			  },
			  credits:{
				 enabled:false
			  },
			  lang:{
			  	loading: "'.lg_loading.'"
			  },
			  loading:{
			  	labelStyle:{
			  		color: "'.(inDarkMode() ? '#cfd0d1' : '#737373').'",
			  		fontSize: "18px",
			  		marginTop: "100px",
			  		display: "block"
			  	},
			  	style: {
			  		opacity: 0.7
			  	}
			  },
			  title: {
			  	text: "",
			  	style: {
			  		fontSize: "18px",
			  		color: "#304D71"
			  	},
				align: "left",
				x: -10,
				y: 25
			  },
			  subtitle: {
			  	text: subtitle,
			  	style: {
			  		fontSize: "14px",
			  		color: "'.(inDarkMode() ? '#cfd0d1' : '#737373').'"
			  	},
				align: "right"
			  },
			  xAxis: {
				 categories: series1_labels,
				 labels: xlabelStyle,
				 title: {
				 	text: (data1meta.xlabel ? data1meta.xlabel : $jq("#graph_grouping option:selected").text())
				 },
				 lineColor: grid_line_color,
				 tickColor: grid_line_color,
			  },
			  legend: (data1meta.stacked ? {align:"center",verticalAlign:"top",itemStyle:{color:"#737373"}} : {enabled:false}),
			  yAxis: yAxisGroup,
			  tooltip: '.chartTooltip().',
			  plotOptions: {
				 column: {

					color: series1_color,
					stacking: "normal"
				 },
				 areaspline: {
				 	fillOpacity: 0.1,
				 	color: series1_color
				 }
			  },
			  series: final_series_group
		   });
	}

	function changeGraph(){
		var series = mainchart.series[0];
		newType = series.type == "areaspline" ? "column" : "areaspline";
		changeType(series, newType);

		var series = mainchart.series[1];
		newType = series.type == "areaspline" ? "column" : "areaspline";
		changeType(series, newType);
	}

	function changeType(series, newType) {
		var dataArray = [],
			points = series.data;

		series.chart.addSeries({
			type: newType,
			name: series.name,
			color: series.color,
			data: series.options.data
		}, false);

		series.remove();
	}

	function checkform(){
		var er = "";
		if($jq("#fm_report").val() == ""){
			er += "'.hs_jshtmlentities(lg_reports_er_title).'\n";
		}

		if(er.length != 0){
			hs_alert(er);
			return false;
		}

		return true;
	}

	function deleteReport(report_id){
		$jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_delete']).'",
				{xReport:report_id},
				function(result){
					return goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'todayboard']).'");
		});
		return false;
	}
</script>';

/*****************************************
SELECTS
*****************************************/
$cf_grouping = '';
if (is_array($GLOBALS['customFields'])) {
    $cf_options = '';
    foreach ($GLOBALS['customFields'] as $fvalue) {
        if ($fvalue['fieldType'] != 'checkbox' and $fvalue['fieldType'] != 'datetime' and $fvalue['fieldType'] != 'lrgtext') {
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

/*****************************************
PAGE OUTPUTS
*****************************************/
if(!empty($formerrors)){
	$efb = $efb . errorBox($formerrors['errorBoxText']);
}

$buttons = '
	<div class="button-bar space">
		<button type="submit" name="submit" id="submit" class="btn accent">' . $button . '</button>';
if (isset($_GET['xReport']) && $report->canEdit($user, $fm)) {
	$buttons .= '<button type="button" id="delete_btn" class="btn altbtn" onclick="deleteReport(' . $_GET['xReport'] . ');">' . lg_reports_deletereport . '</button>';
}
$buttons .= '</div>';

$pagebody .= renderPageheader((isset($saved_report['sReport']) ? $saved_report['sReport'].' ('.$title.')' : $title), '<div id="report_desc"></div>');

//Form action points to excel download page. We only submit the form to create a CSV.
$pagebody .= '<form action="'.$basepgurl.'&amp;action='.$formaction.'&amp;xReport='.$resourceid.'" method="POST" name="report_form" id="report_form" enctype="multipart/form-data" accept-charset="UTF-8">';
//$pagebody .= '<form action="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'excel', 'show' => $_GET['show']]).'" id="report_form" method="post" onsubmit="return checkform();">
//   			  <input type="hidden" id="report_show" name="report" value="'.$show.'">';
$pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any
$pagebody .= csrf_field();

$pagebody .= '
<div id="report_wrap">
	<div class="tab_wrap">
		<ul class="tabs" id="reporttabs">
			<li><a href="#graph_tab" class="active"><span>'.lg_reports_graph.'</span></a></li>
			<li><a href="#data_tab"><span>'.lg_reports_data.'</span></a></li>
			<li><a href="#save_tab"><span>'. lg_reports_customize.'</span></a></li>
		</ul>

		<div id="graph_tab" class="padded" name="graph_tab">
			<div id="graph_wrap">
				<div id="graph_wrap_bar" style="height:320px;">
					<div class="loading">'.lg_loading.'</div>
				</div>
			</div>

			' . ($option_note ? '<div style="">' . displaySystemBox($option_note) . '</div>' : '') . '

			<div class="card padded">
				<div style="display:flex;flex-direction:column;">
					<div style="display:flex;justify-content:flex-start;align-items:center;margin-bottom:14px;" class="graph_options">
						<div>
							<label class="datalabel">'.lg_reports_from.'</label>
							'.calinput('from', reports::repCreateFromDT($from)).'
						</div>
						<div>
							<label class="datalabel">'.lg_reports_to.'</label>
							'.calinput('to', reports::repCreateToDT($to)).'
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
										<option value="date_hour" '.selectionCheck('date_hour', $fm_graph_grouping).'>'.lg_reports_grouping_date_hour.'</option>
										<option value="date_day" '.selectionCheck('date_day', $fm_graph_grouping).'>'.lg_reports_grouping_date_day.'</option>
										<option value="date_month" '.selectionCheck('date_month', $fm_graph_grouping).'>'.lg_reports_grouping_date_month.'</option>
										<option value="date_year" '.selectionCheck('date_year', $fm_graph_grouping).'>'.lg_reports_grouping_date_year.'</option>
										'.($option_grouping_reqhistory ? '
										<optgroup label="'.lg_reports_grouping_reqhistory.'">
											<option value="note_creator" '.selectionCheck('note_creator', $fm_graph_grouping).'>'.lg_reports_grouping_notecreator.'</option>
										</optgroup>
										' : '').'

										'.($option_grouping_tracker ? '
										<optgroup label="'.lg_reports_grouping_tracker.'">
											<option value="xPersonTracker" '.selectionCheck('xPersonTracker', $fm_graph_grouping).'>'.lg_reports_grouping_trackerstaff.'</option>
											<option value="sUserId" '.selectionCheck('sUserId', $fm_graph_grouping).'>'.lg_reports_grouping_customerid.'</option>
											<option value="sEmail" '.selectionCheck('sEmail', $fm_graph_grouping).'>'.lg_reports_grouping_customer_email.'</option>
											<option value="xCategory" '.selectionCheck('xCategory', $fm_graph_grouping).'>'.lg_reports_grouping_category.'</option>
										</optgroup>
										' : '').'
										'.($option_extra_groups ? '
										<optgroup label="'.lg_reports_grouping_aggtime.'">
											<option value="date_agg_hour" '.selectionCheck('date_agg_hour', $fm_graph_grouping).'>'.lg_reports_grouping_agg_hour.'</option>
											<option value="date_agg_day" '.selectionCheck('date_agg_day', $fm_graph_grouping).'>'.lg_reports_grouping_agg_day.'</option>
											<option value="date_agg_month" '.selectionCheck('date_agg_month', $fm_graph_grouping).'>'.lg_reports_grouping_agg_month.'</option>
										</optgroup>
										<optgroup label="'.lg_reports_grouping_reqdetails.'">
											<option value="sUserId" '.selectionCheck('sUserId', $fm_graph_grouping).'>'.lg_reports_grouping_customerid.'</option>
											<option value="sEmail" '.selectionCheck('sEmail', $fm_graph_grouping).'>'.lg_reports_grouping_customer_email.'</option>
											<option value="xCategory" '.selectionCheck('xCategory', $fm_graph_grouping).'>'.lg_reports_grouping_category.'</option>
											<option value="xStatus" '.selectionCheck('xStatus', $fm_graph_grouping).'>'.lg_reports_grouping_status.'</option>
											<option value="xPersonAssignedTo" '.selectionCheck('xPersonAssignedTo', $fm_graph_grouping).'>'.lg_reports_grouping_assigned.'</option>
											<option value="fOpenedVia" '.selectionCheck('fOpenedVia', $fm_graph_grouping).'>'.lg_reports_grouping_contactvia.'</option>
										</optgroup>
										'.$cf_grouping.$reptag_grouping : '').'
									</select>
								</div>' : '').'

								'.($option_bizhours ? '
								<div>
									<label for="usebizhours" class="datalabel">'.lg_reports_hourcalcon.' <a href="" id="bizhour_exp_link" class="actionlink" onclick="return false;">[?]</a></label>
									<select name="usebizhours" id="usebizhours">
										<option value="1" '.selectionCheck(1, $fm_usebizhours).'>'.lg_reports_usebizhours.'</option>
										<option value="0" '.selectionCheck(0, $fm_usebizhours).'>'.lg_reports_allhours.'</option>
									</select>
								</div>' : '').'

								'.($option_based_on ? '
								<div>
									<label for="date_type" class="datalabel">'.lg_reports_basedon.'</label>
									<select name="date_type" id="date_type" onchange="">
										<option value="open" '.selectionCheck('open', $fm_date_type).'>'.lg_reports_basedon_open.'</option>
										<option value="close" '.selectionCheck('close', $fm_date_type).'>'.lg_reports_basedon_close.'</option>
									</select>
								</div>' : '').'

								'.($option_label_limit ? '
								<div>
									<label for="date_type" class="datalabel">'.lg_reports_groupabove.'</label>
									<select name="option_group_above" id="option_group_above" onchange="">
										<option value="5" '.selectionCheck(5, $fm_option_group_above).'>5</option>
										<option value="10" '.selectionCheck(10, $fm_option_group_above).'>10</option>
										<option value="15" '.selectionCheck(15, $fm_option_group_above).'>15</option>
										<option value="20" '.selectionCheck(20, $fm_option_group_above).'>20</option>
										<option value="30" '.selectionCheck(30, $fm_option_group_above).'>30</option>
										<option value="40" '.selectionCheck(40, $fm_option_group_above).'>40</option>
										<option value="50" '.selectionCheck(50, $fm_option_group_above).'>50</option>
										<option value="100" '.selectionCheck(100, $fm_option_group_above).'>100</option>
									</select>
								</div>' : '').'

								'.($option_speedby ? '
								<div>
									<label for="speedby" class="datalabel">'.lg_reports_speedby.'</label>
									<select name="speedby" id="speedby" onchange="">
										<option value="hour" '.selectionCheck('hour', $fm_speedby).'>'.lg_reports_speedby_hour.'</option>
										<option value="min" '.selectionCheck('min', $fm_speedby).'>'.lg_reports_speedby_min.'</option>
									</select>
								</div>' : '').'

								'.($option_billable ? '
								<div>
									<label for="billable" class="datalabel">'.lg_reports_billable.'</label>
									<select name="billable" id="billable" onchange="">
										<option value="all" '.selectionCheck('all', $fm_billable).'>'.lg_reports_billable_all.'</option>
										<option value="billable" '.selectionCheck('billable', $fm_billable).'>'.lg_reports_billable_is.'</option>
										<option value="not_billable" '.selectionCheck('not_billable', $fm_billable).'>'.lg_reports_billable_isnt.'</option>
									</select>
								</div>' : ''). '

					</div>
				</div>


				<div style="">

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
		<div id="data_tab" class="padded" name="data_tab" style="display:none;margin-top: 14px;">
			<div class="yui-ge">
				<div class="yui-u first">
					<div id="data_tab_report" class="card padded"></div>
				</div>
				<div class="yui-u">
					'.displayContentBoxTop(lg_reports_stats.': <span id="report-stats-desc"></span>', '', '', '100%', 'box-no-top-margin').'
						<table style="width:100%;" class="report-stats-table">
							<tr>
								<td>'.lg_reports_statsmedian.'</td>
								<td align="right" id="stats_median"></td>
							</tr>
							<tr>
								<td>'.lg_reports_statsaverage.'</td>
								<td align="right" id="stats_avg"></td>
							</tr>
							<tr>
								<td>'.lg_reports_statsmin.'</td>
								<td align="right" id="stats_min"></td>
							</tr>
							<tr>
								<td>'.lg_reports_statsmax.'</td>
								<td align="right" id="stats_max"></td>
							</tr>
							<tr>
								<td>'.lg_reports_statssum.'</td>
								<td align="right" id="stats_sum"></td>
							</tr>
						</table>
					'.displayContentBoxBottom().'

					<div id="series2_stats_div" style="display:none;">
						'.displayContentBoxTop(lg_reports_stats.': <span id="series2_report-stats-desc"></span>', '', '', '100%', 'box-no-top-margin').'
							<table style="width:100%;" class="report-stats-table">
								<tr>
									<td>'.lg_reports_statsmedian.'</td>
									<td align="right" id="series2_stats_median"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsaverage.'</td>
									<td align="right" id="series2_stats_avg"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsmin.'</td>
									<td align="right" id="series2_stats_min"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsmax.'</td>
									<td align="right" id="series2_stats_max"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statssum.'</td>
									<td align="right" id="series2_stats_sum"></td>
								</tr>
							</table>
						'.displayContentBoxBottom().'
					</div>

					<div id="series3_stats_div" style="display:none;">
						'.displayContentBoxTop(lg_reports_stats.': <span id="series3_report-stats-desc"></span>', '', '', '100%', 'box-no-top-margin').'
							<table style="width:100%;" class="report-stats-table">
								<tr>
									<td>'.lg_reports_statsmedian.'</td>
									<td align="right" id="series3_stats_median"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsaverage.'</td>
									<td align="right" id="series3_stats_avg"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsmin.'</td>
									<td align="right" id="series3_stats_min"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statsmax.'</td>
									<td align="right" id="series3_stats_max"></td>
								</tr>
								<tr>
									<td>'.lg_reports_statssum.'</td>
									<td align="right" id="series3_stats_sum"></td>
								</tr>
							</table>
						'.displayContentBoxBottom().'
					</div>
				</div>
			</div>
		</div>
		<div id="save_tab" class="padded" name="save_tab" style="display:none;margin-top: 14px;">
			' . chartSave($resourceid, $fm, $report->folders($user), $buttons, $_GET['pg'], $_GET['show'], $report) . '
		</div>
	</div>

</div>


<div id="bizhour_exp" style="display:none;">
	'.displayContentBoxTop(lg_reports_allhours).'
	'.$option_allhours_exp.'
	'.displayContentBoxBottom().'

	'.displayContentBoxTop(lg_reports_usebizhours).'
	'.$option_usebizhours_exp.'
	'.displayContentBoxBottom().'
</div>
';

$pagebody .= '
<script type="text/javascript">
    $jq(document).ready(function(){
	    $jq("#bizhour_exp_link").click(function(){
		    hs_overlay("bizhour_exp");
	    });
	    $jq("#fEmail").on("click", function(e){
            if($jq(this).is(":checked")) {
                $jq(".email-options").show();
            } else {
                $jq(".email-options").hide();
            }
        });
    });
 </script>
';

$pagebody .= '</form>';
