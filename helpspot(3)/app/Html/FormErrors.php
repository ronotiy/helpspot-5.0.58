<?php

namespace HS\Html;

class FormErrors
{
    protected $errors;

    /**
     * Set any error messages.
     *
     * @param array $errors
     */
    public function set(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get an error message.
     *
     * @param string $error
     * @return mixed|null
     */
    public function get($error)
    {
        if (isset($this->errors[$error])) {
            return $this->errors[$error];
        }
    }
}
