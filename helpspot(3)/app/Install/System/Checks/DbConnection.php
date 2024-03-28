<?php

namespace HS\Install\System\Checks;

use Illuminate\Database\DatabaseManager;
use HS\Install\System\SystemCheckInterface;

class DbConnection implements SystemCheckInterface
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * If system check is valid (OK).
     * @var bool
     */
    protected $valid;

    /**
     * Error.
     * @var string
     */
    protected $error;

    /**
     * Connection name.
     * @var null|string
     */
    protected $connection = null;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
        $this->connection = null;
    }

    /**
     * Set connection name.
     * @param string $connection
     * @return $this
     */
    public function with($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * This is required to pass.
     * @return bool
     */
    public function required()
    {
        return true;
    }

    /**
     * If we cannot connect to the DB
     * we get an exception here.
     * @return bool
     */
    public function valid()
    {
        try {
            $this->db->connection($this->connection);
            $this->valid = true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->valid = false;
        }

        return $this->valid;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}
