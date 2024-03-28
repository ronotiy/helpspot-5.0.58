<?php

namespace HS\IncomingMail\Mailboxes;

use Net_URL;
use Exception;
use \HS\Mailbox as MailboxModel;
use Illuminate\Support\Facades\Log;

class Imap implements Mailbox
{
    private $folderName = 'helpspot_archive_folder';

    private $conn;

    protected $count;

    protected $msg_raw_header;

    /**
     * @var mixed
     */
    private $connection_uri;

    /**
     * @var MailboxModel
     */
    private $mailbox;

    public function __construct(MailboxModel $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function connect()
    {
        $connection = $this->buildConnectionUrl($this->mailbox->toArray());
        $this->connection_uri = $connection['uri'];
        $this->conn = imap_open($connection['uri'], urldecode($connection['user']), urldecode($connection['pass']), null);

        if (false === $this->conn) {
            throw new Exception('Cannot connect '.implode(';', array_unique(imap_errors())));
        }

        return true;
    }

    /**
     * Attempt to create a helpspot_archive_folder
     * @return bool
     */
    public function createMailbox()
    {
        try {
            $this->connect();
        } catch (Exception $e) {
            return false;
        }

        try {
            $newMailbox = str_replace($this->mailbox->sMailbox, $this->folderName, $this->connection_uri);
            $status = imap_status($this->conn, $newMailbox, SA_ALL);
            if (! $status) { // If status is false, then the folder doesn't exist.
                try {
                    return imap_createmailbox($this->conn, imap_utf7_encode($newMailbox));
                } catch (Exception $e) {
                    Log::error($e, [
                        'message' => "Can't create mailbox for #".$this->mailbox->getKey(),
                    ]);
                    return false;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Move a message to the helpspot_archive_folder
     *
     * @param $mid
     * @return bool
     */
    public function archive($mid)
    {
        return imap_mail_move($this->conn, $mid, $this->folderName);
    }

    /**
     * Get the number of messages.
     * @return int
     */
    public function messageCount()
    {
        $this->count = @imap_num_msg($this->conn);
        return $this->count;
    }

    /**
     * Get a list of all messages in the inbox.
     * @return array
     */
    public function getMessages()
    {
        return range(1, $this->count);
    }

    /**
     * Get the uid based on the message number.
     *
     * @param $num
     * @return int
     */
    public function getUid($num)
    {
        return imap_uid($this->conn, $num);
    }

    /**
     * Get a single message.
     *
     * @param $uid
     * @return string
     */
    public function getMessage($uid)
    {
        return $this->imapFetch($this->conn, $uid);
    }

    /**
     * Delete this message (doesn't delete from mailbox stack, this deletes from imap server).
     *
     * @param $id
     * @return bool|mixed
     */
    public function delete($id)
    {
        return imap_delete($this->conn, $id);
    }

    /**
     * Expunge all messages marked for deletion on the server.
     *
     * @return    int
     */
    public function expunge()
    {
        return imap_expunge($this->conn);
    }

    /**
     * @param $conn
     * @param $id - The Message UID
     * @return string
     */
    protected function imapFetch($conn, $id)
    {
        $this->msg_raw_header = imap_fetchheader($conn, $id);
        // Handle the non-standard Multipart/Voice-Message type by treating is as a normal mixed message.
        $this->msg_raw_header = str_replace(['Multipart/Voice-Message', 'multipart/voice-message'], ['multipart/mixed', 'multipart/mixed'], $this->msg_raw_header);

        return $this->msg_raw_header."\n".imap_body($conn, $id);
    }

    /**
     * Build the connection uri.
     *
     * @param $box
     * @return array|bool
     */
    public function buildConnectionUrl($box)
    {
        if (! class_exists('Net_URL') && ! @include_once(cBASEPATH.'/helpspot/pear/Net/URL.php')) {
            $this->conn = false;
            return false;
        }

        $uri = trim($box['sType']).'://'.urlencode(trim($box['sUsername'])).':'.urlencode(decrypt(trim($box['sPassword']))).'@'.trim($box['sHostname']).':'.trim($box['sPort']).'/'.trim($box['sMailbox']);

        if (trim($box['sSecurity']) != '') {
            $uri = $uri.'#'.$box['sSecurity'];
        }

        $opt = null;

        $net_url = new Net_URL($uri);

        $uri = '{'.$net_url->host;

        if (! empty($net_url->port)) {
            $uri .= ':'.$net_url->port;
        }

        $secure = ('tls' == substr($net_url->anchor, 0, 3)) ? '' : '/ssl';

        $uri .= ('s' == (substr($net_url->protocol, -1))) ? '/'.substr($net_url->protocol, 0, 4).$secure : '/'.$net_url->protocol;

        if (! empty($net_url->anchor)) {
            $uri .= '/'.$net_url->anchor;
        }

        $uri .= '}';

        // Trim off the leading slash '/'
        if (! empty($net_url->path)) {
            $uri .= substr($net_url->path, 1, (utf8_strlen($net_url->path) - 1));
        }

        return [
            'uri' => $uri,
            'user' => $net_url->user,
            'pass' => $net_url->pass,
        ];
    }
}
