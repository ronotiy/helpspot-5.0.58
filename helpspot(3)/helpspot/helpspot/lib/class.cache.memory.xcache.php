<?php

class Cache_XCache extends Memory_Cache
{
    //Constructor
    public function __construct()
    {
        parent::__construct();
    }

    public function set($name, $value, $ttl = 0)
    {
        //Add to stack
        if ($name != $this->stack_name && ! in_array($name, $this->stack)) {
            $this->add_to_stack($name);
        }

        return xcache_set($this->prefix.$name, $value, $ttl);
    }

    public function get($name)
    {
        return xcache_get($this->prefix.$name);
    }

    public function exists($name)
    {
        return xcache_isset($this->prefix.$name);
    }

    public function delete($name)
    {
        //Can't delete protected variables
        if (! in_array($this->protected)) {
            $this->rm_frm_stack($name);

            return xcache_unset($this->prefix.$name);
        } else {
            return false;
        }
    }

    public function clearcache()
    {
        //Loop over stack and individually unset
        foreach ($this->stack as $k=>$v) {
            $this->delete($v);
        }
    }
}
