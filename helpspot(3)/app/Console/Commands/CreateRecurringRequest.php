<?php

namespace HS\Console\Commands;

use Carbon\Carbon;
use HS\Response;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateRecurringRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create recurring request';

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
     */
    public function handle()
    {
        $this->loadTheWorld();
        $responses = Response::where('fDeleted', 0)->where('fRecurringRequest', 1)->where('dtSendsAt', '<', Carbon::now())->get();
        foreach ($responses as $response) {
            $result = $this->createRequest($response);
            if (isset($result['fb'])) {
                // all worked
                $response->dtSendsAt = $response->calculateNextSendDate();
                $response->save();
            } elseif (isset($result['errorBoxText'])) {
                // something failed.
                \Illuminate\Support\Facades\Log::error($result['errorBoxText']);
            }
        }
    }

    /**
     * @param Response $response
     * @return array
     */
    protected function createRequest(Response $response)
    {
        global $user;
        $user = [];
        $user['xPerson'] = 0;
        $user['sFname'] = '';
        $user['sLname'] = '';
        $user['sEmail'] = '';
        $user['tSignature'] = '';
        $options = json_decode($response->tResponseOptions);
        $reqid = '';
        $fm = $this->buildFmArray($options);
        $fm['tBody'] = $response->tResponse;
        $fm['skipCustomChecks'] = 1;
        $fm = $this->generateMissingCustomFields($fm);

        if (isset($options->attachment) && $options->attachment) {
            foreach ($options->attachment as $id) {
                $fm['reattach'][] = $id;
                $document = apiGetDocument($id);
                if ($this->isImage($document['sFileMimeType'])) {
                    $fm['tBody'] .= "\r\n##". lg_inline_image.' ('.$document['sFilename'].')##';
                }
            }
        }

        return apiProcessRequest($reqid, $fm, $_FILES, __FILE__, __LINE__);
    }

    /**
     * @param $opts
     * @return array
     */
    protected function buildFmArray($opts)
    {
        $f = [];
        foreach ($opts as $key => $val) {
            $f[$key] = $val;
        }
        return array_merge($f, [
            'xPersonAssignedTo' => $opts->xPersonAssignedTo,
            'sEmail' => $opts->sEmail,
            'xCategory' => $opts->xCategory,
            'xStatus' => $opts->xStatus,
            'fPublic' => $opts->fPublic,
            'sTitle' => $opts->sTitle,
            'emailfrom' => $this->getEmailFrom($opts->emailfrom),
            'ccstaff' => [],
            'emailtogroup' => $opts->togroup,
            'emailccgroup' => $opts->ccgroup,
            'emailbccgroup' => $opts->bccgroup,
            'sRequestPassword' => '',
            'note_is_markdown' => 1,
            'fNoteIsHTML' => 1,
            'xPersonOpenedBy' => 1,
            'sUserId' => $opts->sUserId,
            'sFirstName' => $opts->sFirstName,
            'sLastName' => $opts->sLastName,
            'sPhone' => $opts->sPhone,
            'fUrgent' => '',
            'dtGMTOpened' => '',
            'sub_create' => 1,
            'fOpenedVia' => 1,
            'fOpen' => 1,
            'external_note' => '',
        ]);
    }

    /**
     * Sometimes people might create a response and then later add custom fields. Instead
     * of erroring we set those Custom$x values to an empty string.
     *
     * @param $fm array of request data.
     * @return array
     */
    protected function generateMissingCustomFields(array $fm)
    {
        foreach ($GLOBALS['customFields'] as $field) {
            $id = $field['xCustomField'];
            $fm = Arr::add($fm, 'Custom'.$id, null);
        }
        return $fm;
    }

    /**
     * Get the email from address
     * @param $emailfrom
     * @return mixed|string
     */
    protected function getEmailFrom($emailfrom)
    {
        if ($emailfrom == "") {
            // use the default
            $emailfrom = hs_setting('cHD_NOTIFICATIONEMAILNAME').'*'.hs_setting('cHD_NOTIFICATIONEMAILACCT').'*0"';
        }
        return $emailfrom;
    }

    /**
     * @param $sFileMimeType
     * @return bool
     */
    protected function isImage($sFileMimeType)
    {
        return in_array($sFileMimeType, ['image/png', 'image/gif', 'image/jpeg', 'image/pjpeg']);
    }

    /**
     * Load all the required files.
     */
    protected function loadTheWorld()
    {
        require_once cBASEPATH . '/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH . '/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH . '/helpspot/lib/api.hdcategories.lib.php';
        require_once cBASEPATH . '/helpspot/lib/api.mailboxes.lib.php';
        require_once cBASEPATH . '/helpspot/lib/api.kb.lib.php';
        require_once cBASEPATH . '/helpspot/lib/class.requestupdate.php';
        require_once cBASEPATH . '/helpspot/lib/api.thermostat.lib.php';
        require_once cBASEPATH . '/helpspot/lib/display.lib.php';
        require_once cBASEPATH . '/helpspot/lib/class.notify.php';
        require_once cBASEPATH . '/helpspot/lib/lookup.lib.php';
        require_once cBASEPATH . '/helpspot/lib/phpass/PasswordHash.php';
    }
}
