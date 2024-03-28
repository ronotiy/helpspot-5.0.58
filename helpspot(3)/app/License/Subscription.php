<?php

namespace HS\License;

use Carbon\Carbon;

class Subscription
{
    /**
     * The HS License array.
     *
     * @var array
     */
    private $license;

    /**
     * Subscription constructor.
     * @param array $license
     */
    public function __construct(array $license)
    {
        $this->license = $license;
    }

    /**
     * Do they have a subscription?
     *
     * @return bool
     */
    public function hasSubscription()
    {
        return (bool) isset($this->license['Subscription']) ? $this->license['Subscription'] : false;
    }

    /**
     * @return mixed
     */
    protected function supportEnds()
    {
        return $this->license['SupportEnds'];
    }

    /**
     * Get their subscription tier.
     *
     * @return string
     */
    public function tier()
    {
        return $this->license['SubscriptionTier'];
    }

    /**
     * If this is a free user.
     *
     * @return string
     */
    public function isFree()
    {
        return $this->tier() == 'free';
    }

    /**
     * Is their subscription expired.
     *
     * @return bool
     */
    public function expired()
    {
        if (! $this->hasSubscription()) {
            return false;
        }

        $endDate = Carbon::createFromTimestamp($this->supportEnds());

        return $endDate->addDays(14)->lt(Carbon::now());
    }

    /**
     * How many days until they are expired.
     * @return int
     */
    public function endsIn()
    {
        $endDate = Carbon::createFromTimestamp($this->supportEnds());

        return Carbon::now()->diffInDays($endDate->addDays(14));
    }

    /**
     * Are they on the grace period? We give them a 14 day leeway after expiration
     * before it shuts down.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        if (! $this->hasSubscription()) {
            return false;
        }

        $endDate = Carbon::createFromTimestamp($this->supportEnds());

        return Carbon::now()->gt($endDate);
    }

    /**
     * Can they add a new item based on their tier.
     *
     * @param string $type
     * @param int $currentNumber
     * @return bool
     */
    public function canAdd($type = '', $currentNumber = 0)
    {
        // Change from original subscription policies. With new tiers, allow the few regular
        // subscribers (pro/enterprise) we have to just add anything. Free still has restrictions.
        if (! $this->hasSubscription() or $this->tier() == 'pro' or $this->tier() == 'enterprise') {
            return true;
        }

        // Should only be free user here
        switch ($type) {
            case 'mailbox':
                return $currentNumber < 3;

            break;
            case 'portal':
                return $currentNumber < 1;

            break;
            case 'user':
                return $currentNumber < 3;

            break;
        }
    }
}
