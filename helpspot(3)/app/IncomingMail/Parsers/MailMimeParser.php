<?php

namespace HS\IncomingMail\Parsers;

use HS\IncomingMail\Message;
use Illuminate\Support\Facades\Log;
use ZBateson\MailMimeParser\Message\Part\MessagePart;

class MailMimeParser extends BaseParser implements Parser
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
     * Parser constructor.
     * @param Message $message
     * @param string $transaction
     */
    public function __construct(Message $message, $transaction='')
    {
        $this->message = $message;
        $this->transaction = $transaction;
    }

    /**
     * Parse the message.
     *
     * @param $body
     * @return object
     */
    public function parse($body)
    {
        $this->parseBody($body);

        if ($this->message->isForward()) {
            $this->message->parseForward();
        }

        return $this->message;
    }

    /**
     * Parse the message but do not parse the forward data. This is used in testing mostly.
     * @param $body
     * @return Message
     */
    public function parseWithoutForward($body)
    {
        $this->parseBody($body);
        return $this->message;
    }

    protected function parseBody($body)
    {
        $parser = new \ZBateson\MailMimeParser\MailMimeParser();
        $this->parseSections($parser->parse(trim($body)));
    }

    /**
     * Parse the sections of an email.
     *
     * @param $body
     */
    protected function parseSections(\ZBateson\MailMimeParser\Message $body)
    {
        $headers = [];
        foreach ($body->getRawHeaders() as $item) {
            $key = strtolower($item[0]);
            $headers[$key] = $item[1];
        }

        $this->message->headers = $headers;

        $this->message->setSubjectAndConvert();
        $this->message->setBodyText($body->getTextContent());
        $this->message->setBodyHtml($body->getHtmlContent());

        $attachmentCount = $body->getAttachmentCount();
        if ($attachmentCount > 0) {
            for ($i = 0; $i < $attachmentCount; $i++) {
                $this->setAttachment($body->getAttachmentPart($i));
            }
        }
    }

    /**
     * Parse the inline attachments.
     * @param MessagePart $att
     */
    protected function setAttachment(MessagePart $att)
    {
        Log::debug('['.get_class($this).'] adding attachment to message', [
            'file_name' => $att->getFilename(),
            'file_mime' => $att->getContentType(),
            'file_content_id' => $att->getContentId(),
            'transaction' => $this->transaction,
        ]);
        $data = [
            'name' => $att->getFilename(),
            'mimetype' => $att->getContentType(),
            'body' => $att->getContent(),
            'content-id' => '<'.$att->getContentId().'>',
            'transaction' => $this->transaction,
        ];
        $this->message->setInlineAttachment($data);
    }
}
