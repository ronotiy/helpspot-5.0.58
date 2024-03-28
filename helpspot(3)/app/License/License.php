<?php

namespace HS\License;

use DateTime;
use HS\Base\Gettable;
use HS\Cloud\IsHosted;

class License
{
    use Gettable, IsHosted;

    /**
     * Customer ID.
     * @var string
     */
    protected $customerId;

    /**
     * Customer Name.
     * @var string
     */
    protected $customerName;

    /**
     * Number of users licensed.
     * @var int
     */
    protected $numberUsers;

    /**
     * If the license has a support contract
     * All licenses come with first year of support
     * This tells if they've purchased extra support.
     * @var bool
     */
    protected $hasSupportContract;

    /**
     * Support End Date.
     * @var \DateTime
     */
    protected $supportEndDate;

    /**
     * License Creation Date.
     * @var \DateTime
     */
    protected $creationDate;

    /**
     * If this is for a stable version
     * of HelpSpot. False if for a Beta.
     * @var bool
     */
    protected $forStableVersion;

    /**
     * If the license is for a subscription purchase.
     *
     * @var mixed
     */
    protected $subscription;

    /**
     * The subscription plan (pro, enterprise, etc).
     *
     * @var string
     */
    protected $subscriptionTier;

    /**
     * Create new License.
     * @param $customerId
     * @param $customerName
     * @param $numberUsers
     * @param $hasSupportContract
     * @param $supportEndDate
     * @param $creationDate
     * @param $forStableVersion
     * @param $subscription
     * @param $subscriptionTier
     * @throws LicenseException
     */
    public function __construct($customerId, $customerName, $numberUsers,
                                $hasSupportContract, $supportEndDate,
                                $creationDate, $forStableVersion,
                                $subscription, $subscriptionTier = false)
    {
        $this->setCustomerId($customerId);
        $this->customerName = $customerName;
        $this->setNumberUsers($numberUsers);
        $this->hasSupportContract = ($hasSupportContract) ? true : false;
        $this->setSupportEndDate($supportEndDate);
        $this->setCreationDate($creationDate);
        $this->forStableVersion = ($forStableVersion) ? true : false;
        $this->subscription = $subscription;
        $this->subscriptionTier = $subscriptionTier;
    }

    /**
     * Test if the license is currently supported.
     * @return bool
     */
    public function isCustomerSupported()
    {
        if ($this->isHosted()) {
            return true;
        } // if they are hosted then assume they are always supported.
        return  $this->todayIsBefore($this->supportEndDate);
    }

    /**
     * Set customer id, throwing an error if
     * it doesn't meet required needs.
     * @param $customerId
     * @throws LicenseException
     */
    protected function setCustomerId($customerId)
    {
        if (empty($customerId)) {
            throw new LicenseException('Customer ID must be set and non-empty');
        }

        $this->customerId = $customerId;
    }

    /**
     * Set number of users, throwing an error
     * if it's less than 1 or not a number.
     * @param $numberUsers
     * @throws LicenseException
     */
    protected function setNumberUsers($numberUsers)
    {
        //if( $numberUsers !== 'unlimited' && ! is_numeric($numberUsers) or $numberUsers < 1)
        if (! is_numeric($numberUsers) && $numberUsers !== 'unlimited') {
            throw new LicenseException(sprintf('Number of users must "unlimited" or an integer. Value given: %s', $numberUsers));
        }

        if (is_numeric($numberUsers) && $numberUsers < 1) {
            throw new LicenseException(sprintf('Invalid number of licenses. Value given: %s', $numberUsers));
        }

        $this->numberUsers = $numberUsers;
    }

    /**
     * Set license support end date.
     * @throws LicenseException
     */
    protected function setSupportEndDate($supportEndDate)
    {
        $supportEndDateParsed = $this->returnDateTime($supportEndDate);

        if ($supportEndDateParsed instanceof DateTime === false) {
            throw new LicenseException('Support End Date must be instance of DateTime or a Timestamp integer.');
        }

        if ($supportEndDateParsed->getTimestamp() == 0) {
            throw new LicenseException('Support End Date must be a valid date');
        }

        $this->supportEndDate = $supportEndDateParsed;
    }

    /**
     * Set license Creation Date.
     * @param $creationDate
     * @throws LicenseException
     */
    protected function setCreationDate($creationDate)
    {
        $createDateParsed = $this->returnDateTime($creationDate);

        if ($createDateParsed instanceof DateTime === false) {
            throw new LicenseException('Create Date must be instance of DateTime or a Timestamp integer.');
        }

        if ($createDateParsed->getTimestamp() == 0) {
            throw new LicenseException('Creation Date must be a valid date');
        }

        $this->creationDate = $createDateParsed;
    }

    /**
     * Determine if is already a DateTime object
     * or convert Timestamp to DateTime object.
     * @param $datetime
     * @return bool|DateTime
     */
    protected function returnDateTime($datetime)
    {
        if ($datetime instanceof DateTime) {
            return $datetime;
        }

        if (is_numeric($datetime)) {
            return DateTime::createFromFormat('U', $datetime);
        }

        return false;
    }

    /**
     * Test if today (now) is before the given date
     * Does not require second, minute, or even hourly precision.
     * @param DateTime $datetime
     * @return bool
     * @throws \Exception
     */
    protected function todayIsBefore(DateTime $datetime)
    {
        $now = new DateTime;

        return $now <= $datetime;
    }
}
