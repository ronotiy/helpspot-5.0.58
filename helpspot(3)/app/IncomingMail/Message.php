<?php

namespace HS\IncomingMail;

use HS\IncomingMail\Processors\ReplyAbove;

class Message
{
    public $headers;

    protected $body;

    protected $subject;

    protected $bodyText;

    protected $fromName;

    protected $bodyHtml;

    protected $fromEmail;

    protected $headersRaw;

    protected $msg_is_html;

    protected $attachments = [];

    protected $inline_attachments = [];

    /**
     * Append text to the body property.
     *
     * @param $text
     */
    public function setBodyText($text)
    {
        $this->bodyText .= $text;
    }

    /**
     * Append text to the HTML body property.
     *
     * @param $text
     */
    public function setBodyHtml($text)
    {
        $this->bodyHtml .= $text;
    }

    /**
     * Set the inline attachments.
     *
     * @param array $attachment
     */
    public function setInlineAttachment(array $attachment)
    {
        $ct = count($this->inline_attachments) + 1;
        $this->inline_attachments[$ct]['name'] = $attachment['name'];
        $this->inline_attachments[$ct]['mimetype'] = $attachment['mimetype'];
        $this->inline_attachments[$ct]['body'] = $attachment['body'];
        $this->inline_attachments[$ct]['content-id'] = $attachment['content-id'];
        $this->inline_attachments[$ct]['transaction'] = $attachment['transaction'] ?? '';
    }

    /**
     * Set the email attachments.
     *
     * @param array $attachment
     */
    public function setAttachment(array $attachment)
    {
        $ct = count($this->attachments) + 1;
        $this->attachments[$ct]['name'] = $attachment['name'];
        $this->attachments[$ct]['mimetype'] = $attachment['mimetype'];
        $this->attachments[$ct]['body'] = $attachment['body'];
        $this->attachments[$ct]['content-id'] = $attachment['content-id'];
        $this->attachments[$ct]['transaction'] = $attachment['transaction'] ?? '';
    }

