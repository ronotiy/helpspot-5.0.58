<?php

namespace HS\Domain\Workspace;

trait HasHistoryLog
{
    protected $parsedTLog;

    /**
     * Get request history tLog item which are expected to be an array by key
     * @param $key
     * @param $rawLog
     * @return mixed
     */
    public function getRequestDataArray($key)
    {
        $items = explode(',', $this->getRequestData($key));

        return collect($items)->filter(function($item) {
            return ! empty($item);
        })->map(function($item) {
            return trim($item);
        })->toArray();
    }

    /**
     * Get request history tLog item by key
     * @param $key
     * @param $rawLog
     * @return string
     */
    public function getRequestData($key)
    {
        if( is_null($this->parsedTLog) ) {
            $this->parsedTLog = hs_unserialize($this->tLog, []);
        }

        return $this->log[$key] ?? '';
    }
}
