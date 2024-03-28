<?php

namespace HS\Domain\Workspace\Jobs;

use HS\Mail\SendFrom;
use HS\Jobs\SendMessage;
use HS\Mail\Mailer\MessageBuilder;

use Illuminate\Bus\Queueable;

/**
 * Check & send Reminders due
 * Class CheckReminders.
 */
class CheckReminders
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

        $reminders = apiGetAllCurrentReminders(date('U'));

        if (is_object($reminders) && $reminders->RecordCount() > 0) {
            $allStaff = apiGetAllUsersComplete();
            $catlist = [];
            $cats = apiGetAllCategoriesComplete();
            while ($cat = $cats->FetchRow()) {
                $catlist[$cat['xCategory']] = $cat['sCategory'];
            }
            $catlist[0] = lg_inbox;

            while ($r = $reminders->FetchRow()) {
                $req = apiGetRequest($r['xRequest']);

                $tos = [];
                if ($r['fNotifyEmail'] == 1 || ($r['fNotifyEmail'] == 0 && $r['fNotifyEmail2'] == 0)) {
                    $tos[] = $r['sEmail'];
                }

                if ($r['fNotifyEmail2'] == 1) {
                    $tos[] = $r['sEmail2'];
                }

                $vars = getPlaceholders([
                    'email_subject' => lg_mailsub_reminder,
                    'tracking_id' => '{'.utf8_trim(hs_setting('cHD_EMAILPREFIX')).$req['xRequest'].'}',
                    'requestdetails' => renderRequestTextHeader($req, $allStaff, $catlist, 'text'),
                    'requestdetails_html' => renderRequestTextHeader($req, $allStaff, $catlist, 'html'),
                ], $req);

                $body = nl2br($r['tReminder']);

                $messageBuilder = (new MessageBuilder(SendFrom::default(), $r['xRequest']))
                    ->to($tos)
                    ->subject('reminder', $vars)
                    ->body('reminder', $body, $vars);

                SendMessage::dispatch($messageBuilder)
                    ->onQueue(config('queue.high_priority_queue')); // mail.private

                //delete even if mail not sent, so that emails aren't sent forever
                apiDeleteReminder($r['xReminder']);
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
        require_once cBASEPATH.'/helpspot/lib/display.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.language.php';
        ob_clean();
    }
}
