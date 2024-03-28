<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
 * GET THERMOSTAT RESULTS FOR REQUEST
 *****************************************
 * @param $xRequest
 * @return false
 */
function apiGetThermostatResponse($xRequest)
{
    $result = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Thermostat WHERE xRequest = ?', [$xRequest]);

    return ($result) ? $result : false;
}

/******************************************
GENERALIZE SCORE TO NPS LABEL
 ******************************************/
function apiGetResponseType($score, $type = 'nps')
{
    if ($type == 'csat') {
        if ($score <= 3) {
            return lg_conditional_at_thermostat_detractor;
        }
        if ($score >= 4) {
            return lg_conditional_at_thermostat_promoter;
        }
    } else {
        if ($score <= 6) {
            return lg_conditional_at_thermostat_detractor;
        }
        if ($score == 7 || $score == 8) {
            return lg_conditional_at_thermostat_passive;
        }
        if ($score >= 9) {
            return lg_conditional_at_thermostat_promoter;
        }
    }
}

/******************************************
GET THERMOSTAT API TOKEN
 ******************************************/
function apiGetThermostatToken()
{
    return hs_setting('cHD_THERMOSTAT_TOKEN', '');
}

/******************************************
SET THERMOSTAT API TOKEN
 ******************************************/
function apiSetThermostatToken($token)
{
    $apitoken = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Settings WHERE sSetting = ?', ['cHD_THERMOSTAT_TOKEN']);

    if ($apitoken) {
        // cHD_THERMOSTAT_TOKEN exists in the settings table, so update it
        return storeGlobalVar('cHD_THERMOSTAT_TOKEN', $token);
    }

    $result = $GLOBALS['DB']->Execute('INSERT INTO HS_Settings (sSetting, tValue) VALUES (?, ?)', ['cHD_THERMOSTAT_TOKEN', $token]);
    \Facades\HS\Cache\Manager::forget(\HS\Cache\Manager::CACHE_SETTINGS_KEY);

    return $result;
}

/******************************************
GET THERMOSTAT "SINCE" DATE
 ******************************************/
function apiGetThermostatSince()
{
    return hs_setting('cHD_THERMOSTAT_SINCE', '');
}

/******************************************
SET THERMOSTAT "SINCE" DATE
 ******************************************/
function apiSetThermostatSince($since)
{
    if (! is_numeric($since)) {
        $since = strtotime($since);
    }

    $sinceExists = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Settings WHERE sSetting = ?', ['cHD_THERMOSTAT_SINCE']);

    if ($sinceExists) {
        // cHD_THERMOSTAT_TOKEN exists in the settings table, so update it
        return storeGlobalVar('cHD_THERMOSTAT_SINCE', $since);
    }

    $result = $GLOBALS['DB']->Execute('INSERT INTO HS_Settings (sSetting, tValue) VALUES (?, ?)', ['cHD_THERMOSTAT_SINCE', $since]);
    \Facades\HS\Cache\Manager::forget(\HS\Cache\Manager::CACHE_SETTINGS_KEY);

    return $result;
}

/******************************************
RETRIEVE USER SURVEYS
 ******************************************/
function getThermostatSurveys()
{
    $token = apiGetThermostatToken();
    if (! $token) {
        // Don't perform an action if we don't have an API token
        return;
    }

    $url = sprintf('%s/api/surveys', getThermostatBaseUrl());

    try {
        $client = new GuzzleHttp\Client;
        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody());
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Failed to send request to thermo
        errorLog($e->getMessage().'::'.$e->getResponse(), 'Integration', __FILE__, __LINE__);
        \Illuminate\Support\Facades\Log::error($e);
    }
}

/******************************************
CALL THERMOSTAT API TOKEN
 ******************************************/
function sendThermostatSurvey($req, $surveyId)
{
    $token = apiGetThermostatToken();
    if (! $token || empty($req['sEmail'])) {
        // Don't perform an action if we don't have an API token
        return;
    }

    $url = sprintf('%s/api/survey/%s/sendnow', getThermostatBaseUrl(), $surveyId);

    try {
        $client = new GuzzleHttp\Client;
        $response = $client->request('POST', $url, [
            'form_params' => [
                'email' => $req['sEmail'],
                'xrequest' => $req['xRequest'],
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody());
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Log http response error if:
        //  - Exception has no response
        //  - OR Exception has a response and the response code is not a 403
        //     - A 403 error occurs when user has hit account limits, such as imposed spending limits
        //     - We don't want to log that situation
        if (! $e->hasResponse() || ($e->hasResponse() && $e->getResponse()->getStatusCode() != 403)) {
            // Failed to send request to thermo
            errorLog($e->getMessage(), 'Integration', __FILE__, __LINE__);
            \Illuminate\Support\Facades\Log::error($e);
        }
    }
}

/******************************************
ADD USER EMAIL TO THERMOSTAT SURVEY CAMPAIGN
 ******************************************/
function addThermostatEmail($req, $surveyId)
{
    $token = apiGetThermostatToken();
    if (! $token || empty($req['sEmail'])) {
        // Don't perform an action if we don't have an API token
        return;
    }

    $url = sprintf('%s/api/survey/%s/email', getThermostatBaseUrl(), $surveyId);

    try {
        $client = new GuzzleHttp\Client;
        $response = $client->request('POST', $url, [
            'form_params' => [
                'email' => $req['sEmail'],
                'xrequest' => $req['xRequest'],
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody());
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Failed to send request to thermo
        errorLog($e->getMessage().'::'.$e->getResponse(), 'Integration', __FILE__, __LINE__);
        \Illuminate\Support\Facades\Log::error($e);
    }
}

/******************************************
Get Survey Results
 ******************************************/
function pollThermostatResponses()
{
    $token = apiGetThermostatToken();
    if (! $token) {
        // Don't perform an action if we don't have an API token
        return;
    }

    $since = apiGetThermostatSince();
    $query = (! empty($since)) ? '?since='.$since : '';
    $url = sprintf('%s/api/helpspot%s', getThermostatBaseUrl(), $query);

    try {
        $client = new GuzzleHttp\Client;
        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $data = json_decode($response->getBody());
        if (count($data->responses)) {
            apiSetThermostatSince($data->since);
        }

        return $data->responses;
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Failed to send request to thermo
        errorLog($e->getMessage().'::'.$e->getResponse(), 'Integration', __FILE__, __LINE__);
        \Illuminate\Support\Facades\Log::error($e);
    }
}

function getThermostatBaseUrl()
{
    return hs_setting('cHD_THERMOSTAT_BASE', 'https://thermostat.io');
}
