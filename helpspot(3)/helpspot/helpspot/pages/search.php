<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

// Don't allow lesser perm levels when in that mode
if (! perm('fCanAdvancedSearch')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['admin.settings', 'ajax_gateway', 'request', 'filter.requests', 'conditional.ui']);

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.conditional.ui.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'search']);
$tab = 'nav_search';
$subtab = '';
$hidePageFrame = 0;
$pagetitle = lg_search_title;
$allStaff = apiGetAllUsersComplete();
$vmode = isset($_GET['vmode']) ? true : false;

$args['sUserId'] = isset($_GET['sUserId']) ? trim($_GET['sUserId']) : '';
$args['sFirstName'] = isset($_GET['sFirstName']) ? trim($_GET['sFirstName']) : '';
$args['sLastName'] = isset($_GET['sLastName']) ? trim($_GET['sLastName']) : '';
$args['sEmail'] = isset($_GET['sEmail']) ? trim($_GET['sEmail']) : '';
$args['sPhone'] = isset($_GET['sPhone']) ? trim($_GET['sPhone']) : '';
$args['fOpenedVia'] = isset($_GET['fOpenedVia']) ? $_GET['fOpenedVia'] : '';
$args['all'] = isset($_GET['all']) ? $_GET['all'] : 1; //switches between and/or searching

$args['q'] = isset($_GET['q']) ? $_GET['q'] : '';
$args['area'] = isset($_GET['area']) ? $_GET['area'] : '';

$ui = new hs_conditional_ui_auto();
$rule = new hs_auto_rule();

$tags = apiGetAllTags();
if ($tags) {
    $taglist = '<select id="taglist" style="margin-right:15px;min-width:200px;" onchange="addTag();"><option value=""></option>';
    while ($t = $tags->FetchRow()) {
        $taglist .= '<option value="'.$t['xTag'].'">'.$t['sTag'].'</option>';
    }
    $taglist .= '</select>';
} else {
    $taglist = '';
}

if (isset($_COOKIE['last_search']) && $_COOKIE['last_search'] != '') {
    $cookie = json_decode($_COOKIE['last_search'], false);

    $last_tab = $cookie->tab;
    $cookie = get_object_vars($cookie->values);
}

if (isset($_GET['reports'])) {
    include cBASEPATH.'/helpspot/lib/class.reports.php';
    $report = new reports($_POST);

    //Add in date range
    $_POST['condition_reportTime_1'] = ($_POST['date_type'] == 'close' ? 'betweenClosedDates' : 'betweenDates');
    $_POST['condition_reportTime_2'] = reports::repCreateFromDT($_POST['from']).','.$report::repCreateToDT($_POST['to']);
    $rule->SetAutoRule($_POST);
    $conditionhtml = $ui->createConditionsUI($rule);
    $orderby = 'dtGMTOpened';
    $orderbydir = 'DESC';
} elseif (isset($last_tab) && $last_tab == 'advanced') {
    $rule->SetAutoRule($cookie);
    $conditionhtml = $ui->createConditionsUI($rule);
    $orderby = $cookie['orderBy'];
    $orderbydir = $cookie['orderByDir'];
} else {
    $rowid = $ui->generateID('condition');
    $default = [$rowid.'_1'=>'relativedate', $rowid.'_2'=>'past_30'];
    $rule->SetAutoRule($default);
    $conditionhtml = $ui->createConditionsUI($rule);
    $orderby = 'dtGMTOpened';
    $orderbydir = 'DESC';
}

$init_tags = '';
if (isset($_GET['tags']) || isset($_GET['xTag'])) {
    //Get tag if we were just passed an ID
    if (isset($_GET['xTag'])) {
        $_GET['tags'] = apiGetTagById($_GET['xTag']);
    }

    $init_tags = 'Control.Tabs.findByTabId("tags").setActiveTab("tags");';
    $t = explode(',', $_GET['tags']);
    foreach ($t as $k=>$v) {
        $init_tags .= '
			setSelectByText("taglist","'.hs_htmlspecialchars($v).'");
			addTag();
		';
    }
}

