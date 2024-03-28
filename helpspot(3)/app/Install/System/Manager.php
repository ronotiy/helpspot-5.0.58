<?php

namespace HS\Install\System;

use Illuminate\Support\MessageBag;

class Manager
{
    /**
     * System Checks.
     * @var array
     */
    protected $checkers = [];

    /**
     * Errors for checkAllExcept.
     * @var \Illuminate\Support\MessageBag
     */
    protected $errors;

    /**
     * Non-Essential Errors for checkAllExcept
     * The install process can continue despite
     * these failing.
     * @var \Illuminate\Support\MessageBag
     */
    protected $nonEssentialErrors;

    public function __construct()
    {
        $this->errors = new MessageBag;
        $this->nonEssentialErrors = new MessageBag;
    }

    /**
     * Register a Check.
     * @param $key
     * @param SystemCheckInterface $checker
     * @return $this
     */
    public function check($key, SystemCheckInterface $checker)
    {
        $this->checkers[$key] = $checker;

        return $this;
    }

    /**
     * See if a checker has been registered.
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->checkers[$key]) || isset($this->conditionalChecks[$key]);
    }

    /**
     * Get a checker.
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->checkers[$key];
        }

        throw new \Exception('System Check of key '.$key.' not defined');
    }

    /**
     * Check if Checker determines
     * the system is valid.
     * @param $key
     * @return mixed
     */
    public function isValid($key)
    {
        $checker = $this->get($key);

        return $checker->valid();
    }

    /**
     * Check all Checkers
     * except any passed.
     * @param array $except
     * @return bool
     */
    public function checkAllExcept($except = [])
    {
        $allValid = true;

        foreach ($this->checkers as $key => $checker) {
            if (in_array($key, $except)) {
                continue;
            }

            if (! $checker->valid()) {
                if ($checker->required()) {
                    $allValid = false;
                    $this->errors->add($key, $checker->getError());
                } else {
                    $this->nonEssentialErrors->add($key, $checker->getError());
                }
            }
        }

        return $allValid;
    }

    /**
     * Get errors.
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get non-essential errors.
     * @return \Illuminate\Support\MessageBag
     */
    public function getNonEssentialErrors()
    {
        return $this->nonEssentialErrors;
    }
}
