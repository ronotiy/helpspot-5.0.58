<?php

class Cache_APC extends Memory_Cache
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

        return apc_store($this->prefix.$name, $value, $ttl);
    }

    public function get($name)
    {
        return apc_fetch($this->prefix.$name);
    }

    public function exists($name)
    {
        //apc_exists is too new at this time, just added in 3.1.4
        if ($this->get($this->prefix.$name)) {
            return true;
        } else {
            return false;
        }
    }

    public function delete($name)
    {
        //Can't delete protected variables
        if (! in_array($this->protected)) {
            $this->rm_frm_stack($name);

            return apc_delete($this->prefix.$name);
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
