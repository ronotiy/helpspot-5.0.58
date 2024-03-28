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
LANG
*****************************************/
$GLOBALS['lang']->load(['ajax_gateway', 'conditional.ui']);

/*****************************************
LIBS
*****************************************/
include_once cBASEPATH.'/helpspot/lib/class.conditional.ui.php';
include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.triggers']);
$hidePageFrame = 0;
$pagetitle = lg_admin_trigger_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$showdeleted = isset($_GET['showdeleted']) ? 1 : 0;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$fm['xTrigger'] = isset($_GET['triggerid']) ? $_GET['triggerid'] : '';

/*****************************************
ACTION
*****************************************/
//Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && ! empty($fm['xTrigger'])) {
    apiDeleteResource('HS_Triggers', 'xTrigger', $fm['xTrigger'], 'delete');
    return redirect()
        ->route('admin', ['pg' => 'admin.triggers'])
        ->with('feedback', lg_admin_trigger_fbinactive);
}

//Restore
if (isset($_GET['action']) && $_GET['action'] == 'undelete' && ! empty($fm['xTrigger'])) {
    apiDeleteResource('HS_Triggers', 'xTrigger', $fm['xTrigger'], 'undelete');
    return redirect()
        ->route('admin', ['pg' => 'admin.triggers'])
        ->with('feedback', lg_admin_trigger_fbrestored);
}

//Add
if (isset($_POST['submit']) && empty($fm['xTrigger'])) {
    $trigger = new hs_trigger();
    $trigger->SetTrigger($_POST);
    $order = $GLOBALS['DB']->GetOne('SELECT MAX(fOrder) FROM HS_Triggers') + 1;

    $GLOBALS['DB']->Execute('INSERT INTO HS_Triggers(sTriggerName,fOrder,fDeleted,fType,tTriggerDef) VALUES (?,?,?,?,?)',
                             [$trigger->name, $order, 0, $trigger->type, hs_serialize($trigger)]);

    return redirect()
        ->route('admin', ['pg' => 'admin.triggers'])
        ->with('feedback', lg_admin_trigger_fbadded);

//Update
} elseif (isset($_POST['submit']) && is_numeric($fm['xTrigger'])) {
    $trigger = new hs_trigger();
    $trigger->SetTrigger($_POST);

    $GLOBALS['DB']->Execute('UPDATE HS_Triggers SET sTriggerName=?,tTriggerDef=?,fType=? WHERE xTrigger = ?',
                             [$trigger->name, hs_serialize($trigger), $trigger->type, $fm['xTrigger']]);

    return redirect()
        ->route('admin', ['pg' => 'admin.triggers'])
        ->with('feedback', lg_admin_trigger_fbedited);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
$ui = new hs_conditional_ui_trigger();

if (! empty($fm['xTrigger'])) {
    $triggerrow = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Triggers WHERE xTrigger = ?', [$fm['xTrigger']]);

    $trigger = hs_unserialize($triggerrow['tTriggerDef']);

    $title = lg_admin_trigger_edit.': '.$trigger->name;
    $button = lg_admin_trigger_buttonedit;
    $conditionhtml = $ui->createConditionsUI($trigger);
    $actionhtml = $ui->createActionsUI($trigger);
    $datatable = '';
    $buttonclick = '';

    $anyall = $trigger->anyall;
    $type = $trigger->type;
    $option_bizhours = $trigger->option_bizhours;
    $option_log = $trigger->option_log;
    $option_no_notifications = $trigger->option_no_notifications;
    $option_direct_call_only = $trigger->option_direct_call_only;
    $sTriggerName = $trigger->name;

    // Delete button. Only show proper one for deleted/restore
    if ($triggerrow['fDeleted'] == 0) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_trigger_delwarn.'\',\''.$basepgurl.'&action=delete&triggerid='.$fm['xTrigger'].'\');">'.lg_admin_trigger_del.'</button>';
    }

    if ($triggerrow['fDeleted'] == 1) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities(htmlentities($trigger->name)).'\',\''.$basepgurl.'&action=undelete&triggerid='.$fm['xTrigger'].'\');">'.lg_restore.'</button>';
    }
} else {
    $title = lg_admin_trigger_add;
    $button = lg_admin_trigger_button;

    $trigger = new hs_trigger();
    $rowid = $ui->generateID('condition');
    $default = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>'is', $rowid.'_3'=>1];
    $trigger->SetTrigger($default);

    $conditionhtml = $ui->createConditionsUI($trigger);
    $actionhtml = $ui->newAction();
    $buttonclick = ' onClick="if(!allow_submit){hs_confirm(\''.hs_jshtmlentities(lg_admin_trigger_confirm).'\',function(){allow_submit=true;$jq(\'#submit_button\').click();});return false;}"';

    // build data table
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_trigger_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_trigger_noshowdel.'</a>';
    }

    $data = apiGetTriggers($showdeleted);

    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_trigger_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xTrigger'],
                                            ['type'=>'link', 'label'=>lg_admin_trigger_namecol, 'sort'=>0,
                                              'code'=>'<a href="'.$basepgurl.'&triggerid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                              'fields'=>'xTrigger', 'linkfields'=>['xTrigger', 'sTriggerName'], ]],
                                //options
                                ['title_right'=>$showdellink,
                                       'sortable'=>true,
                                       'sortablefields'=>['xTrigger', 'sTriggerName'],
                                       'sortabletitle'=>lg_admin_trigger_sorttitle,
                                       'sortable_callback'=>'sort_trigger',
                                       'title'=>$pagetitle.$dellable], $basepgurl);

    $anyall = '';
    $type = 2;
    $sTriggerName = '';

    $delbutton = '';
}

