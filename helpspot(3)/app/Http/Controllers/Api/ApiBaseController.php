<?php

namespace HS\Http\Controllers\Api;

use HS\Http\Requests;

use Illuminate\Http\Request;
use HS\Http\Controllers\Controller;

class ApiBaseController extends Controller
{
    public function apiFileCalled()
    {
        $response = require_once cBASEPATH.'/api/index.php';

        return response($response, httpStatusCode(), contentTypeHeader());
    }

    public function status()
    {
        require_once cBASEPATH.'/helpspot/lib/class.api.base.php';
        require_once cBASEPATH.'/helpspot/lib/class.api.public.php';

        ob_start();
        new \api_public;
        $output = ob_get_contents();
        ob_end_clean();

        if (! empty($output)) {
            return response($output, 200, ['Content-Type' => 'text-xml']);
        }

        return response('<?xml version="1.0" encoding="UTF-8"?><reply>ok</reply>', 200, ['Content-Type' => 'text-xml']);
    }
}
