<?php

// SECURITY: Don't allow direct calls
use HS\Domain\Workspace\Request;

if (! defined('cBASEPATH')) {
    die();
}

class api_private extends api
{
    //API access type
    public $api = 'private';

    public $disabled = false;

    //Valid methods, ones which can be publicly called.
    public $valid_methods = ['private.customer.getPasswordByEmail',
                               'private.customer.setPasswordByEmail',
                               'private.document.get',
                               'private.request.create',
                               'private.request.update',
                               'private.request.get',
                               'private.request.multiGet',
                               'private.request.getChanged',
                               'private.request.search',
                               'private.request.subscriptions',
                               'private.request.subscribe',
                               'private.request.unsubscribe',
                               'private.request.addTimeEvent',
                               'private.request.deleteTimeEvent',
                               'private.request.getTimeEvents',
                               'private.request.getCategories',
                               'private.request.getMailboxes',
                               'private.request.getStatusTypes',
                               'private.request.getCustomFields',
                               'private.request.merge',
                               'private.request.markRead',
                               'private.request.markUnRead',
                               'private.request.markTrash',
                               'private.request.markSpam',
                               'private.filter.get',
                               'private.filter.getStream',
                               'private.filter.getColumnNames',
                               'private.timetracker.search',
                               'private.user.getFilters',
                               'private.user.preferences',
                               'private.util.getActiveStaff',
                               'private.util.getAllStaff',
                               'private.util.getStaffPhoto',
                               'private.response.listAll',
                               'private.response.usersMostCommon',
                               'private.response.get',
                               'private.addressbook.createContact',
                               'private.addressbook.deleteContact',
                               'private.addressbook.getContacts',
                               'private.webhook.subscribe',
                               'private.webhook.unsubscribe',
                               'private.meta',
                               'private.version', ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        //If API type disabled then abort
        if (! hs_setting('cHD_WSPRIVATE')) {
            $this->disabled = true;
            header('HTTP/1.1 '.$this->error_header);
            if ($this->output_type == 'json') {
                header('Content-Type: text/javascript;');
                echo '{"reply":"Private API not enabled"}';
            } elseif ($this->output_type == 'php') {
                header('Content-Type: text/html; charset=UTF-8');
                echo serialize('Private API not enabled');
            } else {
                header('Content-type: text/xml');
                echo '<?xml version="1.0" encoding="UTF-8"?><reply>Private API not enabled</reply>';
            }
        }
    }

    /**
     * Get the portal password for an email account.
     * @GET sEmail (REQUIRED)
     * @GET sPassword
     */
    public function private_customer_getPasswordByEmail()
    {
        $this->_error(303);
    }

    /**
     * Set the portal password for an email account.
     * @GET sEmail (REQUIRED)
     * @GET sPassword (REQUIRED)
     */
    public function private_customer_setPasswordByEmail()
    {
        $this->_GET('sEmail');
        $this->_GET('sPassword');

        if (! $this->in_error()) {
            $rs = apiPortalPasswordUpdate($this->_GET('sEmail'), $this->_GET('sPassword'));

            if ($rs) {
                $this->result = ['sPassword'=>$this->_GET('sPassword')];
            } else {
                $this->_error(211);
            }
        }
    }

    /**
     * Retrieve document details.
     * @GET xDocumentId (REQUIRED)
     * @GET fRawValues
     */
    public function private_document_get()
    {
        $this->root_tag = 'document';
        $id = $this->_GET('xDocumentId');

        $rs = apiGetDocumentWithBlob($id, __FILE__, __LINE__);

        if (! hs_empty($rs['sFileLocation'])) {
            $rs['sBody'] = base64_encode(file_get_contents(hs_setting('cHD_ATTACHMENT_LOCATION_PATH').$rs['sFileLocation']));
        } else {
            $rs['sBody'] = base64_encode($rs['blobFile']);
        }

        // Remove the original blob or location since they can't use those.
        unset($rs['blobFile'], $rs['sFileLocation']);

        $this->result = $rs;
    }

