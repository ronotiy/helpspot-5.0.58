<?php

namespace HS\Console\Commands;

use HS\User;
use Carbon\Carbon;
use HS\Mail\SendFrom;
use HS\Jobs\SendMessage;
use Illuminate\Console\Command;
use HS\Mail\Mailer\MessageBuilder;
use HS\Domain\Reports\SavedReports;

class EmailReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email any scheduled reports';

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
     * @throws \Throwable
     */
    public function handle()
    {
        $reports = SavedReports::where('dtSendsAt', '<', Carbon::now())->where('fEmail', 1)->get();
        foreach ($reports as $report) {
            if ($subscribed = $this->getToAddresses($report->fSendToStaff)) {
                foreach ($subscribed as $to) {
                    $this->send($report, $to->sEmail);
                }
            }
            if ($report->fSendToExternal) {
                $tos = explode(',', $report->fSendToExternal);
                foreach ($tos as $email) {
                    $this->send($report, trim($email));
                }
            }
            // update the sends at to the next time.
            $report->dtSendsAt = $report->calculateNextSendDate();
            $report->save();
        }
    }

    /**
     * @param SavedReports $report
     * @param $email
     * @throws \Throwable
     */
    protected function send(SavedReports $report, $email)
    {
        $message = (new MessageBuilder(SendFrom::default()))
            ->to($email)
            ->setSubject($report->sReport)
            ->setBodyHtml(view('mail.report', $this->buildBody($report))->render());

        SendMessage::dispatch($message)
            ->onQueue(config('queue.high_priority_queue')); // mail.private
    }

    /**
     * @param $staff
     * @return mixed
     */
    protected function getToAddresses($staff)
    {
        $people = explode(',', $staff);
        return User::whereIn('xPerson', $people)->get();
    }

    /**
     * Build the message.
     *
     * @param SavedReports $report
     * @return array
     */
    public function buildBody(SavedReports $report)
    {
        error_reporting(E_ERROR | E_PARSE);
        require_once cBASEPATH.'/helpspot/lib/class.reports.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.conditional.ui.php';
        require_once cBASEPATH.'/helpspot/lib/class.business_hours.php';
        require_once cBASEPATH.'/helpspot/lib/display.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.hscsv.php';
        require_once cBASEPATH.'/helpspot/lib/class.filter.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        include_once cBASEPATH.'/helpspot/lib/class.language.php';

        $GLOBALS['lang'] = new \language('reports');
        $GLOBALS['lang']->load(['reports', 'conditional.ui']);

        $data = hs_unserialize($report->tData);
        $data['sReport'] = $report->sReport;
        $data['speedby'] = 'hour';
        $data['grouping'] = false;
        $range = $report->calculateDateRange($data['sDataRange']);
        $data['from'] = $range['start'];
        $data['to'] = $range['end'];
        $reportData = new \reports($data);
        $reportData->create_csv($report->sShow);
        $data = str_getcsv($reportData->csv->filestr, "\n");

        return [
            'report' => $report,
            'tableData' => $data,
        ];
    }
}
