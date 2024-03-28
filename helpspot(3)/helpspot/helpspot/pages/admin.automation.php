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
include cBASEPATH.'/helpspot/lib/class.conditional.ui.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.automation']);
$hidePageFrame = 0;
$pagetitle = lg_admin_automation_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$showdeleted = isset($_GET['showdeleted']) ? 1 : 0;

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$option_once   = 1;
$fm['xAutoRule'] = isset($_GET['ruleid']) ? $_GET['ruleid'] : '';

/*****************************************
ACTION
*****************************************/
//Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && ! empty($fm['xAutoRule'])) {
    apiDeleteResource('HS_Automation_Rules', 'xAutoRule', $fm['xAutoRule'], 'delete');
    return redirect()
        ->route('admin', ['pg' => 'admin.automation'])
        ->with('feedback', lg_admin_automation_fbinactive);
}

//Restore
if (isset($_GET['action']) && $_GET['action'] == 'undelete' && ! empty($fm['xAutoRule'])) {
    apiDeleteResource('HS_Automation_Rules', 'xAutoRule', $fm['xAutoRule'], 'undelete');
    return redirect()
        ->route('admin', ['pg' => 'admin.automation'])
        ->with('feedback', lg_admin_automation_fbrestored);
}

//Add
if (isset($_POST['submit']) && empty($fm['xAutoRule'])) {
    $rule = new hs_auto_rule();
    $rule->SetAutoRule($_POST);

    \Illuminate\Support\Facades\DB::transaction(function() use($rule) {
        $order = $GLOBALS['DB']->GetOne('SELECT MAX(fOrder) FROM HS_Automation_Rules') + 1;

        /** @var \HS\AutomationRule $newRule */
        $newRule = \HS\AutomationRule::create([
            'sRuleName' => $rule->name,
            'fOrder' => $order,
            'fDeleted' => false,
            'tRuleDef' => hs_serialize($rule),
            'fDirectOnly' => isset($_POST['option_direct_call_only']),
            'sSchedule' => isset($_POST['option_schedule']) ? $_POST['option_schedule'] : 'every_minute',
        ]);
        $newRule->setNextRunTime();
    });


    return redirect()
        ->route('admin', ['pg' => 'admin.automation'])
        ->with('feedback', lg_admin_automation_fbadded);

//Update
} elseif (isset($_POST['submit']) && is_numeric($fm['xAutoRule'])) {
    $rule = new hs_auto_rule();
    $rule->SetAutoRule($_POST);

    \Illuminate\Support\Facades\DB::transaction(function() use($rule, $fm) {
        /** @var \HS\AutomationRule $updatedRule */
        $updatedRule = \HS\AutomationRule::findOrFail($fm['xAutoRule']);

        $updatedRule->sRuleName = $rule->name;
        $updatedRule->tRuleDef = hs_serialize($rule);
        $updatedRule->fDirectOnly = isset($_POST['option_direct_call_only']);
        $updatedRule->sSchedule = isset($_POST['option_schedule']) ? $_POST['option_schedule'] : $updatedRule->sSchedule;

        $updatedRule->save();
        $updatedRule->setNextRunTime();
    });

    return redirect()
        ->route('admin', ['pg' => 'admin.automation'])
        ->with('feedback', lg_admin_automation_fbedited);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
$ui = new hs_conditional_ui_auto();

if (! empty($fm['xAutoRule'])) {
    $rulerow = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Automation_Rules WHERE xAutoRule = ?', [$fm['xAutoRule']]);

    $rule = hs_unserialize($rulerow['tRuleDef']);
    $title = lg_admin_automation_edit.': '.$rule->name;
    $button = lg_admin_automation_buttonedit;
    $conditionhtml = $ui->createConditionsUI($rule);
    $actionhtml = $ui->createActionsUI($rule);
    $datatable = '';
    $buttonclick = '';

    $anyall = $rule->anyall;
    $option_no_notifications = $rule->option_no_notifications;
    $option_direct_call_only = $rule->option_direct_call_only;
    $option_once = $rule->option_once;
    $srulename = $rule->name;
    $option_schedule = $rulerow['sSchedule'];

    // Delete button. Only show proper one for deleted/restore
    if ($rulerow['fDeleted'] == 0) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_automation_delwarn.'\',\''.$basepgurl.'&action=delete&ruleid='.$fm['xAutoRule'].'\');">'.lg_admin_automation_del.'</button>';
    }

    if ($rulerow['fDeleted'] == 1) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($rule->name).'\',\''.$basepgurl.'&action=undelete&ruleid='.$fm['xAutoRule'].'\');">'.lg_restore.'</button>';
    }
} else {
    $title = lg_admin_automation_add;
    $button = lg_admin_automation_button;

    $rule = new hs_auto_rule();
    $rowid = $ui->generateID('condition');
    $default = [$rowid.'_1'=>'fOpen', $rowid.'_2'=>1];
    $rule->SetAutoRule($default);

    $conditionhtml = $ui->createConditionsUI($rule);
    $actionhtml = $ui->newAction();
    $buttonclick = ' onClick="if(checkform() && !allow_submit){hs_confirm(\''.hs_jshtmlentities(lg_admin_automation_confirm).'\',function(){allow_submit=true;$jq(\'#submit_button\').click();});return false;}"';

    // build data table
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_automation_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_automation_noshowdel.'</a>';
    }

    $data = apiGetAutoRules($showdeleted);

    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_automation_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xAutoRule'],
                                            ['type'=>'link', 'label'=>lg_admin_automation_namecol, 'sort'=>0,
                                              'code'=>'<a href="'.$basepgurl.'&ruleid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                              'fields'=>'xAutoRule', 'linkfields'=>['xAutoRule', 'sRuleName'], ], ],
                                //options
                                ['title_right'=>$showdellink,
                                       'sortable'=>true,
                                       'sortablefields'=>['xAutoRule', 'sRuleName'],
                                       'sortabletitle'=>lg_admin_automation_sorttitle,
                                       'sortable_callback'=>'sort_autorule',
                                       'title'=>$pagetitle.$dellable, ], $basepgurl);

    $anyall = '';
    $srulename = '';

    $delbutton = '';
}

