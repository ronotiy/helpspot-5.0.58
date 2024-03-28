<?php

namespace HS\Base;

trait Gettable
{
    /**
     * Retrieve private attributes
     *   Attributes should be protected
     *   so they cannot be *set* arbitrarily.
     *   This allows us to *get* them as if they
     *   were public.
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }

        return null;
    }
}
