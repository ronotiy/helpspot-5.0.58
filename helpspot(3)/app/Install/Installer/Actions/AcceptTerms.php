<?php

namespace HS\Install\Installer\Actions;

use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;

class AcceptTerms
{
    use \HS\Base\Gettable;

    /**
     * @var bool
     */
    protected $accept;

    public function __construct($accept)
    {
        $this->setAccept($accept);
    }

    protected function setAccept($accept)
    {
        if (! $accept) {
            $messages = new MessageBag(['accept' => 'You must accept the license terms to continue']);

            throw new ValidationException($messages, 'You must accept the license terms to continue');
        }

        $this->accept = true;
    }
}
