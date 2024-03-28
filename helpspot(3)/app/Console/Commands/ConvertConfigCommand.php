<?php

namespace HS\Console\Commands;

use HS\Cloud\IsHosted;
use Illuminate\Console\Command;
use Symfony\Component\Process\PhpProcess;

class ConvertConfigCommand extends Command
{
    use IsHosted;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:convert {--d|dump : Echo out the env file instead of creating (or overwriting) the .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert constants created in config.php to a .env file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $configPath = base_path('config.php');
        if (file_exists($configPath)) {
            $process = new PhpProcess(<<<EOF
<?php require_once('$configPath');
var_export(get_defined_constants(true)['user']);
EOF
);
            $process->run();

            if($process->isSuccessful()) {
                $constants = [];
                eval('$constants = ' . $process->getOutput() . ';');
            } else {
                $this->error('Error parsing config.php. Output: ['.$process->getOutput().$process->getErrorOutput().']');
            }

            // Map expected constants into .env file items
            $defaults = [
                'cHOST' => '',
                'cDEBUG' => '',
                'cDBTYPE' => '',
                'cDBHOSTNAME' => '',
                'cDBNAME' => '',
                'cDBUSERNAME' => '',
                'cDBPASSWORD' => '',
            ];

            $env = $this->buildEnv(array_merge($defaults, $constants));

            if ($this->option('dump')) {
                echo $env;
            } else {
                file_put_contents(base_path('.env'), $env);
            }
        }
    }

    protected function buildEnv($envVars)
    {
        extract($envVars);

        switch($cDBTYPE) {
            case 'mssql':
            case 'sqlsrv':
                $cDBTYPE = 'sqlsrv';
                $port = 1433;
                break;
            case 'mysqli':
            case 'mysql':
            default:
                $cDBTYPE = 'mysql';
                $port = 3306;
                break;
        }

        $cDEBUG = ($cDEBUG) ? 'true' : 'false';

        $queueConnection = ($this->isHosted()) ? 'redis' : 'database';

        $env = <<<ENV
APP_DEBUG=$cDEBUG
APP_URL=$cHOST
APP_KEY=

DB_CONNECTION=$cDBTYPE
DB_HOST=$cDBHOSTNAME
DB_PORT=$port
DB_DATABASE="$cDBNAME"
DB_USERNAME="$cDBUSERNAME"
DB_PASSWORD="$cDBPASSWORD"

QUEUE_CONNECTION=$queueConnection
ENV;

        return $env;
    }
}
