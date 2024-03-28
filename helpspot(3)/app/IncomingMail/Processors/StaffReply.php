<?php

namespace HS\IncomingMail\Processors;

use HS\IncomingMail\Message;
use Illuminate\Support\Str;

class StaffReply
{
    /**
     * @var Message
     */
    private $message;

    private $msgRequestId;

    private $msgPerson;

    public function __construct(Message $message, $msgRequestId, $msgPerson)
    {
        $this->message = $message;
        $this->msgRequestId = $msgRequestId;
        $this->msgPerson = $msgPerson;
    }

    public function isNotification()
    {
        if (! $this->msgPerson) {
            return false;
        }
        $hsNotification = '<hs-notification-'.md5(hs_setting('cHD_CUSTOMER_ID').$this->msgRequestId.strtolower($this->msgPerson['sEmail']));
        if (hs_setting('cSTAFFREPLY_AS_PUBLIC') && $this->msgPerson['xPerson'] != 0 && Str::startsWith(trim($this->message->getHeaderByKey('in-reply-to')), $hsNotification)) {
            return userIsActive($this->msgPerson); // make sure they are an active staff.
        }
        return false;
    }

    public function save($origReq, $logger)
    {
        $GLOBALS['user'] = $this->msgPerson; // set the staff as the global user for creating the request.
        $post = [];
        $post['reqid'] = $this->msgRequestId;
        $post['ignore_category'] = true;
        $post['skipCustomChecks'] = true;
        $post['fPublic'] = 1;
        $post['fOpen'] = 1; // always reopen it.
        $post['sub_update'] = true;  // always an update, not an update and close
        $post['xPerson'] = $this->msgPerson['xPerson'];
        $post['tBody'] = utf8_trim($this->message->getBody());

        //keep data the same between original and this update
        $orig = apiGetRequest($this->msgRequestId);
        $post = array_merge($orig, $post);

        //set send from
        $xPersonAssignedTo = ($origReq['xPersonAssignedTo'] == 0) ? $this->msgPerson : $origReq['xPersonAssignedTo'];
        $mailbox = apiGetMailbox($orig['xMailboxToSendFrom']);
        $em[0] = $mailbox['sReplyName'] ? replyNameReplace($mailbox['sReplyName'], $xPersonAssignedTo) : hs_setting('cHD_NOTIFICATIONEMAILNAME');
        $em[1] = $mailbox['sReplyEmail'] ? $mailbox['sReplyEmail'] : hs_setting('cHD_NOTIFICATIONEMAILACCT');
        $em[2] = $mailbox['xMailbox'] ? $mailbox['xMailbox'] : 0;

        $post['emailfrom'] = implode('*', $em);

        //override orig reqs dt to modify dt
        $post['dtGMTOpened'] = date('U');

        //assign the request to this user
        if ($origReq['xPersonAssignedTo'] == 0) {
            $post['xPersonAssignedTo'] = $this->msgPerson;
        }

        //Set CC/BCC
        $email_groups = getEmailGroups($orig);
        if (! empty($email_groups['last_cc'])) {
            $post['emailccgroup'] = $email_groups['last_cc'];
        }

        if (! empty($email_groups['last_bcc'])) {
            $post['emailbccgroup'] = $email_groups['last_bcc'];
        }

        //set html notes
        $post['fNoteIsHTML'] = ($this->message->isHtml() ? 1 : 0);

        $files = [];
        $result = apiProcessRequest($this->msgRequestId, $post, $files, __FILE__, __LINE__);

        // Show any errors
        if (isset($result['fb'])) {
            $logger->display('Staff Email Parsed');
        } elseif (isset($result['errorBoxText'])) {
            $logger->display('ERROR: '.$result['errorBoxText']);
        }

        return $result;
    }
}
