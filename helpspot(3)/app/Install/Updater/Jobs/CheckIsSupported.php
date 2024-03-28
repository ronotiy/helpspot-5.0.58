<?php

namespace HS\Install\Updater\Jobs;

use HS\License\Decryptor;
use Illuminate\Bus\Queueable;
use HS\Base\ValidationException;
use Illuminate\Support\MessageBag;

use HS\Install\Updater\UpdateRepository;

class CheckIsSupported
{
    use Queueable;

    /**
     * @var null|string
     */
    private $db_connection;

    /**
     * Create a new job instance.
     *
     * @param null $connection
     */
    public function __construct($connection = null)
    {
        $this->db_connection = $connection;
    }

    /**
     * @param UpdateRepository $updateRepository
     * @param Decryptor $decryptor
     * @return mixed
     * @throws ValidationException
     */
    public function handle(UpdateRepository $updateRepository, Decryptor $decryptor)
    {
        try {
            $customerId = $updateRepository->getGlobal('cHD_CUSTOMER_ID', $this->db_connection);
            $rawLicense = $updateRepository->getGlobal('cHD_LICENSE', $this->db_connection);

            $license = $decryptor->getLicense($customerId, $rawLicense);
        } catch (\Exception $e) {
            throw new ValidationException(
                new MessageBag(['license' => 'Current license is invalid']),
                'Current license is invalid'
            );
        }

        if (! $license->isCustomerSupported()) {
            throw new ValidationException(
                new MessageBag(['license' => 'Current license is expired']),
                'Current license is expired'
            );
        }

        return $license;
    }
}