/*****************************************
ACTION
*****************************************/
if (isset($_POST['saveAs_sFilterName']) && ! empty($_POST['saveAs_sFilterName'])) {
    //Add or Save As
    $rule = new hs_auto_rule();
    $rule->SetAutoRule($_POST);

    $build['tFilterDef'] = hs_serialize($rule);
    $build['xPerson'] = $user['xPerson'];
    $build['sFilterName'] = $_POST['saveAs_sFilterName'];
    $build['sShortcut'] = '';
    $build['fShowCount'] = 0;
    $build['fCacheNever'] = 0;
    $build['fDisplayTop'] = 0;
    $build['fType'] = 2;
    $build['fPermissionGroup'] = 0;
    $build['fCustomerFriendlyRSS'] = 0;

    $new_id = apiAddEditFilter($build);

    return redirect()
        ->route('admin', ['pg' => 'workspace', 'show' => $new_id])
        ->with('feedback', lg_filter_requests_fbadded);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
	<script type="text/javascript">
	Event.observe(window,"load",function(){
		searchtabs = new Control.Tabs("search_tabs");
		'.(isset($_GET['reports']) ? '
			Control.Tabs.findByTabId("advanced").setActiveTab("advanced");
			$("advanced_form").onsubmit();
		' : 'setup_form()').';

		//Clear results on tab change by user
		Control.Tabs.observe("beforeChange",function(control_tabs_instance,old_container,new_container){
			$("results").update("");
		});

		'.$init_tags.'

	});

	function save_form(){
		var form = {
			tab: searchtabs.activeContainer.id,
			values: Form.serialize(searchtabs.activeContainer.id + "_form", true)
		};

		var dt = new Date();
		dt.setSeconds(dt.getSeconds()+'.hs_setting('cHD_SAVE_LAST_SEARCH').');

		setCookie("last_search", Object.toJSON(form), dt);
	}

	function setup_form(){
		var ls = getCookie("last_search");
		if(ls && 1=='.(! empty($init_tags) ? '0' : '1').'){
			eval("var setup=" + ls);
			Control.Tabs.findByTabId(setup.tab).setActiveTab(setup.tab);

			if(setup.tab == "customer_search"){
				$("sUserId").value = setup.values.sUserId;
				$("sFirstName").value = setup.values.sFirstName;
				$("sLastName").value = setup.values.sLastName;
				$("sEmail").value = setup.values.sEmail;
				$("sPhone").value = setup.values.sPhone;
				setSelectToValue("fOpenedVia", setup.values.fOpenedVia);
			}else if(setup.tab == "data"){
				$("q").value = setup.values.q;
				setSelectToValue("area", setup.values.area);
			}else if(setup.tab == "tags"){
				var tags = setup.values["sTag[]"].toString().split(",");
				for(var i=0;i < tags.length;i++){
					setSelectByText("taglist",tags[i]);
					addTag();
				}
				$jq("#taglist").selectedIndex = 0;
			}else if(setup.tab == "advanced"){

			}

			//Call form on submit so ajax actions take place
			$(setup.tab + "_form").onsubmit();
		}
	}

	function addTag(){
		$jq("#results").html(ajaxLoading(\''.lg_loading.'\'));
		$jq.get("'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'search_add_tag']).'&xTag="+$jq("#taglist").val(), function(data) {
			if($jq("#rt-notags").length != 0) $jq("#rt-notags").remove();

			$jq("#tagWrap").append(data);
			$jq("#taglist option:selected").remove();
			$jq("#taglist").selectedIndex = 0;

			tagClick();

			//DO SEARCH
			tagSearch();
		});
	}

	function tagClick(){
		$jq("#tagWrap .rt").click(function(){
			$jq(this).remove();
			tagSearch();
		});
	}

	function tagSearch(){
		save_form();
		$jq.get("'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'search_tags']).'&"+$jq("#tags_form").serialize(), function(data) {
			$jq("#results").html(data);
		});
	}
	</script>
';

$onload = '';

/*****************************************
SEARCH
*****************************************/

$contactSelect = '<option value=""></option>';
foreach ($GLOBALS['openedVia'] as $key=>$value) {
    //if($key != 6 && $key != 7){
    $contactSelect .= '<option value="'.$key.'" '.selectionCheck($key, $args['fOpenedVia']).'>'.$value.'</option>';
    //}
}

/*****************************************
PAGE OUTPUTS
*****************************************/
//HELP
if ((config('database.default') == 'mysql') && (substr($v['version'], 0, 1) != 3)) {
    $help .= '<h3>'.lg_search_infotips.'</h3>
               <ul>
                    <li>'.lg_search_my_tip1.'</li>
                    <li>'.lg_search_my_tip2.'</li>
                    <li>'.lg_search_my_tip3.'</li>
                    <li>'.lg_search_my_tip4.'</li>
                    <li>'.lg_search_my_tip5.'</li>
               </ul>';
} elseif (config('database.default') == 'sqlsrv') {
    $help .= '<h3>'.lg_search_infotips.'</h3>
               <ul>
                    <li>'.lg_search_ms_tip1.'</li>
                    <li>'.lg_search_ms_tip2.'</li>
                    <li>'.lg_search_ms_tip3.'</li>
                    <li>'.lg_search_ms_tip4.'</li>
                    <li>'.lg_search_ms_tip5.'</li>
               </ul>';
}

$pagebody .= '
<div class="card padded">
	<div class="customer_tabs tab_wrap" id="search_tabs">
		<ul class="tabs">
			<li><a href="#customer_search"><span>'.lg_search_customer.'</span></a></li>
			<li><a href="#advanced"><span>'.lg_search_advanced.'</span></a></li>
			<li><a href="#data"><span>'.lg_search_data.'</span></a></li>
            <li><a href="#tags"><span>'.lg_search_tags.'</span></a></li>
			<li><a href="#tips"><span>'.lg_search_tips.'</span></a></li>
		</ul>

    		<div id="customer_search" class="">
    			<form action="'.route('admin', ['pg' => 'search']).'" method="get" id="customer_search_form" name="customer_search_form" onsubmit="save_form();$(\'results\').innerHTML = ajaxLoading(\''.lg_loading.'\');'.hsAJAXinline('function(){ $(\'results\').innerHTML = arguments[0].responseText;arguments[0].responseText.evalScripts(); }', 'search_customers', "'+ Form.serialize('customer_search_form') +'").';return false;">
    			'.csrf_field().'
                    '.displayContentBoxTop('', '', false, '100%').'
        				<div class="yui-gb">
        					<div class="yui-u first">
        						<label class="datalabel" for="sUserId">'.lg_request_custid.'</label>
        						<input tabindex="101" name="sUserId" id="sUserId" type="text" size="19" style="width: 88%;font-weight:bold;" value="'.formClean($args['sUserId']).'">
        					</div>
        					<div class="yui-u">
        						<label class="datalabel" for="sFirstName">'.lg_request_fname.'</label>
        						<input tabindex="102" name="sFirstName" id="sFirstName" type="text" size="19" style="width: 88%;font-weight:bold;" value="'.formClean($args['sFirstName']).'">
        					</div>
        					<div class="yui-u">
        						<label class="datalabel" for="sLastName">'.lg_request_lname.'</label>
        						<input tabindex="103" name="sLastName" id="sLastName" type="text" size="19" style="width: 88%;font-weight:bold;" value="'.formClean($args['sLastName']).'">
        					</div>
        				</div>

        				<div class="yui-gb" style="margin-top:15px;">
        					<div class="yui-u first">
        						<label class="datalabel" for="fOpenedVia">'.lg_request_contactedvia.'</label>
        						<select tabindex="100" name="fOpenedVia" id="fOpenedVia" style="width: 88%;" class="'.errorClass('fOpenedVia').'">'.$contactSelect.'</select>'.errorMessage('fOpenedVia').'
        					</div>
        					<div class="yui-u">
        						<label class="datalabel" for="sEmail">'.lg_request_email.'</label>
        						<input tabindex="104" name="sEmail" id="sEmail" type="text" size="19" style="width: 88%;font-weight:bold;" value="'.formClean($args['sEmail']).'">
        					</div>
        					<div class="yui-u">
        						<label class="datalabel" for="sPhone">'.lg_request_phone.'</label>
        						<input tabindex="105" name="sPhone" id="sPhone" type="text" size="19" style="width: 88%;font-weight:bold;" value="'.formClean($args['sPhone']).'">
        					</div>
        				</div>
                    '.displayContentBoxBottom().'

    				<div style="padding-top:10px;">
    					<button type="submit" class="btn accent" id="search_button_customer">'.lg_search_title.'</button>
    				</div>
    			</form>
    		</div>

    		<div id="data" class="" style="display:none">
    			<form action="'.route('admin', ['pg' => 'search']).'" method="get" id="data_form" name="data_form" onsubmit="save_form();$(\'results\').innerHTML = ajaxLoading(\''.lg_loading.'\');'.hsAJAXinline('function(){ $(\'results\').innerHTML = arguments[0].responseText;arguments[0].responseText.evalScripts(); }', 'search_data', "'+ Form.serialize('data_form') +'").';return false;">
    				'.csrf_field().'
                        '.displayContentBoxTop('', '', false, '100%').'
            				<label class="datalabel" for="q">'.lg_search_fulltext.'</label>
            				<input tabindex="104" name="q" id="q" type="text" size="19" style="width: 60%;font-weight:bold;" value="'.formClean($args['q']).'">
            				&nbsp;
            				<select tabindex="100" name="area" id="area" style="width: 25%;" class="">
            					'.(! perm('fCanViewOwnReqsOnly') ? '<option value="reqs">'.lg_search_requests.'</option>' : '').'
            					<option value="kb">'.lg_search_kb.'</option>
            				</select>
    				    '.displayContentBoxBottom().'
    				<div style="padding-top:10px;">
    					<button type="submit" class="btn accent" id="search_button_data">'.lg_search_title.'</button>
    				</div>
    			</form>
    		</div>

    		<div id="advanced" class="" style="display:none;">
    			<form action="'.route('admin', ['pg' => 'search']).'" method="post" id="advanced_form" name="advanced_form" onsubmit="save_form();$(\'results\').innerHTML = ajaxLoading(\''.lg_loading.'\');'.hsAJAXinline('function(){ $(\'results\').innerHTML = arguments[0].responseText;arguments[0].responseText.evalScripts(); }', 'search_adv', "'+ Form.serialize('advanced_form') +'").';return false;">
    				'.csrf_field().'

                    '.displayContentBoxTop('', '', false, '100%').'
                        <div class="" style="padding-left:0px;">
                            <label class="datalabel" for="orderBy" style="display:inline;">'.lg_filter_requests_orderby.'</label>
                            <select name="orderBy">
                                '.orderBySelect($orderby).'
                            </select>
                            <select name="orderByDir">
                                <option value="DESC" '.selectionCheck('DESC', $orderbydir).'>'.lg_filter_requests_descending.'</option>
                                <option value="ASC" '.selectionCheck('ASC', $orderbydir).'>'.lg_filter_requests_ascending.'</option>
                            </select>
                        </div>

        				'.(isset($_GET['reports']) ? '<label class="datalabel" style="margin-bottom:5px;">'.($_POST['date_type'] == 'close' ? lg_search_reporttimeclosed : lg_search_reporttime).': '.hs_showShortDate($_POST['from']).' '.lg_and.' '.hs_showShortDate($_POST['to']).'</label>' : '').'
        				<div id="cond_wrapper">'.$conditionhtml.'</div>

                        <div class="condition-menu">
                            <img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
                                 onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_condition').'">
                        </div>
                    '.displayContentBoxBottom().'

    				<div class="button-bar" style="padding-top:10px;">
    					<button type="submit" class="btn accent" id="search_button_adv">'.lg_search_title.'</button>
    					'.save_as_button(lg_search_saveasfilter, lg_search_saveasfilterlabel, 'saveAs_sFilterName', '', '280', 'bottomMiddle', lg_search_saveasfilterex, "$('advanced_form').submit();").'
    				</div>
    			</form>
    		</div>

    		<div id="tags" class="" style="display:none;min-height:50px;">
    			<form action="'.route('admin', ['pg' => 'search']).'" method="post" id="tags_form" name="tags_form" onsubmit="return false;">
    				'.csrf_field().'
                    '.displayContentBoxTop('', '', false, '100%').'
        				<div id="tagWrap">
        					<span id="rt-notags">'.lg_search_tagsnone.'</span>
        				</div>

        				<div class="nice-line"></div>

        				<label class="datalabel" for="reportingTagsInput">'.lg_search_tagspick.'</label>
        				'.$taglist.'
                    '.displayContentBoxBottom().'
    			</form>
    		</div>

    		<div id="tips" style="display:none;">
    			<div>
    				<h3 style="margin-top:0px;">'.lg_search_cust_titletips.'</h3>

    				<ul>
    					<li>'.lg_search_wild.'</li>
    				</ul>
    			</div>

    			<div>
    				'.$help.'
    			</div>
    		</div>

            <div id="results" style="padding-top: 50px;"></div>
    	</div>
    </div>
';
