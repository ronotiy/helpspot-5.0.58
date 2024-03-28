<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//no guestsf
if (perm('fCanViewOwnReqsOnly')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['ajax_gateway', 'conditional.ui', 'workspace.stream']);

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.conditional.ui.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests']);
$hidePageFrame = 0;
$pagetitle = lg_filter_requests_title;
$tab = 'nav_workspace';
$subtab = 'nav_filter_requests';

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$filterid = isset($_REQUEST['filterid']) ? $_REQUEST['filterid'] : '';

// Figure out who is really editing it. Could be owner or admin
$person = isAdmin() && isset($_GET['xPerson']) ? $_GET['xPerson'] : $user['xPerson'];

$filters = apiGetAllFilters($person, 'all');
// Check that person should have access.
if (! empty($filterid) && ! empty($filters)) {
    if (! isAdmin() && $filters[$filterid]['xPerson'] != $person) {
        exit();
    }
}

$fm['sFilterName'] = isset($_POST['sFilterName']) ? $_POST['sFilterName'] : '';
$fm['sFilterFolder'] = isset($_POST['sFilterFolder']) ? $_POST['sFilterFolder'] : '';
$fm['sShortcut'] = isset($_POST['sShortcut']) ? $_POST['sShortcut'] : '';
$fm['urgentinline'] = isset($_POST['urgentinline']) ? $_POST['urgentinline'] : 0;
$fm['fShowCount'] = isset($_POST['fShowCount']) ? $_POST['fShowCount'] : 0;
$fm['fCacheNever'] = isset($_POST['fCacheNever']) ? $_POST['fCacheNever'] : 0;
$fm['fDisplayTop'] = isset($_POST['fDisplayTop']) ? $_POST['fDisplayTop'] : 0;
$fm['fCustomerFriendlyRSS'] = isset($_POST['fCustomerFriendlyRSS']) ? $_POST['fCustomerFriendlyRSS'] : 0;
$fm['fType'] = isset($_POST['fType']) ? $_POST['fType'] : 2;
$fm['sFilterView'] = isset($_POST['sFilterView']) ? $_POST['sFilterView'] : 'grid';
$fm['fPermissionGroup'] = isset($_POST['fPermissionGroup']) ? $_POST['fPermissionGroup'] : [];
$fm['sPersonList'] = isset($_POST['sPersonList']) ? $_POST['sPersonList'] : [];
$fm['orderBy'] = isset($_POST['orderBy']) ? $_POST['orderBy'] : 'xRequest';
$fm['orderByDir'] = isset($_POST['orderByDir']) ? $_POST['orderByDir'] : 'DESC';
$fm['groupBy'] = isset($_POST['groupBy']) ? $_POST['groupBy'] : '';
$fm['groupByDir'] = isset($_POST['groupByDir']) ? $_POST['groupByDir'] : 'ASC';
$fm['displayColumns'] = isset($_POST['displayColumns']) ? $_POST['displayColumns'] : $GLOBALS['defaultFilterCols'];

if (isset($_POST['saveAs_sFilterName']) && ! empty($_POST['saveAs_sFilterName'])) {
    $fm['sFilterName'] = $_POST['saveAs_sFilterName'];
}

/*****************************************
ACTION
*****************************************/
//Delete
if (isset($_GET['delete']) && isset($filterid)) {
    apiDeleteFilter($filterid);
    return redirect()
        ->route('admin', ['pg' => 'workspace']);
}

