<?php

namespace HS\Install\Installer\Jobs;

use HS\Jobs\Job;

use HS\System\Features;
use HS\License\CheckLicense;
use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;
use Illuminate\Filesystem\Filesystem;
use HS\Install\Installer\InstallRepository;
use HS\Install\Installer\Actions\CheckAccount;

class Configure extends Job
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $licensePath;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $supportType;

    public function __construct(array $data, $licensePath, $language, $supportType = 'internal')
    {
        $this->data = $data;
        $this->licensePath = $licensePath;
        $this->language = $language;
        $this->supportType = $supportType;
    }

    /**
     * @param Features $features
     * @param CheckAccount $checker
     * @param InstallRepository $repository
     * @param CheckLicense $licenseCheck
     * @param Filesystem $filesystem
     * @return mixed
     * @throws ValidationException
     */
    public function handle(
        Features $features,
        CheckAccount $checker,
        InstallRepository $repository,
        CheckLicense $licenseCheck,
        Filesystem $filesystem
    ) {
        $errors = new MessageBag; // Fallback
        if (! $checker->isValid($this->data)) {
            $errors = $checker->getErrors();
        }

        if (! $errors->has('license')) {
            try {
                $license = $licenseCheck->getLicense(
                    $this->data['customerid'], $this->licensePath
                );
            } catch (ValidationException $e) {
                $errors->merge($e->getErrors());
            }
        }

        // If any errors, bail out
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        // Begin Install and Configure
        $repository->migrateAndSeed();
        $repository->setAutoIncrementStart();

        $data = $checker->getData();

        $repository->saveGlobals([
            'cHD_VERSION' => hs_setting('cHD_VERSION'),
            'cHD_NEWVERSION' => hs_setting('cHD_VERSION'),
            'cHD_NOTIFICATIONEMAILACCT' => $data['notemail'],
            'cHD_NOTIFICATIONEMAILNAME' => $data['notificationname'],
            'cHD_FORUMMAILACCT' => $data['notemail'],
            'cHD_FORUMMAILNAME' => $data['notificationname'],
            'cHD_ORGNAME' => $data['helpdeskname'],
            'cHD_LANG' => $this->language,
        ]);

        // SMTP items if Windows
        // Or if SMTP settings exist (check simply based on presence of host variable)
        if ($features->isWindows() || (isset($data['cHD_MAIL_SMTPHOST']) && ! empty($data['cHD_MAIL_SMTPHOST']))) {
            $smtp = [
                'cHD_MAIL_SMTPTIMEOUT' => 10,
                'cHD_MAIL_SMTPPROTOCOL' => $data['cHD_MAIL_SMTPPROTOCOL'], // Not validated, may be present
                'cHD_MAIL_SMTPAUTH' => 1,
                'cHD_MAIL_SMTPHOST' => $data['cHD_MAIL_SMTPHOST'],
                'cHD_MAIL_SMTPHELO' => '',
                'cHD_MAIL_SMTPUSER' => $data['cHD_MAIL_SMTPUSER'],
                'cHD_MAIL_SMTPPASS' => $data['cHD_MAIL_SMTPPASS'],
                'cHD_MAIL_SMTPPORT' => $data['cHD_MAIL_SMTPPORT'],
            ];

            $smtpvars = serialize($smtp);
            $repository->saveGlobal('cHD_MAIL_SMTPCONN', $smtpvars);
            $repository->saveGlobal('cHD_MAIL_OUTTYPE', 'smtp');
        }

        // TimeZone
        $repository->saveGlobal('cHD_TIMEZONE_OVERRIDE', $data['cHD_TIMEZONE_OVERRIDE']);

        // License - need encrypted string
        $encodedAndEncryptedLicense = $filesystem->get($this->licensePath);
        $repository->saveLicense($license, $encodedAndEncryptedLicense);

        // Init Global Constants
        // Remaining tasks requires them
        $repository->initializeGlobals();

        // Import relevant HS API includes
        $repository->importApi();

        // Add initial person
        $person = $repository->addUser($data['fname'], $data['lname'], $data['adminemail'], $data['adminpass']);

        // Ensure support type value is "internal" or "external" only
        $supportType = ($this->supportType === 'internal') ? 'internal' : 'external';

        // Add initial category
        $repository->addCategories($person, $supportType);

        // Record chosen supported type for possible future use
        $repository->addGlobal('cHD_SUPPORT_TYPE', $supportType);

        // Add Request and Portal Login
        $repository->addRequest();

        // Add filter (x2)
        $repository->addFilters();

        // Return data for view output to use
        $data['license'] = $license;

        return $data;
    }
}
