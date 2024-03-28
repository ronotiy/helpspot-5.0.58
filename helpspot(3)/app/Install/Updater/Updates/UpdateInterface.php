<?php

namespace HS\Install\Updater\Updates;

interface UpdateInterface
{
    public function run();

    public function getVersion();
}
