<?php

class hs_conditional_ui_auto extends hs_conditional_ui
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        //Call parent constructor
        parent::__construct();

        $this->uitype = 'conditionalui_auto';
    }

    /*****************************************
    TEMPLATE METHODS
    *****************************************/

    /**
     * Return the auto type list.
     */
    public function getTypeList($value, $hide_subgroups = false)
    {
        $out = '
			<option value=""></option>
			<optgroup label="'.lg_conditional_at_ogcustinfo.'">
				<option value="sUserId" '.selectionCheck('sUserId', $value).'>'.lg_conditional_at_userid.'</option>
				<option value="sEmail" '.selectionCheck('sEmail', $value).'>'.lg_conditional_at_email.'</option>
				<option value="sFirstName" '.selectionCheck('sFirstName', $value).'>'.lg_conditional_at_fname.'</option>
				<option value="sLastName" '.selectionCheck('sLastName', $value).'>'.lg_conditional_at_lname.'</option>
				<option value="sPhone" '.selectionCheck('sPhone', $value).'>'.lg_conditional_at_phone.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogreqdetails.'">
				<option value="xRequest" '.selectionCheck('xRequest', $value).'>'.lg_conditional_at_xrequest.'</option>
				<option value="xPersonAssignedTo" '.selectionCheck('xPersonAssignedTo', $value).'>'.lg_conditional_at_assignedto.'</option>
				<option value="xCategory" '.selectionCheck('xCategory', $value).'>'.lg_conditional_at_category.'</option>
				<option value="xStatus" '.selectionCheck('xStatus', $value).'>'.lg_conditional_at_status.'</option>
				<option value="fOpen" '.selectionCheck('fOpen', $value).'>'.lg_conditional_at_open.'</option>
				<option value="fUrgent" '.selectionCheck('fUrgent', $value).'>'.lg_conditional_at_urgent.'</option>
				<option value="fNotUrgent" '.selectionCheck('fNotUrgent', $value).'>'.lg_conditional_at_not_urgent.'</option>
				<option value="reportingTags" '.selectionCheck('reportingTags', $value).'>'.lg_conditional_at_reportingtags.'</option>
				<option value="xPersonOpenedBy" '.selectionCheck('xPersonOpenedBy', $value).'>'.lg_conditional_at_openedby.'</option>
				<option value="sTitle" '.selectionCheck('sTitle', $value).'>'.lg_conditional_at_title.'</option>
				<option value="fOpenedVia" '.selectionCheck('fOpenedVia', $value).'>'.lg_conditional_at_openvia.'</option>
				<option value="xOpenedViaId" '.selectionCheck('xOpenedViaId', $value).'>'.lg_conditional_at_mailbox.'</option>
				<option value="xPortal" '.selectionCheck('xPortal', $value).'>'.lg_conditional_at_portal.'</option>
			</optgroup>';

        //Add custom fields as options
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            $out .= '<optgroup label="'.lg_conditional_at_ogcustomfields.'">';
            foreach ($GLOBALS['customFields'] as $k=>$v) {
                $fid = 'Custom'.$v['fieldID'];
                $out .= '<option value="'.$fid.'" '.selectionCheck($fid, $value).'>'.$v['fieldName'].'</option>';
            }
            $out .= '</optgroup>';
        }

        $out .= '

			<optgroup label="'.lg_conditional_at_ogsearch.'">
				<option value="sSearch" '.selectionCheck('sSearch', $value).'>'.lg_conditional_at_search.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogdatetime.'">
				<option value="relativedate" '.selectionCheck('relativedate', $value).'>'.lg_conditional_at_relativedate.'</option>
				<option value="relativedateclosed" '.selectionCheck('relativedateclosed', $value).'>'.lg_conditional_at_relativedateclosed.'</option>
				<option value="dtSinceCreated" '.selectionCheck('dtSinceCreated', $value).'>'.lg_conditional_at_sincecreated.'</option>
				<option value="dtSinceClosed" '.selectionCheck('dtSinceClosed', $value).'>'.lg_conditional_at_sinceclosed.'</option>
				<option value="lastupdate" '.selectionCheck('lastupdate', $value).'>'.lg_conditional_at_sincelastupdate.'</option>
				<option value="lastpubupdate" '.selectionCheck('lastpubupdate', $value).'>'.lg_conditional_at_sincelastpubupdate.'</option>
				<option value="lastcustupdate" '.selectionCheck('lastcustupdate', $value).'>'.lg_conditional_at_sincelastcustupdate.'</option>
				<option value="speedtofirstresponse" '.selectionCheck('speedtofirstresponse', $value).'>'.lg_conditional_at_speedtofirstresponse.'</option>
				<option value="relativedatetoday" '.selectionCheck('relativedatetoday', $value).'>'.lg_conditional_at_relativedatetoday.'</option>
				<option value="relativedatelastpub" '.selectionCheck('relativedatelastpub', $value).'>'.lg_conditional_at_relativedatelastpub.'</option>
				<option value="relativedatelastcust" '.selectionCheck('relativedatelastcust', $value).'>'.lg_conditional_at_relativedatelastcust.'</option>
				<option value="beforeDate" '.selectionCheck('beforeDate', $value).'>'.lg_conditional_at_beforedate.'</option>
				<option value="afterDate" '.selectionCheck('afterDate', $value).'>'.lg_conditional_at_afterdate.'</option>
				<option value="closedBeforeDate" '.selectionCheck('closedBeforeDate', $value).'>'.lg_conditional_at_closedbeforedate.'</option>
				<option value="closedAfterDate" '.selectionCheck('closedAfterDate', $value).'>'.lg_conditional_at_closedafterdate.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogassignmentchain.'">
				<option value="acFromTo" '.selectionCheck('acFromTo', $value).'>'.lg_conditional_at_acfromto.'</option>
				<option value="acWasEver" '.selectionCheck('acWasEver', $value).'>'.lg_conditional_at_acwasever.'</option>
				<option value="acReassignedBy" '.selectionCheck('acReassignedBy', $value).'>'.lg_conditional_at_acreassignedby.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogother.'">
				<option value="ctPublicUpdates" '.selectionCheck('ctPublicUpdates', $value).'>'.lg_conditional_at_pubupdates.'</option>
				<option value="iLastReplyBy" '.selectionCheck('iLastReplyBy', $value).'>'.lg_conditional_at_lastreplyby.'</option>
				<option value="updatedby" '.selectionCheck('updatedby', $value).'>'.lg_conditional_at_updatedby.'</option>
			</optgroup>
			<optgroup label="'.lg_conditional_at_ogadvanced.'">
				'.(! $hide_subgroups ? '<option value="subconditions_and" '.selectionCheck('subconditions_and', $value).'>'.lg_conditional_at_subcondand.'</option>' : '').'
				'.(! $hide_subgroups ? '<option value="subconditions_or" '.selectionCheck('subconditions_or', $value).'>'.lg_conditional_at_subcondor.'</option>' : '').'
				'.(isAdmin() ? '<option value="wheresql" '.selectionCheck('wheresql', $value).'>'.lg_conditional_at_wheresql.'</option>' : '').'
			</optgroup>
			<optgroup label="'.lg_conditional_at_ogintegrations.'">
				<option value="thermostat_nps_score" '.selectionCheck('thermostat_nps_score', $value).'>'.lg_conditional_at_thermostat_nps_score.'</option>
				<option value="thermostat_csat_score" '.selectionCheck('thermostat_csat_score', $value).'>'.lg_conditional_at_thermostat_csat_score.'</option>
				<option value="thermostat_feedback" '.selectionCheck('thermostat_feedback', $value).'>'.lg_conditional_at_thermostat_feedback.'</option>
			</optgroup>';

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
				<option value="webhook" '.selectionCheck('webhook', $value).'>'.lg_conditional_at_webhook.'</option>
            </optgroup>

            <optgroup label="'.lg_conditional_at_ogintegrations.'">
                <option value="thermostat_send" '.selectionCheck('thermostat_send',$value).'>'.lg_conditional_at_thermostat_send.'</option>
                <option value="thermostat_add_email" '.selectionCheck('thermostat_add_email',$value).'>'.lg_conditional_at_thermostat_add_email.'</option>
            </optgroup>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsStrings($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_mr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_mr_isnot.'</option>
			<option value="begins_with" '.selectionCheck('begins_with', $value).'>'.lg_conditional_mr_begins.'</option>
			<option value="ends_with" '.selectionCheck('ends_with', $value).'>'.lg_conditional_mr_ends.'</option>
			<option value="contains" '.selectionCheck('contains', $value).'>'.lg_conditional_mr_contains.'</option>
			<option value="not_contain" '.selectionCheck('not_contain', $value).'>'.lg_conditional_mr_notcontain.'</option>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsBasic($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_mr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_mr_isnot.'</option>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsNumber($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_mr_is.'</option>
			<option value="less_than" '.selectionCheck('less_than', $value).'>'.lg_conditional_at_lessthan.'</option>
			<option value="greater_than" '.selectionCheck('greater_than', $value).'>'.lg_conditional_at_greaterthan.'</option>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraintsTimeSince($rowid, $value)
    {
        $out = '
			<option value="less_than" '.selectionCheck('less_than', $value).'>'.lg_conditional_at_lessthan.'</option>
			<option value="greater_than" '.selectionCheck('greater_than', $value).'>'.lg_conditional_at_greaterthan.'</option>';

        return $out;
    }

    /**
     * The constraint fields for relative time.
     */
    public function getBaseConstraintsRelativeTime($rowid, $value)
    {
        $out = '
			<option value="today" '.selectionCheck('today', $value).'>'.lg_conditional_at_today.'</option>
			<option value="tomorrow" '.selectionCheck('tomorrow', $value).'>'.lg_conditional_at_tomorrow.'</option>
			<option value="yesterday" '.selectionCheck('yesterday', $value).'>'.lg_conditional_at_yesterday.'</option>
			<option value="past_7" '.selectionCheck('past_7', $value).'>'.lg_conditional_at_past7.'</option>
			<option value="past_14" '.selectionCheck('past_14', $value).'>'.lg_conditional_at_past14.'</option>
			<option value="past_30" '.selectionCheck('past_30', $value).'>'.lg_conditional_at_past30.'</option>
			<option value="past_60" '.selectionCheck('past_60', $value).'>'.lg_conditional_at_past60.'</option>
			<option value="past_90" '.selectionCheck('past_90', $value).'>'.lg_conditional_at_past90.'</option>
			<option value="past_365" '.selectionCheck('past_365', $value).'>'.lg_conditional_at_past365.'</option>
			<option value="this_week" '.selectionCheck('this_week', $value).'>'.lg_conditional_at_thisweek.'</option>
			<option value="this_month" '.selectionCheck('this_month', $value).'>'.lg_conditional_at_thismonth.'</option>
			<option value="this_year" '.selectionCheck('this_year', $value).'>'.lg_conditional_at_thisyear.'</option>
			<option value="last_week" '.selectionCheck('last_week', $value).'>'.lg_conditional_at_lastweek.'</option>
			<option value="last_month" '.selectionCheck('last_month', $value).'>'.lg_conditional_at_lastmonth.'</option>
			<option value="last_year" '.selectionCheck('last_year', $value).'>'.lg_conditional_at_lastyear.'</option>
			<option value="next_7" '.selectionCheck('next_7', $value).'>'.lg_conditional_at_next7.'</option>
			<option value="next_14" '.selectionCheck('next_14', $value).'>'.lg_conditional_at_next14.'</option>
			<option value="next_30" '.selectionCheck('next_30', $value).'>'.lg_conditional_at_next30.'</option>
			<option value="next_90" '.selectionCheck('next_90', $value).'>'.lg_conditional_at_next90.'</option>
			<option value="next_365" '.selectionCheck('next_365', $value).'>'.lg_conditional_at_next365.'</option>
			<option value="next_week" '.selectionCheck('next_week', $value).'>'.lg_conditional_at_nextweek.'</option>
			<option value="next_month" '.selectionCheck('next_month', $value).'>'.lg_conditional_at_nextmonth.'</option>
			<option value="next_year" '.selectionCheck('next_year', $value).'>'.lg_conditional_at_nextyear.'</option>
			<option value="date_is_set" '.selectionCheck('date_is_set', $value).'>'.lg_conditional_at_dateset.'</option>
			<option value="date_is_not_set" '.selectionCheck('date_is_not_set', $value).'>'.lg_conditional_at_datenotset.'</option>
			';

        return $out;
    }

    /**
     * The constraint fields for drill down custom fields.
     */
    public function getBaseConstraintsDrillDown($rowid, $value)
    {
        $out = '
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_mr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_mr_isnot.'</option>
			<option value="begins_with" '.selectionCheck('begins_with', $value).'>'.lg_conditional_mr_begins.'</option>';

        return $out;
    }

    /*****************************************
    LOGIC METHODS - AUTO
    *****************************************/

    /**
     * Pass in a auto rule and this will return complete conditions UI.
     */
    public function createConditionsUI($rule)
    {
        if (! is_object($rule) || get_class($rule) != 'hs_auto_rule') {
            return false;
        }
        $hidden = ['betweenDates', 'betweenClosedDates'];
        $out = '';

        foreach ($rule->CONDITIONS as $k=>$v) {
            //If it's a subgroup we need to go one deeper
            if (strpos($k, 'subgroup_') !== false) {
                //Loop over condition set of subgroup
                foreach ($v as $innerk=>$subv) {
                    $rowid = $this->generateID('condition');

                    $out .= sprintf($this->conditionTemplate($rowid, $k),
                                    $this->getTypeList($subv['IF'], true),
                                    $this->getConditionConstraints($subv['IF'], $rowid, $subv, true));
                }
            } else {
                $rowid = $this->generateID('condition');
                $out .= sprintf($this->conditionTemplate($rowid, false, (isset($v['IF']) ? $v['IF'] : false)),
                                $this->getTypeList($v['IF']),
                                $this->getConditionConstraints($v['IF'], $rowid, $v, true));
            }
        }

        return $out;
    }

    /**
     * Pass in a mail rule and this will return complete actions UI.
     */
    public function createActionsUI($rule)
    {
        if (! is_object($rule) || get_class($rule) != 'hs_auto_rule') {
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
            case 'sUserId':
            case 'sEmail':
            case 'sFirstName':
            case 'sLastName':
            case 'sPhone':
            case 'sTitle':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsStrings($rowid, $is).'
								</select>';
                $out .= ' <input type="text" value="'.formClean($value).'" size="30" name="'.$rowid.'_3" id="'.$rowid.'_3">';

                break;

            case 'dtSinceCreated':
            case 'dtSinceClosed':
            case 'lastupdate':
            case 'lastpubupdate':
            case 'lastcustupdate':
            case 'speedtofirstresponse':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsTimeSince($rowid, $is).'
								</select>';
                $out .= '<div style="display: inline-block;">
                            <input type="text" value="'.formClean($value).'" size="10" name="'.$rowid.'_3" id="'.$rowid.'_3" style="margin:0;">
                            <img id="jscalc_'.$rowid.'" src="'.static_url().'/static/img5/calculator-solid.svg" style="width:22px;margin-bottom:3px;margin-left:4px;vertical-align: bottom;cursor: pointer;"
							onClick="calc_row=\''.$rowid.'_3\';hs_overlay({href:\''.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'jscalc']).'\',width:320,title:\''.hs_jshtmlentities(lg_conditional_at_calcmin).'\'});">
                        </div>';

                break;

            case 'relativedate':
            case 'relativedatetoday':
            case 'relativedateclosed':
            case 'relativedatelastpub':
            case 'relativedatelastcust':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								<option value="today" '.selectionCheck('today', $value).'>'.lg_conditional_at_today.'</option>
								<option value="yesterday" '.selectionCheck('yesterday', $value).'>'.lg_conditional_at_yesterday.'</option>
								<option value="past_7" '.selectionCheck('past_7', $value).'>'.lg_conditional_at_past7.'</option>
								<option value="past_14" '.selectionCheck('past_14', $value).'>'.lg_conditional_at_past14.'</option>
								<option value="past_30" '.selectionCheck('past_30', $value).'>'.lg_conditional_at_past30.'</option>
								<option value="past_60" '.selectionCheck('past_60', $value).'>'.lg_conditional_at_past60.'</option>
								<option value="past_90" '.selectionCheck('past_90', $value).'>'.lg_conditional_at_past90.'</option>
								<option value="past_365" '.selectionCheck('past_365', $value).'>'.lg_conditional_at_past365.'</option>
								<option value="this_week" '.selectionCheck('this_week', $value).'>'.lg_conditional_at_thisweek.'</option>
								<option value="this_month" '.selectionCheck('this_month', $value).'>'.lg_conditional_at_thismonth.'</option>
								<option value="this_year" '.selectionCheck('this_year', $value).'>'.lg_conditional_at_thisyear.'</option>
								<option value="last_week" '.selectionCheck('last_week', $value).'>'.lg_conditional_at_lastweek.'</option>
								<option value="last_month" '.selectionCheck('last_month', $value).'>'.lg_conditional_at_lastmonth.'</option>
								<option value="last_year" '.selectionCheck('last_year', $value).'>'.lg_conditional_at_lastyear.'</option>
								</select>';

                break;

            case 'beforeDate':
            case 'afterDate':
            case 'closedBeforeDate':
            case 'closedAfterDate':
                $out .= calinput($rowid.'_2', $value);

                break;

            case 'ctPublicUpdates':
            case 'xRequest':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsNumber($rowid, $is).'
								</select>';
                $out .= ' <input type="text" value="'.formClean($value).'" size="10" name="'.$rowid.'_3" id="'.$rowid.'_3">';

                break;

            case 'fOpenedVia':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3">';
                foreach ($GLOBALS['openedVia'] as $k=>$v) {
                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value).'>'.$v.'</option>';
                }
                $out .= '</select>';

                break;

            case 'xOpenedViaId':
                include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

                $boxes = apiGetAllMailboxes(0, '');

                if (hs_rscheck($boxes)) {
                    $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
									'.$this->getBaseConstraintsBasic($rowid, $is).'
									</select> ';

                    $out = '<select name="'.$rowid.'_3">';
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
									'.$this->getBaseConstraintsBasic($rowid, $is).'
									</select> ';

                    $out = '<select name="'.$rowid.'_3"><option value="0">'.hs_jshtmlentities(lg_conditional_at_portal_default).'</option>';
                    while ($row = $portals->FetchRow()) {
                        $out .= '<option value="'.$row['xPortal'].'" '.selectionCheck($row['xPortal'], $value).'>'.$row['sPortalName'].'</option>';
                    }
                    $out .= '</select>';
                }

                break;

            case 'xStatus':
                $status = apiGetActiveStatus();

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3">';
                foreach ($status as $k=>$v) {
                    $out .= '<option value="'.$k.'" '.selectionCheck($k, $value).'>'.hs_htmlspecialchars($v).'</option>';
                }
                $out .= '</select>';

                break;

            case 'fOpen':
                $out = '
				<select name="'.$rowid.'_2">
					<option value="-1" '.selectionCheck('-1', $value).'>'.utf8_strtolower(lg_all).'</option>
					<option value="0" '.selectionCheck(0, $value).'>'.utf8_strtolower(lg_isclosed).'</option>
					<option value="1" '.selectionCheck(1, $value).'>'.utf8_strtolower(lg_isopen).'</option>
				</select>
				';

                break;

            case 'iLastReplyBy':
                $out = '
				<select name="'.$rowid.'_2">
					<option value="0" '.selectionCheck(0, $value).'>'.lg_conditional_at_lastreplyby_cust.'</option>
					<option value="any" '.selectionCheck('any', $value).'>'.lg_conditional_at_lastreplyby_staff.'</option>';
                    $allStaff = apiGetAllUsersComplete();
                    foreach ($allStaff as $k=>$v) {
                        if ($v['fDeleted'] == 0) {
                            $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                        }
                    }
                    $out .= '</select>';

                break;

            case 'xCategory':
                $catsList = apiGetAllCategories(0, '');

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3">';
                $out .= categorySelectOptions($catsList, $value, '<option value="0" '.selectionCheck(0, $value).'>'.hs_htmlspecialchars(lg_conditional_at_unassigned).'</option>');
                $out .= '</select>';

                break;

            case 'reportingTags':
                $catsList = apiGetAllCategories(0, '');
                $out = '<select name="'.$rowid.'_2">';
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
            case 'xPersonOpenedBy':
                $allStaff = apiGetAllUsersComplete();

                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
								'.$this->getBaseConstraintsBasic($rowid, $is).'
								</select> ';

                $out = '<select name="'.$rowid.'_3">';
                $out .= '<option value="0" '.selectionCheck(0, $value).'>'.hs_htmlspecialchars(lg_conditional_at_unassigned).'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    if ($v['fDeleted'] == 0) {
                        $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                    }
                }
                $out .= '</select>';

                break;

            case 'updatedby':
                $allStaff = apiGetAllUsersComplete();

                $out = '<select name="'.$rowid.'_2">';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    if ($v['fDeleted'] == 0) {
                        $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                    }
                }
                $out .= '</select>';

                break;

            case 'acWasEver':
                $allStaff = apiGetAllUsersComplete();

                $out = '<select name="'.$rowid.'_2">';
                $out .= '<option value="0" '.selectionCheck(0, $value).'>'.lg_inbox.'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                }
                $out .= '</select>';

                break;

            case 'acReassignedBy':
                $allStaff = apiGetAllUsersComplete();

                $out = '<select name="'.$rowid.'_2">';
                $out .= '<option value="-1" '.selectionCheck(-1, $value).'>'.lg_systemname.'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                }
                $out .= '</select>';

                break;

            case 'acFromTo':
                $allStaff = apiGetAllUsersComplete();

                $out = '<select name="'.$rowid.'_2">';
                $out .= '<option value="0" '.selectionCheck(0, $is).'>'.lg_inbox.'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $is).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                }
                $out .= '</select>';

                $out .= '<select name="'.$rowid.'_3">';
                $out .= '<option value="0" '.selectionCheck(0, $value).'>'.lg_inbox.'</option>';
                $out .= '<option value="loggedin" '.selectionCheck('loggedin', $value).'>'.hs_htmlspecialchars(lg_conditional_at_currentlyloggedin).'</option>';
                foreach ($allStaff as $k=>$v) {
                    $out .= '<option value="'.$v['xPerson'].'" '.selectionCheck($v['xPerson'], $value).'>'.hs_htmlspecialchars($v['sFname']).' '.hs_htmlspecialchars($v['sLname']).'</option>';
                }
                $out .= '</select>';

                break;

            case 'sSearch':
                $out = ' <input type="text" value="'.formClean($value).'" size="30" name="'.$rowid.'_2" id="'.$rowid.'_2">';

                $out .= '<script type="text/javascript">
						$jq().ready(function(){
							hs_overlay({html:"<div style=\'width:400px\'><p>'.hs_jshtmlentities(lg_conditional_ftwarning).'</p><p>'.hs_jshtmlentities(lg_conditional_ftwarning2).'</p></div>"});
							$jq("#fShowCount").attr("checked", false);
							$jq("#fShowCount").attr("disabled", "disabled");
						});
						</script>';

                break;

            //These are only used for searches from reports, no public ui for them currently
            case 'betweenClosedDates':
            case 'betweenDates':
                $out = '<input type="hidden" name="'.$rowid.'_1" value="'.$type.'" />
						<input type="hidden" name="'.$rowid.'_2" value="'.$value.'" />';

                break;

            case 'wheresql':
                $out = ' <textarea name="'.$rowid.'_2" id="'.$rowid.'_2" rows="4" cols="40" style="width:95%">'.formClean($value).'</textarea>';

                break;

            case 'fUrgent':
                return '';

                break;

            case 'fNotUrgent':
                return '';

                break;

            case 'subconditions_and':
            case 'subconditions_or':
                $subgroupid = (! empty($value) ? $value : $this->generateID('subgroup_'));
                $out .= '<input type="hidden" name="'.$rowid.'_2" id="'.$rowid.'_2" value="'.$subgroupid.'" />';
                $out .= '<img src="'.static_url().'/static/img5/add-circle.svg" id="'.$subgroupid.'" class="hand svg28" style="margin-left:8px;" alt="'.lg_conditional_addcon.'" title="'.lg_conditional_addcon.'"
					 		onClick="'.hsAJAXinline('function(){ $(\''.$rowid.'\').insert({after:arguments[0].responseText}); }', 'conditionalui_auto', 'do=new_condition&subid='.$subgroupid.'').'">';
                if (! $is_init) {
                    $out .= '<script tyle="text/javascript">$("'.$subgroupid.'").onclick();</script>';
                }

                break;

            case 'thermostat_nps_score':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onchange="showhide_thermostatfield(\''.$rowid.'\');">
                                '.$this->getBaseConstraintsNumber($rowid, $is).'
                                <option value="type" '.selectionCheck('type', $is).'>Type</option>
                              </select>';
                $out = ' <input type="text" value="'.formClean($value).'" size="10" name="'.$rowid.'_3" id="'.$rowid.'_3_tf">';
                $out .= '<select name="'.$rowid.'_3" id="'.$rowid.'_3_sf">';
                $out .= '<option value="promoter" '.selectionCheck('promoter', $value).'>'.lg_conditional_at_thermostat_promoter.'</option>';
                $out .= '<option value="passive" '.selectionCheck('passive', $value).'>'.lg_conditional_at_thermostat_passive.'</option>';
                $out .= '<option value="detractor" '.selectionCheck('detractor', $value).'>'.lg_conditional_at_thermostat_detractor.'</option>';
                $out .= '</select>';
                $out .= '<script type="text/javascript"> showhide_thermostatfield("'.$rowid.'"); </script>';

                break;

            case 'thermostat_csat_score':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onchange="showhide_thermostatfield(\''.$rowid.'\');">
                                '.$this->getBaseConstraintsNumber($rowid, $is).'
                                <option value="type" '.selectionCheck('type', $is).'>Type</option>
                              </select>';

                $out = ' <input type="text" value="'.formClean($value).'" size="10" name="'.$rowid.'_3" id="'.$rowid.'_3_tf">';
                $out .= '<select name="'.$rowid.'_3" id="'.$rowid.'_3_sf">';
                $out .= '<option value="satisfied" '.selectionCheck('satisfied', $value).'>'.lg_conditional_at_thermostat_satisfied.'</option>';
                $out .= '<option value="dissatisfied" '.selectionCheck('dissatisfied', $value).'>'.lg_conditional_at_thermostat_dissatisfied.'</option>';
                $out .= '</select>';
                $out .= '<script type="text/javascript"> showhide_thermostatfield("'.$rowid.'"); </script>';

                break;

            case 'thermostat_feedback':
                $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">
                                <option value="yes" '.selectionCheck('yes', $value).'>'.utf8_ucfirst(lg_yes).'</option>
                                <option value="no" '.selectionCheck('no', $value).'>'.utf8_ucfirst(lg_no).'</option>
                              </select>';

                break;
        }

        //Handle custom fields
        if (isset($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                $fid = 'Custom'.$fvalue['fieldID'];
                if ($type == $fid) { 	//check if current type is one of the custom fields
                    switch ($fvalue['fieldType']) {
                    case 'select':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsBasic($rowid, $is).'</select> ';
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
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="'.formClean($fvalue['sTxtSize']).'" value="'.formClean($value).'">';

                        break;
                    case 'lrgtext':
                        $out .= '<input name="'.$rowid.'_2" id="'.$rowid.'_2" type="text" size="40" value="'.formClean($value).'">';

                        break;
                    case 'checkbox':
                        //$out .= '<input name="'.$rowid.'_2" id="'.$rowid.'_2" type="checkbox" value="1" '.checkboxCheck(1,$value).'>';
                        $out .= '<select name="'.$rowid.'_2" id="'.$rowid.'_2">';
                        $out .= '<option value="0" '.selectionCheck(0, $value).'>'.lg_notchecked.'</option>';
                        $out .= '<option value="1" '.selectionCheck(1, $value).'>'.lg_checked.'</option>';
                        $out .= '</select>';

                        break;
                    case 'numtext':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsNumber($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="10" maxlength="10" value="'.formClean($value).'">';

                        break;
                    case 'drilldown':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsDrillDown($rowid, $is).'</select> ';
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
                    case 'date':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2" onchange="showhide_datefield(\''.$rowid.'\');">
										'.$this->getBaseConstraintsNumber($rowid, $is).'
										'.$this->getBaseConstraintsRelativeTime($rowid, $is).'
										</select>';

                        $out = calinput($rowid.'_3', $value).'
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

                        $out = calinput($rowid.'_3', $value, true).'
						<script type="text/javascript">
						showhide_datefield(\''.$rowid.'\');
						</script>';

                        break;
                    case 'regex':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out = '
						<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="30" value="'.formClean($value).'">
						<img src="'.static_url().'/static/img5/remove.svg" class="hand svg28" id="regex_img_'.$rowid.'_3" align="top" border="0" alt="" />
						<script type="text/javascript">
						Event.observe("'.$rowid.'_3", "keyup", function(event){ if('.hs_jshtmlentities($fvalue['sRegex']).'.test($("'.$rowid.'_3").value)){ $("regex_img_'.$rowid.'_3").src="'.static_url().'/static/img5/match.svg"; }else{ $("regex_img_'.$rowid.'_3").src="'.static_url().'/static/img5/remove.svg"; } });
						</script>';

                        break;
                    case 'ajax':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsStrings($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="30" value="'.formClean($value).'">';

                        break;
                    case 'decimal':
                        $condition = '<select name="'.$rowid.'_2" id="'.$rowid.'_2">'.$this->getBaseConstraintsNumber($rowid, $is).'</select> ';
                        $out .= '<input name="'.$rowid.'_3" id="'.$rowid.'_3" type="text" size="10" maxlength="10" value="'.formClean($value).'">';

                        break;
                    }
                }
            }
        }

        // trim is required to ensure proper spacing in UI
        return trim($condition).trim($out);
    }
}
