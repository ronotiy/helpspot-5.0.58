<?php

namespace HS\IncomingMail\Processors;

class ReplyAbove
{
    /**
     * Parse the reply above.
     *
     * @param $body
     * @param $replyText
     * @param string $type
     * @return mixed|string
     */
    public function process($body, $replyText, $type = 'html')
    {
        // When replies mess with text by adding spaces/tabs it breaks.
        $body = $this->_ensureSingleLineReplyAbove($body, $replyText);
        if (empty($body) or ($rapos = utf8_strpos($body, $replyText)) === false) {
            return $body;
        }

        if ($type == 'html') {
            $note = $this->parseReplyAboveHtml($body, $rapos);
        } else {
            $note = $this->parseReplyAboveText($body, $rapos);
        }

        //sanity check, if note is 0 then use orig
        if ($this->isNoteEmpty($note)) {
            $note = $body;
        }

        return $note;
    }

    /**
     * @param $note
     * @return bool
     */
    public function isNoteEmpty($note)
    {
        $trimmed = trim(strip_tags(str_replace('&nbsp;', '', $note)));

        return strlen($trimmed) == 0;
    }

    /**
     * @param $body
     * @param $rapos
     * @return string
     */
    protected function parseReplyAboveHtml($body, $rapos)
    {
        //phase 1 look back - reply above
        $note = $this->_lookBack($body, $rapos);

        // is it an outlook with top border?
        $pattern = '/(style="border:none;border-top:solid) (#(?:[0-9a-fA-F]{3}){1,2}) (1.0pt;)/';
        $note = preg_replace($pattern, '$1 $3', $note);
        // Converts into the following without the border color:
        // '<div style="border:none;border-top:solid 1.0pt;padding:3.0pt',

        //Do additional loop back checks for common things at the bottom of an email but above the reply text
$patterns = ['>On '.date('M j, Y').',', 	// Apple
    '>On '.date('D, M j, Y'),		// Gmail
    '<div style="border:none;border-top:solid 1.0pt;padding:3.0pt',
    '<div style="border:none;border-top:solid #B5C4DF 1.0pt;padding:3.0pt',	// Outlook
    '<div style=\'border:none;border-top:solid #B5C4DF 1.0pt;padding:3.0pt', ]; // Outlook

        foreach ($patterns as $k=>$pattern) {
            if (substr_count($note, $pattern)) { //Only move forward if there's a single match
                $pos = strripos($note, $pattern);
                if ($pos) {
                    $adjustment = (utf8_strpos($pattern, '>') === 0 ? 1 : 0);
                    $note = $this->_lookBack($note, ($pos + $adjustment)); //+1 accounts for tag > patterns start with
                }
            }
        }

        //re add body and html
        return $note.'</body></html>';
    }

    /**
     * @param $body
     * @param $rapos
     * @return mixed|string
     */
    protected function parseReplyAboveText($body, $rapos)
    {
        $note = utf8_substr($body, 0, $rapos);
        $note = utf8_rtrim($note, "> \t\n\r\o\x0B");
        $pos = preg_match(lg_outlookseparator, '', $match);
        if ($pos) {
            $sep_position = utf8_strpos($note, $match[0]);
            $note = utf8_substr($note, 0, $sep_position);
        }

        return $note;
    }

    /**
     * Make sure the reply above is on one line.
     *
     * @param string $body
     * @param string $replyText
     * @return mixed
     */
    public function _ensureSingleLineReplyAbove($body, $replyText)
    {
        // Converts to this regex:
        // ##\s*Reply\s*ABOVE\s*THIS\s*LINE\s*to\s*add\s*a\s*note\s*to\s*this\s*request\s*##
        $search = str_replace(' ', '\s*', "/$replyText/");
        // Now use that to search and replace with a single line.
        return preg_replace($search, $replyText, $body);
    }

    /**
     * Helper for reply above function.
     *
     * @param $note
     * @param $pos
     * @return mixed
     */
    public function _lookBack($note, $pos)
    {
        return utf8_substr($note, 0, $pos);
    }
}
