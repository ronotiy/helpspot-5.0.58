<?php


namespace HS\IncomingMail;

use Illuminate\Cache\Lock;
use Illuminate\Support\Facades\Cache;

trait FetchMailConcurrency
{
    /**
     * @var Lock
     */
    protected $lock;

    /**
     * @return Lock
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * @return bool
     */
    public function mailboxIsBusy()
    {
        $this->lock = Cache::lock($this->mailboxCacheKey(), 60*10); // Lock for 10 minutes
        return ! $this->lock->get();
    }

    /**
     * @return bool
     */
    public function setMailboxAvailable()
    {
        return $this->lock->release();
    }

    public function mailboxCacheKey()
    {
        return vsprintf('fetchmail:%s', [
            $this->getKey()
        ]);
    }
}
