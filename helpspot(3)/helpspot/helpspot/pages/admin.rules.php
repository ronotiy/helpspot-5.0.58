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
include cBASEPATH.'/helpspot/lib/class.mail.rule.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.rules']);
$hidePageFrame = 0;
$pagetitle = lg_admin_rules_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$showdeleted = isset($_GET['showdeleted']) ? 1 : 0;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$fm['xMailRule'] = isset($_GET['ruleid']) ? $_GET['ruleid'] : '';

/*****************************************
ACTION
*****************************************/
//Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && ! empty($fm['xMailRule'])) {
    apiDeleteResource('HS_Mail_Rules', 'xMailRule', $fm['xMailRule'], 'delete');
    return redirect()
        ->route('admin', ['pg' => 'admin.rules'])
        ->with('feedback', lg_admin_rules_fbinactive);
}

//Restore
if (isset($_GET['action']) && $_GET['action'] == 'undelete' && ! empty($fm['xMailRule'])) {
    apiDeleteResource('HS_Mail_Rules', 'xMailRule', $fm['xMailRule'], 'undelete');
    return redirect()
        ->route('admin', ['pg' => 'admin.rules'])
        ->with('feedback', lg_admin_rules_fbrestored);
}

//Add
if (isset($_POST['submit']) && empty($fm['xMailRule'])) {
    $rule = new hs_mail_rule();
    $rule->SetMailRule($_POST);
    $order = $GLOBALS['DB']->GetOne('SELECT MAX(fOrder) FROM HS_Mail_Rules') + 1;

    $GLOBALS['DB']->Execute('INSERT INTO HS_Mail_Rules(sRuleName,fOrder,fDeleted,tRuleDef) VALUES (?,?,?,?)',
                             [$rule->name, $order, 0, hs_serialize($rule)]);

    return redirect()
        ->route('admin', ['pg' => 'admin.rules'])
        ->with('feedback', lg_admin_rules_fbadded);

//Update
} elseif (isset($_POST['submit']) && is_numeric($fm['xMailRule'])) {
    $rule = new hs_mail_rule();
    $rule->SetMailRule($_POST);

    $GLOBALS['DB']->Execute('UPDATE HS_Mail_Rules SET sRuleName=?,tRuleDef=? WHERE xMailRule = ?',
                             [$rule->name, hs_serialize($rule), $fm['xMailRule']]);

    return redirect()
        ->route('admin', ['pg' => 'admin.rules'])
        ->with('feedback', lg_admin_rules_fbedited);
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
$ui = new hs_conditional_ui_mail();

if (! empty($fm['xMailRule'])) {
    $rulerow = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Mail_Rules WHERE xMailRule = ?', [$fm['xMailRule']]);

    $rule = hs_unserialize($rulerow['tRuleDef']);

    $title = lg_admin_rules_edit.': '.$rule->name;
    $button = lg_admin_rules_buttonedit;
    $conditionhtml = $ui->createConditionsUI($rule);
    $actionhtml = $ui->createActionsUI($rule);
    $datatable = '';

    $anyall = $rule->anyall;
    $srulename = $rule->name;

    $option_bizhours = $rule->option_bizhours;

    // Delete button. Only show proper one for deleted/restore
    if ($rulerow['fDeleted'] == 0) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_rules_delwarn.'\',\''.$basepgurl.'&action=delete&ruleid='.$fm['xMailRule'].'\');">'.lg_admin_rules_del.'</button>';
    }

    if ($rulerow['fDeleted'] == 1) {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($rule->name).'\',\''.$basepgurl.'&action=undelete&ruleid='.$fm['xMailRule'].'\');">'.lg_restore.'</button>';
    }
} else {
    $title = lg_admin_rules_add;
    $button = lg_admin_rules_button;
    $conditionhtml = $ui->newCondition();
    $actionhtml = $ui->newAction();

    // build data table
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_rules_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_rules_noshowdel.'</a>';
    }

    $data = apiGetMailRules($showdeleted);

    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_rules_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xMailRule'],
                                            ['type'=>'link', 'label'=>lg_admin_rules_namecol, 'sort'=>0,
                                              'code'=>'<a href="'.$basepgurl.'&ruleid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                              'fields'=>'xMailRule', 'linkfields'=>['xMailRule', 'sRuleName'], ], ],
                                //options
                                ['title_right'=>$showdellink,
                                       'sortable'=>true,
                                       'sortablefields'=>['xMailRule', 'sRuleName'],
                                       'sortabletitle'=>lg_admin_rules_sorttitle,
                                       'sortable_callback'=>'sort_mailrule',
                                       'title'=>$pagetitle.$dellable, ], $basepgurl);

    $anyall = '';
    $srulename = '';

    $delbutton = '';
    $option_bizhours = '';
}

