<?php

namespace HS\IncomingMail\Loggers;

use hsTimer;

class EchoLogger implements MailLogger
{
    private $id;

    private $timer;

    private $debug;

    public function __construct($id, $debug = false)
    {
        $this->timer = new hsTimer;
        $this->id = $id;
        $this->debug = $debug;
    }

    public function start($id)
    {
        echo "\n\n";
        if ($this->debug && $this->timer) {
            echo ">>>> Message: #{$this->id} - Time (Starting...): ".$this->timer->stop_n_show()."\n";
        }
    }

    public function show($msg)
    {
        if ($this->debug) {
            return $this->display($msg);
        }
    }

    public function display($msg)
    {
        echo "\t>>>> Message: #{$this->id} - Time (".$msg.'): '.$this->timer->stop_n_show();
        if (function_exists('xdebug_peak_memory_usage')) {
            echo ' - Memory: '.round(xdebug_peak_memory_usage() / 1048576, 2).'MB';
        }
        echo "\n";
    }
}
