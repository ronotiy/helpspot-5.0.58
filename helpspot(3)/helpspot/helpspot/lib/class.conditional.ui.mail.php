<?php

class hs_conditional_ui_mail extends hs_conditional_ui
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        //Call parent constructor
        parent::__construct();

        $this->uitype = 'conditionalui_mail';
    }

    /*****************************************
    TEMPLATE METHODS
    *****************************************/

    /**
     * Return the mail type list.
     */
    public function getTypeList($value)
    {
        $out = '
			<option value=""></option>
			<optgroup label="'.lg_conditional_at_ogemaildetails.'">
				<option value="to" '.selectionCheck('to', $value).'>'.lg_conditional_mr_to.'</option>
				<option value="from" '.selectionCheck('from', $value).'>'.lg_conditional_mr_from.'</option>
				<option value="cc" '.selectionCheck('cc', $value).'>'.lg_conditional_mr_cc.'</option>
				<option value="subject" '.selectionCheck('subject', $value).'>'.lg_conditional_mr_subject.'</option>
				<option value="headers" '.selectionCheck('headers', $value).'>'.lg_conditional_mr_headers.'</option>
				<option value="email_body" '.selectionCheck('email_body', $value).'>'.lg_conditional_mr_emailbody.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ogreqdetails.'">
				<option value="customer_id" '.selectionCheck('customer_id', $value).'>'.lg_conditional_mr_customerid.'</option>
				<option value="mailbox_id" '.selectionCheck('mailbox_id', $value).'>'.lg_conditional_mr_mailbox.'</option>
				<option value="has_attach" '.selectionCheck('has_attach', $value).'>'.lg_conditional_mr_hasattach.'</option>
				<option value="is_urgent" '.selectionCheck('is_urgent', $value).'>'.lg_conditional_mr_urgent.'</option>
				<option value="is_spam" '.selectionCheck('is_spam', $value).'>'.lg_conditional_mr_spam.'</option>
				<option value="is_not_spam" '.selectionCheck('is_not_spam', $value).'>'.lg_conditional_mr_notspam.'</option>
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
				<option value="movetotrash" '.selectionCheck('movetotrash', $value).'>'.lg_conditional_mra_movetotrash.'</option>
				<option value="addprivnote" '.selectionCheck('addprivnote', $value).'>'.lg_conditional_mra_addprivatenote.'</option>
				<option value="close" '.selectionCheck('close', $value).'>'.lg_conditional_mra_close.'</option>
			</optgroup>

			<optgroup label="'.lg_conditional_at_ognotifications.'">
				<option value="notify" '.selectionCheck('notify', $value).'>'.lg_conditional_mra_notify.'</option>
				<option value="auto_notifysms" '.selectionCheck('auto_notifysms', $value).'>'.lg_conditional_at_notifysms.'</option>
				<option value="auto_notifyexternal" '.selectionCheck('auto_notifyexternal', $value).'>'.lg_conditional_at_notifyexternal.'</option>
				<option value="instantreply" '.selectionCheck('instantreply', $value).'>'.lg_conditional_mra_instantreply.'</option>
                <option value="subscribe_staff" '.selectionCheck('subscribe_staff', $value).'>'.lg_conditional_at_subscribestaff.'</option>
                <option value="unsubscribe_staff" '.selectionCheck('unsubscribe_staff', $value).'>'.lg_conditional_at_unsubscribestaff.'</option>
                </optgroup>';

        return $out;
    }

    /**
     * The constraint fields.
     */
    public function getBaseConstraints($rowid, $value)
    {
        $out = '
		<select name="'.$rowid.'_2" id="'.$rowid.'_2"
				onChange="$(\''.$rowid.'_3\').value = $F(this) == \'matches\' ? \''.lg_conditional_phpregex.'\' : \'\';">
			<option value="is" '.selectionCheck('is', $value).'>'.lg_conditional_mr_is.'</option>
			<option value="is_not" '.selectionCheck('is_not', $value).'>'.lg_conditional_mr_isnot.'</option>
			<option value="begins_with" '.selectionCheck('begins_with', $value).'>'.lg_conditional_mr_begins.'</option>
			<option value="ends_with" '.selectionCheck('ends_with', $value).'>'.lg_conditional_mr_ends.'</option>
			<option value="contains" '.selectionCheck('contains', $value).'>'.lg_conditional_mr_contains.'</option>
			<option value="not_contain" '.selectionCheck('not_contain', $value).'>'.lg_conditional_mr_notcontain.'</option>
			<option value="matches" '.selectionCheck('matches', $value).'>'.lg_conditional_mr_matches.'</option>
		</select>';

        return $out;
    }

    /*****************************************
    LOGIC METHODS - MAIL
    *****************************************/

    /**
     * Pass in a mail rule and this will return complete conditions UI.
     */
    public function createConditionsUI($rule)
    {
        if (! is_object($rule) || get_class($rule) != 'hs_mail_rule') {
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
        if (! is_object($rule) || get_class($rule) != 'hs_mail_rule') {
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
     * Create HTML for a new mail condition.
     */
    public function newCondition()
    {
        $out = $this->conditionTemplate($this->generateID('condition'));

        return sprintf($out, $this->getTypeList(null), '');
    }

    /**
     * Create HTML for a new mail action.
     */
    public function newAction()
    {
        $out = $this->actionTemplate($this->generateID('action'));

        return sprintf($out, $this->getActionTypeList(null), '');
    }

    /**
     * Return the details form fields for a condition.
     */
    public function getConditionConstraints($type, $rowid, $ruledef = [])
    {
        $is = isset($ruledef['IS']) ? $ruledef['IS'] : 'contains';
        $value = isset($ruledef['VALUE']) ? $ruledef['VALUE'] : '';
        $out = $this->getBaseConstraints($rowid, $is);

        switch ($type) {
            case 'to':
            case 'from':
            case 'cc':
            case 'subject':
            case 'headers':
            case 'email_body':
            case 'customer_id':
                return $out.' <input type="text" value="'.formClean($value).'" size="30" name="'.$rowid.'_3" id="'.$rowid.'_3">';

                break;

            case 'mailbox_id':
                include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

                $boxes = apiGetAllMailboxes(0, '');

                if (hs_rscheck($boxes)) {
                    $out = '<select name="'.$rowid.'_2">';
                    while ($row = $boxes->FetchRow()) {
                        $out .= '<option value="'.$row['xMailbox'].'" '.selectionCheck($row['xMailbox'], $value).'>'.hs_jshtmlentities(replyNameDisplay($row['sReplyName'])).' - '.$row['sReplyEmail'].'</option>';
                    }
                    $out .= '</select>';

                    return $out;
                }

                break;

            case 'has_attach':
            case 'is_urgent':
            case 'is_spam':
            case 'is_not_spam':
                return '';

                break;
        }
    }
}
