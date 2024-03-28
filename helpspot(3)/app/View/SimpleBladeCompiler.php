<?php

namespace HS\View;

class SimpleBladeCompiler extends BaseBladeCompiler
{
    /**
     * All of the available compiler functions.
     *
     * @var array
     */
    protected $compilers = [
        'Comments',
        'Echos',
    ];

    // Switch these so HelpSpot parses everything as raw (allowing HTML)
    protected $rawTags = ['{{', '}}'];
    protected $contentTags = ['{!!', '!!}'];
}