    /**
     * Create a request.
     * @POST tNote (REQUIRED)
     * @POST xCategory (REQUIRED)
     * @POST dtGMTOpened
     * @POST fNoteType (default to private)
     * @POST reportingTags
     * @POST sTitle (used in emails as subject)
     * @POST xPersonAssignedTo
     * @POST fOpen
     * @POST xStatus
     * @POST sUserId
     * @POST sFirstName
     * @POST sLastName
     * @POST sEmail
     * @POST sPhone
     * @POST fUrgent
     * @POST fOpenedVia
     * @POST email_from (ID of mailbox, 0 for no email)
     * @POST email_cc (comma list)
     * @POST email_bcc (comma list)
     * @POST email_to (comma list)
     * @POST email_staff (comma list of person ID's)
     * @POST Custom#
     * @POST FileX_sFilename
     * @POST FileX_sFileMimeType
     * @POST FileX_bFileBody
     */
    public function private_request_create()
    {
        $this->root_tag = 'request';
        include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.multiportal.php';
        $time = time();
        $customFields = apiGetCustomFields();
        $data = [];

        //Check requireds
        $this->_POST('tNote');
        $this->_POST('xCategory');

        if (! $this->in_error()) {
            $data['dtGMTOpened'] = $this->_POST('dtGMTOpened', $time);
            if (isset($_POST['reportingTags'])) {
                $data['reportingTags'] = explode(',', $this->_POST('reportingTags', ''));
            }

            $data['sub_create'] = true;
            $data['fNoteIsHTML'] = $this->_POST('fNoteIsHTML', (hs_setting('cHD_HTMLEMAILS') ? 1 : 0));
            if (isset($_POST['xPersonOpenedBy']) && $_POST['xPersonOpenedBy'] > 0) {
                $GLOBALS['user'] = apiGetUser($_POST['xPersonOpenedBy']);
            }
            $data['xPersonOpenedBy'] = $this->_POST('xPersonOpenedBy', $this->user['xPerson']);

            //Note body
            $data['tBody'] = $this->_POST('tNote', '');
            $data['note_is_markdown'] = 0;
            if ($data['fNoteIsHTML'] && hs_setting('cHD_HTMLEMAILS')) {
                $data['note_is_markdown'] = 1;
            } //if html emails setup pass through markdown filter (note that html will pass through OK)

            if ($this->_POST('fNoteType', 0) == 1) {//public
                $data['fPublic'] = 1;
            } elseif ($this->_POST('fNoteType', 0) == 2) { //external
                $data['fPublic'] = 0;
                $data['external_note'] = 1;
            } else { //private
                $data['fPublic'] = 0;
            }

            if (isset($_POST['sTitle'])) {
                $data['sTitle'] = $this->_POST('sTitle', '');
            } else {
                $data['sTitle'] = ! hs_empty($data['sTitle']) ? $data['sTitle'] : lg_request_subjectdefault;
            }
            if (isset($_POST['xPersonAssignedTo'])) {
                $data['xPersonAssignedTo'] = $this->_POST('xPersonAssignedTo', 0);
            }
            if (isset($_POST['fOpen'])) {
                $data['fOpen'] = $this->_POST('fOpen', 1);
                if ($this->_POST('fOpen') == '0') {
                    $data['dtGMTClosed'] = $time;
                }
            }
            if (isset($_POST['xStatus'])) {
                $data['xStatus'] = $this->_POST('xStatus', hs_setting('cHD_STATUS_ACTIVE', 1));
            }
            if (isset($_POST['xCategory'])) {
                $data['xCategory'] = $this->_POST('xCategory', 0);
            }
            if (isset($_POST['xPortal'])) {
                $data['xPortal'] = $this->_POST('xPortal', 0);
            }
            $data['fOpenedVia'] = (isset($_POST['fOpenedVia']) && $_POST['fOpenedVia'] != 1) ? $this->_POST('fOpenedVia', 6) : 6;
            if (isset($_POST['sUserId'])) {
                $data['sUserId'] = $this->_POST('sUserId', '');
            }
            if (isset($_POST['sFirstName'])) {
                $data['sFirstName'] = $this->_POST('sFirstName', 0);
            }
            if (isset($_POST['sLastName'])) {
                $data['sLastName'] = $this->_POST('sLastName', 0);
            }
            if (isset($_POST['sEmail'])) {
                $data['sEmail'] = $this->_POST('sEmail', 0);
            }
            if (isset($_POST['sPhone'])) {
                $data['sPhone'] = $this->_POST('sPhone', 0);
            }
            if (isset($_POST['fUrgent'])) {
                $data['fUrgent'] = $this->_POST('fUrgent', 0);
            }
            if (isset($_POST['skipCustomChecks'])) {
                $data['skipCustomChecks'] = $this->_POST('skipCustomChecks', 0);
            }

            //CC/BCC/To/Notify
            if (isset($_POST['email_from']) && $_POST['email_from'] == 0) { //If 0 do not send email
                $data['emailfrom'] = '';
            } elseif (isset($_POST['email_from'])) {
                $mb = apiGetMailbox($this->_POST('email_from', 0));
                $data['emailfrom'] = hs_jshtmlentities($mb['sReplyName']).'*'.$mb['sReplyEmail'].'*'.$mb['xMailbox'];
                $data['xMailboxToSendFrom'] = $mb['xMailbox']; //make sure we send from this mailbox when a staffer updates
            } elseif ($data['xPortal'] > 0) { //Make sure API created requests for a custom portal get the correct send from
                $portal = new hs_multiportal($data['xPortal']);
                $mb = apiGetMailbox($portal->xMailboxToSendFrom);
                $data['emailfrom'] = hs_jshtmlentities($mb['sReplyName']).'*'.$mb['sReplyEmail'].'*'.$mb['xMailbox'];
                $data['xMailboxToSendFrom'] = $mb['xMailbox']; //make sure we send from this mailbox when a staffer updates
            } else {
                $data['emailfrom'] = $this->_POST('email_from', hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILNAME')).'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT'));
            }
            if (isset($_POST['email_cc'])) {
                $data['emailccgroup'] = $this->_POST('email_cc', '');
            }
            if (isset($_POST['email_bcc'])) {
                $data['emailbccgroup'] = $this->_POST('email_bcc', '');
            }
            if (isset($_POST['email_to'])) {
                $data['emailtogroup'] = $this->_POST('email_to', '');
            }
            if (isset($_POST['email_staff'])) {
                $data['ccstaff'] = $this->_POST('email_staff', '');
            }

            // Handle custom fields
            if (! empty($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $v) {
                    $custid = 'Custom'.$v['fieldID'];
                    if (isset($_POST[$custid])) {
                        $data[$custid] = $this->_POST($custid, 0);
                    }
                }
            }

            //Pass in update
            $rs = apiProcessRequest('', $data, $this->_mapAttachments(), __FILE__, __LINE__);

            if ($rs && ! isset($rs['errorBoxText'])) {
                $this->result = ['xRequest'=>$rs['reqid']];
            } elseif (isset($rs['errorBoxText'])) {
                $this->_error(207, $rs['errorBoxText']);
            } else {
                $this->_error(206);
            }
        }
    }

    /**
     * Update an existing request.
     * @POST xRequest (REQUIRED)
     * @POST tNote
     * @POST dtGMTChange
     * @POST reportingTags
     * @POST fNoteType
     * @POST sTitle (used in emails as subject)
     * @POST xPersonAssignedTo
     * @POST xPerson
     * @POST fOpen
     * @POST xStatus
     * @POST xCategory
     * @POST sUserId
     * @POST sFirstName
     * @POST sLastName
     * @POST sEmail
     * @POST sPhone
     * @POST fUrgent
     * @POST email_from (ID of mailbox, 0 for no email)
     * @POST email_cc (comma list)
     * @POST email_bcc (comma list)
     * @POST email_to (comma list)
     * @POST email_staff (comma list of person ID's)
     * @POST Custom#
     * @POST FileX_sFilename
     * @POST FileX_sFileMimeType
     * @POST FileX_bFileBody
     */
    public function private_request_update()
    {
        $this->root_tag = 'request';
        include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        include_once cBASEPATH.'/helpspot/lib/class.multiportal.php';

        $reqid = $this->_POST('xRequest');
        $time = time();
        $customFields = apiGetCustomFields();

        // They want to update as the customer via the private api (usually to avoid having the enable the public api)
        // so we have to override the global user just like in the public api
        if (isset($_POST['xPerson']) && $_POST['xPerson'] == 0) {
            $GLOBALS['user'] = [];
            $GLOBALS['user']['xPerson'] = 0;
            $GLOBALS['user']['sFname'] = '';
            $GLOBALS['user']['sLname'] = '';
            $GLOBALS['user']['sEmail'] = '';

            // Don't allow outbound email from customer which could be confusing
            $_POST['email_from'] = 0;
        }

        // If they're trying to override the acting user we have to update the global user
        // :( this is not ideal.
        if (isset($_POST['xPerson']) && $_POST['xPerson'] > 0) {
            $GLOBALS['user'] = apiGetUser($_POST['xPerson']);
        }

        //Get the request information
        if (is_numeric($reqid)) {
            $request = apiGetRequest($reqid);
        }

        //Check if this is a merged request
        if ($request == false && $merged_id = apiCheckIfMerged($reqid)) {
            $this->_error(208, '', ['xRequest' => $merged_id]);

            return;
        }

        //Check that the request valid
        if (! $this->in_error() && $request) {

            if (Request::reachedHistoryLimit($reqid)) {
                return $this->_error(106);
            }

            //If request was already closed then we should reopen.
            if (intval($request['fOpen']) === 0) {
                // Reopen request
                $request['fOpen'] = 1;
                $request['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);
                $request['dtGMTOpened'] = $this->_POST('dtGMTChange', $time);
                //if the user isn't active then send to inbox
                $ustatus = apiGetUser($request['xPersonAssignedTo']);
                if ($ustatus['fDeleted'] == 1) {
                    $request['xPersonAssignedTo'] = 0;
                }

                $update = new requestUpdate($reqid, $request, 0, __FILE__, __LINE__);
                $update->notify = false; //notify below instead
                $reqResult = $update->checkChanges();
            }

            //If request was in trash we should remove it
            if (intval($request['fTrash']) === 1) {
                // Reopen request
                $request['fTrash'] = 0;
                $request['dtGMTTrashed'] = 0;
                $request['dtGMTOpened'] = $this->_POST('dtGMTChange', $time);

                $update = new requestUpdate($reqid, $request, 0, __FILE__, __LINE__);
                $update->notify = false; //notify below instead
                $reqResult = $update->checkChanges();
            }

            //Setup array of request data
            $data = apiGetRequest($reqid);
            $data['dtGMTOpened'] = $this->_POST('dtGMTChange', $time);
            //Handle rep tags
            $data['reportingTags'] = array_keys(apiGetRequestRepTags($reqid));
            if (isset($_POST['reportingTags'])) {
                $data['reportingTags'] = explode(',', $this->_POST('reportingTags', ''));
            }

            $data['sub_update'] = true;
            $data['fNoteIsHTML'] = $this->_POST('fNoteIsHTML', (hs_setting('cHD_HTMLEMAILS') ? 1 : 0));

            if (isset($_POST['tNote']) && ! empty($_POST['tNote'])) {
                $data['tBody'] = ($data['fNoteIsHTML'] && hs_setting('cHD_HTMLEMAILS') ? hs_markdown($this->_POST('tNote', '')) : $this->_POST('tNote', '')); //if html emails setup pass through markdown filter (note that html will pass through OK)
                if ($this->_POST('fNoteType', 0) == 1) {//public
                    $data['fPublic'] = 1;
                } elseif ($this->_POST('fNoteType', 0) == 2) { //external
                    $data['fPublic'] = 0;
                    $data['external_note'] = 1;
                } else { //private
                    $data['fPublic'] = 0;
                }
            }
            if (isset($_POST['sTitle'])) {
                $data['sTitle'] = $this->_POST('sTitle', '');
            } else {
                $data['sTitle'] = ! hs_empty($data['sTitle']) ? $data['sTitle'] : lg_request_subjectdefault;
            }
            if (isset($_POST['xPersonAssignedTo'])) {
                $data['xPersonAssignedTo'] = $this->_POST('xPersonAssignedTo', 0);
            }
            if (isset($_POST['fOpen'])) {
                $data['fOpen'] = $this->_POST('fOpen', 1);
            }
            if (isset($_POST['xStatus'])) {
                $data['xStatus'] = $this->_POST('xStatus', hs_setting('cHD_STATUS_ACTIVE', 1));
            }
            if (isset($_POST['xCategory'])) {
                $data['xCategory'] = $this->_POST('xCategory', 0);
            }
            if (isset($_POST['sUserId'])) {
                $data['sUserId'] = $this->_POST('sUserId', '');
            }
            if (isset($_POST['sFirstName'])) {
                $data['sFirstName'] = $this->_POST('sFirstName', 0);
            }
            if (isset($_POST['sLastName'])) {
                $data['sLastName'] = $this->_POST('sLastName', 0);
            }
            if (isset($_POST['sEmail'])) {
                $data['sEmail'] = $this->_POST('sEmail', 0);
            }
            if (isset($_POST['sPhone'])) {
                $data['sPhone'] = $this->_POST('sPhone', 0);
            }
            if (isset($_POST['fUrgent'])) {
                $data['fUrgent'] = $this->_POST('fUrgent', 0);
            }
            if (isset($_POST['skipCustomChecks'])) {
                $data['skipCustomChecks'] = $this->_POST('skipCustomChecks', 0);
            }

            //CC/BCC/To/Notify
            if (isset($_POST['email_from'])) { //use the past in mailbox id
                $mbid = $_POST['email_from'];
            } elseif ($request['xMailboxToSendFrom'] != 0) { //use the send from set previously
                $mbid = $request['xMailboxToSendFrom'];
            } elseif ($request['fOpenedVia'] == 1 && $request['xOpenedViaId'] != 0) { //use the mailbox it came in on if it's an email
                $mbid = $request['xOpenedViaId'];
            }

            if (isset($_POST['email_from']) && $_POST['email_from'] == 0) { //If 0 do not send email
                $data['emailfrom'] = '';
            } elseif (isset($mbid)) {
                $mb = apiGetMailbox($mbid);
                $data['emailfrom'] = hs_jshtmlentities($mb['sReplyName']).'*'.$mb['sReplyEmail'].'*'.$mb['xMailbox'];
            } else { //last resort send from default
                $data['emailfrom'] = $this->_POST('email_from', hs_jshtmlentities(hs_setting('cHD_NOTIFICATIONEMAILNAME')).'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0');
            }

            if (isset($_POST['email_cc'])) {
                $data['emailccgroup'] = $this->_POST('email_cc', '');
            }
            if (isset($_POST['email_bcc'])) {
                $data['emailbccgroup'] = $this->_POST('email_bcc', '');
            }
            if (isset($_POST['email_to'])) {
                $data['emailtogroup'] = $this->_POST('email_to', '');
            }
            if (isset($_POST['email_staff'])) {
                $data['ccstaff'] = $this->_POST('email_staff', '');
            }

            // Handle custom fields
            if (! empty($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $v) {
                    $custid = 'Custom'.$v['fieldID'];
                    if (isset($_POST[$custid])) {
                        $data[$custid] = $this->_POST($custid, 0);
                    }
                }
            }

            //Pass in update
            $rs = apiProcessRequest($reqid, $data, $this->_mapAttachments(), __FILE__, __LINE__);

            if ($rs && ! isset($rs['errorBoxText'])) {
                $this->result = ['xRequest'=>$rs['reqid']];
            } elseif (isset($rs['errorBoxText'])) {
                $this->_error(212, $rs['errorBoxText']);
            } else {
                $this->_error(210);
            }
        } else {
            $this->_error(103);
        }
    }

    /**
     * Retrieve request details.
     * @GET xRequest (REQUIRED)
     * @GET inlineImages
     * @GET fRawValues
     */
    public function private_request_get()
    {
        $this->root_tag = 'request';
        $reqid = $this->_GET('xRequest');
        $inlineImages = $this->_GET('inlineImages', 0);
        $this->result = $this->_request_get($reqid, $inlineImages);
    }

    /**
     * Retrieve request details for multiple requests at once.
     * @GET xRequest (REQUIRED)
     * @GET inlineImages
     * @GET fRawValues
     */
    public function private_request_multiGet()
    {
        $this->root_tag = 'requests';
        $reqids = $this->_GET('xRequest');
        $inlineImages = $this->_GET('inlineImages', 0);

        foreach ((array) $reqids as $reqid) {
            $this->result['request'][] = $this->_request_get($reqid, $inlineImages);
        }
    }

    /**
     * private method that lets us get request details for both get and multiGet.
     * @GET xRequest (REQUIRED)
     * @GET fRawValues
     */
    private function _request_get($reqid, $inlineImages)
    {
        $result = '';

        if ($reqid && ! $this->in_error()) {
            //check for merged and return merged request if the originally requested request has been merged
            if ($merged_id = apiCheckIfMerged($reqid)) {
                $reqid = $merged_id;
            }

            $result = apiGetRequest($reqid);

            if (perm('fCanViewOwnReqsOnly')) {
                if ($result['xPersonAssignedTo'] != $user['xPerson']) {
                    return '';
                }
            }

            if (perm('fLimitedToAssignedCats')) {
                $cats = apiGetUserCats($user['xPerson']);
                if (! in_array($result['xCategory'], $cats)) {
                    return '';
                }
            }

            //Adjust fields
            if (! $this->_GET('fRawValues', 0)) {
                foreach ($GLOBALS['filterCols'] as $field=>$fv) {
                    if (isset($result[$field]) && $field != 'iLastReplyBy' && $field != 'tNote' && ! isset($fv['function_args']) && isset($fv['function'])) {
                        if ($result[$field]) {
                            $result[$field] = call_user_func($fv['function'], $result[$field]);
                        } else {
                            $result[$field] = '';
                        }
                    }
                }

                //Handle open via, mailbox col, cat and status which cannot be handled above
                if ($result['xOpenedViaId'] != 0) {
                    $result['xOpenedViaId'] = hs_mailbox_from_id($result['xOpenedViaId']);
                }
                if ($result['iLastReplyBy'] != 0) {
                    $u = apiGetUser($result['iLastReplyBy']);
                    $result['iLastReplyBy'] = $u['sFname'].' '.$u['sLname'];
                }
                $result['xCategory'] = apiGetCategoryName($result['xCategory']);
                $result['xStatus'] = apiGetStatusName($result['xStatus']);
                $result['fOpenedVia'] = $GLOBALS['openedVia'][$result['fOpenedVia']];
            }

            //Reporting tags
            $tag_list = [];
            $tags = apiGetRequestRepTags($reqid);
            if (! empty($tags)) {
                foreach ($tags as $k=>$tag) {
                    $tag_list[] = ['xReportingTag'=>$k, 'sReportingTag'=>$tag];
                }
                $result['reportingTags'] = ['tag'=>$tag_list];
            }

            //Subscribers
            $subscriber_list = [];
            $subscribers = apiGetRequestSubscribers($reqid);
            if (! empty($subscribers)) {
                foreach ($subscribers as $k=>$sub) {
                    $subscriber_list[] = ['xPerson'=>$sub];
                }
                $result['subscribers'] = $subscriber_list;
            }

            //Add in request history
            $result['request_history'] = ['item'=>hs_clean_req_history_for_API($reqid, $result, ($this->_GET('fRawValues', 0) ? false : true), ($this->_GET('inlineImages', 0) ? true : false))];

            //Remove from result set
            unset($result['sCategory']);
            unset($result['sStatus']);
            unset($result['iLastReadCount']);
            unset($result['sRequestHash']);
        }

        return $result;
    }

    /**
     * Return all requests which have changed since the date given.
     * @GET dtGMTChange
     */
    public function private_request_getChanged()
    {
        $this->root_tag = 'requests';
        if ($this->_GET('dtGMTChange') && is_numeric($this->_GET('dtGMTChange'))) {
            $data['dtGMTChange'] = $this->_GET('dtGMTChange');
        }

        $this->result = ['xRequest'=>$GLOBALS['DB']->GetCol('SELECT xRequest FROM HS_Request_History WHERE dtGMTChange > ?', [$data['dtGMTChange']])];
    }

    /**
     * Search for requests, wildcards allowed.
     * @GET anyall
     * @GET xRequest
     * @GET sUserId
     * @GET sFirstName
     * @GET sLastName
     * @GET sEmail
     * @GET sPhone
     * @GET sSearch
     * @GET fRawValues
     * @GET start
     * @GET length
     */
    public function private_request_search()
    {
        $this->root_tag = 'requests';
        include_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';

        $data['anyall'] = $this->_GET('anyall', 'all');
        if ($this->_GET('xRequest', 0)) {
            $data['xRequest'] = (int) $this->_GET('xRequest');
        }
        if ($this->_GET('sUserId', 0)) {
            $data['sUserId'] = $this->_GET('sUserId');
        }
        if ($this->_GET('sFirstName', 0)) {
            $data['sFirstName'] = $this->_GET('sFirstName');
        }
        if ($this->_GET('sLastName', 0)) {
            $data['sLastName'] = $this->_GET('sLastName');
        }
        if ($this->_GET('sEmail', 0)) {
            $data['sEmail'] = $this->_GET('sEmail');
        }
        if ($this->_GET('sPhone', 0)) {
            $data['sPhone'] = $this->_GET('sPhone');
        }
        if ($this->_GET('sSearch', 0)) {
            $data['sSearch'] = $this->_GET('sSearch');
        }

        if (isset($_GET['fOpen'])) {
            $data['fOpen'] = $this->_GET('fOpen');
        }
        if (isset($_GET['xCategory'])) {
            $data['xCategory'] = $this->_GET('xCategory');
        }
        if (isset($_GET['xPersonAssignedTo'])) {
            $data['xPersonAssignedTo'] = $this->_GET('xPersonAssignedTo');
        }
        if (isset($_GET['xStatus'])) {
            $data['xStatus'] = $this->_GET('xStatus');
        }
        if (isset($_GET['reportingTags'])) {
            $data['reportingTags'] = explode(',', $this->_GET('reportingTags', ''));
        }

        if (! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $v) {
                $custid = 'Custom'.$v['fieldID'];
                if (isset($_GET[$custid])) {
                    $data[$custid] = $this->_GET($custid);
                }
            }
        }

        //Date options
        if (isset($_GET['beforeDate'])) {
            $data['beforeDate'] = $this->_GET('beforeDate');
        }
        if (isset($_GET['afterDate'])) {
            $data['afterDate'] = $this->_GET('afterDate');
        }
        if (isset($_GET['closedBeforeDate'])) {
            $data['closedBeforeDate'] = $this->_GET('closedBeforeDate');
        }
        if (isset($_GET['closedAfterDate'])) {
            $data['closedAfterDate'] = $this->_GET('closedAfterDate');
        }
        if (isset($_GET['relativedate'])) {
            $data['relativedate'] = $this->_GET('relativedate');
        }

        //Order options
        if (isset($_GET['orderBy'])) {
            $data['orderBy'] = $this->_GET('orderBy');
        }
        if (isset($_GET['orderByDir'])) {
            $data['orderByDir'] = $this->_GET('orderByDir');
        }

        //Always show urgent inline
        $data['urgentinline'] = true;

        //Set basic columns so data is pulled in for them by filter
        $data['displayColumns'] = ['view', 'fOpenedVia', 'fullname', 'reqsummary', 'dtGMTTrashed'];

        //Filter
        $f = new hs_filter($data);

        //Paginate
        if ($this->_GET('start', 0)) {
            $f->paginate = $this->_GET('start');
        }
        if ($this->_GET('length', 0)) {
            $f->paginate_length = $this->_GET('length');
        }

        //Run filter
        $rs = $f->outputResultSet();

        $this->result = $this->_rsToOutputArray($rs, 'request', ['iLastReadCount', 'sRequestHash']);

        //Adjust fields
        foreach ($this->result['request'] as $k=>$v) {
            $this->result['request'][$k]['tNote'] = strip_tags(initRequestClean($v['tNote'], true));

            //Run functions
            if (! $this->_GET('fRawValues', 0)) {
                foreach ($GLOBALS['filterCols'] as $field=>$fv) {
                    if (isset($this->result['request'][$k][$field]) && $field != 'iLastReplyBy' && $field != 'tNote' && ! isset($fv['function_args']) && isset($fv['function'])) {
                        if ($this->result['request'][$k][$field]) {
                            $this->result['request'][$k][$field] = call_user_func($fv['function'], $this->result['request'][$k][$field]);
                        } else {
                            $this->result['request'][$k][$field] = '';
                        }
                    }
                }

                //Handle mailbox col, cat and status which cannot be handled above
                if ($this->result['request'][$k]['xOpenedViaId'] != 0) {
                    $this->result['request'][$k]['xOpenedViaId'] = hs_mailbox_from_id($this->result['request'][$k]['xOpenedViaId']);
                }
                if ($this->result['request'][$k]['iLastReplyBy'] != 0) {
                    $u = apiGetUser($this->result['request'][$k]['iLastReplyBy']);
                    $this->result['request'][$k]['iLastReplyBy'] = $u['sFname'].' '.$u['sLname'];
                }
                $this->result['request'][$k]['xCategory'] = $this->result['request'][$k]['sCategory'];
                $this->result['request'][$k]['xStatus'] = $this->result['request'][$k]['sStatus'];
                $this->result['request'][$k]['fOpenedVia'] = $GLOBALS['openedVia'][$this->result['request'][$k]['fOpenedVia']];
            }

            //Remove from result set
            unset($this->result['request'][$k]['sCategory']);
            unset($this->result['request'][$k]['sStatus']);
            unset($this->result['request'][$k]['iLastReadCount']);
        }
    }

    /**
     * Return subscriptions for a user.
     */
    public function private_request_subscriptions()
    {
        $this->root_tag = 'subscriptions';
        if (perm('fCanViewOwnReqsOnly')) {
            die();
        } //can't subscribe in this case

        $xperson = $this->_GET('xPerson');

        if (! $this->in_error()) {
            //Does more than we need here
            //$data = apiGetSubscribersByPerson($xperson,'',__FILE__,__LINE__);

            $res = $GLOBALS['DB']->Execute('SELECT HS_Request.xRequest,HS_Subscriptions.xSubscriptions
											FROM HS_Subscriptions,HS_Request
											WHERE HS_Request.xRequest = HS_Subscriptions.xRequest AND HS_Subscriptions.xPerson = ? AND HS_Request.fOpen = 1
											ORDER BY HS_Subscriptions.xRequest ASC', [$xperson]);

            $this->result = $this->_rsToOutputArray($res, 'request');
        }
    }

    /**
     * Add a subscriber.
     * @POST xRequest (REQUIRED)
     * @POST xPerson (REQUIRED)
     */
    public function private_request_subscribe()
    {
        $this->_POST('xRequest');
        $this->_POST('xPerson');

        if (! $this->in_error()) {
            $result = apiSubscribeToRequest($this->_POST('xRequest'), $this->_POST('xPerson'));

            if ($result) {
                $this->result = ['subscribed'=>true];
            } else {
                $this->_error(214);
            }
        }
    }

    /**
     * Unsubscribe a staffer.
     * @POST xRequest (REQUIRED)
     * @POST xPerson (REQUIRED)
     */
    public function private_request_unsubscribe()
    {
        $this->_POST('xRequest');
        $this->_POST('xPerson');

        if (! $this->in_error()) {
            $result = apiUnSubscribeToRequest($this->_POST('xRequest'), $this->_POST('xPerson'));

            if ($result) {
                $this->result = ['unsubscribed'=>true];
            } else {
                $this->_error(215);
            }
        }
    }

    /**
     * Create a time request for an event.
     * @POST xRequest (REQUIRED)
     * @POST xPerson (REQUIRED)
     * @POST iMonth (REQUIRED)
     * @POST iDay (REQUIRED)
     * @POST iYear (REQUIRED)
     * @POST tDescription (REQUIRED)
     * @POST tTime (REQUIRED)
     * @POST dtGMTDateAdded
     * @POST fBillable
     */
    public function private_request_addTimeEvent()
    {
        $data = ['xRequest'		=> $this->_POST('xRequest'),
                      'xPerson'			=> $this->_POST('xPerson'),
                      'iMonth'			=> $this->_POST('iMonth'),
                      'iDay'			=> $this->_POST('iDay'),
                      'iYear'			=> $this->_POST('iYear'),
                      'tDescription'	=> $this->_POST('tDescription'),
                      'tTime'			=> $this->_POST('tTime'),
                      'dtGMTDateAdded' 	=> $this->_POST('dtGMTDateAdded', ''),
                      'fBillable'		=> $this->_POST('fBillable', 0), ];

        if (! $this->in_error()) {
            if ($rs = apiAddTime($data)) {
                $this->result = ['xTimeId'=>$rs];
            } else {
                $this->_error(209);
            }
        }
    }

    /**
     * delete a time event.
     * @POST xTimeId (REQUIRED)
     */
    public function private_request_deleteTimeEvent()
    {
        $data = ['xTimeId'=> $this->_POST('xTimeId')];

        if (! $this->in_error()) {
            if ($rs = apiDeleteTime($data)) {
                $this->result = ['deleted'=>true];
            } else {
                $this->_error(209);
            }
        }
    }

    /**
     * List time events for a request.
     * @GET xRequest (REQUIRED)
     * @GET fRawValues
     */
    public function private_request_getTimeEvents()
    {
        $this->root_tag = 'time_events';

        $reqid = $this->_GET('xRequest');

        if (! $this->in_error()) {
            if ($rs = apiGetTimeForRequest($reqid)) {
                $this->result = $this->_rsToOutputArray($rs, 'event', []);

                //Adjust fields
                if (! $this->_GET('fRawValues', 0)) {
                    $staff = apiGetAllUsersComplete();

                    foreach ($this->result['event'] as $k=>$v) {
                        $this->result['event'][$k]['dtGMTDate'] = hs_showShortDate($this->result['event'][$k]['dtGMTDate']);
                        $this->result['event'][$k]['dtGMTDateAdded'] = hs_showDate($this->result['event'][$k]['dtGMTDateAdded']);
                        $this->result['event'][$k]['xPerson'] = $staff[$this->result['event'][$k]['xPerson']]['fullname'];
                    }
                }
            } else {
                $this->_error(209);
            }
        }
    }

    /**
     * Return categories.
     */
    public function private_request_getCategories()
    {
        $this->root_tag = 'categories';

        $allStaff = apiGetAssignStaff();

        $result = apiGetAllCategoriesComplete();
        $out = rsToArray($result, 'xCategory', false);

        foreach ($out as $k=>$v) {
            //Custom fields
            if (! empty($v['sCustomFieldList'])) {
                $checked_fields = [];
                $fields = hs_unserialize($v['sCustomFieldList']);

                foreach ($fields as $k=>$fid) {
                    $checked_fields[] = $fid;
                }

                $out[$v['xCategory']]['sCustomFieldList'] = ['xCustomField'=>$checked_fields];
            } else {
                $out[$v['xCategory']]['sCustomFieldList'] = null;
            }

            //Person list
            $person_list = [];
            $person_list_array = hs_unserialize($v['sPersonList']);
            foreach ($person_list_array as $k=>$id) {
                $person_list[] = ['xPerson'=>$id, 'fullname'=>$allStaff[$id]['fullname'], 'assigned_requests'=>$allStaff[$id]['request_count']];
            }

            $out[$v['xCategory']]['sPersonList'] = ['person'=>$person_list];

            //Reporting tags
            $tag_list = [];
            $tags = apiGetReportingTags($v['xCategory']);
            foreach ($tags as $k=>$tag) {
                $tag_list[] = ['xReportingTag'=>$k, 'sReportingTag'=>$tag];
            }

            $out[$v['xCategory']]['reportingTags'] = ['tag'=>$tag_list];

            //strip cols from return
            $out[$v['xCategory']] = $this->_stripColsFromArray($out[$v['xCategory']], []);
        }

        $this->result = ['category'=>$out];
    }

    /**
     * Return mailboxes.
     * @GET fActiveOnly
     */
    public function private_request_getMailboxes()
    {
        include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        //$this->root_tag = 'mailboxes';

        if ($this->_GET('fActiveOnly', 1)) {
            $mailbox_list = apiGetAllMailboxes(0, '');
        } else {
            $mailbox_list = apiGetAllMailboxes(0, '');
            $mailbox_list2 = apiGetAllMailboxes(1, '');
            $mailbox_list = array_merge(rsToArray($mailbox_list, 'xMailbox', false), rsToArray($mailbox_list2, 'xMailbox', false));
        }

        $out = [];
        foreach ($mailbox_list as $k=>$v) {
            $out[] = ['xMailbox'=>$v['xMailbox'], 'sReplyName'=>replyNameDisplay($v['sReplyName']), 'sReplyEmail'=>$v['sReplyEmail']];
        }

        $this->result = ['mailbox'=>$out];
    }

    /**
     * Return status types.
     * @GET fActiveOnly
     */
    public function private_request_getStatusTypes()
    {
        //$this->root_tag = 'status';

        if ($this->_GET('fActiveOnly', 1)) {
            $status_list = apiGetActiveStatus();
        } else {
            $status_list = apiGetStatus();
        }

        $out = [];
        foreach ($status_list as $k=>$v) {
            $out[] = ['sStatus'=>$v, 'xStatus'=>$k];
        }

        $this->result = ['status'=>$out];
    }

    /**
     * Return all the custom fields and information about them.
     * @GET xCategory
     */
    public function private_request_getCustomFields()
    {
        $this->root_tag = 'customfields';

        if ($this->_GET('xCategory', 0)) {
            $catCustoms = apiGetCategoryCustomFields($this->_GET('xCategory', 0));
        }

        $out = [];
        $fields = apiGetCustomFields();
        if (is_array($fields)) {
            foreach ($fields as $v) {
                //send field if it's always visible, if a category isn't set, or if a category is set and this field is part of it.
                if ($v['isAlwaysVisible'] || ! isset($catCustoms) || in_array($v['fieldID'], $catCustoms)) {
                    $out[$v['fieldID']] = $v;
                    $out[$v['fieldID']]['fieldName'] = hs_htmlspecialchars($v['fieldName']);

                    if (! hs_empty($out[$v['fieldID']]['listItems']) && $out[$v['fieldID']]['fieldType'] != 'drilldown') {
                        $list = hs_unserialize($v['listItems']);
                        if (! empty($list)) {
                            $out[$v['fieldID']]['listItems'] = ['item'=>$list];
                        } else {
                            $out[$v['fieldID']]['listItems'] = '';
                        }
                    } else {
                        $out[$v['fieldID']]['listItems'] = '';
                    }

                    $out[$v['fieldID']] = $this->_stripColsFromArray($out[$v['fieldID']], ['fieldID']);
                }
            }
        }

        $this->result = ['field'=>$out];
    }

    /**
     * Merge 2 requests.
     * @POST xRequestFrom
     * @POST xRequestTo
     */
    public function private_request_merge()
    {
        $this->_POST('xRequestFrom');
        $this->_POST('xRequestTo');

        if (! $this->in_error()) {
            $result = apiMergeRequests($this->_POST('xRequestFrom'), $this->_POST('xRequestTo'));

            if ($result) {
                $this->result = ['xRequest'=>$this->_POST('xRequestTo')];
            } else {
                $this->_error(213);
            }
        }
    }

    /**
     * Mark a request in the users My Queue read.
     * @POST xRequest
     */
    public function private_request_markRead()
    {
        $this->root_tag = 'result';

        $this->_POST('xRequest');

        $request = apiGetRequest($this->_POST('xRequest'));

        if (! $this->in_error() && $request['xPersonAssignedTo'] == $this->user['xPerson']) {
            updateReadUnread($this->_POST('xRequest'));

            $this->result = ['isUnread'=>0];
        } else {
            $this->_error(215);
        }
    }

    /**
     * Mark a request in the users My Queue unread.
     * @POST xRequest
     */
    public function private_request_markUnRead()
    {
        $this->root_tag = 'result';

        $this->_POST('xRequest');

        if (! $this->in_error()) {
            $GLOBALS['DB']->Execute('UPDATE HS_Request SET iLastReadCount = (iLastReadCount-1) WHERE xRequest = ? AND xPersonAssignedTo = ?', [$this->_POST('xRequest'), $this->user['xPerson']]);

            $this->result = ['isUnread'=>1];
        } else {
            $this->_error(215);
        }
    }

    /**
     * Move a request to the trash.
     * @POST xRequest
     */
    public function private_request_markTrash()
    {
        $this->root_tag = 'result';

        $this->_POST('xRequest');

        if (! $this->in_error()) {
            $user = apiGetUserByAuth($this->user['sUsername'], $this->user['sEmail']);

            if ($user['fCanManageTrash'] == 1) {
                $origReq = apiGetRequest($this->_POST('xRequest'));

                $origReq['fTrash'] = 1;
                $origReq['dtGMTTrashed'] = date('U');
                $origReq['dtGMTOpened'] = date('U');	//current dt
                $update = new requestUpdate($this->_POST('xRequest'), $origReq, $this->user['xPerson'], __FILE__, __LINE__);
                $reqResult = $update->checkChanges();
            }

            $this->result = ['trashed'=>$this->_POST('xRequest')];
        } else {
            $this->_error(216);
        }
    }

    /**
     * Move a request to spam.
     * @POST xRequest
     */
    public function private_request_markSpam()
    {
        $this->root_tag = 'result';

        $this->_POST('xRequest');

        if (! $this->in_error()) {
            $user = apiGetUserByAuth($this->user['sUsername'], $this->user['sEmail']);

            if ($user['fCanManageSpam'] == 1) {
                $fm = apiGetRequest($this->_POST('xRequest'));
                $spamreqhis = apiGetInitialRequest($this->_POST('xRequest'));
                if (! hs_empty($spamreqhis['tEmailHeaders']) || $fm['fOpenedVia'] == 7) {
                    $fm['xStatus'] = hs_setting('cHD_STATUS_SPAM', 2);	//set to spam
                    $fm['xPersonAssignedTo'] = 0;	//no assignee
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($this->_POST('xRequest'), $fm, $this->user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();
                }
            }

            $this->result = ['spammed'=>$this->_POST('xRequest')];
        } else {
            $this->_error(217);
        }
    }

    /**
     * Return a filter.
     * @GET xFilter (REQUIRED)
     * @GET fRawValues
     * @GET start
     * @GET length
     */
    public function private_filter_get()
    {
        $this->root_tag = 'filter';
        include_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';

        // All user filters
        $filters = apiGetAllFilters($this->user['xPerson'], 'all');

        //inbox
        if ($this->_GET('xFilter') == 'inbox') {
            $f = new hs_filter();
            $f->useSystemFilter('inbox');
        } elseif ($this->_GET('xFilter') == 'myq') {
            $f = new hs_filter();
            $f->useSystemFilter('myq');
        } elseif (is_numeric($this->_GET('xFilter'))) {
            $f = new hs_filter($filters[$this->_GET('xFilter')]);
        } else {
            return;
        }

        //Paginate
        if ($this->_GET('start', 0)) {
            $f->paginate = $this->_GET('start');
        }
        if ($this->_GET('length', 0)) {
            $f->paginate_length = $this->_GET('length');
        }

        $rs = $f->outputResultSet();
        $this->result = $this->_rsToOutputArray($rs, 'request', ['sRequestHash']);

        //Adjust fields
        foreach ($this->result['request'] as $k=>$v) {
            $this->result['request'][$k]['tNote'] = strip_tags(initRequestClean($v['tNote'], true));

            //If myq call then do unread analysis
            if ($this->_GET('xFilter') == 'myq') {
                $this->result['request'][$k]['isUnread'] = $this->result['request'][$k]['history_ct'] > $this->result['request'][$k]['iLastReadCount'] ? 1 : 0;
                unset($this->result['request'][$k]['history_ct']);
            }

            if ($this->result['request'][$k]['speedtofirstresponse_biz'] != 0) {
                if (! isset($GLOBALS['bizhours'])) {
                    $GLOBALS['bizhours'] = new business_hours;
                }
                $this->result['request'][$k]['speedtofirstresponse_biz'] = $GLOBALS['bizhours']->getBizTime($this->result['request'][$k]['dtGMTOpened'], $this->result['request'][$k]['speedtofirstresponse_biz']);
            }

            //Run functions
            if (! $this->_GET('fRawValues', 0)) {
                foreach ($GLOBALS['filterCols'] as $field=>$fv) {
                    if (isset($this->result['request'][$k][$field]) && $field != 'iLastReplyBy' && $field != 'tNote' && ! isset($fv['function_args']) && isset($fv['function'])) {
                        if ($this->result['request'][$k][$field]) {
                            $this->result['request'][$k][$field] = call_user_func($fv['function'], $this->result['request'][$k][$field]);
                        } else {
                            $this->result['request'][$k][$field] = '';
                        }
                    }
                }

                if ($this->result['request'][$k]['speedtofirstresponse_biz'] != 0) {
                    $this->result['request'][$k]['speedtofirstresponse_biz'] = parseSecondsToTimeWlabel($this->result['request'][$k]['speedtofirstresponse_biz']);
                }

                //Handle mailbox col, cat and status which cannot be handled above
                if ($this->result['request'][$k]['xOpenedViaId'] != 0) {
                    $this->result['request'][$k]['xOpenedViaId'] = hs_mailbox_from_id($this->result['request'][$k]['xOpenedViaId']);
                }
                if ($this->result['request'][$k]['iLastReplyBy'] != 0) {
                    $u = apiGetUser($this->result['request'][$k]['iLastReplyBy']);
                    $this->result['request'][$k]['iLastReplyBy'] = $u['sFname'].' '.$u['sLname'];
                }
                $this->result['request'][$k]['xCategory'] = $this->result['request'][$k]['sCategory'];
                $this->result['request'][$k]['xStatus'] = $this->result['request'][$k]['sStatus'];
                $this->result['request'][$k]['fOpenedVia'] = $GLOBALS['openedVia'][$this->result['request'][$k]['fOpenedVia']];
            }

            //Remove from result set
            unset($this->result['request'][$k]['sCategory']);
            unset($this->result['request'][$k]['sStatus']);
            unset($this->result['request'][$k]['iLastReadCount']);
        }
    }

    /**
     * Return labels for columns.
     */
    public function private_filter_getColumnNames()
    {
        $this->root_tag = 'labels';

        $this->result = [];
        foreach ($GLOBALS['filterCols'] as $k=>$v) {
            if (! in_array($k, ['view', 'isunread', 'takeitfilter', 'takeit', 'livelookup'])) {
                $this->result[$k] = (! empty($v['label']) ? $v['label'] : $v['label2']);
            }
        }
    }

    /**
     * Return time events for a customer.
     * @GET sUserId
     * @GET sEmail
     * @GET sFirstName
     * @GET sLastName
     * @GET fOpen
     * @GET xStatus
     * @GET xMailbox
     * @GET fOpenedVia
     * @GET xCategory
     * @GET fUrgent
     * @GET xPersonAssignedTo
     * @GET xPersonOpenedBy
     * @GET Custom#
     * @GET start_time (30 days back by default)
     * @GET end_time (right now by default)
     * @GET fRawValues
     * @GET orderBy (dtGMTDate)
     * @GET orderByDir (desc)
     */
    public function private_timetracker_search()
    {
        $this->root_tag = 'time_events';
        $data = [];

        //Setup serach criteria
        $data['start_time'] = $this->_GET('start_time', hs_strtotime('-30 day', time()));
        $data['end_time'] = $this->_GET('end_time', time());
        $data['orderBy'] = $this->_GET('orderBy', 'dtGMTDate');
        $data['orderByDir'] = $this->_GET('orderByDir', 'DESC');
        if (isset($_GET['sUserId'])) {
            $data['sUserId'] = $this->_GET('sUserId', 0);
        }
        if (isset($_GET['sEmail'])) {
            $data['sEmail'] = $this->_GET('sEmail', 0);
        }
        if (isset($_GET['sFirstName'])) {
            $data['sFirstName'] = $this->_GET('sFirstName', 0);
        }
        if (isset($_GET['sLastName'])) {
            $data['sLastName'] = $this->_GET('sLastName', 0);
        }
        if (isset($_GET['fOpen'])) {
            $data['fOpen'] = $this->_GET('fOpen', 1);
        }
        if (isset($_GET['xStatus'])) {
            $data['xStatus'] = $this->_GET('xStatus', 0);
        }
        if (isset($_GET['xMailbox'])) {
            $data['xMailbox'] = $this->_GET('xMailbox', 0);
        }
        if (isset($_GET['fOpenedVia'])) {
            $data['fOpenedVia'] = $this->_GET('fOpenedVia', 0);
        }
        if (isset($_GET['xCategory'])) {
            $data['xCategory'] = $this->_GET('xCategory', 0);
        }
        if (isset($_GET['fUrgent'])) {
            $data['fUrgent'] = $this->_GET('fUrgent', 0);
        }
        if (isset($_GET['xPersonAssignedTo'])) {
            $data['xPersonAssignedTo'] = $this->_GET('xPersonAssignedTo', 0);
        }
        if (isset($_GET['xPersonOpenedBy'])) {
            $data['xPersonOpenedBy'] = $this->_GET('xPersonOpenedBy', 0);
        }

        // Handle custom fields
        if (! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $v) {
                $custid = 'Custom'.$v['fieldID'];
                if (isset($_GET[$custid])) {
                    $data[$custid] = $this->_GET($custid, 0);
                }
            }
        }

        $rs = apiTimeTrackerSearch($data);

        if ($rs) {
            $this->result = $this->_rsToOutputArray($rs, 'event', []);

            //Adjust fields
            if (! $this->_GET('fRawValues', 0)) {
                $staff = apiGetAllUsersComplete();

                foreach ($this->result['event'] as $k=>$v) {
                    $this->result['event'][$k]['dtGMTDate'] = hs_showShortDate($this->result['event'][$k]['dtGMTDate']);
                    $this->result['event'][$k]['dtGMTDateAdded'] = hs_showDate($this->result['event'][$k]['dtGMTDateAdded']);
                    $this->result['event'][$k]['xPerson'] = $staff[$this->result['event'][$k]['xPerson']]['fullname'];
                }
            }
        }
    }

    /**
     * Return a users filters.
     */
    public function private_user_getFilters()
    {
        $this->root_tag = 'filters';
        include_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';

        $i = 0;
        $out = [];

        //Setup columns
        $cols = [];
        foreach ($GLOBALS['filterCols'] as $k=>$v) {
            if (! in_array($k, ['view', 'isunread', 'takeitfilter', 'takeit', 'livelookup'])) {
                $cols[$k] = (! empty($v['label']) ? $v['label'] : $v['label2']);
            }
        }

        //Add inbox
        $inboxCount = new hs_filter('', true);
        $inboxCount->useSystemFilter('inbox');

        $out[$i]['xFilter'] = 'inbox';
        $out[$i]['sFilterName'] = lg_inbox;
        foreach ($inboxCount->filterDef['displayColumns'] as $k=>$v) {
            $out[$i]['displayColumns'][$v] = $cols[$v];
        }
        $out[$i]['fGlobal'] = 0;
        $out[$i]['sFilterFolder'] = '';
        $out[$i]['count'] = $inboxCount->outputCountTotal();
        $out[$i]['unread'] = '';
        $out[$i]['dtGMTLastPublicUpdate'] = '';
        $i++;

        //Add myqueue
        $myCount = new hs_filter('', true);
        $myCount->useSystemFilter('myq');

        $select = 'SELECT MAX(dtGMTChange) AS lastpubupdate FROM HS_Request_History
			LEFT JOIN
				HS_Request
			ON
				HS_Request_History.xRequest = HS_Request.xRequest
			WHERE
				HS_Request.xPersonAssignedTo = '.$this->user['xPerson'].'
			AND
                HS_Request_History.fPublic = 1';

        $lastpubupdate = $GLOBALS['DB']->GetOne($select);

        $myUnread = new hs_filter('', true);
        $myUnread->useSystemFilter('myq_unread');

        $out[$i]['xFilter'] = 'myq';
        $out[$i]['sFilterName'] = lg_myq;
        foreach ($myCount->filterDef['displayColumns'] as $k=>$v) {
            $out[$i]['displayColumns'][$v] = $cols[$v];
        }
        $out[$i]['fGlobal'] = 0;
        $out[$i]['sFilterFolder'] = '';
        $out[$i]['count'] = $myCount->outputCountTotal();
        $out[$i]['unread'] = $myUnread->outputCountTotal();
        $out[$i]['dtGMTLastPublicUpdate'] = $lastpubupdate;
        $i++;

        $filters = apiGetAllFilters($this->user['xPerson'], 'all');
        foreach ($filters as $k=>$f) {
            $filCount = new hs_filter($f, true);

            $out[$i]['xFilter'] = $k;
            $out[$i]['sFilterName'] = $f['sFilterName'];
            foreach ($filCount->filterDef['displayColumns'] as $k=>$v) {
                $out[$i]['displayColumns'][$v] = $cols[$v];
            }
            $out[$i]['fGlobal'] = $f['fGlobal'];
            $out[$i]['sFilterFolder'] = $f['sFilterFolder'];
            $out[$i]['count'] = $filCount->outputCountTotal();
            $out[$i]['unread'] = '';
            $i++;
        }

        $this->result = ['filter'=>$out];
    }

    /**
     * Return this users prefs.
     */
    public function private_user_preferences()
    {
        $this->root_tag = 'preferences';
        $user = apiGetUser($this->user['xPerson']);

        $this->result = $this->_stripColsFromArray($user, ['sPasswordHash', 'tWorkspace', 'fKeyboardShortcuts']);
    }

    /**
     * Return list of active staff.
     */
    public function private_util_getActiveStaff()
    {
        $this->root_tag = 'staff';
        $staff = apiGetAllUsers();

        $this->result = $this->_rsToOutputArray($staff, 'person', ['sPasswordHash', 'tWorkspace', 'fKeyboardShortcuts']);
    }

    /**
     * Return list of all staff ever.
     */
    public function private_util_getAllStaff()
    {
        $this->root_tag = 'staff';
        $staff = apiGetAllUsersComplete();

        foreach ($staff as $id=>$person) {
            $this->result['person'][$id] = $this->_stripColsFromArray($person, ['sPasswordHash', 'tWorkspace', 'fKeyboardShortcuts']);
        }
    }

    /**
     * Return list of all staff ever.
     */
    public function private_util_getStaffPhoto()
    {
        $this->root_tag = 'photo';
        $id = $this->_GET('xPerson');

        $staffer = apiGetUser($id);

        $file = $GLOBALS['DB']->GetRow('SELECT sFilename,sFileMimeType,blobPhoto,sSeries FROM HS_Person_Photos WHERE xPersonPhotoId = ?', [$staffer['xPersonPhotoId']]);

        // Return the users custom photo if they have one or else nothing
        // This is a change from v4 where all users had images but now with the letter avatars that's not the case
        if (!empty($file['blobPhoto'])) {
            $data = $file['blobPhoto'];
        } else {
            $data = '';
        }

        $this->result['data'] = base64_encode($data);
    }

    /**
     * Return all responses in the installation.
     */
    public function private_response_listAll()
    {
        $this->root_tag = 'responses';

        $rs = apiGetAllRequestResponses(0, $this->user['xPerson'], $this->user['fUserType'], false, '');

        // Going to hide tResponseOptions for now until we decide if we want to expose that. It's complex.
        $this->result = $this->_rsToOutputArray($rs, 'response', ['tResponseOptions']);
    }

    /**
     * Return all most used responses for the user.
     */
    public function private_response_usersMostCommon()
    {
        $this->root_tag = 'responses';

        $rs = apiGetMostUsedResponses($this->user['xPerson']);

        // Going to hide tResponseOptions for now until we decide if we want to expose that. It's complex.
        $this->result = $this->_rsToOutputArray($rs, 'response', ['tResponseOptions']);
    }

    /**
     * Return a single response.
     */
    public function private_response_get()
    {
        $this->root_tag = 'response';
        $id = $this->_GET('xResponse');

        $rs = apiGetRequestResponse($id);

        // Going to hide tResponseOptions for now until we decide if we want to expose that. It's complex.
        unset($rs['tResponseOptions']);

        $this->result = $rs;
    }

    /**
     * Return details on the installation.
     */
    public function private_meta()
    {
        $this->root_tag = 'meta';
        $this->result = [
            'name' => hs_setting('cHD_ORGNAME'),
            'url' => cHOST,
            'id' => hs_setting('cHD_CUSTOMER_ID'),
        ];
    }

    /**
     * Create an address book entry.
     */
    public function private_addressbook_createContact()
    {
        $this->root_tag = 'addressbook';

        //Check requireds
        $this->_POST('sFirstName');
        $this->_POST('sLastName');
        $this->_POST('sEmail');

        if (! $this->in_error()) {
            $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/
            apiCreateABContact([
                    'sFirstName' 	=> $this->_POST('sFirstName'),
                    'sLastName' 	=> $this->_POST('sLastName'),
                    'sEmail' 		=> $this->_POST('sEmail'),
                    'sTitle' 		=> $this->_POST('sTitle', ''),
                    'sDescription' 	=> $this->_POST('sDescription', ''),
                    'fHighlight' 	=> $this->_POST('fHighlight', 0),
                ]);

            $insertid = dbLastInsertID('HS_Address_Book', 'xContact');
            $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

            $this->result = ['xContact'=>$insertid];
        }
    }

    /**
     * Create a webhook trigger through Zapier.
     */
    public function private_webhook_subscribe()
    {
        $this->_POST('target_url');
        $this->_POST('event');
        if (! $this->in_error()) {
            if ($this->_POST('event') === 'request_created') {
                $this->_POST['sTriggerName'] = 'Webhook Creation Subscription  - Created By API';
                $this->_POST['fType'] = 1;
            } else {
                $this->_POST['sTriggerName'] = 'Webhook Update Subscription - Created By API';
                $this->_POST['fType'] = 2;
            }
            $this->_POST['anyall'] = 'all';
            $this->_POST['option_log'] = '0';
            $this->_POST['conditionjdQGM5a99b3ac872dc_1'] = 'fOpen';
            $this->_POST['conditionjdQGM5a99b3ac872dc_2'] = 'is_not';
            $this->_POST['conditionjdQGM5a99b3ac872dc_3'] = '-1';
            $this->_POST['actionlyxCG5a99b3ac87986_1'] = 'webhook';
            $this->_POST['actionlyxCG5a99b3ac87986_2'] = $this->_POST('target_url');

            $trigger = new hs_trigger();
            $trigger->SetTrigger($this->_POST);

            $order = $GLOBALS['DB']->GetOne('SELECT MAX(fOrder) FROM HS_Triggers') + 1;
            $GLOBALS['DB']->Execute('INSERT INTO HS_Triggers(sTriggerName,fOrder,fDeleted,fType,tTriggerDef) VALUES (?,?,?,?,?)',
                [$trigger->name, $order, 0, $trigger->type, hs_serialize($trigger)]);
            $xTrigger = dbLastInsertID('HS_Triggers', 'xTrigger');
            $this->result = ['id'=>$xTrigger];
        }
    }

    /**
     * Delete the webhook trigger.
     */
    public function private_webhook_unsubscribe()
    {
        if (! $this->in_error()) {
            apiDeleteResource('HS_Triggers', 'xTrigger', $this->_POST('id'), 'delete');
            $this->result = $this->_POST('id');
        }
    }

    /**
     * Delete an address book entry.
     */
    public function private_addressbook_deleteContact()
    {
        $this->root_tag = 'addressbook';

        //Check requireds
        $this->_POST('xContact');

        if (! $this->in_error()) {
            apiDeleteABContact($this->_POST('xContact'));

            $this->result = ['deleted'=>true];
        }
    }

    /**
     * Get all address book contacts.
     */
    public function private_addressbook_getContacts()
    {
        $this->root_tag = 'addressbook';

        $this->result = $this->_rsToOutputArray(apiGetABContacts(), 'contact');
    }

    /**
     * FIND POSTED ATTACHMENTS AND MAP THEM TO A NORMAL FILES ARRAY WHICH THE PROCESS FUNCTION CAN USE.
     */
    public function _mapAttachments()
    {
        $files['doc'] = ['error'=>[],
                               'name'=>[],
                               'type'=>[],
                               'content-id'=>[],
                               'is_apiattach'=>[],
                               'tmp_name'=>[], ];

        //Add documents if any sent
        if (isset($_POST['tNote']) && ! empty($_POST['tNote'])) {
            for ($c = 1; $c < 10; $c++) {
                $file = 'File'.$c;
                if (! empty($_POST[$file.'_sFilename']) && ! empty($_POST[$file.'_sFileMimeType']) && ! empty($_POST[$file.'_bFileBody'])) {
                    $key = $c - 1;

                    //Write file to disk so we can use standard process for attachments
                    $tmpdir = is_writable(ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
                    $destination = $tmpdir.'/'.md5($this->_POST($file.'_sFilename', '').uniqid('helpspot_api').'.txt');
                    $ok = writeFile($destination, base64_decode($this->_POST($file.'_bFileBody', '')));

                    if ($ok) {
                        $files['doc']['tmp_name'][$key] = $destination;
                        $files['doc']['name'][$key] = $this->_POST($file.'_sFilename', '');
                        $files['doc']['type'][$key] = $this->_POST($file.'_sFileMimeType', '');
                        $files['doc']['content-id'][$key] = '';
                        $files['doc']['error'][$key] = 0;
                        $files['doc']['is_apiattach'][$key] = true; //needed for clean up of file written to disk
                    } else {
                        errorLog('Could not write API attached file to disk', 'API', __FILE__, __LINE__);
                    }
                }
            }
        }

        return $files;
    }
}
