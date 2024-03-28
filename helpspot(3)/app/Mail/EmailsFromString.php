<?php

namespace HS\Mail;

use Illuminate\Support\Collection;

/**
 * From a string, parse out comma-separated and/or line-separated emails.
 * Save a list of "ignored" emails if they are found to be invalid
 * Class EmailsFromString
 * @package App\Mail
 */
class EmailsFromString
{
    private $emails;

    private $parsed;

    private $skipped = [];

    /**
     * EmailsFromString constructor.
     * @param $emails a string of emails
     */
    public function __construct($emails) {
        $this->emails = $emails;

        $lines = $this->lines($emails);

        $this->parsed = $lines->flatMap(function($line) {
            return $this->commas($line);
        })->filter(function($value) {
            $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);

            if( ! $isEmail && ! empty($value) ) {
                $this->skipped[] = $value;
            }

            return $isEmail;
        });
    }

    public function emails() {
        return $this->parsed;
    }

    public function skipped() {
        return $this->skipped;
    }

    /**
     * Split new lines into an array
     * @link https://stackoverflow.com/a/11165332/1412984 (David's answer)
     * @param $emails
     * @return Collection
     */
    protected function lines($emails) {
        return collect(preg_split("/\r\n|\n|\r/", $emails))->transform(function($item) {
            return trim($item);
        });
    }

    /**
     * Split a line of comma separated into emails
     * @param $line
     * @return array
     */
    protected function commas($line) {
        return collect(explode(',', $line))->transform(function($item) {
            return trim($item);
        })->toArray();
    }
}