    /**
     * See #822
     * If the email is not multipart, we may need to convert the subject line
     * as it may not be encoded in ascii.
     * Multipart emails have a content-type header per part, however non-multipart do not.
     * We'll detect if the main content-type is NOT Multipart. If not, we'll attempt to convert
     * the header to utf8. Note that content-type headers may not include a charset (monsters!).
     *
     * @param $headers
     * @return bool
     */
    public function shouldConvertSubject($headers)
    {
        if (isset($headers['content-type'])) {
            if (! str_contains(strtolower($headers['content-type']), 'multipart')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert the charset of the subject.
     *
     * @param $msg
     * @return mixed
     */
    public function charsetConvertSubject($msg)
    {
        $originalSubject = $msg->headers['subject'];
        if (isset($msg->ctype_parameters['charset']) && ! empty($msg->ctype_parameters['charset'])) {
            $subject = hs_charset_convert($msg->ctype_parameters['charset'], 'UTF-8', $msg->headers['subject']);
        } else {
            $subject = hs_check_charset_and_convert($msg->headers['subject'], 'UTF-8');
        }

        // If conversion stripped the subject line
        if (! empty($originalSubject) && empty($subject)) {
            $subject = $originalSubject;
        }

        return $subject;
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        // weird edge case with an eml with multiple subjects. We are just
        // going to take the last because we don't have much of a choice.
        if (is_array($subject)) {
            $subject = last($subject);
        }

        $this->subject = $subject;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSubjectAndConvert()
    {
        // Bug #822
        if ($this->shouldConvertSubject($this->headers)) {
            $this->headers['subject'] = $this->charsetConvertSubject($this);
        }

        // weird edge case with an eml with multiple subjects. We are just
        // going to take the last because we don't have much of a choice.
        if (isset($this->headers['subject']) and is_array($this->headers['subject'])) {
            $this->headers['subject'] = last($this->headers['subject']);
        }

        $this->subject = hs_charset_emailheader($this->headers['subject']);

        return $this;
    }

    /**
     * Get the subject.
     *
     * @param $noSubjectMsg
     * @return string
     */
    public function getSubject($noSubjectMsg = '')
    {
        if (empty($this->subject)) {
            return $noSubjectMsg;
        }

        return trim($this->subject);
    }

    /**
     * @return array|bool
     */
    public function getFromName()
    {
        $name = false;
        if ($from = $this->getParsedEmailHeaderByKey('forward-from')) {
            $name = $from['personal'];
        } elseif ($replyTo = $this->getParsedEmailHeaderByKey('reply-to')) {
            $name = $replyTo['personal'];
        } elseif ($from = $this->getParsedEmailHeaderByKey('from')) {
            $name = $from['personal'];
        }

        if (! $name) {
            // let's use the first part of the email.
            $email = explode("@", $this->getFromEmail());
            $parsed = parseName($email[0]);
            return implode(" ", $parsed);
        }

        return $name;
    }

    /**
     * @return bool|string
     */
    public function getFromEmail()
    {
        if ($from = $this->getParsedEmailHeaderByKey('forward-from') and isset($from['mailbox'])) {
            return trim($from['mailbox'].'@'.$from['host']);
        } elseif ($replyTo = $this->getParsedEmailHeaderByKey('reply-to') and isset($replyTo['mailbox'])) {
            return trim($replyTo['mailbox'].'@'.$replyTo['host']);
        } elseif ($from = $this->getParsedEmailHeaderByKey('from') and isset($from['mailbox'])) {
            return trim($from['mailbox'].'@'.$from['host']);
        }

        return false;
    }

    /**
     * Get an array of email addresses
     * `['john@example.com', 'jane@example.org'];`.
     *
     * @return mixed
     */
    public function ccEmails()
    {
        if (! $ccs = $this->getHeaderByKey('cc')) {
            return false;
        }

        return array_map(function ($item) {
            return $item->mailbox.'@'.$item->host;
        }, imap_rfc822_parse_adrlist($ccs, null));
    }

    /**
     * @param $key
     * @return array|bool
     */
    public function getParsedEmailHeaderByKey($key)
    {
        if (isset($this->headers[$key])) {
            return hs_parse_email_header($this->headers[$key]);
        }

        return false;
    }

    /**
     * @param $key
     * @return bool
     */
    public function getHeaderByKey($key)
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        } elseif (isset($this->headers[strtolower($key)])) {
            return $this->headers[strtolower($key)];
        }

        return false;
    }

    /**
     * Get the body of the email.
     *
     * @return string
     */
    public function getBody()
    {
        // First parse the reply above.
        $this->replyAbove();
        // Next clean it which fixes any invalid html from parsing the reply above
        $body = $this->cleanMessage();
        // Finally clean outlook junk
        $this->body = $this->cleanOutlook($body);

        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getParsedBody()
    {
        return $this->body;
    }

    /**
     * @param $body
     * @param string $type
     * @return mixed
     */
    public function setParsedBody($body, $type = 'html')
    {
        $this->body = $body;

        if ($type == 'html') {
            return $this->bodyHtml = $body;
        }

        return $this->bodyText = $body;
    }

    /**
     * @return mixed
     */
    public function getTextBody()
    {
        return $this->bodyText;
    }

    /**
     * @return mixed
     */
    public function getHtmlBody()
    {
        return $this->bodyHtml;
    }

    /**
     * Is this an internal HelpSpot message? These are ones where in weird
     * circumstances HelpSpot can email itself and cause looping.
     *
     * @param $orgName
     * @return bool
     */
    public function isHelpSpotMessage($orgName)
    {
        if (isset($this->headers['x-helpspot']) && ($this->headers['x-helpspot'] == '<'.md5($orgName).'>' || $this->headers['x-helpspot'] == md5($orgName))) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function isHtml()
    {
        return $this->msg_is_html;
    }

    /**
     * Clean the message.
     */
    public function cleanMessage()
    {
        if ((hs_setting('cHD_STRIPHTML') == 2 && ! empty($this->bodyHtml)) || empty($this->bodyText)) {
            $cleaner = app('html.cleaner');
            $body = $cleaner->clean($this->bodyHtml, false);

            //Check if the message is empty (or stripped to empty like only has a div in it)
            $msg_is_empty = $cleaner->wasContentStripped($body);

            //This is a fallback. If the HTML is soo bad that the purify system strips all the HTML and leaves it empty then use the text version or a stripped version of the HTML.
            if ($msg_is_empty && empty($this->bodyText)) { //if no text version we must escape the HTML version
                $body = nl2br(trim(strip_tags($this->bodyHtml)));
                $this->msg_is_html = 1;
            } elseif ($msg_is_empty) { //we have a text version so use it
                $body = $this->bodyText;
            } else {
                //Everything is OK let the system know it's HTML
                $this->msg_is_html = 1;
            }
        } else {
            $body = $this->bodyText;
        }

        return $body;
    }

    /**
     * Clean excess whitespace left by Outlook.
     *
     * @param $body
     * @return mixed
     */
    public function cleanOutlook($body)
    {
        return preg_replace('/(\n\s*){3,}/', "\n\n", $body);
    }

    /**
     * Parse the reply above.
     */
    public function replyAbove()
    {
        $replyAbove = app(ReplyAbove::class);
        $this->bodyHtml = $replyAbove->process($this->bodyHtml, hs_setting('cHD_EMAIL_REPLYABOVE'), 'html');
        $this->bodyText = $replyAbove->process($this->bodyText, hs_setting('cHD_EMAIL_REPLYABOVE'), 'text');
    }

    /**
     * Is this message important?
     *
     * @return bool
     */
    public function isImportant()
    {
        return $this->getHeaderByKey('X-Priority') == 1 ||
            $this->getHeaderByKey('X-MS-Priority') == 1 ||
            $this->getHeaderByKey('Importance') == 'High';
    }

    /**
     * Is this message a delivery error?
     *
     * @return bool
     */
    public function isDeliverError()
    {
        $msgFromName = $this->getFromName();
        $msgFromEmail = $this->getFromEmail();

        if (str_contains(strtolower($msgFromEmail), strtolower('MAILER-DAEMON')) ||
            str_contains(strtolower($msgFromName), strtolower('Mail Delivery System')) !== false ||
            str_contains(strtolower($msgFromName), strtolower('Mail Delivery Service')) !== false ||
            str_contains(strtolower($msgFromName), strtolower('Mail Delivery Subsystem')) !== false ||
            isset($this->headers['X-Failed-Recipients']) ||
            isset($this->headers['X-Autoreply']) ||
            isset($this->headers['Precedence']) ||
            isset($this->headers['Auto-Submitted'])) {
            return true;
        }

        return false;
    }

    /**
     * Is the email a forward?
     *
     * @return bool
     */
    public function isForward()
    {
        if (utf8_strpos($this->headers['subject'], '##forward:true##') !== false ||
            utf8_strpos($this->getHtmlBody(), '##forward:true##') !== false ||
            utf8_strpos($this->getTextBody(), '##forward:true##') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Parse an email to gets it forward data.
     */
    public function parseForward()
    {
        //Clean subject and body of tag
        $this->subject = str_replace('##forward:true##', '', $this->subject);
        $this->bodyText = str_replace('##forward:true##', '', $this->bodyText);
        $this->bodyHtml = str_replace('##forward:true##', '', $this->bodyHtml);

        //Find first instance of the from information.
        //(^> |^>|^) covers normal of > from: and no space >from: and outlook style from:
        $found = preg_match('/(^> |^>|^)(from|frm|van|von):(.*)$/im', $this->bodyText, $from_match);

        if (! $found) {
            return false;
        }

        // Trim whitespace and newlines, which
        // trips up the preg_match regex in some cases
        $matchedEmail = trim($from_match[3]);

        // Outlook often sends forward in format:  some-email@example.com [mailto:someone-else@another.com]
        // We want to take that case and grab the mailto: portion, often in square brackets.
        if (str_contains($matchedEmail, 'mailto:') !== false) {
            $hasMailTo = preg_match('/\[mailto:(.*)\]$/im', $matchedEmail, $mailToMatch);
            if ($hasMailTo && isset($mailToMatch[1])) {
                $matchedEmail = $mailToMatch[1];
            }
        }

        // Ensure what we have is really valid.
        $forwardCheck = hs_parse_email_header(trim($matchedEmail));
        if (empty($forwardCheck['mailbox']) or empty($forwardCheck['host'])) {
            return false;
        }

        // Setup a dummy "forward-from" email header so we can use
        // it in the getFromName and getFromEmail
        $this->headers['forward-from'] = trim($matchedEmail);

        return $this;
    }

    /**
     * Unit testing helper to over-ride a header.
     *
     * @param $key
     * @param $value
     */
    public function overrideHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return false;
    }

    /**
     * @param $property
     * @param $value
     * @return $this
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }
}
