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
 * This class provides an extension to Mail_IMAP that adds debugging
 * utilities for the base IMAP.php class. The debugging functionality
 * provided by this class is currently accessed by supplying various
 * $_GET method arguments.
 *
 * @author       Richard York <rich_y@php.net>
 * @category     Mail
 * @license      BSD
 * @version      2.0.0alpha1 RC 4
 * @copyright    (c) Copyright 2004, Richard York, All Rights Reserved.
 * @since        PHP 4.2.0
 * @since        C-Client 2001
 * @tutorial     http://www.smilingsouls.net
 */
class Mail_IMAP_Debug extends Mail_IMAP
{
    public function Mail_IMAP_Debug($connection = null, $get_info = true)
    {
        $this->Mail_IMAP($connection, $get_info);

        if (isset($_GET['dump_mid'])) {
            $this->debug($_GET['dump_mid']);
        } else {
            $this->error->push(MAIL_IMAP_ERROR, 'error', ['method' => 'Mail_IMAP_Debug', 'error_string' => 'No mid was specified for debugging.']);
        }
    }

    /**
     * Dumps various information about a message for debugging. Specify $_GET
     * variables to view information.
     *
     * Calling on the debugger exits script execution after debugging operations
     * have been completed.
     *
     * @param    int  $mid         $mid to debug
     * @return   void
     */
    public function debug($mid = 0)
    {
        $this->_declareParts($mid);

        if (isset($_GET['dump_mb_info'])) {
            $this->dump($this->mailboxInfo);
        }
        if (isset($_GET['dump_cid'])) {
            $this->dump($this->msg[$mid]['in']['cid']);
        }
        if (isset($_GET['dump_related'])) {
            $this->dump($this->getRelatedParts($mid, $_GET['dump_related']));
        }
        if (isset($_GET['dump_msg']) && isset($_GET['dump_pid'])) {
            $this->getParts($mid, $_GET['dump_pid']);
            $this->dump($this->msg);
        }
        if (isset($_GET['dump_pid'])) {
            $this->dump($this->structure[$mid]['pid']);
        }
        if (isset($_GET['dump_ftype'])) {
            $this->dump($this->structure[$mid]['ftype']);
        }
        if (isset($_GET['dump_structure'])) {
            $this->dump($this->structure[$mid]['obj']);
        }
        if (isset($_GET['test_pid'])) {
            echo imap_fetchbody($this->mailbox, $mid, $_GET['test_pid'], null);
        }
        if (isset($_GET['dump_mb_list'])) {
            $this->dump($this->getMailboxes());
        }
        if (isset($_GET['dump_headers'])) {
            $this->dump($this->getHeaders($mid, $_GET['dump_headers'], true));
        }
        if ($this->error->hasErrors()) {
            $this->dump($this->error->getErrors(true));
        }

        // Skip everything else in debug mode
        exit;
    }

    /**
     * Calls on var_dump and outputs with HTML <pre> tags.
     *
     * @param    mixed  $thing         $thing to dump.
     * @return   void
     */
    public function dump(&$thing)
    {
        echo "<pre style='display: block; font-family: monospace; white-space: pre;'>\n";
        var_dump($thing);
        echo "</pre>\n";
    }
}
