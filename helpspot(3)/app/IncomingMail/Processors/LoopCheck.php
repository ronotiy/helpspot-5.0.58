<?php

namespace HS\IncomingMail\Processors;

use HS\IncomingMail\Message;

class LoopCheck
{
    /**
     *
     * @param Message $message
     * @return bool
     */
    public function newMessageInLoop(Message $message)
    {
        $hash = md5(utf8_trim($message->getParsedBody()));
        $check = $GLOBALS['DB']->Execute( 'SELECT xRequest FROM HS_Request
										   WHERE fOpenedVia = 1 AND dtGMTOpened > ? AND sTitle = ? AND sRequestHash = ?',
										   [time()-hs_setting('cHD_EMAILLOOP_TIME'), $message->getSubject(lg_no_subject), $hash] );
        if (is_object($check) && $check->RecordCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $email
     * @return bool
     */
    public function shouldAutoRespond($email)
    {
        $check = $GLOBALS['DB']->GetOne('SELECT COUNT(*) AS loopct FROM HS_Request WHERE sEmail = ? AND dtGMTOpened > ?', [$email, time()-hs_setting('cHD_EMAILLOOP_TIME')]);
        if ($check > 0) {
            return true;
        }
        return false;
    }

    /**
     * Check that the same message hasn't been sent to this request before over the last X time period. If it has then delete it without importing it.
     *
     * @param Message $message
     * @param $msgRequestId
     * @param int $time The time() the update request started coming in.
     * @return bool
     */
    public function updateInLoop(Message $message, $msgRequestId, $time)
    {
        //Do an MD5 hash of the message which is used to find duplicate emails in the loop checks farther down
        $msgMessageMD5Hash = md5(trim($message->getParsedBody()));

        //Limit this to just one request over a certain time span since it is possible for someone to send the same message in and really want to do it in different requests or at different times
        $loopCheck = $GLOBALS['DB']->GetOne('SELECT COUNT(*) AS loopct FROM HS_Request_History WHERE
            sRequestHistoryHash <> ?
            AND sRequestHistoryHash = ?
            AND xRequest = ?
            AND dtGMTChange > ?', ['', $msgMessageMD5Hash, $msgRequestId, $time-hs_setting('cHD_EMAIL_LOOPCHECK_TIME')]);

        if ($loopCheck > 0) {
            return true;
        }

        return false;
    }
}
