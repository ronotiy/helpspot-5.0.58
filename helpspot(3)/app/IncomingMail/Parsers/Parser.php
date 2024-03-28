<?php

namespace HS\IncomingMail\Parsers;

use HS\IncomingMail\Message;

interface Parser
{
    /**
     * Parser constructor.
     * @param Message $message
     */
    public function __construct(Message $message);

    /**
     * Parse the message.
     *
     * @param $body
     * @return object
     */
    public function parse($body);

    /*
     * Parse the sections of an email
     *
     * @param $body
     */
    //public function parseSections($body);

    /*
     * Flatten the email message and figure
     * out the attachments and text.
     *
     * @param $mime
     * @return void
     */
//    public function flatten($mime);
}
