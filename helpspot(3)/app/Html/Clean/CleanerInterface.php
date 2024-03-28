<?php

namespace HS\Html\Clean;

interface CleanerInterface
{
    public function clean($input, $stripImg = false);

    public function wasContentStripped($content);
}
