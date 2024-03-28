<?php

//\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
//\\\       \\\\\\\\|                                                      \\
//\\\ @@    @@\\\\\\| Mail_IMAP                                            \\
//\\ @@@@  @@@@\\\\\|______________________________________________________\\
//\\\@@@@| @@@@\\\\\|                                                      \\
//\\\ @@ |\\@@\\\\\\|(c) Copyright 2004 Richard York, All rights Reserved  \\
//\\\\  ||   \\\\\\\|______________________________________________________\\
//\\\\  \\_   \\\\\\|Redistribution and use in source and binary forms,    \\
//\\\\\        \\\\\|with or without modification, are permitted provided  \\
//\\\\\  ----  \@@@@|that the following conditions are met:                \\
//@@@@@\       \@@@@|                                                      \\
//@@@@@@\     \@@@@@| o Redistributions of source code must retain the     \\
//\\\\\\\\\\\\\\\\\\|  above copyright notice, this list of conditions and \\
//    the following disclaimer.                                            \\
//  o Redistributions in binary form must reproduce the above copyright    \\
//    notice, this list of conditions and the following disclaimer in the  \\
//    documentation and/or other materials provided with the distribution. \\
//  o The names of the authors may not be used to endorse or promote       \\
//    products derived from this software without specific prior written   \\
//    permission.                                                          \\
//                                                                         \\
//  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS    \\
//  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT      \\
//  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR  \\
//  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT   \\
//  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,  \\
//  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT       \\
//  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,  \\
//  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY  \\
//  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT    \\
//  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE  \\
//  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.   \\
//\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\

/**
 * This class provides an extension to Mail_IMAP that adds mailbox management
 * features. These features include the ability to create/rename/delete
 * (sub)mailboxes on the server, as well as the ability to move/copy mail
 * from one folder to another, and finally the ability to import messages
 * into the server.
 *
 * @author       Richard York <rich_y@php.net>
 * @category     Mail
 * @license      BSD
 * @version      2.0.0alpha1RC4
 * @copyright    (c) Copyright 2004, Richard York, All Rights Reserved.
 * @since        PHP 4.2.0
 * @since        C-Client 2001
 * @tutorial     http://www.spicypeanut.net
 *
 * @todo add simple message filter function
 */
class Mail_IMAP_ManageMB extends Mail_IMAP
{
    public function Mail_IMAP_ManageMB($connection, $get_info = true)
    {
        $this->Mail_IMAP($connection, $get_info);
    }

    /**
     * This method creates, renames and deletes mailboxes from the server.
     *
     * @param    string $action
     *   One of create|rename|delete, this tells the method what you want to
     *    do with a mailbox.
     * @param    string $mb_name
     *   The name of the mailbox to create, delete or rename.
     * @param    string $mb_rename
     *   (optional) New name for the mailbox, if it is being renamed.
     *
     * @return   bool
     * @see      imap_createmailbox
     * @see      imap_renamemailbox
     * @see      imap_deletemailbox
     */
    public function manageMB($action, $mb_name, $mb_rename = null)
    {
        switch ($action) {
            case 'create':
            {
                if (@imap_createmailbox($this->mailbox, imap_utf7_encode($this->mailboxInfo['host'].'INBOX.'.$mb_name))) {
                    $ret = true;
                } else {
                    $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'Unable to create MB: '.$mb_name);
                    $ret = false;
                }

                break;
            }
            case 'rename':
            {
                if (empty($rename)) {
                    $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'No mailbox provided to rename.');
                }
                if (@imap_renamemailbox($this->mailbox, $this->mailboxInfo['host'].'INBOX.'.$mb_rename, $this->mailboxInfo['host'].'INBOX.'.$mb_name)) {
                    $ret = true;
                } else {
                    $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'Unable to rename MB: '.$mb_name);
                    $ret = false;
                }

                break;
            }
            case 'delete':
            {
                if (@imap_deletemailbox($this->mailbox, $this->mailboxInfo['host'].'INBOX.'.$mb_name)) {
                    $ret = true;
                } else {
                    $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'Unable to delete MB: '.$mb_name);
                    $ret = false;
                }

                break;
            }
            default:
            {
                $this->error->push(MAIL_IMAP_ERROR_INVALID_ACTION, 'error', ['action' => $action, 'arg' => '$action']);
                $ret = false;
            }

            return $ret;
        }
    }

    /**
     * This method manages the mail inside of a mailbox and allows mail to be
     * copied or moved from the mailbox that the user is connected to to the
     * specified mailbox.
     *
     * @param    string $action
     *   One of copy|move if copy, a copy of the message will remain in the
     *   current mailbox. If move the message is permenently moved to the
     *   specified mailbox.
     * @param    array $msg_list
     *   An array of messages to move, see (@link imap_mail_copy} or {@link imap_mail_move}
     *   for more options. The array is imploded into a comma separated list, therefore
     *   other options such as 1:10 syntax or * syntax may be specified in the array.
     * @param    string $dest_mb
     *   The destination mailbox, such as 'INBOX.Drafts' or 'INBOX.Sent'
     *
     * @return bool
     * @see imap_mail_copy
     * @see imap_mail_move
     */
    public function manageMail($action, $msg_list, $dest_mb)
    {
        if (is_array($msg_list)) {
            $msg_list = implode($msg_list, ',');
        } else {
            $this->error->push(MAIL_IMAP_ERROR_ARGUMENT_REQUIRES_ARRAY, 'error', ['arg' => '$msg_list']);

            return false;
        }
        switch ($action) {
            case 'copy':
            {
                $opt = (isset($this->option['mail_move'])) ? $this->option['mail_move'] : null;

                break;
            }
            case 'move':
            {
                $opt = (isset($this->option['mail_copy'])) ? $this->option['mail_copy'] : null;

                break;
            }
            default:
            {
                $this->error->push(MAIL_IMAP_ERROR_INVALID_ACTION, 'error', ['action' => $action, 'arg' => '$action']);

                return false;
            }
        }

        $action = 'imap_mail_'.$action;

        if (@$action($this->mailbox, $msg_list, $dest_mb, $opt)) {
            $ret = true;
        } else {
            $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'Unable to copy or move messages, imap_mail_'.$action.' failed!');
            $ret = false;
        }

        return $ret;
    }

    /**
     * This method provides the functionality to import MIME messages into the server
     * using the {@link imap_append} method.
     *
     * @param string $dest_mb
     *   The destination mailbox where the messages will be imported to.
     * @param array $messages
     *   An array of MIME messages to import.
     *
     * @return bool
     * @see imap_append
     */
    public function importMail($dest_mb, $messages)
    {
        if (is_array($messages)) {
            $opt = (isset($this->option['append'])) ? $this->option['append'] : null;

            foreach ($messages as $msg) {
                if (! @imap_append($this->mailbox, $this->mailboxInfo['host'].$dest_mb, $msg, $opt)) {
                    $this->error->push(MAIL_IMAP_ERROR, 'error', null, 'Unable to import message, imap_append failed!');
                    $ret = false;
                }
            }

            if (! isset($ret)) {
                $ret = true;
            }
        } else {
            $this->error->push(MAIL_IMAP_ERROR_ARGUMENT_REQUIRES_ARRAY, 'error', ['arg' => '$messages']);
            $ret = false;
        }

        return $ret;
    }
}
