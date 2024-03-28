<?php

namespace HS\Install\Installer\Handlers;

use HS\System\Features;
use HS\License\CheckLicense;
use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;
use HS\CommandBus\HandlerInterface;
use Illuminate\Filesystem\Filesystem;
use HS\Install\Installer\InstallRepository;
use HS\Install\Installer\Actions\CheckAccount;

class ConfigureHandler implements HandlerInterface
{
    /**
     * @var \HS\System\Features
     */
    private $features;

    /**
     * @var \HS\Install\Installer\Actions\CheckAccount
     */
    private $checker;

    /**
     * @var \HS\Install\Installer\InstallRepository
     */
    private $repository;

    /**
     * @var \HS\License\CheckLicense
     */
    private $licenseCheck;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(
        Features $features,
        CheckAccount $checker,
        InstallRepository $repository,
        CheckLicense $licenseCheck,
        Filesystem $filesystem
    ) {
        $this->features = $features;
        $this->checker = $checker;
        $this->repository = $repository;
        $this->licenseCheck = $licenseCheck;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $command
     * @throws \HS\Base\ValidationException
     * @return array $data  Parsed data, including License object
     */
    public function handle($command)
    {
        $errors = new MessageBag; // Fallback
        if (! $this->checker->isValid($command->data)) {
            $errors = $this->checker->getErrors();
        }

        if (! $errors->has('license')) {
            try {
                $license = $this->licenseCheck->getLicense(
                    $command->data['customerid'], $command->licensePath
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
        $this->repository->migrateAndSeed();
        $this->repository->setAutoIncrementStart();

        $data = $this->checker->getData();

        $this->repository->saveGlobals([
            'cHD_VERSION' => hs_setting('cHD_VERSION'),
            'cHD_NEWVERSION' => hs_setting('cHD_VERSION'),
            'cHD_NOTIFICATIONEMAILACCT' => $data['notemail'],
            'cHD_NOTIFICATIONEMAILNAME' => $data['notificationname'],
            'cHD_FORUMMAILACCT' => $data['notemail'],
            'cHD_FORUMMAILNAME' => $data['notificationname'],
            'cHD_ORGNAME' => $data['helpdeskname'],
            'cHD_LANG' => $command->language,
        ]);

        // SMTP items if Windows
        // Or if SMTP settings exist (check simply based on presence of host variable)
        if ($this->features->isWindows() || (isset($data['cHD_MAIL_SMTPHOST']) && ! empty($data['cHD_MAIL_SMTPHOST']))) {
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
            $this->repository->saveGlobal('cHD_MAIL_SMTPCONN', $smtpvars);
            $this->repository->saveGlobal('cHD_MAIL_OUTTYPE', 'smtp');
        }

        // TimeZone
        $this->repository->saveGlobal('cHD_TIMEZONE_OVERRIDE', $data['cHD_TIMEZONE_OVERRIDE']);

        // License - need encrypted string
        $encodedAndEncryptedLicense = $this->filesystem->get($command->licensePath);
        $this->repository->saveLicense($license, $encodedAndEncryptedLicense);

        // Init Global Constants
        // Remaining tasks requires them
        $this->repository->initializeGlobals();

        // Import relevant HS API includes
        $this->repository->importApi();

        // Add initial person
        $person = $this->repository->addUser($data['fname'], $data['lname'], $data['adminemail'], $data['adminpass']);

        // Ensure support type value is "internal" or "external" only
        $supportType = ($command->supportType === 'internal') ? 'internal' : 'external';

        // Add initial category
        $this->repository->addCategories($person, $supportType);

        // Record chosen supported type for possible future use
        $this->repository->addGlobal('cHD_SUPPORT_TYPE', $supportType);

        // Add Request and Portal Login
        $this->repository->addRequest();

        // Add filter (x2)
        $this->repository->addFilters();

        // Return data for view output to use
        $data['license'] = $license;

        return $data;
    }
}
