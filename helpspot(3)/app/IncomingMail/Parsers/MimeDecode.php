<?php

namespace HS\IncomingMail\Parsers;

use Mail_mimeDecode;
use Soundasleep\Html2text;
use HS\IncomingMail\Message;
use Html2Text\Html2TextException;

class MimeDecode extends BaseParser implements Parser
{
    /**
     * @var Message
     */
    private $message;

    /**
     * @var string
     */
    private $transaction;

    /**
     * MimeDecode constructor.
     * @param Message $message
     * @param string $transaction
     */
    public function __construct(Message $message, $transaction = '')
    {
        $this->message = $message;
        $this->transaction = $transaction;
    }

    /**
     * Parse the body and setup the Message class.
     *
     * @param $body
     * @return object
     * @throws Html2TextException
     */
    public function parse($body)
    {
        $decoded = $this->decode($body);

        // Edge case bug. x-spam-report can contain the body and not be in ascii, which causes
        // it to fail to insert and is a pain in the ass to debug. By unsetting here we don't have
        // worry about this and it shouldn't really ever be used in triggers/rules.
        unset($decoded->headers['x-spam-report'], $decoded->headers['X-Spam-Report']);
        unset($decoded->headers['x-ham-report'], $decoded->headers['X-Ham-Report']);

        $this->message->headers = $decoded->headers;

        $this->message->setSubjectAndConvert();

        $this->flatten([$decoded]);

        if ($this->message->isForward()) {
            $this->message->parseForward();
        }

        return $this->message;
    }

    /**
     * Decode the message.
     *
     * @param $body
     * @return object
     */
    public function decode($body)
    {
        $params = $this->getMimeDecodeParams($body);

        return (new Mail_mimeDecode($params['input']))->decode($params);
    }

    /**
     * Get the params to pass to mime decode.
     *
     * @param $body
     * @return array
     */
    public function getMimeDecodeParams($body)
    {
        return [
            'include_bodies' => true,
            'decode_bodies' => true,
            'decode_headers' => false,
            'crlf' => "\r\n",
            'input' => $body,
        ];
    }

    /**
     * Flatten the email message and figure
     * out the attachments and text.
     *
     * @param array $mime
     * @return void
     * @throws Html2TextException
     */
    public function flatten($mime)
    {
        foreach ($mime as $k=>$o) {
            if (isset($o->parts) && is_array($o->parts)) {
                $this->flatten($o->parts);
            } else {
                if (isset($o->ctype_primary) && isset($o->ctype_secondary)) {
                    // Convert any strange charsets
                    // See bug #652, and do try to not re-introduce this bug someday
                    if (isset($o->ctype_parameters['charset'])) {
                        $o->ctype_parameters['charset'] = $this->convertCharset($o->ctype_parameters['charset']);
                    } else {
                        $o->ctype_parameters['charset'] = 'utf-8'; // If the message doesn't include a charset assume utf-8
                    }
                    // Text attachment
                    if ((! isset($o->disposition) || strtolower($o->disposition) == 'inline')
                        && strtolower($o->ctype_primary) == 'text' && strtolower($o->ctype_secondary) == 'plain') {
                        $this->message->setBodyText($this->textAttachment($o->body, $o->ctype_parameters['charset']));
                    }
                    // HTML
                    elseif ((! isset($o->disposition) || strtolower($o->disposition) == 'inline')
                        && strtolower($o->ctype_primary) == 'text' && strtolower($o->ctype_secondary) == 'html') {
                        $this->message->setBodyHtml($this->htmlAttachment($o->body, $o->ctype_parameters['charset']));
                    }
                    //INLINE
                    elseif (isset($o->disposition) && strtolower($o->disposition) == 'inline') {
                        $this->inlineAttachments($o);
                    }
                    //ATTACHMENT
                    elseif (! isset($o->disposition) || strtolower($o->disposition) == 'attachment') {
                        $this->msgAttachments($o);
                    }
                }
            }
        }

        // See #1258 where we had a case with an eml without a text body that is
        // needed for checking forwards. If we do this here then we only have to
        // parse it once through the 3rd party package, verse doing it when fetching.
        if ($this->message->getTextBody() == '') {
            $this->message->setBodyText(
                Html2Text::convert($this->message->getHtmlBody(), true)
            );
        }
    }

    /**
     * * Parse the inline attachments.
     * @param $o
     */
    protected function inlineAttachments(\stdClass $o)
    {
        $data = [
            'name' => $this->_findFileName($o),
            'mimetype' => $o->ctype_primary.'/'.$o->ctype_secondary,
            'body' => $o->body,
            'content-id' => (isset($o->headers['content-id']) ? $o->headers['content-id'] : ''),
            'transaction' => $this->transaction,
        ];
        $this->message->setInlineAttachment($data);
    }

    /**
     * Parse the message attachments.
     * @param $o
     */
    protected function msgAttachments(\stdClass $o)
    {
        $data = [
            'name' => $this->_findFileName($o),
            'mimetype' => $o->ctype_primary.'/'.$o->ctype_secondary,
            'body' => $o->body,
            'content-id' => (isset($o->headers['content-id']) ? $o->headers['content-id'] : ''),
            'transaction' => $this->transaction,
        ];
        $this->message->setAttachment($data);
    }

    /**
     * Figure out the file name by looking in a few spots.
     *
     * @param $mimeObject
     * @return string
     */
    protected function _findFileName(&$mimeObject)
    {
        /*
         * Via Wikipedia:
         * Many mail user agents also send messages with the file name in the name parameter of the content-type
         * header instead of the filename parameter of the content-disposition header. This practice is discouraged –
         * the file name should be specified either through just the filename parameter, or through both the filename and the name parameters.
         */
        if (isset($mimeObject->d_parameters['filename'])) {
            return hs_charset_emailheader($mimeObject->d_parameters['filename']);
        } elseif (isset($mimeObject->ctype_parameters['name'])) {
            return hs_charset_emailheader($mimeObject->ctype_parameters['name']);
        } else {
            //Use known name
            return 'unknown_filename.'.hs_lookup_mime($mimeObject->ctype_primary.'/'.$mimeObject->ctype_secondary);
        }
    }
}
