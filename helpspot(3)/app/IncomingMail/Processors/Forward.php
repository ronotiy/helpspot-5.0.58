<?php

namespace HS\IncomingMail\Processors;

use HS\IncomingMail\Message;

class Forward
{
    /**
     * @var Message
     */
    private Message $message;

    /**
     * @var string|string[]
     */
    private array|string $tmp_html;

    /**
     * @var string|string[]
     */
    private array|string $tmp_text;

    private array $msg_parsed_header;
    private string $forwarded_email_from;

    /**
     * Forward constructor.
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Is the email a forward?
     *
     * @return bool
     */
    public function isForward()
    {
        if (utf8_strpos($this->message->getSubject(), '##forward:true##') !== false ||
            utf8_strpos($this->message->getHtmlBody(), '##forward:true##') !== false ||
            utf8_strpos($this->message->getTextBody(), '##forward:true##') !== false) {
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
        $this->msg_parsed_header['subject'] = str_replace('##forward:true##', '', $this->msg_parsed_header['subject']);
        $this->tmp_text = str_replace('##forward:true##', '', $this->tmp_text);
        $this->tmp_html = str_replace('##forward:true##', '', $this->tmp_html);

        //Find first instance of the from information.
        //(^> |^>|^) covers normal of > from: and no space >from: and outlook style from:
        $found = preg_match('/(^> |^>|^)(from|frm|van|von):(.*)$/im', $this->tmp_text, $from_match);
        if ($found) {
            // Trim whitespace and newlines, which
            // trips up the preg_match regex in some cases
            $matchedEmail = trim($from_match[3]);

            // Outlook often sends forward in format:  some-email@example.com [mailto:someone-else@another.com]
            // We want to take that case and grab the mailto: portion, often in square brackets.
            if (str_contains($matchedEmail, 'mailto:')) {
                $hasMailTo = preg_match('/\[mailto:(.*)\]$/im', $matchedEmail, $mailToMatch);
                if ($hasMailTo && isset($mailToMatch[1])) {
                    $matchedEmail = $mailToMatch[1];
                }
            }

            //Be sure what we found is valid
            $forwardCheck = hs_parse_email_header(trim($matchedEmail));
            if (! empty($forwardCheck['mailbox']) && ! empty($forwardCheck['host'])) {
                $this->forwarded_email_from = trim($matchedEmail);
            }
        }
    }
}
