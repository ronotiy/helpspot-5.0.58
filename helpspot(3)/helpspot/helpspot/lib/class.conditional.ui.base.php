<?php

class hs_conditional_ui
{
    public $uitype = 'conditionalui_mail';

    public $anyall = '';		//match any or all

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Create the unique ID of the row.
     */
    public function generateID($type)
    {
        $type = $type.$this->generateRandomString(5);

        return uniqid($type);
    }

    public function generateRandomString($length = 24)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTU';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * The template for a condition.
     */
    public function conditionTemplate($row, $subid = false, $field = false)
    {
        $hidden = ['betweenDates', 'betweenClosedDates'];

        if (in_array($field, $hidden)) {
            //Hide hidden fields
            $out = '<div style="display:none;">%s %s</div>';
        } else {
            $subgroup_remove = 'if($(\''.$row.'_2\') && $(\''.$row.'_2\').value.indexOf(\'subgroup_\') !== -1){$$(\'.\' + $(\''.$row.'_2\').value).each(function(elem){elem.remove()});}';
            $out = '
			<div class="conditionrow '.($subid ? $subid : '').'" id="'.$row.'">
				<div class="conditioninner" id="'.$row.'_inner" '.($subid ? 'style="padding-left:30px;"' : '').'>
					<select name="'.$row.'_1" id="'.$row.'_1"
							onChange="'.$subgroup_remove.'$(\''.$row.'_1\').blur();$(\''.$row.'_select\').innerHTML=\'<span class=condition-loading>'.hs_jshtmlentities(lg_loading).'</span>\';'.hsAJAXinline('function(){ $(\''.$row.'_select\').innerHTML = arguments[0].responseText;arguments[0].responseText.evalScripts(); }', $this->uitype, 'do=constraints&type=\' + $F(\''.$row.'_1\') + \'&rowid='.$row.'').'">
						%s
					</select>
					'.($subid ? '<input type="hidden" name="'.$row.'_subgroup" value="'.$subid.'" />' : '').'
					<div id="'.$row.'_select" style="display:inline;">%s</div>
				</div>
				<div class="conditionremove">
					<img src="'.static_url().'/static/img5/remove.svg" class="hand svg28" alt="'.lg_conditional_remcon.'" title="'.lg_conditional_remcon.'"
						 onClick="Element.remove(\''.$row.'\');">
				</div>
			</div>';
        }

        return $out;
    }

    /**
     * The template for an action.
     */
    public function actionTemplate($row)
    {
        $out = '
		<div class="conditionrow" id="'.$row.'">
			<div class="conditioninner" id="'.$row.'_inner">
				<select name="'.$row.'_1" id="'.$row.'_1"
						onChange="$(\''.$row.'_1\').blur();$(\''.$row.'_select\').innerHTML=\'<span class=condition-loading>'.hs_jshtmlentities(lg_loading).'</span>\';'.hsAJAXinline('function(){ $(\''.$row.'_select\').innerHTML = arguments[0].responseText;arguments[0].responseText.evalScripts(); }', $this->uitype, 'do=actiondetails&type=\' + $F(\''.$row.'_1\') + \'&rowid='.$row.'').'">
					%s
				</select>
				<div id="'.$row.'_select" style="display:inline;">%s</div>
			</div>
			<div class="conditionremove">
				<img src="'.static_url().'/static/img5/remove.svg" class="hand svg28" alt="'.lg_conditional_remcon.'" title="'.lg_conditional_remcon.'"
				     onClick="Element.remove(\''.$row.'\');">
			</div>
		</div>';

        return $out;
    }

