<?php

class hs_conditional_ui_trigger extends hs_conditional_ui
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        //Call parent constructor
        parent::__construct();

        $this->uitype = 'conditionalui_trigger';
    }

    /*****************************************
    TEMPLATE METHODS
    *****************************************/

    /**
     * Return the auto type list.
     */
    public function getTypeList($value)
    {
        $out = '
			<option value=""></option>

			<optgroup label="'.lg_conditional_at_otrigger.'">
				<option value="acting_person" '.selectionCheck('acting_person', $value).'>'.lg_conditional_at_acting_person.'</option>
				<option value="note_type" '.selectionCheck('note_type', $value).'>'.lg_conditional_at_notetype.'</option>
				<option value="note_content" '.selectionCheck('note_content', $value).'>'.lg_conditional_at_notecontent.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogcustinfo.'">
				<option value="sUserId" '.selectionCheck('sUserId', $value).'>'.lg_conditional_at_userid.'</option>
				<option value="sEmail" '.selectionCheck('sEmail', $value).'>'.lg_conditional_at_email.'</option>
				<option value="sFirstName" '.selectionCheck('sFirstName', $value).'>'.lg_conditional_at_fname.'</option>
				<option value="sLastName" '.selectionCheck('sLastName', $value).'>'.lg_conditional_at_lname.'</option>
				<option value="sPhone" '.selectionCheck('sPhone', $value).'>'.lg_conditional_at_phone.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogreqdetails.'">
				<option value="xPersonAssignedTo" '.selectionCheck('xPersonAssignedTo', $value).'>'.lg_conditional_at_assignedto.'</option>
				<option value="xCategory" '.selectionCheck('xCategory', $value).'>'.lg_conditional_at_category.'</option>
				<option value="xStatus" '.selectionCheck('xStatus', $value).'>'.lg_conditional_at_status.'</option>
				<option value="fOpen" '.selectionCheck('fOpen', $value).'>'.lg_conditional_at_open.'</option>
				<option value="fUrgent" '.selectionCheck('fUrgent', $value).'>'.lg_conditional_at_urgency.'</option>
				<option value="reportingTags" '.selectionCheck('reportingTags', $value).'>'.lg_conditional_at_reportingtags.'</option>
				<option value="fOpenedVia" '.selectionCheck('fOpenedVia', $value).'>'.lg_conditional_at_openvia.'</option>
				<option value="xOpenedViaId" '.selectionCheck('xOpenedViaId', $value).'>'.lg_conditional_at_mailbox.'</option>
				<option value="xPortal" '.selectionCheck('xPortal', $value).'>'.lg_conditional_at_portal.'</option>
			</optgroup>';

        //Add custom fields as options
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            $out .= '<optgroup label="'.lg_conditional_at_ogcustomfields.'">';
            foreach ($GLOBALS['customFields'] as $k=>$v) {
                if ($v['fieldType'] == 'date' || $v['fieldType'] == 'datetime') {
                    continue;
                }
                $fid = 'Custom'.$v['fieldID'];
                $out .= '<option value="'.$fid.'" '.selectionCheck($fid, $value).'>'.$v['fieldName'].'</option>';
            }
            $out .= '</optgroup>';
        }

        return $out;
    }

    /**
     * Return the action type list.
     */
    public function getActionTypeList($value)
    {
        $out = '
			<option value=""></option>
			<optgroup label="'.lg_conditional_at_ogreqdetails.'">
				<option value="setcategory" '.selectionCheck('setcategory', $value).'>'.lg_conditional_mra_setcat.'</option>
				<option value="setreptags" '.selectionCheck('setreptags', $value).'>'.lg_conditional_setreptags.'</option>
				<option value="setcustomfield" '.selectionCheck('setcustomfield', $value).'>'.lg_conditional_mra_setcustom.'</option>
				<option value="setstatus" '.selectionCheck('setstatus', $value).'>'.lg_conditional_mra_setstatus.'</option>
				<option value="markurgent" '.selectionCheck('markurgent', $value).'>'.lg_conditional_mra_markurgent.'</option>
				<option value="marknoturgent" '.selectionCheck('marknoturgent', $value).'>'.lg_conditional_mra_marknoturgent.'</option>
				<option value="movetoinbox" '.selectionCheck('movetoinbox', $value).'>'.lg_conditional_mra_movetoinbox.'</option>
				<option value="movetotrash" '.selectionCheck('movetotrash', $value).'>'.lg_conditional_mra_movetotrash.'</option>
				<option value="addprivnote" '.selectionCheck('addprivnote', $value).'>'.lg_conditional_mra_addprivatenote.'</option>
				<option value="close" '.selectionCheck('close', $value).'>'.lg_conditional_mra_close.'</option>
				<option value="open" '.selectionCheck('open', $value).'>'.lg_conditional_at_openreq.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ognotifications.'">
				<option value="auto_notify" '.selectionCheck('auto_notify', $value).'>'.lg_conditional_mra_notify.'</option>
				<option value="auto_notifysms" '.selectionCheck('auto_notifysms', $value).'>'.lg_conditional_at_notifysms.'</option>
				<option value="auto_notifyexternal" '.selectionCheck('auto_notifyexternal', $value).'>'.lg_conditional_at_notifyexternal.'</option>
				<option value="auto_emailcustomer" '.selectionCheck('auto_emailcustomer', $value).'>'.lg_conditional_at_emailcustomer.'</option>
				<option value="auto_emailresults" '.selectionCheck('auto_emailresults', $value).'>'.lg_conditional_at_emailresults.'</option>
               <option value="subscribe_staff" '.selectionCheck('subscribe_staff', $value).'>'.lg_conditional_at_subscribestaff.'</option>
                <option value="unsubscribe_staff" '.selectionCheck('unsubscribe_staff', $value).'>'.lg_conditional_at_unsubscribestaff.'</option>

            </optgroup>

			<optgroup label="'.lg_conditional_at_ogadvanced.'">
				<option value="request_push" '.selectionCheck('request_push', $value).'>'.lg_conditional_at_requestpush.'</option>
				<option value="live_lookup" '.selectionCheck('live_lookup', $value).'>'.lg_conditional_tr_livelookup.'</option>
				<option value="webhook" '.selectionCheck('webhook', $value).'>'.lg_conditional_tr_webhook.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogintegrations.'">
				<option value="thermostat_send" '.selectionCheck('thermostat_send', $value).'>'.lg_conditional_at_thermostat_send.'</option>
				<option value="thermostat_add_email" '.selectionCheck('thermostat_add_email', $value).'>'.lg_conditional_at_thermostat_add_email.'</option>
			</optgroup>';

        return $out;
    }

    /**
     * Trigger specific options.
     */
    public function getTriggerConstraints($value)
    {
        $out = '
			<option value="changed" '.selectionCheck('changed', $value).'>'.lg_conditional_tr_changed.'</option>
			<option value="changed_to" '.selectionCheck('changed_to', $value).'>'.lg_conditional_tr_changed_to.'</option>
			<option value="changed_from" '.selectionCheck('changed_from', $value).'>'.lg_conditional_tr_changed_from.'</option>
			<option value="not_changed" '.selectionCheck('not_changed', $value).'>'.lg_conditional_tr_notchanged.'</option>
			<option value="not_changed_to" '.selectionCheck('not_changed_to', $value).'>'.lg_conditional_tr_notchanged_to.'</option>
			<option value="not_changed_from" '.selectionCheck('not_changed_from', $value).'>'.lg_conditional_tr_notchanged_from.'</option>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsStrings($rowid, $value, $triggeropts = true)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_tr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_tr_isnot.'</option>
			<option value="begins_with" '.selectionCheck('begins_with', $value).'>'.lg_conditional_tr_begins.'</option>
			<option value="ends_with" '.selectionCheck('ends_with', $value).'>'.lg_conditional_tr_ends.'</option>
			<option value="contains" '.selectionCheck('contains', $value).'>'.lg_conditional_tr_contains.'</option>
			<option value="not_contain" '.selectionCheck('not_contain', $value).'>'.lg_conditional_tr_notcontain.'</option>
			<option value="matches" '.selectionCheck('matches', $value).'>'.lg_conditional_tr_matches.'</option>';

        return $out.($triggeropts ? $this->getTriggerConstraints($value) : '');
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsBasic($rowid, $value, $triggeropts = true)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_tr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_tr_isnot.'</option>';

        return $out.($triggeropts ? $this->getTriggerConstraints($value) : '');
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsNumber($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_tr_is.'</option>
			<option value="less_than" '.selectionCheck('less_than', $value).'>'.lg_conditional_tr_lessthan.'</option>
			<option value="greater_than" '.selectionCheck('greater_than', $value).'>'.lg_conditional_tr_greaterthan.'</option>';

        return $out.$this->getTriggerConstraints($value);
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsTimeSince($rowid, $value)
    {
        $out = '
			<option value="less_than" '.selectionCheck('less_than', $value).'>'.lg_conditional_tr_lessthan.'</option>
			<option value="greater_than" '.selectionCheck('greater_than', $value).'>'.lg_conditional_tr_greaterthan.'</option>';

        return $out.$this->getTriggerConstraints($value);
    }

    /**
     * The constraint fields for drill down custom fields.
     */
    public function getBaseConstraintsDrillDown($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_tr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_tr_isnot.'</option>
			<option value="begins_with" '.selectionCheck('begins_with', $value).'>'.lg_conditional_tr_begins.'</option>';

        return $out.$this->getTriggerConstraints($value);
    }

    /**
     * The constraint fields for reporting tags.
     */
    public function getBaseConstraintsReportingTags($rowid, $value)
    {
        $out = '
			<option value="rt_is_selected" '.selectionCheck('rt_is_selected', $value).'>'.lg_conditional_tr_rt_is_selected.'</option>
			<option value="rt_is_not_selected" '.selectionCheck('rt_is_not_selected', $value).'>'.lg_conditional_tr_rt_is_not_selected.'</option>
			<option value="rt_was_selected" '.selectionCheck('rt_was_selected', $value).'>'.lg_conditional_tr_rt_was_selected.'</option>
			<option value="rt_was_not_selected" '.selectionCheck('rt_was_not_selected', $value).'>'.lg_conditional_tr_rt_was_not_selected.'</option>';

        return $out;
    }

    /*****************************************
    LOGIC METHODS - TRIGGER
    *****************************************/

    /**
     * Pass in a auto rule and this will return complete conditions UI.
     */
    public function createConditionsUI($rule)
    {
        if (! is_object($rule) || get_class($rule) != 'hs_trigger') {
            return false;
        }

        $out = '';

        foreach ($rule->CONDITIONS as $k=>$v) {
            $rowid = $this->generateID('condition');

            $out .= sprintf($this->conditionTemplate($rowid),
                            $this->getTypeList($v['IF']),
                            $this->getConditionConstraints($v['IF'], $rowid, $v));
        }

        return $out;
    }

    /**
     * Pass in a mail rule and this will return complete actions UI.
     */
    public function createActionsUI($rule)
    {
        if (! is_object($rule) || get_class($rule) != 'hs_trigger') {
            return false;
        }

        $out = '';

        foreach ($rule->ACTIONS as $k=>$v) {
            $rowid = $this->generateID('action');

            $out .= sprintf($this->actionTemplate($rowid),
                            $this->getActionTypeList(key($v)),
                            $this->getActionConstraints(key($v), $rowid, $v, true));
        }

        return $out;
    }

    /**
     * Create HTML for a new auto condition.
     */
    public function newCondition($subid = false)
    {
        $out = $this->conditionTemplate($this->generateID('condition'), $subid);

        return sprintf($out, $this->getTypeList('', $subid), '');
    }

    /**
     * Create HTML for a new auto action.
     */
    public function newAction()
    {
        $out = $this->actionTemplate($this->generateID('action'));

        return sprintf($out, $this->getActionTypeList(null), '');
    }

    /**
     * Return the details form fields for a condition.
     */
    public function getConditionConstraints($type, $rowid, $ruledef = [], $is_init = false)
    {
        global $user;
        $is = isset($ruledef['IS']) ? $ruledef['IS'] : '';
        $value = isset($ruledef['VALUE']) ? $ruledef['VALUE'] : '';
        $out = '';
        $condition = '';

        switch ($type) {
            case 'acting_person':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is, false).'
								</select>';
                $out = '
				<select name="'.$rowid.'_3" id="'.$rowid.'_3">
					<option value="0" '.selectionCheck(0, $value).'>'.lg_conditional_at_acting_person_cust.'</option>';
                    $allStaff = apiGetAllUsersComplete();
                    foreach ($allStaff as $k=>$v) {
                        if ($v['fDeleted'] == 0) {
                            $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                        }
                    }
                    $out .= '</select>';

                break;

            case 'note_type':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is, false).'
								</select>';
                $out = '
					<select name="'.$rowid.'_3" id="'.$rowid.'_3">
						<option value="1" '.selectionCheck(1, $value).'>'.lg_conditional_tr_public.'</option>
						<option value="0" '.selectionCheck(0, $value).'>'.lg_conditional_tr_private.'</option>
						<!--<option value="3" '.selectionCheck(3, $value).'>'.lg_conditional_tr_external.'</option>-->
					</select>
				';

                break;

            case 'note_content':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsStrings($rowid, (empty($is) ? 'contains' : $is), false).'
								</select>';
                $out = ' <input type="text" value="'.formClean($value).'" size="30" name="'.$rowid.'_3" id="'.$rowid.'_3">';

                break;

            case 'sUserId':
            case 'sEmail':
            case 'sFirstName':
            case 'sLastName':
            case 'sPhone':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsStrings($rowid, $is).'
								</select>';
                $out .= ' <input type="text" value="'.formClean($value).'" size="30" name="'.$rowid.'_3" id="'.$rowid.'_3">';

                break;

            case 'fOpenedVia':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                foreach ($GLOBALS['openedVia'] as $k=>$v) {
                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value).'>'.$v.'</option>';
                }
                $out .= '</select>';

                break;

            case 'xOpenedViaId':
                include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

                $boxes = apiGetAllMailboxes(0, '');

                if (hs_rscheck($boxes)) {
                    $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
									'.$this->getBaseConstraintsBasic($rowid, $is).'
									</select> ';

                    $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                    while ($row = $boxes->FetchRow()) {
                        $out .= '<option value="'.$row['xMailbox'].'" '.selectionCheck($row['xMailbox'], $value).'>'.hs_jshtmlentities(replyNameDisplay($row['sReplyName'])).' - '.$row['sReplyEmail'].'</option>';
                    }
                    $out .= '</select>';
                }

                break;

            case 'xPortal':

                $portals = apiGetAllPortalsComplete();

                if (hs_rscheck($portals)) {
                    $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
									'.$this->getBaseConstraintsBasic($rowid, $is, false).'
									</select> ';

                    $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3"><option value="0">'.hs_jshtmlentities(lg_conditional_at_portal_default).'</option>';
                    while ($row = $portals->FetchRow()) {
                        $out .= '<option value="'.$row['xPortal'].'" '.selectionCheck($row['xPortal'], $value).'>'.$row['sPortalName'].'</option>';
                    }
                    $out .= '</select>';
                }

                break;

            case 'xStatus':
                $status = apiGetActiveStatus();

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                foreach ($status as $k=>$v) {
                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value).'>'.hs_htmlspecialchars($v).'</option>';
                }
                $out .= '</select>';

                break;

            case 'fOpen':

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '
				<select name="'.$rowid.'_3" id="'.$rowid.'_3">
					<option value="-1" '.selectionCheck('-1', $value).'>'.utf8_strtolower(lg_all).'</option>
					<option value="0" '.selectionCheck(0, $value).'>'.utf8_strtolower(lg_isclosed).'</option>
					<option value="1" '.selectionCheck(1, $value).'>'.utf8_strtolower(lg_isopen).'</option>
				</select>
				';

                break;

            case 'fUrgent':

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '
				<select name="'.$rowid.'_3" id="'.$rowid.'_3">
					<option value="0" '.selectionCheck(0, $value).'>'.utf8_strtolower(lg_isnormal).'</option>
					<option value="1" '.selectionCheck(1, $value).'>'.utf8_strtolower(lg_isurgent).'</option>
				</select>
				';

                break;

            case 'xCategory':
                $catsList = apiGetAllCategories(0, '');

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                $out .= categorySelectOptions($catsList, $value, '<option value="0" '.selectionCheck(0, $value).'>'.hs_htmlspecialchars(lg_conditional_at_unassigned).'</option>');
                $out .= '</select>';

                break;

            case 'reportingTags':

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsReportingTags($rowid, $is).'
								</select> ';

                $catsList = apiGetAllCategories(0, '');
                $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                if (hs_rscheck($catsList)) {
                    while ($c = $catsList->FetchRow()) {
                        if ($c['fDeleted'] == 0) {
                            $reptags = apiGetReportingTags($c['xCategory']);
                            foreach ($reptags as $k=>$v) {
                                if (! hs_empty($v)) {
                                    $group = (empty($c['sCategoryGroup']) ? '' : $c['sCategoryGroup'].' / ');
                                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value).'> '.hs_htmlspecialchars($group.$c['sCategory']).' / '.hs_htmlspecialchars($v).'</option>';
                                }
                            }
                        }
                    }
                }
                $out .= '</select>';

                break;

            case 'xPersonAssignedTo':
                $allStaff = apiGetAllUsersComplete();

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                $out .= '<option value="0" '.selectionCheck(0, $value).'>'.hs_htmlspecialchars(lg_conditional_at_unassigned).'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    if ($v['fDeleted'] == 0) {
                        $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                    }
                }
                $out .= '</select>';

                break;
        }

        //Handle custom fields
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                $fid = 'Custom'.$fvalue['fieldID'];
                if ($type == $fid) { 	//check if current type is one of the custom fields
                    switch ($fvalue['fieldType']) {
                    case 'select':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsBasic($rowid, $is).'</select> ';
                        $out = '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                        $items = hs_unserialize($fvalue['listItems']);
                        //provide an empty box first
                        $out .= '<option value=""></option>';
                        if (is_array($items)) {
                            foreach ($items as $v) {
                                $out .= '<option value="'.formClean($v).'" '.selectionCheck($v, $value).'>'.formClean($v).'</option>';
                            }
                        }
                        $out .= '</select>';

                        break;
                    case 'text':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="'.formClean($fvalue['sTxtSize']).'" value="'.formClean($value).'">';

                        break;
                    case 'lrgtext':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="40" value="'.formClean($value).'">';

                        break;
                    case 'checkbox':
                        //$out .= '<input name="'.$rowid.'_2" id="'.$rowid.'_2" type="checkbox" value="1" '.checkboxCheck(1,$value).'>';
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsBasic($rowid, $is).'</select> ';
                        $out .= '<select name="'.$rowid.'_3" id="'.$rowid.'_3">';
                        $out .= '<option value="0" '.selectionCheck(0, $value).'>'.lg_notchecked.'</option>';
                        $out .= '<option value="1" '.selectionCheck(1, $value).'>'.lg_checked.'</option>';
                        $out .= '</select>';

                        break;
                    case 'numtext':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsNumber($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="10" maxlength="10" value="'.formClean($value).'">';

                        break;
                    case 'drilldown':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsDrillDown($rowid, $is).'</select> ';
                        $drilldown_array = hs_unserialize($fvalue['listItems']);

                        $unid = str_replace('condition', '', $rowid).$fvalue['fieldID']; //ID used so that using multipel on page doesn't conflict

                        //Create array of selected values
                        if (! hs_empty($value)) {
                            $keys = [];
                            $depth = find_max_array_depth($drilldown_array);	//Find number of select boxes
                            $values = explode('#-#', $value);					//Create array out of selected values string
                            $values = array_pad($values, $depth, '');				//Fill values array full to start, this is important since values are stored in a way that only the selected values are kept. So a 4 tier list if only the first 2 are selected only they are stored so the array would be short if we didn't fill it
                            for ($i = 1; $i <= $depth; $i++) {
                                $keys[] = 'Custom'.$unid.'_'.$i;
                            }	//Create keys array with name of each select box
                            $values = array_combine($keys, $values);				//Combine keys with selected values
                        } else {
                            $values = [];
                        }

                        $out .= RenderDrillDownList($unid, $drilldown_array, $values, ' ', $rowid.'_3');

                        break;
                    /*
                    case 'date':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onchange="showhide_datefield(\''.$rowid.'\');">
                                        '.$this->getBaseConstraintsNumber($rowid, $is).'
                                        '.$this->getBaseConstraintsRelativeTime($rowid, $is).'
                                        </select>';

                        $out = calinput($rowid.'_3',$value).'
                        <script type="text/javascript">
                        showhide_datefield(\''.$rowid.'\');
                        </script>
                        ';

                        break;
                    case 'datetime':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onchange="showhide_datefield(\''.$rowid.'\');">
                                        '.$this->getBaseConstraintsTimeSince($rowid, $is).'
                                        '.$this->getBaseConstraintsRelativeTime($rowid, $is).'
                                        </select>';

                        $out = calinput($rowid.'_3',$value,true).'
                        <script type="text/javascript">
                        showhide_datefield(\''.$rowid.'\');
                        </script>';
                        break;
                    */
                    case 'regex':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out = '
						<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="30" value="'.formClean($value).'">
						<img src="'.static_url().'/static/img5/remove.svg" class="hand svg28" id="regex_img_'.$rowid.'_3" align="top" border="0" alt="" />
						<script type="text/javascript">
						Event.observe("'.$rowid.'_3", "keyup", function(event){ if('.hs_jshtmlentities($fvalue['sRegex']).'.test($("'.$rowid.'_3").value)){ $("regex_img_'.$rowid.'_3").src="'.static_url().'/static/img5/match.svg"; }else{ $("regex_img_'.$rowid.'_3").src="'.static_url().'/static/img5/remove.svg"; } });
						</script>';

                        break;
                    case 'ajax':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="30" value="'.formClean($value).'">';

                        break;
                    case 'decimal':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onChange="trigger_check(\''.$rowid.'_2\',\''.$rowid.'_3\');">'.$this->getBaseConstraintsNumber($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="10" maxlength="10" value="'.formClean($value).'">';

                        break;
                    }
                }
            }
        }

        return $condition.$out;
    }
}
