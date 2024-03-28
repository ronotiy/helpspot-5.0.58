<?php

namespace HS\Install\Installer\Handlers;

use HS\CommandBus\HandlerInterface;
use HS\Install\Installer\Actions\AcceptTerms;

class AgreeTermsHandler implements HandlerInterface
{
    public function handle($command)
    {
        // Throws \HS\Base\ValidationException
        $terms = new AcceptTerms($command->userAgrees);
    }
}