    /**
     * Return the details form fields for an action. Shared for both rules and automation.
     */
    public function getActionConstraints($type, $rowid, $value = '', $is_init = false)
    {
        global $user;
        $out = '';

        switch ($type) {
            case 'setcategory':
                $catsList = apiGetAllCategories(0, '');
                $catsSelect = '<select name="'.$rowid.'_2" id="'.$rowid.'_2"
									   onChange="'.hsAJAXinline('function(){ if($(\''.$rowid.'_3\')){Element.remove(\''.$rowid.'_3\');} new Insertion.After(\''.$rowid.'_2\', arguments[0].responseText); }', $this->uitype, 'do=actiondetails&type=assignable_staff&xCategory=\' + $F(this) + \'&rowid='.$rowid.'').'">';
                $catsSelect .= categorySelectOptions($catsList, $value[$type]);
                $catsSelect .= '</select>';

                //If a value is passed in then the UI is being built during a page refresh and we have to setup assignable staff as well
                $assignsel = '';
                if (! hs_empty($value)) {
                    $assignsel = $this->getActionConstraints('assignable_staff', $rowid, ['xPerson'=>$value['assign_to'], 'xCategory'=>$value[$type]]);
                }

                //Select for assignable staff
                $catsSelect .= $assignsel;

                return $catsSelect;

                break;

            case 'setreptags':
                $catsList = apiGetAllCategories(0, '');
                $catsSelect = '<select name="'.$rowid.'_2" id="'.$rowid.'_2"
									   onChange="$jq(\'*[id*=_reptag_group]\').hide();$jq(\'#'.$rowid.'_\'+$jq(this).val()+\'_reptag_group\').show();">';
                $catsSelect .= categorySelectOptions($catsList, $value[$type]['xCategory']);
                $catsSelect .= '</select>';

                //Setup rep tags.
                $repselect = '';
                $catsList->Move(0);
                while ($cat = $catsList->FetchRow()) {
                    $repselect .= '<div id="'.$rowid.'_'.$cat['xCategory'].'_reptag_group" style="display:none;padding:10px 0 0 15px;">';
                    $rs = apiGetReportingTags($cat['xCategory']);

                    foreach ($rs as $k=>$v) {
                        $repselect .= '<input type="checkbox" name="'.$rowid.'_3[]" value="'.$k.'" '.(isset($value[$type]['reportingTags']) && in_array($k, $value[$type]['reportingTags']) ? 'checked="checked"' : '').' /> '.$v.'<br />';
                    }
                    $repselect .= '</div>';
                }

                if (isset($value[$type]['xCategory'])) { //loading existing rule
                    $repselect .= '<script type="text/javascript">
							$jq().ready(function(){
								$jq(\'#'.$rowid.'_'.$value[$type]['xCategory'].'_reptag_group\').show();
							});
							</script>';
                }

                return $catsSelect.$repselect;

                break;

            //Not called directly. Called by onclick in setcategory above
            case 'assignable_staff':
                $sel = ! empty($value['xPerson']) ? $value['xPerson'] : '';
                $allStaff = apiGetAllUsersComplete();
                $cat = apiGetCategory($value['xCategory']);
                $catstaff = hs_unserialize($cat['sPersonList']);

                $inbox_default = $cat['xPersonDefault'] == 0 ? ' ('.lg_default.') ' : '';
                $out = ' <select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                $out .= '<option value="0" '.($sel === 0 ? 'selected' : '').'>'.lg_inbox.$inbox_default.'</option>';
                foreach ($catstaff as $p) {
                    // Ignore disabled staff
                    if ($allStaff[$p]['fDeleted'] == 1) {
                        continue;
                    }
                    $name = $allStaff[$p]['sFname'].' '.$allStaff[$p]['sLname'];
                    if ($cat['xPersonDefault'] == $p) {
                        $name .= ' ('.lg_default.') ';
                        //if(empty($sel)) $sel = $p;
                    }
                    $out .= '<option value="'.$p.'" '.selectionCheck($p, $sel).'>'.hs_htmlspecialchars($name).'</option>';
                }
                $out .= '</select>';

                return $out;

                break;

            case 'setcustomfield':
                return renderCustomFields($value[$type], $GLOBALS['customFields']);

                break;

            case 'close':
            case 'open':
            case 'setstatus':

                $status = apiGetActiveStatus();

                $out = '<select name="'.$rowid.'_2">';
                foreach ($status as $k=>$v) {
                    if ($type == 'close' && $k == 1) {
                        continue;
                    } //Dont show active option when closing
                    if ($type != 'setstatus' && $k == 2) {
                        continue;
                    } 	//Don't allow SPAM as an option for now
                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value[$type]).'>'.hs_htmlspecialchars($v).'</option>';
                }
                $out .= '</select>';

                return $out;

                break;

            case 'addprivnote':
                $out .= (hs_setting('cHD_HTMLEMAILS') ? showFormatedTextOptions($rowid.'_2', $is_init) : '');
                $out .= '<textarea name="'.$rowid.'_2" id="'.$rowid.'_2" cols="80" rows="10" style="width:95%;">'.$value[$type].'</textarea>';

                return $out;

                break;

            case 'notify':
                $staff = apiGetAllUsers();

                $out = '<select name="'.$rowid.'_2">';
                if (hs_rscheck($staff)) {
                    while ($row = $staff->FetchRow()) {
                        $out .= '<option value="'.$row['xPerson'].'" '.selectionCheck($row['xPerson'], $value[$type]).'>'.$row['fullname'].'</option>';
                    }
                }
                $out .= '</select>';

                return $out;

                break;

            case 'instantreply':
            case 'emailcustomer':
                $out .= (hs_setting('cHD_HTMLEMAILS') && $type != 'auto_notifysms' ? showFormatedTextOptions($rowid.'_2', $is_init) : '');
                $out .= '<textarea name="'.$rowid.'_2" id="'.$rowid.'_2" cols="80" rows="10" style="width:95%;">'.$value[$type].'</textarea><br>'.tagDrop($rowid.'_2');

                return $out;

                break;

            case 'auto_notify':
            case 'auto_notifysms':
            case 'auto_notifyexternal':
                //THIS FALLS THROUGH TO auto_emailcustomer
                if ($type == 'auto_notifyexternal') {
                    $out = '<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_5">'.lg_conditional_at_externalemail.'</label>
							<input type="text" name="'.$rowid.'_5" size="40" value="'.$value[$type]['staffmember'].'" />
							</div>';
                } else {
                    $staff = apiGetAllUsers();

                    $out = '<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_5">'.lg_conditional_at_stafftonotify.'</label>
							<select name="'.$rowid.'_5">
							<option value="assigneduser" '.selectionCheck('assigneduser', $value[$type]['staffmember']).'>'.lg_conditional_at_assignedstaffer.'</option>';
                    if (hs_rscheck($staff)) {
                        while ($row = $staff->FetchRow()) {
                            if ($type == 'auto_notifysms' && hs_empty($row['sSMS'])) {
                                continue;
                            }
                            $out .= '<option value="'.$row['xPerson'].'" '.selectionCheck($row['xPerson'], $value[$type]['staffmember']).'>'.$row['fullname'].'</option>';
                        }
                    }
                    $out .= '</select></div>';
                }

            case 'auto_emailcustomer':
                $mailboxesSelect = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                $mailboxesSelect .= '<option value="frommailbox" '.selectionCheck('frommailbox', $value[$type]).'>'.lg_conditional_at_frommailbox.'</option>
									 <option value="'.hs_setting('cHD_NOTIFICATIONEMAILNAME').'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0" '.selectionCheck(hs_setting('cHD_NOTIFICATIONEMAILNAME').'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0', $value[$type]['mailbox']).'>'.hs_jshtmlentities(lg_default_mailbox).' - '.hs_setting('cHD_NOTIFICATIONEMAILACCT').'</option>';
                $mailboxesres = apiGetAllMailboxes(0, '');
                if (hs_rscheck($mailboxesres)) {
                    while ($box = $mailboxesres->FetchRow()) {
                        if (! hs_empty($box['sReplyEmail'])) {
                            $mailboxesSelect .= '<option value="'.$box['sReplyName'].'*'.$box['sReplyEmail'].'*'.$box['xMailbox'].'" '.selectionCheck($box['sReplyName'].'*'.$box['sReplyEmail'].'*'.$box['xMailbox'], $value[$type]['mailbox']).'>'.hs_jshtmlentities(replyNameDisplay($box['sReplyName'])).' - '.hs_jshtmlentities($box['sReplyEmail']).'</option>';
                        }
                    }
                }
                $mailboxesSelect .= '</select>';

                $out .= '
						<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_3">'.lg_conditional_at_mailboxselect.'</label>
						'.$mailboxesSelect.'</div>

						<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_2">'.lg_conditional_at_subject.'</label>
						<input type="text" name="'.$rowid.'_2" size="40" value="'.$value[$type]['subject'].'"></div>

						<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_4">'.lg_conditional_at_email.'</label>';
                        $out .= (hs_setting('cHD_HTMLEMAILS') && $type != 'auto_notifysms' ? showFormatedTextOptions($rowid.'_4', $is_init) : '');
                        $out .= '<textarea name="'.$rowid.'_4" id="'.$rowid.'_4" cols="80" rows="10" style="width:95%;">'.$value[$type]['email'].'</textarea><br>'.tagDrop($rowid.'_4').'</div>';

                return $out;

                break;

            case 'subscribe_staff':
            case 'unsubscribe_staff':

            case 'auto_emailresults':
                $staff = apiGetAllUsers();

                $out = ' <select name="'.$rowid.'_2">';
                    if (hs_rscheck($staff)) {
                        while ($row = $staff->FetchRow()) {
                            $out .= '<option value="'.$row['xPerson'].'" '.selectionCheck($row['xPerson'], $value[$type]).'>'.$row['fullname'].'</option>';
                        }
                    }
                $out .= '</select>';

                return $out;

                break;

            case 'markurgent':
            case 'marknoturgent':
                return '';

                break;

            case 'movetotrash':
                return '';

                break;

            case 'movetoinbox':
                return '';

                break;

            case 'request_push':
                include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
                $push_classes = listRequestPushClasses();

                if (count($push_classes)) {
                    $pushselect = ' <select name="'.$rowid.'_2">';
                    foreach ($push_classes as $k=>$v) {
                        $pushselect .= '<option value="'.hs_htmlspecialchars($v['name']).'" '.selectionCheck($v['name'], $value[$type]['push_option']).'>'.$v['name'].'</option>';
                    }
                    $pushselect .= '</select>';

                    $out = '
						<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_2">'.lg_conditional_at_pushto.'</label>
						'.$pushselect.'</div>

						<div class="field-wrap"><label class="datalabel" for="'.$rowid.'_3">'.lg_conditional_at_pushcomment.'</label>
						<textarea name="'.$rowid.'_3" id="'.$rowid.'_3" cols="80" rows="10" style="width:95%;">'.$value[$type]['tComment'].'</textarea></div>
					';
                } else {
                    $out .= lg_conditional_at_norequestpush;
                }

                return $out;

                break;

            case 'live_lookup':
                include_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
                $live_lookup_searches = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));

