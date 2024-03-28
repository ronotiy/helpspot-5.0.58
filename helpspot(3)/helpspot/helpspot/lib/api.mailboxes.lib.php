<?php

// SECURITY: Don't allow direct calls
use HS\IncomingMail\Mailboxes\Imap;

if (! defined('cBASEPATH')) {
    die();
}

/******************************************
 * GET ALL MAILBOXES
 *****************************************
 * @param $showdeleted
 * @param $sortby
 */
function apiGetAllMailboxes($showdeleted, $sortby)
{
    $sortby = trim($sortby) != '' ? $sortby : 'sReplyName ASC';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);

    return $GLOBALS['DB']->Execute('SELECT HS_Mailboxes.*, '.dbConcat('@', 'HS_Mailboxes.sUsername', 'HS_Mailboxes.sHostname').' as boxname
                                       FROM HS_Mailboxes
                                       WHERE HS_Mailboxes.fDeleted = ?
                                       ORDER BY '.$sortby.', HS_Mailboxes.sUsername, HS_Mailboxes.sHostname', [$showdeleted]);
}

/******************************************
GET MAILBOX
******************************************/
function apiGetMailbox($mbid)
{
    $mbid = isset($mbid) && is_numeric($mbid) ? $mbid : '';

    return $GLOBALS['DB']->GetRow('SELECT HS_Mailboxes.*, '.dbConcat('@', 'HS_Mailboxes.sUsername', 'HS_Mailboxes.sHostname').' as boxname
                                    FROM HS_Mailboxes WHERE HS_Mailboxes.xMailbox = ?', [$mbid]);
}

/******************************************
ADD/EDIT CATEGORY
******************************************/
function apiAddEditMailbox($box, $f, $l)
{
    // initialize
    $errors = [];
    $box['mode'] = isset($box['mode']) ? $box['mode'] : 'add';
    $box['resourceid'] = hs_numeric($box, 'resourceid') ? $box['resourceid'] : 0;

    $box['sMailbox'] = isset($box['sMailbox']) ? $box['sMailbox'] : 'INBOX';
    $box['xCategory'] = hs_numeric($box, 'xCategory') ? $box['xCategory'] : 0;
    $box['sUsername'] = isset($box['sUsername']) ? $box['sUsername'] : '';
    $box['sHostname'] = isset($box['sHostname']) ? $box['sHostname'] : '';
    $box['sPassword'] = isset($box['sPassword']) ? $box['sPassword'] : '';
    $box['sPasswordConfirm'] = isset($box['sPasswordConfirm']) ? $box['sPasswordConfirm'] : '';
    $box['sPort'] = isset($box['sPort']) ? $box['sPort'] : '110';
    $box['sType'] = isset($box['sType']) ? $box['sType'] : '';
    $box['sSecurity'] = isset($box['sSecurity']) ? $box['sSecurity'] : '';
    $box['fAutoResponse'] = hs_numeric($box, 'fAutoResponse') ? $box['fAutoResponse'] : 1;
    $box['sReplyName'] = isset($box['sReplyName']) ? $box['sReplyName'] : '';
    $box['sReplyEmail'] = isset($box['sReplyEmail']) ? $box['sReplyEmail'] : '';
    $box['tAutoResponse'] = isset($box['tAutoResponse']) ? $box['tAutoResponse'] : lg_admin_mailboxes_msgdefault;
    $box['tAutoResponse_html'] = isset($box['tAutoResponse_html']) ? $box['tAutoResponse_html'] : lg_admin_mailboxes_msgdefault_html;
    $box['sSMTPSettings'] = isset($box['sSMTPSettings']) ? $box['sSMTPSettings'] : '';
    $box['fArchive'] = hs_numeric($box, 'fArchive') ? $box['fArchive'] : 1;

    // Error checks
    if (hs_empty($box['sMailbox'])) {
        $errors['sMailbox'] = lg_admin_mailboxes_er_mailbox;
    }
    if (hs_empty($box['sUsername'])) {
        $errors['sUsername'] = lg_admin_mailboxes_er_username;
    }
    if (hs_empty($box['sHostname'])) {
        $errors['sHostname'] = lg_admin_mailboxes_er_hostname;
    }
    if ($box['mode'] == 'add' and hs_empty($box['sPassword'])) {
        $errors['sPassword'] = lg_admin_mailboxes_er_pass;
    }
    if ($box['mode'] == 'edit' and ! hs_empty($box['sPassword']) and $box['sPassword'] != $box['sPasswordConfirm']) {
        $errors['sPassword'] = lg_admin_mailboxes_er_pass_confirm;
    }

    if (hs_empty($box['sType'])) {
        $errors['sType'] = lg_admin_mailboxes_er_type;
    }
    if (hs_empty($box['sPort'])) {
        $errors['sPort'] = lg_admin_mailboxes_er_port;
    }
    if (hs_empty($box['sReplyEmail']) || ! validateEmail($box['sReplyEmail'])) {
        $errors['sReplyEmail'] = lg_admin_mailboxes_er_autoemail;
    }
    if (hs_empty($box['sReplyName'])) {
        $errors['sReplyName'] = lg_admin_mailboxes_er_autoname;
    }
    //If using the autoresponder than make sure we have all the info filled in
    if ($box['fAutoResponse'] == 1) {
        if (hs_empty($box['tAutoResponse'])) {
            $errors['tAutoResponse'] = lg_admin_mailboxes_er_autoresp;
        }
        if (hs_empty($box['tAutoResponse_html'])) {
            $errors['tAutoResponse_html'] = lg_admin_mailboxes_er_autoresp;
        }
    }

    //Don't allow a mailbox to be added which has a reply to of a system user
    $users = apiGetAllUsersComplete();
    foreach ($users as $k=>$user) {
        if ($user['sEmail'] == $box['sReplyEmail']) {
            $errors['sReplyEmail'] = lg_admin_mailboxes_er_autoemail2;
            break;
        }
    }

    if (empty($errors)) {
        if ($box['mode'] == 'add') {
            $mailbox = new \HS\Mailbox();
            $mailbox->sPassword = encrypt($box['sPassword']);
        } else {
            $mailbox = \HS\Mailbox::find($box['resourceid']);
            $mailbox->sPassword = (! hs_empty($box['sPassword']) ? encrypt($box['sPassword']) : $GLOBALS['DB']->GetOne('SELECT sPassword FROM HS_Mailboxes WHERE xMailbox = ?', [$box['resourceid']]));
        }

        $mailbox->sMailbox = $box['sMailbox'];
        $mailbox->sHostname = $box['sHostname'];
        $mailbox->sUsername = $box['sUsername'];
        $mailbox->sPort = $box['sPort'];
        $mailbox->sType = $box['sType'];
        $mailbox->sSecurity = $box['sSecurity'];
        $mailbox->xCategory = $box['xCategory'];
        $mailbox->fAutoResponse = $box['fAutoResponse'];
        $mailbox->sReplyName = $box['sReplyName'];
        $mailbox->sReplyEmail = $box['sReplyEmail'];
        $mailbox->tAutoResponse = $box['tAutoResponse'];
        $mailbox->tAutoResponse_html = $box['tAutoResponse_html'];
        $mailbox->sSMTPSettings = $box['sSMTPSettings'];

        // create a new folder? Only if fArchive and not an old style account.
        if ($box['fArchive'] == 1 && ! in_array($box['sType'], ['pop3', 'pop3s', 'nntp', 'nntps'])) {
            $imap = new Imap($mailbox);
            $mailbox->fArchive = $imap->createMailbox();
        } else { // for when turning off archive mode
            $mailbox->fArchive = 0;
        }

        $boxRes = $mailbox->save();

        if ($box['mode'] == 'add') {
            return dbLastInsertID('HS_Mailboxes', 'xMailbox');
        } else {
            return $box['resourceid'];
        }
    } else {
        return $errors;
    }
}
