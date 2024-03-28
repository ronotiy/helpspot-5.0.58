<?php

use Carbon\Carbon;

/**
 * Grab and return content-type header
 * array if it was previously set.
 * @return array
 */
function contentTypeHeader()
{
    $headers = [];
    $contentTypeHeader = collect(headers_list())->reject(function ($value) {
        return strpos(strtolower($value), 'content-type') === false;
    })->first();

    if ($contentTypeHeader) {
        list($headerName, $headerValue) = explode(':', $contentTypeHeader);
        $headers[$headerName] = $headerValue;
    }

    return $headers;
}

/**
 * Grab http status if it was previously
 * set/sent so Laravel can return it
 * within it's Response object.
 * @return int
 */
function httpStatusCode()
{
    $setStatus = http_response_code();

    if (is_bool($setStatus)) {
        return 200;
    }

    if (is_numeric($setStatus)) {
        return $setStatus;
    }

    return 200;
}

/**
 * Parse a query string into an array.
 *
 * @param $queryString
 * @return mixed
 */
function queryStringToArray($queryString)
{
    parse_str($queryString, $out);

    return $out;
}

/**
 * Build the log off link with the query string.
 *
 * @param $queryString
 * @return mixed
 */
function logOffParams($queryString)
{
    return array_merge(queryStringToArray($queryString), ['logoff']);
}

/**
 * Load a HelpSpot Setting
 * Used as a single-point of entry to find HS settings
 * as either an env var, or a constant, to help refactoring settings
 * @param $constant
 * @param null $default
 * @return mixed|null
 */
function hs_setting($constant, $default = null)
{
    $env = env($constant);

    if (! is_null($env)) {
        return $env;
    }

    if (defined($constant)) {
        return constant($constant);
    }

    return $default;
}

/**
 * Replace adodb qstr (which does the same for PDO drivers anyway).
 * @param $string
 * @return mixed
 */
function qstr($string)
{
    return \DB::getPdo()->quote($string);
}

function buildNoteBody($body, $vars)
{
    $tBodyTemplateName = uniqid().'_message_tbody';
    \HS\View\Mail\TemplateTemporaryFile::create($tBodyTemplateName, $body);
    return (string) restrictedView($tBodyTemplateName, $vars);
}

function restrictedView($view = null, $data = [], $mergeData = [])
{
    $factory = app('view-simple');

    if (func_num_args() === 0) {
        return $factory;
    }

    return $factory->make($view, $data, $mergeData);
}

function mailView($view = null, $data = [], $mergeData = [])
{
    $factory = app('view-mail');

    if (func_num_args() === 0) {
        return $factory;
    }

    return $factory->make($view, $data, $mergeData);
}

function prepareEmailHistoryMessage($type, $note, $noteIsHTML)
{
    $emailout = '';

    $note = trim($note);
    if (! empty($note)) {
        if ($type == 'text') {
            if ($noteIsHTML == 1) {
                $note = strip_tags(str_replace(['<br>', '<br />', '<BR>', '<BR />', '<p>', '</p>', '<P>', '</P>'], "\n", $note));
            }
            $emailout = '';
            $lines = explode("\n", $note);
            foreach ($lines as $line) {
                $emailout .= '> '.$line."\n";
            }
        } elseif ($type == 'html') {
            //If a text note then add br's
            if ($noteIsHTML == 0) {
                $note = nl2br($note);
            }
            $emailout = $note;
        }
    }

    return $emailout;
}

/**
 * Convert calendar date to timestamp.
 *
 * @param $date
 * @param $format
 * @return mixed
 */
function jsDateToTime($date, $format)
{
    if (! $date) return '';
    $search  = ['%a', '%A', '%d', '%b', '%B', '%m', '%Y', '%H', '%I', '%M', '%p', '%e'];
    $replace = ['D',  'l',  'd',  'M',  'F',  'm',  'Y',  'H',  'h',  'i',  'A',  'd'];
    $dateFormat = str_replace($search, $replace, $format);
    $date = Carbon::createFromFormat($dateFormat, $date);
    return $date->timestamp;
}

/**
 * Format PHP strftime to javascript calendar DATE format.
 *
 * The calendar only supports date and time as separate fields
 * so we need to ONLY get the date from the string in this function.
 *
 * @param $format - The strftime format
 * @return mixed
 */
function formatJsDate($format)
{
    $pieces = explode(" ", $format);
    if (! Illuminate\Support\Str::contains($format, ':%M')) {
        // If it doesn't have minutes then it's just a normal date format
        // and we don't need to do anything special other than replacement
        $string = $format;
    } else if (count($pieces) < 4) {
        $string = $pieces[0];
    } elseif (count($pieces) >= 4) {
        $string = $pieces[0] .' '. $pieces[1] .' '. $pieces[2];
    } else {
        $string = $format;
    }

    $search = ['%m', '%b', '%B', '%d', '%Y'];
    $replace = ['mm', 'M', 'MM', 'dd', 'yy'];
    return str_replace($search, $replace, $string);
}

/**
 * Format PHP strftime to javascript calendar TIME format
 *
 * The calendar only supports date and time as separate fields
 * so we need to ONLY get the time from the string in this function.
 *
 * @param $format  - The strftime format
 * @return mixed
 */
function formatJsTime($format)
{
    $pieces = explode(" ", $format);
    if (count($pieces) === 2) {
        $string = $pieces[1];
    } elseif (count($pieces) === 3) {
        $string = $pieces[1] .' '. $pieces[2];
    } elseif (count($pieces) === 4) {
        $string = $pieces[3];
    } elseif (count($pieces) === 5) {
        $string = $pieces[3] .' '. $pieces[4];
    } else {
        $string = $format;
    }

    $search = ['%I', '%H', '%M', '%p'];
    $replace = ['hh', 'HH', 'ii', 'A'];
    return str_replace($search, $replace, $string);
}

function defaultConnectionDetail($key)
{
    $connection = config('database.default');
    return config('database.connections.'.$connection.'.'.$key);
}

/**
 * Calculate the next send date for reports and responses based on options
 *
 * @param $fSendTime
 * @param $fSendDay
 * @param $fSendEvery
 * @return string
 */
function calculateNextSend($fSendTime, $fSendDay, $fSendEvery)
{
    // First convert decimal minutes into proper
    // for example `5.25` into `5:15`
    $time = explode('.', $fSendTime);
    $hour = array_shift($time);
    $time = $hour.':'.($fSendTime - $hour) * 60;

    if ($fSendEvery == 'daily') {
        return date("Y-m-d H:i:s", strtotime('tomorrow '. $time.':00'));
    } elseif ($fSendEvery == 'weekly') {
        return date("Y-m-d H:i:s", strtotime('next '. $fSendDay.' '.$time.':00'));
    } else {
        return date("Y-m-d H:i:s", strtotime('last day of this month '.$time.':00'));
    }
}

/**
 * str_contains is only for PHP 8
 * so this is a polyfill for php7.
 */
if (! function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}
