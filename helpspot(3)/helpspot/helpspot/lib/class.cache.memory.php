<?php

class Memory_Cache
{
    //Cache variable prefix
    public $prefix = 'helpspot.';

    //Stack of cached values since xCache only allows special admins to clear the full cache
    public $stack = [];

    public $stack_name = 'internal.var.stack';

    //Protected variables are never cleared except when the server is restarted
    public $protected = [
        'internal.var.stack',
        'var.person.status',
    ];

    //Constructor
    public function __construct()
    {
        //Populate stack
        if ($this->exists($this->stack_name)) {
            $this->stack = $this->get($this->stack_name);
        }
    }

    //For functions that take arguments we use this helper to make a unique name based on parameter input
    public function mkname($name, $args)
    {
        $argvals = '';
        if (is_array($args) && ! empty($args)) {
            foreach ($args as $k=>$v) {
                if (is_string($v) || is_numeric($v)) {
                    $argvals = $argvals.$v;
                }
            }
        }

        return $name.md5($argvals);
    }

    //Clear the cache if any POST data is sent.
    public function clear_on_post()
    {
        if (! empty($_POST)) {
            $this->clearcache();
        }
    }

    //Stack functions
    public function add_to_stack($name)
    {
        $this->stack[] = $name;
        $this->stack = array_unique($this->stack);
        $this->set($this->stack_name, $this->stack, 0);
    }

    public function rm_frm_stack($name)
    {
        $key = array_search($name, $this->stack);
        if ($key) {
            unset($this->stack[$key]);
            $this->set($this->stack_name, $this->stack, 0);
        }
    }
}