$anyallselect = '
<select name="anyall" style="margin: 0px 8px;">
	<option value="all" '.selectionCheck('all', $anyall).'>'.lg_admin_automation_all.'</option>
	<option value="any" '.selectionCheck('any', $anyall).'>'.lg_admin_automation_any.'</option>
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

		if($("sRuleName").value == ""){
			er += "'.hs_jshtmlentities(lg_admin_automation_noname).'\n";
		}

		if(nottested){
			er += "\n'.hs_jshtmlentities(lg_admin_automation_nottested).'\n";
		}

		if(er.length != 0){
			hs_alert(er);
			return false;
		}

		return true;
	}

	function sort_autorule(id){
		reorder_call(id,"autorule_order");
	}

	$jq(document).ready(function(){
		$jq("#automation_test").click(function(){
			nottested = false;
			$jq("#test_condition_result").html(ajaxLoading("'.lg_loading.'"));
			$jq.post("'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'auto_testcondition']).'", $jq("#ruleform").serialize(), function(data) {
			 	$jq("#test_condition_result").html(data);
			});
		});

		$jq("#option_direct_call_only").change(function() {
            $jq("#option_schedule").prop("disabled", $jq(this).is(":checked"))
		});
		$jq("#option_direct_call_only").trigger("change");
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

$pagebody .= '	<form action="'.$basepgurl.'&ruleid='.$fm['xAutoRule'].'" method="POST" name="ruleform" id="ruleform" onSubmit="return checkform();">';
$pagebody .= csrf_field();
$pagebody .= renderInnerPageheader($title, lg_admin_automation_note);

$pagebody .= '
	<div class="card padded">
        <div class="fr">
            <div class="label">
                <label for="sRuleName" class="datalabel req">'.lg_admin_automation_name.'</label>
            </div>
            <div class="control">
                <input name="sRuleName" id="sRuleName" type="text" size="40" value="'.formClean($srulename).'" class="'.errorClass('sRuleName').'">'.errorMessage('sRuleName'). '
            </div>
        </div>

	    <div class="sectionhead" style="align-items: center;justify-content:flex-start;">'.sprintf(lg_admin_automation_anyall, $anyallselect).':</div>

	    <div id="cond_wrapper">'.$conditionhtml.'</div>

        <div class="condition-menu" style="margin-top: 10px;">
            <button class="btn rnd" type="button" id="automation_test">'.lg_conditional_at_testcond.'</button>
            <input type="checkbox" name="showall" id="showall" value="" style="margin-bottom:0px;"> <label for="showall" class="datalabel" style="display:inline;">'.lg_conditional_at_showall.'</label>

            <img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
                onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_condition').'">
        </div>

	    <div id="test_condition_result" style="margin-top:4px;"></div>

	    <div class="sectionhead">'.lg_admin_automation_then.':</div>

	    <div id="action_wrapper">'.$actionhtml.'</div>

        <div class="condition-menu">
            <img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
                onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'action_wrapper\', arguments[0].responseText); }', 'conditionalui_auto', 'do=new_action').'">
        </div>

        <div class="sectionhead">'.lg_admin_automation_options. ':</div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label class="datalabel" for="option_once">' . lg_admin_automation_once . '</label>
            </div>
			<div class="control">
				<input type="checkbox" class="checkbox" id="option_once" name="option_once" value="1" ' . checkboxCheck(1, $option_once) . '/>
                <label for="option_once" class="switch"></label>
			</div>
		</div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label class="datalabel" for="option_no_notifications">'.lg_admin_automation_options_nonotificaitons.'</label>
            </div>
            <div class="control">
                <input type="checkbox" class="checkbox" id="option_no_notifications" name="option_no_notifications" value="1" '.checkboxCheck(1, $option_no_notifications). '/>
                <label for="option_no_notifications" class="switch"></label>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="datalabel" for="option_schedule">'.lg_admin_automation_options_custom_schedule.'</label>
                <div class=info tdcheckdesc>'.lg_admin_automation_options_custom_scheduleex.'</div>
            </div>
            <div class="control">
                <select id="option_schedule" name="option_schedule">
                    <option '.selectionCheck('every_minute', $option_schedule). ' value="every_minute">'.lg_admin_automation_options_schedule_every_minute.'</option>
                    <option '.selectionCheck('every_5_minutes', $option_schedule). ' value="every_5_minutes">'.lg_admin_automation_options_schedule_every_5_minutes.'</option>
                    <option '.selectionCheck('every_hour', $option_schedule). ' value="every_hour">'.lg_admin_automation_options_schedule_every_hour.'</option>
                    <option '.selectionCheck('twice_daily', $option_schedule). ' value="twice_daily">'.lg_admin_automation_options_schedule_twice_daily.'</option>
                    <option '.selectionCheck('daily', $option_schedule). ' value="daily">'.lg_admin_automation_options_schedule_daily.'</option>
                    <option '.selectionCheck('weekly', $option_schedule). ' value="weekly">'.lg_admin_automation_options_schedule_weekly.'</option>
                    <option '.selectionCheck('monthly', $option_schedule). ' value="monthly">'.lg_admin_automation_options_schedule_monthly.'</option>
                </select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label class="datalabel" for="option_direct_call_only">'.lg_admin_automation_options_directcallonly.'</label>
                <div class="info tdcheckdesc">'.lg_admin_automation_options_directcallonlyex.'</div>
            </div>
            <div class="control">
                <input type="checkbox" class="checkbox" id="option_direct_call_only" name="option_direct_call_only" value="1" '.checkboxCheck(1, $option_direct_call_only). '/>
                <label for="option_direct_call_only" class="switch"></label>
            </div>
        </div>
    </div>

    <div class="button-bar space">
        <button id="submit_button" type="submit" name="submit" class="btn accent" ' . $buttonclick . '>' . $button . '</button>' . $delbutton . '
    </div>

</form>';