                if (count($live_lookup_searches)) {
                    $livelookupselect = ' <select name="'.$rowid.'_2">';
                    foreach ($live_lookup_searches as $key=>$llv) {
                        $livelookupselect .= '<option value="'.hs_htmlspecialchars($llv['name']).'" '.selectionCheck($llv['name'], $value[$type]).'>'.hs_htmlspecialchars($llv['name']).'</option>';
                    }
                    $livelookupselect .= '</select>';

                    $out = $livelookupselect;
                } else {
                    $out .= lg_conditional_tr_nolivelookup;
                }

                return $out;

                break;

            case 'webhook':
                $out .= '<input type="text" name="'.$rowid.'_2" id="'.$rowid.'_2" style="" value="'.$value[$type].'" />';

                return $out;

                break;
                case "thermostat_send":
                include_once(cBASEPATH . '/helpspot/lib/api.thermostat.lib.php');
                $surveys = getThermostatSurveys();

                $out = ' <select name="'.$rowid.'_2">';
                foreach($surveys as $survey) {
                    $out .= '<option value="'.$survey->id.'" '.selectionCheck($survey->id,$value[$type]).'>'.$survey->name.'</option>';
                }
                $out .= '</select>';

                return $out;
                break;

            case "thermostat_add_email":
                include_once(cBASEPATH . '/helpspot/lib/api.thermostat.lib.php');
                $surveys = getThermostatSurveys();

                $out = ' <select name="'.$rowid.'_2">';
                foreach($surveys as $survey) {
                    $out .= '<option value="'.$survey->id.'" '.selectionCheck($survey->id,$value[$type]).'>'.$survey->name.'</option>';
                }
                $out .= '</select>';

                return $out;
                break;
        }
    }
}
