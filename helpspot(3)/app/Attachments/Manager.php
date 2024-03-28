<?php

namespace HS\Attachments;

use Illuminate\Database\DatabaseManager;

class Manager
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function connection($connection = null)
    {
        $connection = $this->db->connection($connection);
        $class = '\\HS\\Attachments\\'.ucwords($connection->getDriverName()).'Documents';

        return new $class($connection);
    }
}
