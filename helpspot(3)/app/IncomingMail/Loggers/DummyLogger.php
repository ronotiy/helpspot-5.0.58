<?php

namespace HS\IncomingMail\Loggers;

class DummyLogger implements MailLogger
{
    public function __construct($id, $debug = false)
    {
    }

    public function start($id)
    {
        // TODO: Implement start() method.
    }

    public function show($msg)
    {
        // TODO: Implement show() method.
    }

    public function display($msg)
    {
        // TODO: Implement display() method.
    }
}
