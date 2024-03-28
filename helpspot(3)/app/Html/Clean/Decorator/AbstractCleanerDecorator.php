<?php

namespace HS\Html\Clean\Decorator;

use HS\Html\Clean\CleanerInterface;

abstract class AbstractCleanerDecorator implements CleanerInterface
{
    protected $wrappedCleaner;

    public function __construct(CleanerInterface $wrappedCleaner)
    {
        $this->wrappedCleaner = $wrappedCleaner;
    }

    public function clean($input, $stripImg = false)
    {
        return $this->wrappedCleaner->clean($input, $stripImg);
    }

    public function wasContentStripped($content)
    {
        return $this->wrappedCleaner->wasContentStripped($content);
    }
}
