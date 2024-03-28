<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

//Don't let the operation timeout
set_time_limit(0);

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
				<script type="text/javascript" language="JavaScript">
				//FUNCTION TO EDIT TAGS
				function edit_tag(id, tag_id){
					//Figure out if editor is already in use
					var editors = $$(".editor_ok_button");

					if(editors.size() == 0){
						var inplace_editor = new Ajax.InPlaceEditor(id+"_text", "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'customfieldid' => (isset($_GET['customfieldid']) && is_numeric($_GET['customfieldid']) ? $_GET['customfieldid'] : ''), 'action' => 'editcustomdrop']).'",
												{okText: "'.hs_jshtmlentities(lg_save).'",
												cancelText: "'.hs_jshtmlentities(lg_cancel).'",
												savingText: "'.hs_jshtmlentities(lg_saving). '",
												formClassName: "edit_in_place_form",
												size: 40,
												callback: function(form,text){
													$(id+"_hidden").value = encodeURIComponent(text);
													return "value="+text+"&index="+tag_id;
												}});
						//Open editor
						inplace_editor.enterEditMode("click");
					}
				}

				$jq().ready(function(){
					function hideAll() {
						$jq("#sampleselect_block").hide();
						$jq("#sampledrilldown_block").hide();
						$jq("#sampletext_block").hide();
						$jq("#samplelrgtext_block").hide();
						$jq("#sampledate_block").hide();
						$jq("#sampledatetime_block").hide();
						$jq("#sampleregex_block").hide();
						$jq("#sampleajax_block").hide();
						$jq("#samplecheckbox_block").hide();
						$jq("#samplenumtext_block").hide();
						$jq("#sampledecimal_block").hide();
					}
					$jq("#type").on("change", function(){
						hideAll();
						var id = $jq(this).val();
						$jq("#sample"+id+"_block").show();
					});
					hideAll();
					$jq("#sampleselect_block").show();
				});


				function showcustom(id){
					$("sampleselect_block").hide();
					$("sampledrilldown_block").hide();
					$("sampletext_block").hide();
					$("lrgsampletext_block").hide();
					$("sampledate_block").hide();
					$("sampledatetime_block").hide();
					$("sampleregex_block").hide();
					$("sampleajax_block").hide();
					$("samplecheckbox_block").hide();
					$("samplenumtext_block").hide();
					$("sampledecimal_block").hide();

					//Show the one that is selected
					$(id).show();
				}

				add_open = false;
				function add_to_group(id, parent){
					if(!add_open){
						add_open = true;
						$(id).hide();

						id2 = $(id + "_form");
						$(id2).innerHTML = \'<input type="text" name="new_value" id="new_value" style="width:160px;" onkeypress="return noenter(event, \\\'save\\\');" value="" ><input type="hidden" name="new_value_level" id="new_value_level" value="\' + parent + \'" /> <button name="save" id="save" class="btn inline-action accent" style="margin-right:3px;" onclick="update_drill_list();return false;" />'.lg_save.'</button> <button name="cancel" class="btn secondary" onclick="cancel_add_to_group(\\\'\' + id + \'\\\');return false;" />'.lg_cancel.'</button>\';
						$("new_value").focus();
					}else{
						hs_alert("'.hs_jshtmlentities(lg_admin_cfields_drilladdalert).'");
					}
				}

				function cancel_add_to_group(id){
					$(id).show();
					add_open = false;
					$(id + "_form").innerHTML = "";
				}

				function remove_from_group(path){
					hs_confirm("'.hs_jshtmlentities(lg_admin_cfields_drillremovealert).'",function(){
						new Insertion.Top("drilldown_wrapper", \'<input type="hidden" name="remove_value" id="remove_value" value="\' + path + \'" />\');
						update_drill_list();
					});
				}

				function add_sub_element(wrapid, id, parent){
					temp = $(id + "_wrapper").innerHTML;
					$(id + "_wrapper").innerHTML = "";
					$(wrapid + "_sublist").innerHTML = "<ul><li>" + temp + "</li></ul>";
					add_to_group(id, parent);
				}

				function ajax_sample(){
					$(\'sampleajax_example\').innerHTML = "'.hs_jshtmlentities(lg_loading).'";
					$(\'sampleajax_example\').show();
					setTimeout("ajax_data();", 1000);
				}

				function ajax_data(){
					var d = "<select onchange=\"$(\'sampleajax\').value=$F(\'ajax_example_select\');$(\'sampleajax_example\').innerHTML=\'\'\" id=\"ajax_example_select\"><option></option><option value=\"Bob Jones\">Invoice Name</option><option value=\"297239\">Reference Number</option><option value=\"729237923\">Transfer ID</option></select>";

					$(\'sampleajax_example\').innerHTML = d;
				}

				function showVisibility(){
					if($("isAlwaysVisible")){
						if($F("isAlwaysVisible") == 0){
							$("categoryListBox").show();
						}else{
							$("categoryListBox").hide();
						}
					}
				}

				function sort_cf(id){
					reorder_call(id,"cf_order");
				}
				</script>';

$onload = 'showVisibility();';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.tools.customfields']);
$hidePageFrame = 0;
$pagetitle = lg_admin_cfields_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedbackArea = '';
$button = lg_admin_cfields_savefield;
$fieldsTable = '';
$formTitle = '';
$formnote = '';
$textoutput = '';
$formOnSubmit = '';
$editmode = isset($_POST['editmode']) ? $_POST['editmode'] : 0;
$vmode = isset($_POST['vmode']) ? $_POST['vmode'] : 1;
$type = isset($_POST['type']) ? $_POST['type'] : '';

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$delbutton = '';

// Table where custom fields are added
$tablename = 'HS_Request';

$fm['fieldName'] = isset($_POST['fieldName']) ? $_POST['fieldName'] : '';
$fm['isRequired'] = isset($_POST['isRequired']) ? $_POST['isRequired'] : 0;
$fm['isPublic'] = isset($_POST['isPublic']) ? $_POST['isPublic'] : 0;
$fm['isAlwaysVisible'] = isset($_POST['isAlwaysVisible']) ? $_POST['isAlwaysVisible'] : 0;
$fm['sListItems'] = isset($_POST['sListItems']) ? $_POST['sListItems'] : '';
$fm['sListItemsColors'] = isset($_POST['sListItemsColors']) ? $_POST['sListItemsColors'] : '';
$fm['sTxtSize'] = isset($_POST['sTxtSize']) ? $_POST['sTxtSize'] : '';
$fm['lrgTextRows'] = isset($_POST['lrgTextRows']) ? $_POST['lrgTextRows'] : '';
$fm['iDecimalPlaces'] = isset($_POST['iDecimalPlaces']) ? $_POST['iDecimalPlaces'] : 0;
$fm['sRegex'] = isset($_POST['sRegex']) ? $_POST['sRegex'] : '';
$fm['sAjaxUrl'] = isset($_POST['sAjaxUrl']) ? $_POST['sAjaxUrl'] : '';
$fm['drilldown_array'] = isset($_POST['drilldown_array']) ? $_POST['drilldown_array'] : '';

/* Anytime this page is visited clear the cache.
   Allows any changes to clear the cache and also acts as an emergency cache clear */
\Facades\HS\Cache\Manager::forgetGroup('categories')
    ->forget([
        \Facades\HS\Cache\Manager::key('CACHE_CUSTOMFIELD_KEY'),
    ]);

if (isset($_GET['error'])) {
    $formerrors['errorBoxText'] = $_GET['error'];
}

// Setup list colors array. They're submitted in the same order as the list item
// they're nexf to in the UI. Here we map them to the list items value so that
// we can easily apply them in the filter grid.
$colors = [];
if (isset($fm['sListItemsColors']) && is_array($fm['sListItemsColors'])) {
    foreach ($fm['sListItemsColors'] as $k=>$v) {
        $colors[$fm['sListItems'][$k]] = $v;
    }
}
$fm['sListItemsColors'] = hs_serialize($colors);

//setup for lists
$t = [];
if (isset($fm['sListItems']) && is_array($fm['sListItems'])) {
    foreach ($fm['sListItems'] as $v) {
        $t[] = trim(utf8RawUrlDecode($v));
    }
}
$fm['sListItems'] = hs_serialize($t);

//Setup for drill downs.
if (! empty($fm['drilldown_array'])) {
    $fm['sListItems'] = utf8RawUrlDecode($fm['drilldown_array']);
}

// grab array of custom fields
$customFields = $GLOBALS['customFields'];
// if editing then take passed id and set edit mode. If not then find next custom id value
if (isset($_GET['customfieldid']) && is_numeric($_GET['customfieldid'])) {
    $customfieldid = $_GET['customfieldid'];
    $vmode = 2;
    $editmode = 1; //in edit mode so don't try and add columns
    $type = $customFields[$customfieldid]['fieldType'];
    $button = lg_admin_cfields_savefield;
    $formTitle = lg_admin_cfields_edit.$customFields[$customfieldid]['fieldName'];
    //$formTitle = $formTitle . ' (<a href="'.$basepgurl.'&action=delete&customfieldid='.$customfieldid.'" class="redlink" onClick="if(confirm(\''.lg_admin_cfields_delwarn.'\')) return true; else return false;">'.lg_admin_cfields_delete.'</a>)';
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_cfields_delwarn.'\',\''.$basepgurl.'&action=delete&customfieldid='.$customfieldid.'\');">'.lg_admin_cfields_delete.'</button>';
} elseif (isset($_POST['customfieldid']) && is_numeric($_POST['customfieldid'])) {
    $customfieldid = $_POST['customfieldid'];
} else {
    $customfieldid = 0;
    $button = lg_admin_cfields_createfield;
}

/*****************************************
ACTION
*****************************************/
//handle movement
if (isset($_POST['move'])) {
    foreach ($customFields as $v) {
        if (isset($_POST['cf'.$v['fieldID']]) && is_numeric($_POST['cf'.$v['fieldID']])) {
            $GLOBALS['DB']->Execute('UPDATE HS_CustomFields SET iOrder=?
									WHERE xCustomField = ?',
                                    [$_POST['cf'.$v['fieldID']], $v['fieldID']]);
        }
    }

    return redirect()
        ->route('admin', ['pg' => 'admin.tools.customfields'])
        ->with('feedback', lg_admin_cfields_moved);
}

// DELETE COLUMN
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    /** @var \HS\Domain\CustomFields\CreateDeleteCustomField $field */
    $field = app(HS\Domain\CustomFields\CreateDeleteCustomField::class);
    $field->delete($customfieldid);

    return redirect()
        ->route('admin', ['pg' => 'admin.tools.customfields']);
}

// Add new custom field to field array and database
if ($vmode == 3) {
    if (! hs_empty($fm['fieldName']) && ! empty($type)) {
        /** @var \HS\Domain\CustomFields\CreateDeleteCustomField $field */
        $field = app(HS\Domain\CustomFields\CreateDeleteCustomField::class);

        try {
            $field->create($fm, $type, $_POST['categoryList']);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin', ['pg' => 'admin.tools.customfields', 'error' => lg_error.': '.$e->getMessage()]);
        }

        return redirect()
            ->route('admin', ['pg' => 'admin.tools.customfields'])
            ->with('feedback', lg_admin_cfields_fbadded);
    } else {
        $vmode = 2;
        $formerrors['errorBoxText'] = lg_errorbox;
        $formerrors['fieldName'] = lg_admin_cfields_erfieldname;
    }
}

// Update field
if ($vmode == 4) {
    if (! hs_empty($fm['fieldName'])) {
        $GLOBALS['DB']->Execute('UPDATE HS_CustomFields SET fieldName=?,isRequired=?,isPublic=?,isAlwaysVisible=?,listItems=?,listItemsColors=?,
														sTxtSize=?,lrgTextRows=?,iDecimalPlaces=?,sRegex=?,sAjaxUrl=?
							WHERE xCustomField = ?',
            [$fm['fieldName'], $fm['isRequired'], $fm['isPublic'], $fm['isAlwaysVisible'], $fm['sListItems'], $fm['sListItemsColors'], $fm['sTxtSize'], $fm['lrgTextRows'], $fm['iDecimalPlaces'], $fm['sRegex'], $fm['sAjaxUrl'], $customfieldid]);

        //Update custom fields to categories
        $cats = apiGetAllCategories(0, '');
        if (hs_rscheck($cats)) {
            while ($r = $cats->FetchRow()) {
                $sCustomFieldList = hs_unserialize($r['sCustomFieldList']);
                if (in_array($r['xCategory'], $_POST['categoryList'])) {
                    if (! in_array($customfieldid, $sCustomFieldList)) {
                        $sCustomFieldList[] = $customfieldid;
                    }
                } else {
                    $v = array_search($customfieldid, $sCustomFieldList);
                    if ($v !== false) {
                        unset($sCustomFieldList[$v]);
                    }
                }
                $GLOBALS['DB']->Execute('UPDATE HS_Category SET sCustomFieldList = ? WHERE xCategory = ? ', [hs_serialize($sCustomFieldList), $r['xCategory']]);
            }
        }

        return redirect()
            ->route('admin', ['pg' => 'admin.tools.customfields'])
            ->with('feedback', $fm['fieldName'].lg_admin_cfields_fbedited);
    } else {
        $vmode = 2;
        $formerrors['errorBoxText'] = lg_errorbox;
        $formerrors['fieldName'] = lg_admin_cfields_erfieldname;
    }
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
// if in select mode do special items required of select lists
// safari_order_fix(\'sListItems\',\'createrequestfieldform\');
if ($vmode == 2 && $type == 'select') {
    $formOnSubmit = 'onSubmit="val = stopFormEnter(\'listItemInput\'); if (val != true) { $(\'listItemButton\').onclick();return false; }"';
    $listItems = isset($customFields[$customfieldid]['listItems']) ? $customFields[$customfieldid]['listItems'] : '';
    $listItemsColors = isset($customFields[$customfieldid]['listItemsColors']) ? $customFields[$customfieldid]['listItemsColors'] : '';
    $list = hs_unserialize($listItems);
    $listColors = hs_unserialize($listItemsColors);
} elseif ($vmode == 2 && $type == 'regex') {
    $formOnSubmit = 'onSubmit="try{var test = new RegExp($(\'sRegex\').value);}catch(e){hs_alert(\''.hs_jshtmlentities(lg_admin_cfields_regexvalid).'\');return false;};if($F(\'sRegex\') == \'\'){hs_alert(\''.hs_jshtmlentities(lg_admin_cfields_regexreq).'\'); return false;}"';
}

/*****************************************
TABLES
*****************************************/
if ($vmode == 1) {
    $cfres = $GLOBALS['DB']->Execute('SELECT * FROM HS_CustomFields ORDER BY iOrder ASC, fieldName ASC');
    // build data table
    if (is_object($cfres) && $cfres->RecordCount() > 0) {
        $fieldsTable = recordSetTable($cfres,[['type'=>'string', 'label'=>lg_admin_cfields_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xCustomField'],
                                                ['type'=>'link', 'label'=>lg_admin_cfields_colname, 'sort'=>0, 'fields'=>'fieldName',
                                                    'code'=>'<a href="'.$basepgurl.'&customfieldid=%s">%s</a>', 'linkfields'=>['xCustomField', 'fieldName'], ],
                                            ['type'=>'lookup', 'label'=>lg_admin_cfields_coltype, 'sort'=>0, 'width'=>'150', 'fields'=>'fieldType', 'dataarray'=>$GLOBALS['customFieldTypes']],
                                            ['type'=>'bool', 'label'=>lg_admin_cfields_colreq, 'sort'=>0, 'width'=>'80', 'fields'=>'isRequired'],
                                            ['type'=>'bool', 'label'=>lg_admin_cfields_colpub, 'sort'=>0, 'width'=>'60', 'fields'=>'isPublic'], ],

                                            ['title'=>$pagetitle,
                                                   'sortable'=>true,
                                                   'sortable_callback'=>'sort_cf',
                                                   'sortablefields'=>['xCustomField', 'fieldName'], ], $basepgurl);
    }

    $formnote = lg_admin_cfields_createnote;
    $formTitle = lg_admin_cfields_create;
}

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
    setErrors($formerrors);
}

$ft = ! empty($formTitle) ? $formTitle : lg_admin_cfields_create2;

$pagebody .= '
	<form action="'.$basepgurl.'" method="POST" name="createrequestfieldform" id="createrequestfieldform" '.$formOnSubmit.'>
	'.csrf_field().'
	'.$feedbackArea.'
	'.$fieldsTable;

if ($vmode == 1) {
    $button = lg_admin_cfields_step2;

    $sample_drilldown = ['Windows'	=>['2000'	=>['SP 1'=>false, 'SP 2'=>false],
                                                'XP'	=>['Pro'=>false, 'Home'=>false],
                                                'Vista'	=>['Business'=>false, 'Ultimate'=>false, 'Home Premium'=>false, 'Home Basic'=>false], ],
                              'Linux'	=>['Cent OS'	=>['4.1'=>false, '4.2'=>false, '4.3'=>false],
                                                'Red Hat'	=>['7'=>false, '8'=>false, '9'=>false],
                                                'Debian'	=>['2'=>false, '3'=>false, '4'=>false],
                                                'SuSE'		=>['8'=>false, '9'=>false, '10'=>false], ],
                              'OS X'	=>['Panther'=>false,
                                                'Tiger'=>false,
                                                'Leopard'=>false, ],
                  ];

    $pagebody .= renderInnerPageheader($ft, $formnote);

	$pagebody .= '

		<div class="card padded">
			<div class="fr">
				<div class="label">
					<label for="type">Field Type</label>
				</div>
				<div class="control">
					<select name="type" id="type">
						<option value="select">'. lg_lookup_cfields_dropdown . '</option>
						<option value="drilldown">'. lg_lookup_cfields_drilldown . '</option>
						<option value="text">'. lg_lookup_cfields_text . '</option>
						<option value="lrgtext">'. lg_lookup_cfields_lrgtext . '</option>
						<option value="date">'. lg_lookup_cfields_date . '</option>
						<option value="datetime">'. lg_lookup_cfields_datetime . '</option>
						<option value="regex">'. lg_lookup_cfields_regex . '</option>
						<option value="ajax">'. lg_lookup_cfields_ajax . '</option>
						<option value="checkbox">'. lg_lookup_cfields_checkbox . '</option>
						<option value="numtext">'. lg_lookup_cfields_numfield . '</option>
						<option value="decimal">'. lg_lookup_cfields_decimal . '</option>
					</select>
				</div>
			</div>

			'. displayContentBoxTop('Example') . '

				<div class="fr" id="sampleselect_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_dropdown . '</label>
						<div class="info">' . lg_admin_cfields_dropdowndesc . '</div>
					</div>
					<div class="control">
						<select name="sampleselect">
							<option>' . lg_admin_cfields_selsamp . ' 1</option>
							<option>' . lg_admin_cfields_selsamp . ' 2</option>
							<option>' . lg_admin_cfields_selsamp . ' 3</option>
							<option>' . lg_admin_cfields_selsamp . ' 4</option>
						</select>
					</div>
				</div>

				<div class="fr" id="sampledrilldown_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_drilldown . '</label>
						<div class="info">' . lg_admin_cfields_drilldowndesc . '</div>
					</div>
					<div class="control">
						' . RenderDrillDownList('Demo', $sample_drilldown, ['CustomDemo_1' => 'Linux', 'CustomDemo_2' => 'Cent OS', 'CustomDemo_3' => '4.3'], '<img src="' . static_url() . '/static/img5/angle-double-right-solid.svg" style="height: 20px;margin-left:3px;margin-right: 3px;" />') . '
					</div>
				</div>

				<div class="fr" id="sampletext_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_text . '</label>
						<div class="info">' . lg_admin_cfields_textdesc . '</div>
					</div>
					<div class="control">
						<input type="text" name="sampletext" size="30" value="' . lg_admin_cfields_textsamp . '">
					</div>
				</div>

				<div class="fr" id="samplelrgtext_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_lrgtext . '</label>
						<div class="info">' . lg_admin_cfields_lrgtextdesc . '</div>
					</div>
					<div class="control">
						<textarea name="lrgsampletext" cols="50" rows="4">' . lg_admin_cfields_lrgtextsamp . '</textarea>
					</div>
				</div>

				<div class="fr" id="sampledate_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_date . '</label>
						<div class="info">' . lg_admin_cfields_datedesc . '</div>
					</div>
					<div class="control">
						' . calinput('sampledate', '') . '
					</div>
				</div>

				<div class="fr" id="sampledatetime_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_datetime . '</label>
						<div class="info">' . lg_admin_cfields_datetimedesc . '</div>
					</div>
					<div class="control">
						' . calinput('sampledatetime', '', true) . '
					</div>
				</div>

				<div class="fr" id="sampleregex_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_regex . '</label>
						<div class="info">' . lg_admin_cfields_regexdesc . '</div>
					</div>
					<div class="control">
						<input type="text" name="sampleregex" size="30" value="' . lg_admin_cfields_regexsamp . '">
					</div>
				</div>

				<div class="fr" id="sampleajax_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_ajax . '</label>
						<div class="info">' . lg_admin_cfields_ajaxdesc . '</div>
					</div>
					<div class="control">
                        <div>
                            <div style="display:flex;align-items:center;">
    						    <input type="text" name="sampleajax" id="sampleajax" size="30" style="flex:1;">
                                <img src="'.static_url().'/static/img5/sync-solid.svg" onclick="ajax_sample();" style="vertical-align:middle;margin-left:6px;height:26px;" class="hand" /><br>
    						</div>
                            <div id="sampleajax_example" style="display:none">' . lg_loading . '</div>
                        </div>
					</div>
				</div>

				<div class="fr" id="samplecheckbox_block">
					<div class="label tdlcheckbox">
						<label for="type9" class="datalabel">' . lg_lookup_cfields_checkbox . '</label><br>
						<div class="info">' . lg_admin_cfields_checkboxdesc . '</div>
					</div>
					<div class="control">
						<input type="checkbox" id="samplecheckbox" class="checkbox" name="samplecheckbox" value="checkbox">
						<label for="samplecheckbox" class="switch"></label>
					</div>
				</div>

				<div class="fr" id="samplenumtext_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_numfield . '</label>
						<div class="info">' . lg_admin_cfields_numtextdesc . '</div>
					</div>
					<div class="control">
						<input type="text" name="samplenumtext" size="6" value="' . lg_admin_cfields_numtextsamp . '">
					</div>
				</div>

				<div class="fr" id="sampledecimal_block">
					<div class="label">
						<label for="type1" class="datalabel">' . lg_lookup_cfields_decimal . '</label>
						<div class="info">' . lg_admin_cfields_decimaldesc . '</div>
					</div>
					<div class="control">
						<input type="text" name="sampledecimal" size="6" value="' . lg_admin_cfields_decimalsamp . '">
					</div>
				</div>

			'. displayContentBoxBottom() . '

		<input type="hidden" name="vmode" value="2">
		<div class="button-bar space">
			<button type="submit" name="submit" class="btn accent">' . $button . '</button>
			' . $delbutton . '
		</div>

	</div>
	';

} elseif ($vmode == 2) {

	// Handle each type of field
    $fieldName = isset($customFields[$customfieldid]['fieldName']) ? $customFields[$customfieldid]['fieldName'] : '';
    $isRequried = isset($customFields[$customfieldid]['isRequired']) ? $customFields[$customfieldid]['isRequired'] : '';
    $isPublic = isset($customFields[$customfieldid]['isPublic']) ? $customFields[$customfieldid]['isPublic'] : '';
    $isAlwaysVisible = isset($customFields[$customfieldid]['isAlwaysVisible']) ? $customFields[$customfieldid]['isAlwaysVisible'] : 0;

    $sTxtSize = (isset($customFields[$customfieldid]['sTxtSize']) and $customFields[$customfieldid]['sTxtSize'] != '') ? $customFields[$customfieldid]['sTxtSize'] : '25';
    $lrgTextRows = isset($customFields[$customfieldid]['lrgTextRows']) ? $customFields[$customfieldid]['lrgTextRows'] : '4';
    $decimalplaces = isset($customFields[$customfieldid]['iDecimalPlaces']) ? $customFields[$customfieldid]['iDecimalPlaces'] : '2';
    $sregex = isset($customFields[$customfieldid]['sRegex']) ? $customFields[$customfieldid]['sRegex'] : '';
    $sAjaxUrl = isset($customFields[$customfieldid]['sAjaxUrl']) ? $customFields[$customfieldid]['sAjaxUrl'] : '';

    $headscript .= '
		<script type="text/javascript" language="JavaScript">
			jQuery( document ).ready(function( $ ) {
    				$("#sTxtSize").on("blur", function(e){
    					if($(this).val() != '.formClean($sTxtSize).') {
    						hs_alert("'.lg_admin_cfields_textsize_msg.'");
    						return false;
    					}
    				});
				});
		</script>
	';


	$pagebody .= renderPageheader($ft, $formnote);

    $pagebody .= displaySystemBox(lg_admin_cfields_timenote);

	$pagebody .= '
	<div class="card padded">
		<div class="fr">
			<div class="label">
				<label for="fieldName" class="datalabel req">'.lg_admin_cfields_fieldname. '</label>
			</div>
			<div class="control">
				<input tabindex="100" type="text" name="fieldName" size="30" value="'.formClean($fieldName).'" class="'.errorClass('fieldName').'">'.errorMessage('fieldName').'
			</div>
		</div>
	';

    $pagebody .= '<div class="fr">';

    switch ($type) {
        case 'select':
            $pagebody .= '
			<div class="label">
				<label class="datalabel">'.lg_admin_cfields_enterlistitem. '</label>
			</div>
			<div class="control">
				<div style="width:66%">
                    <div style="display:flex;align-items:center;">
					   <input tabindex="101" name="listItemInput" id="listItemInput" type="text" size="40" value="" style="margin-right:10px;flex:1;">
					   <button type="button" id="listItemButton" class="btn inline-action" style="margin: 8px 6px;" onClick="addSortableColumn(\'listItemInput\',\'listItemID\',\'sListItems[]\','.((is_array($list) && ! empty($list)) ? 'true' : 'false').',true);$(\'listItemInput\').value = \'\';">'.lg_admin_cfields_addlistitem.'</button>
					</div>
                    <div class="sortablelist" id="listItemID">';
                if (is_array($list) && ! empty($list)) {
                    foreach ($list as $k=>$tag) {
                        $pagebody .= '
								<div class="sortable_filter" id="listItemID_'.utf8RawUrlEncode($tag).'">
							  		<img src="'.static_url().'/static/img5/grip-lines-regular.svg" style="vertical-align: middle;cursor:move;" class="drag_handle">
							  		<img src="'.static_url().'/static/img5/edit-regular.svg" style="vertical-align: middle;cursor:pointer;height:16px;margin: 0 4px;" onClick="edit_tag(\'listItemID_'.utf8RawUrlEncode($tag).'\', '.$k.');">
							  		<span id="listItemID_'.utf8RawUrlEncode($tag).'_text">'.$tag.'</span>
									<input type="hidden" id="listItemID_'.utf8RawUrlEncode($tag).'_hidden" name="sListItems[]" value="'.utf8RawUrlEncode($tag).'">
									<input class="jscolor jscolor-small" name="sListItemsColors[]" data-jscolor="{required:false,hash:true,closable:true,closeText:\''.lg_admin_cfields_closecolors.'\'}"  value="'.$listColors[utf8RawUrlEncode($tag)].'">
                                    <img src="'.static_url().'/static/img5/remove.svg" style="vertical-align: middle;cursor:pointer;" onClick="return confirmRemove(\'listItemID_'.utf8RawUrlEncode($tag).'\', confirmListDelete);">
								</div>';
                    }
                }
            $pagebody .= '
					</div>
				</div>

				<script type="text/javascript">
				 // <![CDATA[
				   var confirmListDelete = "'.hs_jshtmlentities(lg_admin_cfields_removeitem).'";
				   Sortable.create("listItemID",
					 {tag:"div", constraint: "vertical", handle: "drag_handle"});
				 // ]]>
				 </script>
			</div>';

            break;
        case 'text':
                $pagebody .= '
				<div class="label">
					<label class="datalabel">'.lg_admin_cfields_textsize. '</label>
					<div class="info">' . lg_admin_cfields_textsizedesc . '</div>
				</div>
				<div class="control">
					<input tabindex="100" type="text" id="sTxtSize" name="sTxtSize" size="4" value="'.formClean($sTxtSize).'">
				</div>';

            break;
        case 'lrgtext':
            $pagebody .= '
				<div class="label">
					<label class="datalabel">'.lg_admin_cfields_lrgtextrows. '</label>
				</div>
				<div class="control">
					<input tabindex="101" type="text" name="lrgTextRows" size="4" value="'.formClean($lrgTextRows).'">
				</div>
			';

            break;
        case 'checkbox':
            $pagebody .= '

			';

            break;
        case 'numtext':
                $pagebody .= '';

            break;
        case 'decimal':
                //Currently we don't allow editing of decimal places due to the DB changes required.
				$pagebody .= '
					<div class="label"><label class="datalabel">'.lg_admin_cfields_decimalplaces.'</label></div><div class="control">';
                if ($editmode) {
                    $pagebody .= ' <b>'.$decimalplaces.'</b> <input type="hidden" name="iDecimalPlaces" value="'.$decimalplaces.'" />';
                } else {
                    $pagebody .= '<input tabindex="100" type="text" name="iDecimalPlaces" size="4" value="'.formClean($decimalplaces).'">';
                }
                $pagebody .= '</div>';

            break;
        case 'regex':
            $pagebody .= '
					<div class="label">
						<label class="datalabel req">'.lg_admin_cfields_regexexpression. '</label>
						<div class="info">' . lg_admin_cfields_regexexpressiondesc . ' ex: ' . lg_admin_cfields_regexsamp . '</div>
					</div>
					<div class="control">
						<input tabindex="101" type="text" name="sRegex" id="sRegex" size="50" value="'.formClean($sregex). '">

					</div>
				</div>
				<div class="fr">
						<div class="label">
							<label class="datalabel req">' . lg_admin_cfields_regextestvaluedesc . '</label>
						</div>
						<div class="control">
                            <div style="display:flex;align-items:center;">
							     <input type="text" name="regex_test" id="regex_test" value="" size="40" />
                                <img src="' . static_url() . '/static/img5/remove.svg" class="svg28" id="regex_test_img" align="top" border="0" alt="" style="margin-left:8px;" />
							</div>
                            <script type="text/javascript">
							Event.observe("regex_test", "keyup", function(event){ reg=eval($("sRegex").value);if(reg.test($("regex_test").value)){ $("regex_test_img").src="' . static_url() . '/static/img5/match.svg"; }else{ $("regex_test_img").src="' . static_url() . '/static/img5/remove.svg"; } });
							</script>
						</div>

			';

            break;
        case 'ajax':
            $pagebody .= '
			<div class="label">
				<label class="datalabel req">'.lg_admin_cfields_ajaxurl. '</label>
				<div class="info">' . lg_admin_cfields_ajaxurldesc . '</div>
			</div>
			<div class="control">
				<input tabindex="101" type="text" name="sAjaxUrl" size="90" value="'.formClean($sAjaxUrl).'">
			</div>
			';

            break;
        case 'date':
            $pagebody .= '';

            break;
        case 'datetime':
            $pagebody .= '';

            break;
        case 'drilldown':
            $id = ($customfieldid ? $customfieldid : 'New');
            $list = ! empty($fm['drilldown_array']) ? $fm['sListItems'] : $customFields[$customfieldid]['listItems'];
            $newfield = [];

            $pagebody .= '
			<div class="label">
				<label class="datalabel req">'.lg_admin_cfields_drilldown.'</label>
			</div>
			<div class="control">
				<div id="drilldown_wrapper">
					<input type="hidden" name="drilldown_array" id="drilldown_array" value="'.(hs_empty($list) ? utf8RawUrlEncode(hs_serialize($newfield)) : utf8RawUrlEncode($list)).'" />
				</div>
				<script type="text/javascript">
					function update_drill_list(){
						var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'build_drilldown']).'&rand=" + ajaxRandomString();
						var pars = "xCustomField='.$id.'&drilldown_array=" + $("drilldown_array").value;

						//Grab new value if set
						if($("new_value") && $F("new_value") != ""){
							//Check for quotes, if any abort
							if($F("new_value").indexOf("\'")  == -1 && $F("new_value").indexOf(\'"\')  == -1){
								pars = pars + "&new_value=" + eq($F("new_value")) + "&new_value_level=" + eq($F("new_value_level"));
							}else{
								hs_alert("'.hs_jshtmlentities(lg_admin_cfields_drillquotes).'");
								return false;
							}
						}

						//Grab remove value if set
						if($("remove_value") && $F("remove_value") != ""){
							pars = pars + "&remove_value=" + eq($F("remove_value"));
						}

						add_open = false;

						var updateWs = new Ajax.Updater(
									"drilldown_wrapper",
									url,
									{method: \'post\', parameters: pars, onFailure: ajaxError,evalScripts: true});
					}

					//Run update
					update_drill_list();
				</script>
			</div>
			';

            break;
    }

    $pagebody .= '</div>';

    $pagebody .= '
		<fieldset class="fieldset">
			<div class="sectionhead">'.lg_admin_cfields_options. '</div>

			<div class="fr">
				<div class="label">
					<label class="datalabel">' . lg_admin_cfields_visibility . '</label>
				</div>
				<div class="control">
					<select name="isAlwaysVisible" id="isAlwaysVisible" onchange="showVisibility();">
						<option value="0" ' . selectionCheck(0, $isAlwaysVisible) . '>' . lg_admin_cfields_visibility_cat . '</option>
						<option value="1" ' . selectionCheck(1, $isAlwaysVisible) . '>' . lg_admin_cfields_visibility_all . '</option>
					</select>
				</div>
			</div>

			<div class="fr">
				<div class="label">

				</div>
				<div class="control">
					<div id="categoryListBox">';
						$cats = apiGetAllCategories(0, '');
						if (hs_rscheck($cats)) {
							//							$pagebody .= displayCheckAll();
							while ($r = $cats->FetchRow()) {
								$group = (empty($r['sCategoryGroup']) ? '' : $r['sCategoryGroup'].' / ');
								$sCustomFieldList = hs_unserialize($r['sCustomFieldList']);
								$checked = ($customfieldid == 0 || in_array($customfieldid, $sCustomFieldList) ? 'checked' : '');
								$pagebody .= '<div class="categoryListItem"><input type="checkbox" class="canCheck" name="categoryList[]" value="'.$r['xCategory'].'" style="vertical-align:middle;" '.$checked.' /> '.$group.$r['sCategory'].' '.($r['fAllowPublicSubmit'] ? '(<b>'.lg_ispublic.'</b>)' : '').'</div>';
							}
							$pagebody .= '<a class="btn inline-action js-check-all" href="#">'.lg_checkbox_checkall.'</a>';
						}
    $pagebody .= '
					</div>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="isRequired" class="datalabel">' . lg_admin_cfields_reqfield . '</label>
				</div>
				<div class="control">
					<input tabindex="102" class="checkbox" type="checkbox" id="isRequired" name="isRequired" value="1" ' . checkboxCheck(1, $isRequried) . '>
					<label for="isRequired" class="switch"></label>
				</div>
			</div>';

    if ($type != 'ajax') {
        $pagebody .= '
				<div class="hr"></div>

				<div class="fr">
					<div class="label tdlcheckbox">
						<label for="isPublic" class="datalabel">' . lg_admin_cfields_webform . '</label>
					</div>
					<div class="control">
						<input tabindex="103" type="checkbox" class="checkbox" id="isPublic" name="isPublic" value="1" ' . checkboxCheck(1, $isPublic) . '>
						<label for="isPublic" class="switch"></label>
					</div>
				</div>';
    }

    $pagebody .= '

		</fieldset>

			<input type="hidden" name="type" value="'.$type.'">
			<input type="hidden" name="vmode" value="'.($editmode ? 4 : 3).'">
			<input type="hidden" name="editmode" value="'.$editmode.'">
			<input type="hidden" name="customfieldid" value="'.$customfieldid. '">
	</div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">'. $button .'</button>
        '. $delbutton . '
    </div>
    ';
}

$pagebody .= '</form>';
