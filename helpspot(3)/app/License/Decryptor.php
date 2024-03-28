<?php

namespace HS\License;

use HS\Encryption\EncryptionInterface;

class Decryptor
{
    /**
     * Encryption Class.
     * @var \HS\Encryption\EncryptionInterface
     */
    protected $encrypter;

    /**
     * HelpSpot Security Key.
     * @var string
     */
    protected $applicationKey;

    /**
     * Create new license Decryptor.
     * @param EncryptionInterface $encrypter
     * @param $applicationKey
     */
    public function __construct(EncryptionInterface $encrypter, $applicationKey)
    {
        $this->applicationKey = $applicationKey;
        $this->encrypter = $encrypter;
    }

    /**
     * Decrypt and return new License.
     * @param $customerId
     * @param $encryptedAndEncodedLicense
     * @return License
     */
    public function getLicense($customerId, $encryptedAndEncodedLicense)
    {
        $encryptedLicense = base64_decode($encryptedAndEncodedLicense);
        $customerKey = $this->getCustomerKey($customerId);
        $plainTextLicense = $this->decryptLicense($customerKey, $encryptedLicense);

        $licenseArray = $this->getUnserializedLicense($plainTextLicense);

        return $this->mapLicenseArray($licenseArray);
    }

    /**
     * Encryption key for Customer License is the HelpSpot
     * application key concatenated with the Customer ID.
     * @param $customerId
     * @return string
     */
    protected function getCustomerKey($customerId)
    {
        return md5($this->applicationKey.$customerId);
    }

    /**
     * Decrypt the customer license
     * using the given customer key.
     * @param $customerKey
     * @param $license
     * @return string
     */
    protected function decryptLicense($customerKey, $license)
    {
        $this->encrypter->setKey($customerKey);

        return $this->encrypter->decrypt($license);
    }

    /**
     * Unserialize the license
     * Hopefully returning an array.
     * @param $plainTextLicense
     * @return array
     */
    protected function getUnserializedLicense($plainTextLicense)
    {
        $plainTextLicense = trim($plainTextLicense);

        if (is_string($plainTextLicense) && ! empty($plainTextLicense)) {
            try {
                return unserialize($plainTextLicense);
            } catch (\Exception $e) {
                return [];
            }
        }

        return [];
    }

    /**
     * Map a license array to a new License object.
     * @param $licenseArray
     * @return License
     */
    protected function mapLicenseArray($licenseArray)
    {
        $customerId = isset($licenseArray['CustomerID']) ? $licenseArray['CustomerID'] : '';
        $customerName = isset($licenseArray['CustomerName']) ? $licenseArray['CustomerName'] : '';
        $numberUsers = isset($licenseArray['Users']) ? $licenseArray['Users'] : 0;
        $hasSupportContract = isset($licenseArray['SupportContract']) ? $licenseArray['SupportContract'] : 0;
        $supportEndDate = isset($licenseArray['SupportEnds']) ? $licenseArray['SupportEnds'] : 0;
        $creationDate = isset($licenseArray['CreatedOn']) ? $licenseArray['CreatedOn'] : 0;
        $subscription = isset($licenseArray['Subscription']) ? $licenseArray['Subscription'] : null;
        $subscriptionTier = isset($licenseArray['SubscriptionTier']) ? $licenseArray['SubscriptionTier'] : null;

        $forStableVersion = true;
        if (isset($licenseArray['Beta']) && ! empty($licenseArray['Beta'])) {
            $forStableVersion = false;
        }

        return new License($customerId, $customerName, $numberUsers,
            $hasSupportContract, $supportEndDate,
            $creationDate,
            $forStableVersion,
            $subscription, $subscriptionTier);
    }
}
