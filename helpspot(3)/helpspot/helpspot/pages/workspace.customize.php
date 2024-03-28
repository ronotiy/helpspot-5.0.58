<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('filter.requests');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'workspace.customize']);
$pagetitle = lg_custworkspace_title;
$tab = 'nav_workspace';
$subtab = '';
$formerrors = [];
$area = isset($_REQUEST['area']) ? trim($_REQUEST['area']) : '';

//Figure out current columns
if (! hs_empty($user['tWorkspace'])) {
    $cols = hs_unserialize($user['tWorkspace']);
} else {
    $cols = [];
}

/*****************************************
ACTIONS
*****************************************/
if (! empty($area) && isset($_POST['submit'])) {
    $cols[$area] = $_POST['displayColumns'];

    //Find widths
    foreach ($_POST as $k=>$v) {
        if (strpos($k, 'column_width_') !== false) {
            $field = str_replace('column_width_', '', $k);
            $field = str_replace('_value', '', $field);
            $cols[$area.'_widths'][$field] = $v;
        }
    }

    $newcols = hs_serialize($cols);
    $GLOBALS['DB']->Execute('UPDATE HS_Person SET tWorkspace = ? WHERE xPerson = ?', [$newcols, $user['xPerson']]);
    return redirect()
        ->route('admin', ['pg' => 'workspace', 'show' => $area]);
}

/*****************************************
PAGE TEMPLATE COMPONENTS
*****************************************/

if ($area == 'inbox') {
    $boxheader = lg_custworkspace_inbox;
    $current_columns = isset($cols['inbox']) ? $cols['inbox'] : $GLOBALS['defaultWorkspaceCols'];
    $current_columns_widths = isset($cols['inbox_widths']) ? $cols['inbox_widths'] : [];
} elseif ($area == 'myq') {
    $boxheader = lg_custworkspace_myq;
    $current_columns = isset($cols['myq']) ? $cols['myq'] : $GLOBALS['defaultWorkspaceCols'];
    $current_columns_widths = isset($cols['myq_widths']) ? $cols['myq_widths'] : [];
} else {
    die();
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
	function setColumnWidth(id){
		var value = $F(id + "_value");
		var content = \'<input type="text" value="\' + value + \'" id="\'+id+\'_textbox" name="\'+id+\'_textbox" size="10" /> <button type="button" class="btn inline-action" onclick="insertColumnWidth(\\\'\' + id + \'\\\');">'.hs_jshtmlentities(lg_custworkspace_setcolumnwidthsave).'</button><div class="tiptext">'.hs_jshtmlentities(lg_custworkspace_setcolumnwidthnote).'</div>\';
		
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
		new Tip(id, "<div class=\"tiptext\">'.hs_jshtmlentities(lg_custworkspace_setcolumnwidthfill).'</div>", {
				title: false,
				border: 0,
				radius: 0,
                className: "hstinytipfat",
                stem: "bottomRight",
				hideOn: { element: "closeButton", event: "click" },
				showOn: "click",
				hideOn: false,
				hideAfter: 3,
				hideOthers: true,
				width: "auto",
				hook: { target: "topMiddle", tip: "bottomRight" }
			});	
			
		$(id).prototip.show();		
	}	
</script>	
';

//$onload = 'onloadRowHighlight(\'cols\','.count($GLOBALS['filterCols']).');';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= renderPageheader($boxheader);

$pagebody .= '
<form action="'.$basepgurl.'" method="POST" name="customize" id="customize" onsubmit="safari_order_fix(\'displayColumns[]\',\'customize\');" class="padded">
    '.csrf_field().'
    	
	<div class="sortablelist" id="selected_columns" style="width:50%;">';

            foreach ($current_columns as $k=>$v) {
                $label = isset($GLOBALS['filterCols'][$v]['label2']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
                $colwidth = isset($current_columns_widths[$v]) ? $current_columns_widths[$v] : $GLOBALS['filterCols'][$v]['width'];
                $onclick = isset($GLOBALS['filterCols'][$v]['hideflow']) ? 'overflowMsg' : 'setColumnWidth'; //Show a special message to overflow columns
                $pagebody .= '<div class="sortable_filter" id="selected_col_'.$v.'">
							  <img src="'.static_url().'/static/img5/grip-lines-regular.svg" style="vertical-align: middle;cursor:move;margin-right:6px;" class="drag_handle">
                              <span>'.$label.'</span>
							  <span id="column_width_'.formClean($v).'" class="hand filter_width_text" onclick="'.$onclick.'(this.id);">'.($colwidth ? $colwidth : '<img src="'.static_url().'/static/img5/arrows-h-solid.svg" />').'</span>
							  <img src="'.static_url().'/static/img5/remove.svg" style="vertical-align: middle;cursor:pointer;" onClick="return confirmRemove(\'selected_col_'.$v.'\', confirmListDelete);"> 
                              <input type="hidden" id="column_width_'.formClean($v).'_value" name="column_width_'.formClean($v).'_value" value="'.formClean($colwidth).'">							  
							  <input type="hidden" name="displayColumns[]" value="'.$v.'">
							  </div>';
            }

    $pagebody .= '</div>';

    $pagebody .= '<select name="select_col" id="select_col" onChange="addSortableColumn(\'select_col\',\'selected_columns\',\'displayColumns[]\');"><option value="none">'.lg_custworkspace_selectcol.'</option>
				  '.createFilterColumnList($current_columns).'
				  </select>';

    $pagebody .= '		
	<script type="text/javascript">
	// <![CDATA[
	var confirmListDelete = "'.hs_jshtmlentities(lg_custworkspace_removecol).'";
	Sortable.create("selected_columns",
		{tag:"div", constraint: "vertical", handle: "drag_handle"});
	// ]]>
	</script>	

    <div class="button-bar">
        <button tabindex="106" type="submit" name="submit" class="btn accent" id="submit">'.$boxheader.'</button>
    </div>

    <input type="hidden" name="area" value="'.$area.'">
</form>';
