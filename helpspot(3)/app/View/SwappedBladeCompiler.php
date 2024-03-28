<?php

namespace HS\View;

class SwappedBladeCompiler extends BaseBladeCompiler
{
    // Switch these so HelpSpot parses everything as raw (allowing HTML)
    protected $rawTags = ['{{', '}}'];
    protected $contentTags = ['{!!', '!!}'];
}