$anyallselect = '
<select name="anyall" style="margin: 0px 8px;">
	<option value="all" '.selectionCheck('all', $anyall).'>'.lg_admin_rules_all.'</option>
	<option value="any" '.selectionCheck('any', $anyall).'>'.lg_admin_rules_any.'</option>
</select>';

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
	function checkform(){
		var er = "";

		if($("sRuleName").value == ""){
			er += "'.hs_jshtmlentities(lg_admin_rules_noname).'\n";
		}

		if(er.length != 0){
			hs_alert(er);
			return false;
		}

		return true;
	}

	function sort_mailrule(id){
		reorder_call(id,"mailrule_order");
	}

</script>
';

/*****************************************
PAGE OUTPUTS
****************************************/
if (! empty($fb)) {
    $pagebody .= $fb;
}

$pagebody .= $datatable;

$pagebody .= '	<form action="'.$basepgurl.'&ruleid='.$fm['xMailRule'].'" method="POST" name="ruleform" id="ruleform" onSubmit="return checkform();">';
$pagebody .= csrf_field();
$pagebody .= renderInnerPageheader($title, lg_admin_rules_note);
$pagebody .= '
    <div class="card padded">
        <div class="fr">
            <div class="label">
                <label for="sRuleName" class="datalabel req">' . lg_admin_rules_name . '</label>
            </div>
            <div class="control">
                <input name="sRuleName" id="sRuleName" type="text" size="40" value="' . formClean($srulename) . '" class="' . errorClass('sRuleName') . '">' . errorMessage('sRuleName') . '
            </div>
        </div>

	    <div class="sectionhead" style="align-items: center;justify-content:flex-start;">'.sprintf(lg_admin_rules_anyall, $anyallselect).':</div>

	    <div id="cond_wrapper">'.$conditionhtml.'</div>

        <div class="condition-menu">
    		<img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
			 onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'cond_wrapper\', arguments[0].responseText); }', 'conditionalui_mail', 'do=new_condition').'">
	    </div>

	    <div class="sectionhead">'.lg_admin_rules_then.':</div>

	    <div id="action_wrapper">'.$actionhtml.'</div>

        <div class="condition-menu">
            <img src="'.static_url().'/static/img5/add-circle.svg" class="hand svg28 conditionadd" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
                onClick="'.hsAJAXinline('function(){ new Insertion.Bottom(\'action_wrapper\', arguments[0].responseText); }', 'conditionalui_mail', 'do=new_action').'">
        </div>

        <div class="sectionhead">'.lg_admin_rules_options.':</div>

        <div class="fr">
            <div class="label tdlcheckbox">
                <label for="option_bizhours" class="datalabel">'.lg_admin_rules_hourlabel.'</label>
            </div>
            <div class="control">
                <select name="option_bizhours" id="option_bizhours">
                    <option value="" '.selectionCheck('', $option_bizhours).'>'.lg_admin_rules_anyhours.'</option>
                    <option value="bizhours" '.selectionCheck('bizhours', $option_bizhours).'>'.lg_admin_rules_bizhours.'</option>
                    <option value="offhours" '.selectionCheck('offhours', $option_bizhours).'>'.lg_admin_rules_offhours. '</option>
                </select>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent" ' . $buttonclick . '>' . $button . '</button>' . $delbutton . '
    </div>
</form>
';
