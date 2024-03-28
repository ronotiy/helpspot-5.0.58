#!/usr/bin/env php
<?php

use HS\Console\Commands\AutoRules;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputDefinition;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
define('IN_PORTAL',false);

// HelpSpot
define('cBASEPATH', dirname(__FILE__).'/helpspot');
require_once(cBASEPATH.'/helpspot/lib/class.language.php');

// Laravel
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Artisan Application
|--------------------------------------------------------------------------
|
| When we run the console application, the current CLI command will be
| executed in this console and the response sent back to a terminal
| or another output device for the developers. Here goes nothing!
|
*/

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    $input = (new ArgvInput(null, new InputDefinition([
        new InputOption('id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY)
    ])));

    // Handle mixed option uses such as --id=1 --id=2 --id=3,4,5
    $ruleIds = [];
    foreach($input->getOption('id') as $parsedId) {
        $ids = explode(',', $parsedId);
        $ruleIds = array_merge($ruleIds, $ids);
    }

    // Must pass an ID to do anything
    if(count($ruleIds) == 0) {
        exit(0);
    }

    return $kernel->call(AutoRules::class, ['--id' => array_unique($ruleIds)]);
} catch(\Exception $e) {
    (new ConsoleOutput)->getErrorOutput()->writeln($e->getMessage());
    exit(1);
}

exit(0);
