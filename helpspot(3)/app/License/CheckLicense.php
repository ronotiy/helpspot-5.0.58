<?php

namespace HS\License;

use ErrorException;
use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;
use Illuminate\Filesystem\Filesystem;

class CheckLicense
{
    /**
     * @var \HS\License\Decryptor
     */
    protected $decrypter;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(Decryptor $decrypter, Filesystem $filesystem)
    {
        $this->decrypter = $decrypter;
        $this->filesystem = $filesystem;
    }

    /**
     * Get license, and check its validity (sorta implicitly).
     *
     * @param $customerId
     * @param $uploadedLicense     License File Path
     * @return \HS\License\License
     * @throws \HS\Base\ValidationException
     */
    public function getLicense($customerId, $uploadedLicense)
    {
        try {
            $rawLicense = $this->getRawLicense($uploadedLicense);

            return $this->decrypter->getLicense($customerId, $rawLicense);
        } catch (\ErrorException $e) {
            // This is usually because user's don't have a timezone set in php.ini
            $timeZoneMessage = 'Error in validating License:
                Please ensure the date.timezone setting is configured in the '.php_ini_loaded_file().' file.
                Popular settings are UTC or America/New_York.
                Valid timezones are available here: http://php.net/manual/en/timezones.php';

            throw new ValidationException(
                new MessageBag(['license' => $timeZoneMessage]),
                $timeZoneMessage
            );
        } catch (LicenseException $e) {
            throw new ValidationException(
                new MessageBag(['license' => 'Uploaded license is invalid']),
                'Uploaded license is invalid'
            );
        }
    }

    /**
     * Get raw license from uploaded file path.
     * @param $licenseFile
     * @return string
     */
    public function getRawLicense($licenseFile)
    {
        return $this->filesystem->get($licenseFile);
    }
}