//Add or Save As
if ((isset($_POST['submit']) && empty($filterid)) || (isset($_POST['saveAs_sFilterName']) && ! empty($_POST['saveAs_sFilterName']))) {
    $rule = new hs_auto_rule();
    $rule->SetAutoRule($_POST);

    $fm['tFilterDef'] = hs_serialize($rule);
    $fm['xPerson'] = $person;

    $new_id = apiAddEditFilter($fm);

    return redirect()
        ->route('admin', [
            'pg' => ($fm['sFilterView'] == 'grid' ? 'workspace' : 'workspace.stream'),
            'filter-created' => $new_id,
            'show' => $new_id,
        ])
        ->with('feedback', lg_filter_requests_fbadded);

//Update
} elseif (isset($_POST['submit']) && is_numeric($filterid)) {
    $rule = new hs_auto_rule();
    $rule->SetAutoRule($_POST);

    $fm['tFilterDef'] = hs_serialize($rule);
    $fm['xPerson'] = $person;

    $fm['xFilter'] = $filterid;
    $fm['mode'] = 'edit';
    $rs = apiAddEditFilter($fm);

    // Redirect to managment if admin user and not editing their own filter
    if (isset($_POST['admin_editing'])) {
        return redirect()->route('admin', ['pg' => 'admin.tools.filtermgmt']);
    } else {
        return redirect()
            ->route('admin', [
                'pg' => ($fm['sFilterView'] == 'grid' ? 'workspace' : 'workspace.stream'),
                'show' => $filterid,
            ])
            ->with('feedback', lg_filter_requests_fbedited);
    }
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
$ui = new hs_conditional_ui_auto();

if (! empty($filterid)) {
    $filterrow = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Filters WHERE xFilter = ?', [$filterid]);

    $filter = hs_unserialize($filterrow['tFilterDef']);

    $title = lg_filter_requests_edit.': '.$filter->name;
    $button = lg_filter_requests_buttonedit;
    $conditionhtml = $ui->createConditionsUI($filter);
    $actionhtml = $ui->createActionsUI($filter);
    $datatable = '';
    $buttonclick = '';

    $anyall = $filter->anyall;
    $sfiltername = $filter->name;

    $secondary_button = save_as_button(lg_saveas, lg_filter_requests_saveas_details, 'saveAs_sFilterName', 'sFilterName');
} else {
    $title = lg_filter_requests_title;
    $button = lg_filter_requests_buttonsave;
    $onload = 'Field.focus("sFilterName");';

    $rule = new hs_auto_rule();
    $rowid = $ui->generateID('condition');
    $default = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>1];
    $rule->SetAutoRule($default);

    $conditionhtml = $ui->createConditionsUI($rule);
    $actionhtml = $ui->newAction();

    $anyall = '';
    $srulename = '';

    $secondary_button = '';
}

if (! empty($filterid)) {
    $fm = $filters[$filterid];

    $vmode = 2;
} else {
    $vmode = 1;
}

$anyallselect = '
<select name="anyall">
	<option value="all" '.selectionCheck('all', $anyall).'>'.lg_filter_requests_all.'</option>
	<option value="any" '.selectionCheck('any', $anyall).'>'.lg_filter_requests_any.'</option>
</select>';

$filterFolderSel = '<option value="">'.lg_filter_requests_nofolder.'</option>';
foreach (apiCreateFolderList($filters) as $v) {
    if (! empty($v)) {
        $filterFolderSel .= '<option value="'.hs_htmlspecialchars($v).'" '.selectionCheck($v, $fm['sFilterFolder']).'>'.hs_htmlspecialchars($v).'</option>';
    }
}

//Order by. Create the sub groups, add new cols here
$orderBySel .= orderBySelect($fm['orderBy']);

//Group by. Create the sub groups, add new cols here
$groupByGroups = [];

