<?php

namespace HS\Domain\Workspace\Jobs;

use Illuminate\Bus\Queueable;

/**
 * Delete Trash that has reached
 * it's defined expiration
 * Class DeleteTrash.
 */
class DeleteTrash
{
    use Queueable;

    /**
     * Create a new job instance.
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

        if (hs_setting('cHD_DAYS_TO_LEAVE_TRASH') > 0) {
            $trash = $GLOBALS['DB']->Execute('SELECT xRequest FROM HS_Request WHERE fTrash = 1 AND dtGMTTrashed < ?', [strtotime('-'.hs_setting('cHD_DAYS_TO_LEAVE_TRASH').' Day')]);

            if (hs_rscheck($trash)) {
                while ($row = $trash->FetchRow()) {
                    apiDeleteRequest($row['xRequest']);
                    logMsg('DELETED from trash: '.$row['xRequest']);
                }
            }
        }
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
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.language.php';
        ob_clean();
    }
}