$anyallselect = '
<select name="anyall" style="margin: 0px 8px;">
	<option value="all" '.selectionCheck('all', $anyall).'>'.lg_admin_trigger_all.'</option>
	<option value="any" '.selectionCheck('any', $anyall).'>'.lg_admin_trigger_any.'</option>
</select>';

$createupdateselect = '
<select name="fType" id="fType" style="margin: 0px 8px;">
	<option value="1" '.selectionCheck(1, $type).'>'.lg_admin_trigger_oncreate.'</option>
	<option value="2" '.selectionCheck(2, $type).'>'.lg_admin_trigger_onupdate.'</option>
</select>';

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
	nottested = true;
	allow_submit = false;
	function checkform(){
		var er = "";

		if($("sTriggerName").value == ""){
			er += "'.hs_jshtmlentities(lg_admin_trigger_noname).'\n";
		}

		if(er.length != 0){
			hs_alert(er);
			return false;
		}

		return true;
	}

	function sort_trigger(id){
		reorder_call(id,"trigger_order");
	}

	function trigger_check(cond,val){
		$(val).show();

		if($F(cond) == "changed" || $F(cond) == "not_changed"){
			if($(val).value) $(val).value = "";
			$(val).hide();
		}

		if($F(cond.replace("_1","_2")) == "matches"){
			$(val).value = "'.lg_conditional_phpregex.'";
		}
	}

	//On load init the changes which hide the "did change" items
	document.observe("dom:loaded", function (){
		$$(\'select[onchange^="trigger_check"]\').each(function(e){
			e.onchange();
		});
	});

</script>
';

/*****************************************
PAGE OUTPUTS
****************************************/
if (! empty($fb)) {
    $pagebody .= $fb;
}

$pagebody .= $datatable;

$pagebody .= '<form action="'.$basepgurl.'&triggerid='.$fm['xTrigger'].'" method="POST" name="triggerform" id="triggerform" onSubmit="return checkform();">';
$pagebody .= csrf_field();
$pagebody .= renderInnerPageheader($title, lg_admin_trigger_note);
$pagebody .= '
	<div class="card padded">
		<div class="fr">
            <div class="label">
                <label for="sTriggerName" class="datalabel req">' . lg_admin_trigger_name . '</label>
            </div>
            <div class="control">
                <input name="sTriggerName" id="sTriggerName" type="text" size="40" value="' . formClean($sTriggerName) . '" class="' . errorClass('sTriggerName') . '">' . errorMessage('sTriggerName') . '
            </div>
        </div>

		<div class="sectionhead" style="align-items: center;justify-content:flex-start;">'.sprintf(lg_admin_trigger_anyall, $createupdateselect, $anyallselect).':</div>

		<div id="cond_wrapper">'.$conditionhtml.'</div>

		<div class="condition-menu">
			<img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
			 onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_trigger', 'do=new_condition').'">
		</div>

		<div class="sectionhead">'.lg_admin_trigger_then.':</div>

		<div id="action_wrapper">'.$actionhtml.'</div>

		<div class="condition-menu">
			<img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
				onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'action_wrapper\', arguments[0].responseText); }', 'conditionalui_trigger', 'do=new_action').'">
		</div>

		<div class="sectionhead">'.lg_admin_trigger_options. ':</div>

		<div class="fr">
            <div class="label">
                <label for="option_bizhours" class="datalabel">' . lg_admin_trigger_hourlabel . '</label>
            </div>
            <div class="control">
                <select name="option_bizhours" id="option_bizhours">
					<option value="" ' . selectionCheck('', $option_bizhours) . '>' . lg_admin_trigger_anyhours . '</option>
					<option value="bizhours" ' . selectionCheck('bizhours', $option_bizhours) . '>' . lg_admin_trigger_bizhours . '</option>
					<option value="offhours" ' . selectionCheck('offhours', $option_bizhours) . '>' . lg_admin_trigger_offhours . '</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
            <div class="label">
                <label for="option_log" class="datalabel">' . lg_admin_trigger_log . '</label>
            </div>
            <div class="control">
                <select name="option_log" id="option_log">
					<option value="1" ' . selectionCheck(1, $option_log) . '>' . lg_on . '</option>
					<option value="0" ' . selectionCheck(0, $option_log) . '>' . lg_off . '</option>
				</select>
			</div>
		</div>

		<div class="hr"></div>

		<div class="fr">
			<div class="label tdlcheckbox">
				<label class="datalabel" for="option_no_notifications">' . lg_admin_trigger_options_nonotificaitons . '</label>
			</div>
			<div class="control">
				<input type="checkbox" class="checkbox" id="option_no_notifications" name="option_no_notifications" value="1" ' . checkboxCheck(1, $option_no_notifications) . '/>
                <label for="option_no_notifications" class="switch"></label>
			</div>
		</div>
	</div>

    <div class="button-bar space">
        <button type="submit" name="submit" id="submit_button" class="btn accent" ' . $buttonclick . '>' . $button . '</button>' . $delbutton . '
    </div>
</form>';
