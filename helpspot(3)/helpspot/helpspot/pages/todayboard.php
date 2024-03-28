<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! perm('fModuleReports')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['reports']);

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script src="'.static_url().'/static/js/highcharts/js/highcharts.js"></script>

<script type="text/javascript">

	$jq(document).ready(function() {
		//Todays Requests
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'dash_requests']).'",
			type: "post",
			data: {
				graph_grouping: "date_hour",
				from: '.mktime(0, 0, 0).',
				to: '.mktime(23, 59, 59).'
			},
			cache: false,
			error: function(){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).'",{title:lg_js_error});
			},
			success: function(response){
				'.chartSetup('response.series1.meta').'

				//SERIES 1: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series1.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series1_data[i] = {y: parseFloat(v),
									   name: (label[1] ? label[1] : label[0]),
									   color: series1_color};
					i++;
				});

				final_series_group.push({
							name: response.series1.meta.tooltip,
							type: response.series1.meta.type,
							data: series1_data
						   });

				//SERIES 2: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series2.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series2_data[i] = {y:parseFloat(v),
									   name:(label[1] ? label[1] : label[0]),
									   color: series2_color};
					series2_labels[i] = label[0];
					i++;
				});

				final_series_group.push({
							name: response.series2.meta.tooltip,
							type: response.series2.meta.type,
							data: series2_data,
							color: series2_color
						   });

				dash_requests = new Highcharts.Chart({
					chart: {
						backgroundColor: "transparent",
						renderTo: "graph_requests_today",
						defaultSeriesType: "areaspline",
						marginLeft: 0
					},
					credits:{
						enabled:false
					},
					title: false,
					subtitle: false,
					xAxis: {
						categories: series2_labels, //use 2 because series 1 doesnt have all times, only up until now
						min: 0,
						title: {
							text: "'.lg_reports_grouping_date_hour.'"
						}
					},
					legend: {align:"center",verticalAlign:"top",itemStyle:{color:"'.(inDarkMode() ? '#cfd0d1' : '#737373').'"}},
					yAxis: '.chartYAxisDefault('response.series1.meta').',
					tooltip: '.chartTooltip(false).',
					plotOptions: {
						 areaspline: {
							fillOpacity: 0.1,
							color: series1_color
						 }
					},
					series: final_series_group
				});
			}
		});//end todays requests

		//First response speed
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'dash_first_response']).'",
			type: "post",
			data: {
				graph_grouping: "date_hour",
				from: '.mktime(0, 0, 0).',
				to: '.mktime(23, 59, 59).'
			},
			cache: false,
			success: function(response){
				$jq("#frs_median").html(response.median);
				$jq("#frs_avg").html(response.avg);
			}
		});//end first response speed

		//Closed After
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'report_replies_by_count_closed']).'",
			type: "post",
			data: {
				option_group_above: 5,
				from: '.mktime(0, 0, 0).',
				to: '.mktime(23, 59, 59).'
			},
			cache: false,
			success: function(response){
				'.chartSetup('response.series1.meta').'

				//SERIES 1: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series1.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series1_data[i] = {y: parseFloat(v),
									   name: (label[1] ? label[1] : label[0]),
									   color: series1_color};
					series1_labels[i] = label[0];
					i++;
				});

				final_series_group.push({
							name: response.series1.meta.tooltip,
							type: response.series1.meta.type,
							data: series1_data,
							color: series1_color
						   });

				dash_closedafter = new Highcharts.Chart({
					chart: {
						backgroundColor: "transparent",
						renderTo: "closedafter",
						defaultSeriesType: "column",
						marginLeft: 0,
						marginBottom: 40
					},
					credits:{
						enabled:false
					},
					title: false,
					subtitle: false,
					xAxis: {
						categories: series1_labels,
						min: 0,
						title: {
							text: "'.lg_reports_chartlabel_replies.'"
						}
					},
					yAxis: '.chartYAxisDefault('response.series1.meta').',
					tooltip: '.chartTooltip(false).',
					legend: {enabled:false},
					plotOptions: {
						 areaspline: {
							fillOpacity: 0.1,
							color: series1_color
						 }
					},
					series: final_series_group
				});
			}
		});//end closed after

		//Workload
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'dash_workload']).'",
			type: "post",
			data: {},
			cache: false,
			success: function(response){
				'.chartSetup('response.series1.meta').'

				//SERIES 1: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series1.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series1_data[i] = {y: parseFloat(v),
									   name: (label[1] ? label[1] : label[0]),
									   color: series1_color};
					series1_labels[i] = label[0];
					i++;
				});

				final_series_group.push({
							name: response.series1.meta.tooltip,
							type: response.series1.meta.type,
							data: series1_data
						   });

				dash_workload = new Highcharts.Chart({
					chart: {
						backgroundColor: "transparent",
						renderTo: "workload",
						defaultSeriesType: "bar",
						marginBottom: 0
					},
					credits:{
						enabled:false
					},
					title: false,
					subtitle: false,
					xAxis: {
						categories: series1_labels,
						min: 0,
						title: false
					},
					yAxis: '.chartYAxisDefault('response.series1.meta', [-18, 0]).',
					tooltip: '.chartTooltip(false).',
					legend: {enabled:false},
					plotOptions: {
						 areaspline: {
							fillOpacity: 0.1,
							color: series1_color
						 }
					},
					series: final_series_group
				});
			}
		});//end workload

		//Categories
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'dash_categories']).'",
			type: "post",
			data: {
				from: '.mktime(0, 0, 0).',
				to: '.mktime(23, 59, 59).'
			},
			cache: false,
			success: function(response){
				'.chartSetup('response.series1.meta').'
				var pie_colors = ["#058DC7", "#50B432", "#ED561B", "#DDDF00", "#24CBE5", "#64E572", "#FF9655", "#FFF263", "#6AF9C4"];

				//SERIES 1: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series1.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series1_data[i] = {y: parseFloat(v),
									   name: (label[1] ? label[1] : label[0]),
									   color: pie_colors[i]};
					series1_labels[i] = label[0];
					i++;
				});

				final_series_group.push({
							name: response.series1.meta.tooltip,
							type: response.series1.meta.type,
							data: series1_data
						   });

				dash_categories = new Highcharts.Chart({
				  chart: {
				  	 backgroundColor: "transparent",
					 renderTo: "categories",
					 plotBackgroundColor: null,
					 plotBorderWidth: null,
					 plotShadow: false
				  },
				  credits:{
					enabled:false
				  },
				  title: false,
				  tooltip: {
					 formatter: function() {
						return "<b>"+ this.point.name +"</b>: "+ this.y;
					 }
				  },
				  plotOptions: {
					 pie: {
						allowPointSelect: true,
						size: "110%",
						dataLabels: {
						   enabled: true,
						   distance: -4,
						   color: "#222",
						   connectorColor: "#222",
						   formatter: function() {
							  return "<b>"+ this.point.name +"</b>";
						   }
						}
					 }
				  },
					series: final_series_group
			   });
			}
		});//end categories

		//Todays interactions
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'report_data', 'show' => 'report_interactions']).'",
			type: "post",
			data: {
				graph_grouping: "date_hour",
				from: '.mktime(0, 0, 0).',
				to: '.mktime(23, 59, 59).'
			},
			cache: false,
			error: function(){
				hs_alert("'.hs_jshtmlentities(lg_reports_error).'",{title:lg_js_error});
			},
			success: function(response){
				'.chartSetup('response.series1.meta').'

				//SERIES 1: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series1.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series1_data[i] = {y: parseFloat(v),
									   name: (label[1] ? label[1] : label[0]),
									   color: series1_color};
					series1_labels[i] = label[0];
					i++;
				});

				final_series_group.push({
							name: response.series1.meta.tooltip,
							type: response.series1.meta.type,
							data: series1_data,
							color: series1_color
						   });

				//SERIES 2: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series2.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series2_data[i] = {y:parseFloat(v),
									   name:(label[1] ? label[1] : label[0]),
									   color: series2_color};
					i++;
				});

				final_series_group.push({
							name: response.series2.meta.tooltip,
							type: response.series2.meta.type,
							data: series2_data,
							color: series2_color
						   });

				//SERIES 3: decodes json as strings, cast to int
				var i=0;
				$jq.each(response.series3.data,function(k,v){
					var label = ($jq.type(k) == "string" ? k.split("|") : k);
					series3_data[i] = {y:parseFloat(v),
									   name:(label[1] ? label[1] : label[0]),
									   color: series3_color};
					i++;
				});

				final_series_group.push({
							name: response.series3.meta.tooltip,
							type: response.series3.meta.type,
							data: series3_data,
							color: series3_color
						   });

				dash_requests = new Highcharts.Chart({
					chart: {
						backgroundColor: "transparent",
						renderTo: "interactions",
						defaultSeriesType: "column",
						marginLeft: 0,
						marginBottom: 30
					},
					credits:{
						enabled:false
					},
					title: false,
					subtitle: false,
					xAxis: {
						categories: series1_labels,
						min: 0,
						title: {
							text: "'.lg_reports_grouping_date_hour.'"
						}
					},
					legend: {align:"center",verticalAlign:"top",itemStyle:{color:"'.(inDarkMode() ? '#cfd0d1' : '#737373').'"}},
					yAxis: '.chartYAxisDefault('response.series1.meta').',
					tooltip: '.chartTooltip(false).',
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
		});//end interactions

	});

