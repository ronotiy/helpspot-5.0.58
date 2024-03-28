<?php

namespace HS\Install\System\Checks;

use Illuminate\Database\DatabaseManager;
use HS\Install\System\SystemCheckInterface;

class MysqlVersion implements SystemCheckInterface
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

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
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
        // if we aren't using mysql then just pretend it works.
        if (config('database.default') != 'mysql') {
            return true;
        }

        if (is_bool($this->valid)) {
            return $this->valid;
        }

        try {
            $result = $this->db->select("SHOW VARIABLES WHERE Variable_name = 'version';");
            $result = $result[0];

            // TODO: Percona check?
            $isMaria = false;

            if (strpos($result->Value, 'MariaDB') !== false) {
                $isMaria = true;
            }

            //because of full text indexing we now need at least 5.6
            $this->valid = (version_compare($result->Value, '5.6', '>=') || $isMaria);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->valid = false;
        }

        if ($this->valid === false) {
            $this->error = 'MySQL is not at least 5.6';
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
