<?php

namespace HS\IncomingMail\Processors;

use Illuminate\Support\Arr;
use HS\IncomingMail\Message;
use UserScape_Bayesian_Classifier;

class Spam
{
    /**
     * @var Message
     */
    private Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return bool|int|string
     */
    public function isSpam()
    {
        if (! $blackList = $this->isNotBlackListed(hs_setting('cHD_SPAM_BLACKLIST'))) {
            return $blackList;
        }
        if (! $whitelist = $this->isNotWhiteListed(hs_setting('cHD_SPAM_WHITELIST'))) {
            return $whitelist;
        }

        if (hs_setting('cHD_SPAMFILTER') != 0) { // if it's not turned off.
            return $this->bayesianCheck();
        }

        return '0';
    }

    /**
     * @return int|string
     */
    public function bayesianCheck()
    {
        $spamText['subject'] = $this->message->getSubject();
        $spamText['from'] = $this->message->getFromName();
        $spamText['body'] = $this->message->getParsedBody();
        $spamText['headers'] = $this->message->getFromEmail();
        $spamCheck = new UserScape_Bayesian_Classifier($spamText);

        return $spamCheck->Check();
    }

    /**
     * @param string $blacklist
     * @return bool|string
     */
    public function isNotBlackListed($blacklist = '')
    {
        if (! hs_empty($blacklist)) {
            $patterns = explode("\n", $blacklist);
            $headers = implode("\n", Arr::flatten($this->message->headers));
            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);
                if (! empty($pattern) && stripos($headers, $pattern) !== false) {
                    return '-1';
                }
            }
        }

        return true;
    }

    /**
     * @param string $whitelist
     * @return bool|string
     */
    public function isNotWhiteListed($whitelist = '')
    {
        if (! hs_empty($whitelist)) {
            $patterns = explode("\n", $whitelist);
            $headers = implode("\n", Arr::flatten($this->message->headers));
            foreach ($patterns as $pattern) {
                $pattern = trim($pattern);
                if (! empty($pattern) && stripos($headers, $pattern) !== false) {
                    return '0';
                }
            }
        }

        return true;
    }
}