//Add time spans (today/yesterday/older, 12h,24h,36h,older, days of week (last 7)/older, this week/last week/older)
$groupByGroups[lg_filter_requests_ogdatetime] = array_keys($GLOBALS['timeGroupings']);
$groupByGroups[lg_filter_requests_ogcustinfo] = ['sUserId', 'sLastName', 'sEmail', 'sPhone'];
$groupByGroups[lg_filter_requests_ogreqdetails] = ['sCategory', 'sPersonAssignedTo', 'sStatus', 'fOpenedVia', 'fOpen'];
//Add custom fields
if (isset($GLOBALS['customFields']) && is_array($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $cfV) {
        if (in_array($cfV['fieldType'], ['select', 'decimal', 'text', 'ajax', 'numtext', 'regex'])) {
            $groupByGroups[lg_filter_requests_ogcustomfields][] = 'Custom'.$cfV['fieldID'];
        }
    }
}
//Create select
$groupBySel = '<option value="">'.lg_filter_requests_nogroupby.'</option>';
foreach ($groupByGroups as $group=>$options) {
    $groupBySel .= '<optgroup label="'.$group.'">';
    //Output options
    foreach ($options as $k=>$v) {
        $label = in_array($v, $groupByGroups[lg_filter_requests_ogdatetime]) ? $GLOBALS['timeGroupings'][$v] : '';
        if ($v == 'sPersonAssignedTo') {
            $label = lg_lookup_filter_assignedto;
        } //special case, a col made for grouping only
        if ($v == 'sStatus') {
            $label = lg_lookup_filter_status;
        } //special case
        if (empty($label)) {
            $label = isset($GLOBALS['filterCols'][$v]['label2']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
        }
        $groupBySel .= '<option value="'.$v.'" '.selectionCheck($v, $fm['groupBy']).'>'.$label.'</option>';
    }
    $groupBySel .= '</optgroup>';
}

//Column Groups. Create the sub groups, add new cols here
$columnSel .= createFilterColumnList($fm['displayColumns']);

//First create mapping, then create options
$shortcutSel = '';
foreach ($filters as $fk=>$f) {
    if (isset($f['sShortcut']) && $fk != $filterid) {
        unset($GLOBALS['filterKeys'][$f['sShortcut']]);
    }
}

foreach ($GLOBALS['filterKeys'] as $sk=>$sv) {
    $shortcutSel .= '<option value="'.$sk.'" '.selectionCheck($sk, $fm['sShortcut']).'>'.$sv.'</option>';
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">

	Event.observe(window,"load",function(){
		$$(".tabs").each(function(tabs){
			new Control.Tabs(tabs);
		});
	});

	function checkform(){
		var er = "";

		if($("sFilterName").value == ""){
			er += "'.hs_jshtmlentities(lg_filter_requests_noname).'\n";
		}

		if(trim($("cond_wrapper").innerHTML) == ""){
			er += "'.hs_jshtmlentities(lg_filter_requests_noconds).'\n";
		}

		if(er.length != 0){
			hs_alert(er);
			return false;
		}

		return true;
	}

	function setColumnWidth(id){
		var value = $F(id + "_value");
		var content = \'<input type="text" value="\' + value + \'" id="\'+id+\'_textbox" name="\'+id+\'_textbox" size="10" /> <button type="button" class="btn inline-action" onclick="insertColumnWidth(\\\'\' + id + \'\\\');">'.hs_jshtmlentities(lg_filter_requests_setcolumnwidthsave).'</button><div class="tiptext">'.hs_jshtmlentities(lg_filter_requests_setcolumnwidthnote).'</div>\';

		new Tip(id, content, {
				title: false,
                className: "hstinytipfat",
                stem: "bottomRight",
				hideOn: { element: "closeButton", event: "click" },
				border: 0,
				radius: 0,
				showOn: "click",
				hideOn: "click",
				hideOthers: true,
				width: "auto",
				hook: { target: "topMiddle", tip: "bottomRight" }
			});

		$(id).prototip.show();

		setTimeout(function(){$(id + "_textbox").focus();},100);
	}

	function overflowMsg(id){
		new Tip(id, "<div class=\"tiptext\">'.hs_jshtmlentities(lg_filter_requests_setcolumnwidthfill).'</div>", {
				title: false,
				border: 0,
				radius: 0,
				className: "hstinytipfat",
                stem: "bottomRight",
				hideOn: { element: "closeButton", event: "click" },
				showOn: "click",
				hideOn: false,
				hideAfter: 2,
				hideOthers: true,
				width: "auto",
				hook: { target: "topMiddle", tip: "bottomRight" }
			});

		$(id).prototip.show();
	}

	function run_filter(){
		$jq.ajax({
			url: "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'run_filter']).'",
			type: "post",
			data: $jq("#filterform").serialize(),
			cache: false,
			success: function(data){
				$jq("#test_condition_result").html(data);
			}
		});
	}

</script>
';

/*****************************************
PAGE OUTPUTS
****************************************/
$pagebody .= renderPageheader($title);

if (! empty($fb)) {
    $pagebody .= $fb;
}

$pagebody .= '
<div class="tab_wrap card padded">

<ul class="tabs" id="filtertabs">
    <li><a href="#conditions_tab" class="active"><span>'.lg_filter_requests_filter.'</span></a></li>
    <li><a href="#options_tab"><span>'.lg_filter_requests_moreoptions.'</span></a></li>
</ul>';

$pagebody .= '<form action="'.$basepgurl.'&filterid='.$filterid.'" method="POST" class="filterform padded" name="filterform" id="filterform" onSubmit="safari_order_fix(\'displayColumns[]\',\'filterform\');return checkform();">';
$pagebody .= csrf_field();

        $pagebody .= '
			<div id="conditions_tab" name="conditions_tab" style="">

                <div class="fr">
                    <div class="label">
                        <label class="req" for="sFilterName">'.lg_filter_requests_name.'</label>
                    </div>
                    <div class="control">
                        <input type="text" name="sFilterName" id="sFilterName" value="'.formClean($fm['sFilterName']).'" size="30" class="'.errorClass('sFilterName').'">
                        '.errorMessage('sFilterName').'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label class="req" for="fPermissionGroup">'.lg_filter_requests_perms.'</label>
                    </div>
                    <div class="control">
                            '.permSelectUI($fm['fType'],$fm['fPermissionGroup'],
                                            $GLOBALS['DB']->GetCol('SELECT xPerson FROM HS_Filter_People WHERE xFilter = ?', [$filterid]),
                                            $GLOBALS['DB']->GetCol('SELECT xGroup FROM HS_Filter_Group WHERE xFilter = ?', [$filterid]))
                                        .'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sFilterFolder">
                            '.lg_filter_requests_folder.'
                        </label>
                    </div>
                    <div class="control">
                        <div style="display:flex;align-items:center;">
                            <select name="sFilterFolder" id="sFilterFolder" style="margin-right:10px;flex:1;">
                            '.$filterFolderSel.'
                            </select>
                            <a href="javascript:addFolder(\''.hs_jshtmlentities(lg_ajax_myfilters).'\',\'sFilterFolder\');" class="btn inline-action" style="">
                                '.lg_filter_requests_addfolder.'
                            </a>
                        </div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="fShowCount">'.lg_filter_requests_counts.'</label>
                    </div>
                    <div class="control">
                        <div>
                            <input type="checkbox" class="checkbox" name="fShowCount" id="fShowCount" value="1" '.checkboxCheck(1, ($filterid ? $fm['fShowCount'] : 0)).'>
                            <label for="fShowCount" class="switch"></label>
                        </div>
                    </div>
                </div>';

            $pagebody .= displayContentBoxTop('', '', false, '100%', 'mb-0', 'box_body_solid');
            $pagebody .= '
                        <label class="datalabel" style="margin-bottom:8px;margin-left:10px;">'.sprintf(lg_filter_requests_anyall, $anyallselect).'</label>

                        <div id="cond_wrapper">'.$conditionhtml.'</div>

                        <div class="condition-menu">
                            <img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
                                 onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_condition').'">
                        </div>';
            $pagebody .= displayContentBoxBottom();

            $pagebody .= '<div id="test_condition_result" style="margin-top:24px;"></div>';
        $pagebody .= '</div>';

    $pagebody .= '
		<div id="options_tab" name="options_tab" style="display:none;">

            <div class="fr">
                <div class="label">
                    <label class="" for="orderBy">'.lg_filter_requests_orderby.'</label>
                </div>
                <div class="control">
                    <div class="group">
                        <select name="orderBy" class="short">
                            '.$orderBySel.'
                        </select>
                        <select name="orderByDir" class="tiny">
                            <option value="DESC" '.selectionCheck('DESC', $fm['orderByDir']).'>'.lg_filter_requests_descending.'</option>
                            <option value="ASC" '.selectionCheck('ASC', $fm['orderByDir']).'>'.lg_filter_requests_ascending.'</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="">'.lg_filter_requests_setcols.'</label>
                </div>
                <div class="control">
                    <div class="sortable_wrap">
                        <div class="sortablelist" id="selected_columns">';
                            foreach ($fm['displayColumns'] as $k=>$v) {
                                $label = isset($GLOBALS['filterCols'][$v]['label2']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
                                $colwidth = isset($fm['displayColumnsWidths'][$v]) ? $fm['displayColumnsWidths'][$v] : $GLOBALS['filterCols'][$v]['width'];
                                $onclick = isset($GLOBALS['filterCols'][$v]['hideflow']) ? 'overflowMsg' : 'setColumnWidth'; //Show a special message to overflow columns
                                $pagebody .= '<div class="sortable_filter" id="selected_col_'.formClean($v).'">
                                              <img src="'.static_url().'/static/img5/grip-lines-regular.svg" style="vertical-align: middle;cursor:move;margin-right:6px;" class="drag_handle">
                                              <span>'.hs_htmlspecialchars($label).'</span>
                                              <span id="column_width_'.formClean($v).'" class="hand filter_width_text" onclick="'.$onclick.'(this.id);">'.($colwidth ? $colwidth : '<img src="'.static_url().'/static/img5/arrows-h-solid.svg" />').'</span>
                                              <img src="'.static_url().'/static/img5/remove.svg" style="vertical-align: middle;cursor:pointer;" onClick="return confirmRemove(\'selected_col_'.formClean($v).'\', confirmListDelete);">
                                              <input type="hidden" id="column_width_'.formClean($v).'_value" name="column_width_'.formClean($v).'_value" value="'.formClean($colwidth).'">
                                              <input type="hidden" name="displayColumns[]" value="'.formClean($v).'">
                                              </div>';
                            }
                        $pagebody .= '</div>';

                        $pagebody .= '<select name="select_col" id="select_col" onChange="addSortableColumn(\'select_col\',\'selected_columns\',\'displayColumns[]\');">
                                        <option value="none">'.lg_filter_requests_selectcol.'</option>
                                        '.$columnSel.'
                                      </select>';

                    $pagebody .= '</div>';

                    $pagebody .= '
                    <script type="text/javascript">
                     // <![CDATA[
                       var confirmListDelete = "'.hs_jshtmlentities(lg_filter_requests_removecol).'";
                       Sortable.create("selected_columns",
                         {tag:"div", constraint: "vertical", handle: "drag_handle"});
                     // ]]>
                     </script>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sFilterView">'.lg_filter_requests_filtertype.'</label>
                </div>
                <div class="control">
                    <select name="sFilterView" id="sFilterView" class="short">
                        <option value="grid" '.selectionCheck('grid',$filters[$filterid]['sFilterView']).'>'.lg_workspacestream_grid.'</option>
                        <option value="stream" '.selectionCheck('stream',$filters[$filterid]['sFilterView']).'>'.lg_filter_requests_stream.': '.lg_workspacestream_stream.'</option>
                        <option value="stream-priv" '.selectionCheck('stream-priv',$filters[$filterid]['sFilterView']).'>'.lg_filter_requests_stream.': '.lg_workspacestream_streamwpriv.'</option>
                        <option value="stream-cust" '.selectionCheck('stream-cust',$filters[$filterid]['sFilterView']).'>'.lg_filter_requests_stream.': '.lg_workspacestream_streamcustomers.'</option>
                        <option value="stream-cust-staff" '.selectionCheck('stream-cust-staff',$filters[$filterid]['sFilterView']).'>'.lg_filter_requests_stream.': '.lg_workspacestream_streamcuststaff.'</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="groupBy">'.lg_filter_requests_groupby.'</label>
                </div>
                <div class="control">
                    <div class="group">
                        <select name="groupBy" class="short">
                            '.$groupBySel.'
                        </select>
                        <select name="groupByDir" class="tiny">
                            <option value="DESC" '.selectionCheck('DESC', $fm['groupByDir']).'>'.lg_filter_requests_descending.'</option>
                            <option value="ASC" '.selectionCheck('ASC', $fm['groupByDir']).'>'.lg_filter_requests_ascending.'</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="urgentinline">'.lg_filter_requests_urgentinline.'</label>
                </div>
                <div class="control">
                    <select name="urgentinline" class="short">
                        <option value="0" '.selectionCheck(0, $fm['urgentinline']).'>'.lg_filter_requests_attop.'</option>
                        <option value="1" '.selectionCheck(1, $fm['urgentinline']).'>'.lg_filter_requests_inline.'</option>
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sShortcut">'.lg_filter_requests_shortcut.'</label>
                </div>
                <div class="control">
                    <select name="sShortcut" id="sShortcut" class="short">
                        <option value="">'.lg_filter_requests_none.'</option>
                        '.$shortcutSel.'
                    </select>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fDisplayTop">'.lg_filter_requests_displaytop.'</label>
                </div>
                <div class="control">
                    <div>
                        <input type="checkbox" class="checkbox" name="fDisplayTop" id="fDisplayTop" value="1" '.checkboxCheck(1, $fm['fDisplayTop']).'>
                        <label for="fDisplayTop" class="switch"></label>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fCustomerFriendlyRSS">'.lg_filter_requests_rssfriendly.'</label>
                </div>
                <div class="control">
                    <div>
                        <input type="checkbox" class="checkbox" name="fCustomerFriendlyRSS" id="fCustomerFriendlyRSS" value="1" '.checkboxCheck(1, $fm['fCustomerFriendlyRSS']).'>
                        <label for="fCustomerFriendlyRSS" class="switch"></label>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fCacheNever">'.lg_filter_requests_nevercache.'</label>
                </div>
                <div class="control">
                    <div>
                        <input type="checkbox" class="checkbox" name="fCacheNever" id="fCacheNever" value="1" '.checkboxCheck(1, $fm['fCacheNever']).'>
                        <label for="fCacheNever" class="switch"></label>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

		</div>';

    $pagebody .= '
                <div class="button-bar">

                    <button tabindex="106" type="submit" name="submit" class="btn accent" id="submit">'.$button.'</button>
                    '.$secondary_button.'
                    <button class="btn" type="button" onClick="run_filter();">'.lg_filter_requests_runfilter.'</button>

                    <input type="checkbox" name="showall" id="showall" value="" style="margin-bottom:0px;"> <label for="showall" class="datalabel" style="display:inline;">'.lg_filter_requests_showall.'</label>
                </div>';

    if (isAdmin() && isset($_GET['xPerson']) && $_GET['xPerson'] != $user['xPerson']) {
        $pagebody .= '<input type="hidden" name="admin_editing" value="1">';
    }

    $pagebody .= '
    <input type="hidden" name="vmode" value="'.$vmode.'">
    <input type="hidden" name="filterid" value="'.$filterid.'">
    </form>
</div>';
