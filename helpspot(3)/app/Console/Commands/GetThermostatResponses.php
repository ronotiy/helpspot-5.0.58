<?php

namespace HS\Console\Commands;

use HS\Request;
use HS\Response;
use HS\ThermoResponse;

use Carbon\Carbon;
use Illuminate\Console\Command;

class GetThermostatResponses extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'thermostat:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Thermostat for survey responses.';

    /**
     * Create a new command instance.
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
        $this->loadDependencies();

        $responses = pollThermostatResponses();

        if (! is_array($responses)) {
            return;
        }

        foreach ($responses as $response) {

            // Set xRequest depending on form of custom field saved
            $xRequest = isset($response->custom_fields->xrequest)
                ? $response->custom_fields->xrequest
                : null;

            $xRequest = isset($response->custom_fields->xRequest)
                ? $response->custom_fields->xRequest
                : $xRequest;

            // If we don't have an xRequest for some reason,
            // we can't record this response
            if (! $xRequest) {
                continue;
            }

            // Must be a valid request
            $request = Request::find($xRequest);
            if (! $request) {
                continue;
            }

            try {
                ThermoResponse::create([
                    'xSurvey' => $response->survey_id,
                    'xResponse' => $response->id,
                    'xRequest' => $xRequest,
                    'iScore' => $response->score,
                    'tFeedback' => $response->feedback,
                    'created_at' => new Carbon($response->created_at),
                    'updated_at' => new Carbon($response->updated_at),
                    'type' => $response->type,
                ]);
            } catch (\Exception $e) {
                //# This built up log files and database too quickly when tasks.php was run.
                // TODO: Find a way to record the first error and then ignore the others
                // This is likely and intended to swallow errors raised by having a
                // unique index - we only want one response per request
                // \HS\HelpSpot::app()->make('log')->error($e);
                // errorLog($e->getMessage(),'Database',__FILES__,__LINE__);
            }
        }
    }

    private function loadDependencies()
    {
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';
    }
}
