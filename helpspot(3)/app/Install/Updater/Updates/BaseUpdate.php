<?php

namespace HS\Install\Updater\Updates;

use Illuminate\Database\DatabaseManager;

abstract class BaseUpdate implements UpdateInterface
{
    protected $version;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * @var array
     */
    protected $migrations = [];

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    abstract public function run();

    public function getVersion()
    {
        return $this->version;
    }
}
