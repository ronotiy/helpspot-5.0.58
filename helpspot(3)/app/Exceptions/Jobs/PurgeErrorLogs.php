<?php

namespace HS\Exceptions\Jobs;

use Illuminate\Bus\Queueable;

class PurgeErrorLogs
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->load();

        return $GLOBALS['DB']->Execute('DELETE FROM HS_Errors WHERE dtErrorDate < ?', [strtotime('-60 days', time())]);
    }

    protected function load()
    {
        ob_start();
        /*****************************************
        INCLUDE PATH
         *****************************************/
        set_include_path(cBASEPATH.'/helpspot/pear');

        /*****************************************
        INCLUDE LIBS
         *****************************************/
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        ob_clean();
    }
}
