<?php

namespace HS\Base;

use Illuminate\Support\MessageBag;

class MessageBagException extends \Exception
{
    /**
     * Validation Messages.
     * @var array
     */
    private $messages;

    /**
     * Create new Validation Exception.
     * @param MessageBag $messages
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct(MessageBag $messages, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->messages = $messages;

        parent::__construct($message, $code, $previous);
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getErrors()
    {
        return $this->getMessages();
    }
}
