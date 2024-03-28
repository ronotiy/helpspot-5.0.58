<?php

namespace HS\Install\System;

interface SystemCheckInterface
{
    /**
     * @return bool
     */
    public function valid();

    /**
     * @return bool
     */
    public function required();

    /**
     * @return mixed
     */
    public function getError();
}
