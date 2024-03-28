<?php

namespace HS\Console\Commands;

use HS\Domain\Workspace\Event;
use Illuminate\Console\Command;
use HS\Domain\CustomFields\CustomField;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RequestLogCsvCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'report:logs {--d|from-date=-30 days : Date to generate report from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CSV export of the request events log.';

    protected $fieldToHuman = [
        'xCategory' => 'category',
        'xStatus' => 'status',
        'xPersonAssignedTo' => 'assigned to',
        'fOpen' => 'open',
        'sTitle' => 'subject',
        'sLastName' => 'last name',
        'sFirstName' => 'first name',
        'sEmail' => 'email',
        'fUrgent' => 'urgent',
        'fTrash' => 'trashed',
        'sUserId' => 'customer id',
        'sPhone' => 'phone number',
    ];

    protected $customFields = null;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * Include HS requirements.
         */
        require_once cBASEPATH.'/helpspot/lib/class.hscsv.php';
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';

        /**
         * Prep stderr vs stdout.
         */
        $stdErr = $this->getOutput()->getErrorStyle();

        /*
         * Parse given --from-time option,
         * returning an error if we cannot parse it
         */
        try {
            // Grab from the beginning of given date to capture the full day
            $timestamp = strtotime($this->option('from-date'));
            if ($timestamp === false) {
                throw new \Exception('Time "'.$this->option('from-date').'" invalid');
            }

            $fromDate = new \DateTime(date('Y-m-d 00:00:00', strtotime($this->option('from-date'))));
        } catch (\Exception $e) {
            $error = sprintf('"From date" could not be parsed. Error: %s', $e->getMessage());
            // Ensure we write to stderr and return status code 1
            $stdErr->writeln("<error>$error</error>");

            return 1;
        }

        /**
         * Begin parsing/munging data for CSV export
         * Print them out as CSV.
         */
        $csvFileName = sprintf('request_log_%s', $fromDate->format('Y-m-d'));
        $safeFileName = str_replace([':', '/'], ' ', $csvFileName).'.csv';

        $fp = fopen(cBASEPATH.'/data/documents/'.$safeFileName, 'w');

        $headers = [
            'Request ID',
            'History ID',
            'Person ID',
            'Database Field Changed', // database field
            'Field Changed',          // human-readable field
            'Event Date',
            'Time in State',
            'Value',
            'Database Value',
            'Description',
        ];

        fputcsv($fp, $headers);

        /**
         * Grab events from the given date to "now"
         * Chunking each 1000 rows to save memory.
         */
        $events = Event::where('dtLogged', '>=', $fromDate->getTimestamp())
            ->orderBy('xRequest', 'asc')
            ->orderBy('xRequestHistory', 'asc')
            ->chunk(1000, function ($events) use ($fp) {
                // Write rows
                foreach ($events as $event) {
                    if (strrpos($event->sColumn, 'Custom') === 0) {
                        $fieldName = $this->humanReadableCustomField($event->sColumn);
                    } else {
                        $fieldName = (isset($this->fieldToHuman[$event->sColumn])) ? $this->fieldToHuman[$event->sColumn] : '';
                    }

                    fputcsv($fp, [
                        $event->xRequest,
                        $event->xRequestHistory,
                        $event->xPerson,
                        $event->sColumn,
                        $fieldName,
                        (new \DateTime)->setTimestamp($event->dtLogged)->format('Y-m-d H:i:s'),
                        0, // todo: calculate
                        $event->sLabel,
                        reset(array_filter([$event->sValue, $event->iValue, $event->dValue])),
                        $event->sDescription,
                    ]);
                }
            });

        fclose($fp);

        $this->info('Complete! File located at data/documents/'.$safeFileName);

        if (config('app.debug') && function_exists('xdebug_peak_memory_usage')) {
            $this->info("\nMemory Usage: ".round(xdebug_peak_memory_usage() / 1048576, 2).'MB');
        }
    }

    private function humanReadableCustomField($sColumn)
    {
        if (is_null($this->customFields)) {
            $this->customFields = CustomField::all();
        }

        $fieldId = str_replace('Custom', '', $sColumn);

        foreach ($this->customFields as $customField) {
            if ($customField->xCustomField == $fieldId) {
                return $customField->fieldName;
            }
        }

        return $sColumn;
    }
}
