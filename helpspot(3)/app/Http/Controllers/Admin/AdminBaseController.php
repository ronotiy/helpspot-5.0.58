<?php

namespace HS\Http\Controllers\Admin;

use HS\Http\Requests;

use Illuminate\Http\Request;
use HS\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AdminBaseController extends Controller
{
    public function adminFileCalled()
    {
        // SAML auth ends up here on mobile redirects
        // Other auths are directed to mobile-auth route directly after login
        if (auth()->check() && Cookie::get('mobile-transact')) {
            return redirect()->route('mobile-auth');
        }

        if (Cookie::has('X-HelpSpot-Token')) {
            Cookie::queue(Cookie::forget('X-HelpSpot-Token'));
        }

        $response = require_once cBASEPATH.'/admin.php';

        if( ! $response ) {
            $response = '';
        }

        return ($response instanceof Response)
            ? $response
            : response($response, httpStatusCode(), contentTypeHeader());
    }

    public function sessionCheck()
    {
        if (! auth()->check()) {
            return response([
                'csrf' => csrf_token(),
            ], 200, ['X-HelpSpot-Session' => 'Expired']);
        }

        return response([
            'csrf' => csrf_token(),
        ], 200);
    }
}
