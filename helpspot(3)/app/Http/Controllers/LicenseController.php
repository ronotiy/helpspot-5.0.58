<?php

namespace HS\Http\Controllers;

use language;
use usLicense;

use HS\User;
use Facades\HS\Cache\Manager;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LicenseController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'license' => ['required', 'file'],
        ]);

        if (! defined('IN_PORTAL')) {
            define('IN_PORTAL', false);
        }
        require_once cBASEPATH . '/helpspot/lib/util.lib.php';
        require_once cBASEPATH . '/helpspot/lib/class.license.php';
        include_once cBASEPATH.'/helpspot/lib/class.language.php';
        new language; // Load lg.general.php

        $license = (new usLicense(hs_setting('cHD_CUSTOMER_ID'), $request->license->get(), hs_setting('SSKEY')))
            ->getLicense();

        if ($license) {
            storeGlobalVar('cHD_LICENSE', $request->license->get());
            $this->ensureAtOrUnderLicenseUserLimit($license);

            return redirect()->route('admin');
        }

        throw ValidationException::withMessages([
            'license' => lg_licnotvalid,
        ]);
    }

    private function ensureAtOrUnderLicenseUserLimit($license)
    {
        if (trim($license['Users']) == 'unlimited') {
            return;
        }

        $activestaff = User::active()
            ->where('xPerson', '<>', auth()->user()->xPerson)
            ->orderBy('fUserType', 'desc') // Prefer non-admins
            ->orderBy('xPerson', 'desc') // Prefer newer users
            ->get();

        $activeStaffIds = $activestaff->modelKeys();

        // Add one to offset that current user is not in results
        $numberUsersToDeactive = ($activestaff->count()+1) - $license['Users'];

        if ($numberUsersToDeactive > 0) {
            $usersToDeactivate = array_slice($activeStaffIds, 0, $numberUsersToDeactive);

            User::whereIn('xPerson', $usersToDeactivate)
                ->update(['fDeleted' => 1]);

            Manager::forgetGroup('users');
        }
    }
}
