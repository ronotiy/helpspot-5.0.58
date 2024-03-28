<?php

namespace HS\IncomingMail\Loggers;

interface MailLogger
{
    public function __construct($id, $debug = false);

    public function start($id);

    public function show($msg);

    public function display($msg);
}