</script>
';
$onload = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'todayboard']);
$pagetitle = lg_todayboard_title;
$tab = 'nav_reports';

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/

/*****************************************
PAGE OUTPUTS
*****************************************/

$pagebody .= '<div class="padded"><div id="graph_requests_today" style="height: 250px;margin-bottom:15px;"></div>';

$pagebody .= '
		<div class="yui-gc">
			<div class="yui-u first">
				'.displayContentBoxTop(lg_todayboard_closedafterx).'
				<div id="closedafter" style="height: 200px;"></div>
				'.displayContentBoxBottom().'
			</div>
			<div class="yui-u">
				'.displayContentBoxTop(lg_todayboard_speed).'
					<div id="" style="height: 200px;">
						<div class="chart-meta-info" id="frs_median">&mdash;</div>
						<div class="chart-meta-sub">'.lg_todayboard_median.'</div>
						<div class="nice-line"></div>
						<div class="chart-meta-info" id="frs_avg">&mdash;</div>
						<div class="chart-meta-sub">'.lg_todayboard_average.'</div>
					</div>
				'.displayContentBoxBottom().'
			</div>
		</div>

		<div class="yui-gc">
			<div class="yui-u first">
				'.displayContentBoxTop(lg_todayboard_staffassignment).'
				<div id="workload" style="height: 200px;"></div>
				'.displayContentBoxBottom().'
			</div>
			<div class="yui-u">
				'.displayContentBoxTop(lg_todayboard_category).'
				<div id="categories" style="height: 200px;"></div>
				'.displayContentBoxBottom().'
			</div>
		</div>

		'.displayContentBoxTop(lg_todayboard_interactions).'
		<div id="interactions" style="height: 200px;"></div>
		'.displayContentBoxBottom().'
	</div>';
