<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::get('altlogin', 'Auth\LoginController@showAltLoginForm')->name('altlogin');
Route::get('mobile-auth', 'Auth\MobileAuthController')->name('mobile-auth');

Route::group(['prefix' => 'portal'], function() {
    Route::post('password/email', 'Auth\Portal\ForgotPasswordController@sendResetLinkEmail')->name('portal.password.email');
    Route::post('password/reset', 'Auth\Portal\ResetPasswordController@reset')->name('portal.password.update');
});


Route::any('/test', function() {
    header('Content-type: text/xml');
    echo '<?xml version="1.0" encoding="ISO-8859-1"?>';
    echo "\n".'<ajaxfield>
	<option>
		<value>#4878</value>
		<description>Support</description>
		</option>
	<option>
		<value>#9978</value>
		<description>Emergency Support</description>
	</option>
	<option>
		<value>#9775</value>
		<description>Custom Development</description>
		</option>
	<option>
		<value>#2111</value>
		<description>Training</description>
	</option>
</ajaxfield>';
});

Route::any('/ll', function() {
    header('Content-type: text/xml');
    echo '<?xml version="1.0" encoding="utf-8"?>';
    if (request('customer_id')) {
        echo "\n" . '<livelookup version="1.0" columns="first_name,last_name">
	<customer>
		<customer_id>12334</customer_id>
		<first_name>Bob</first_name>
		<last_name>Jones</last_name>
		<email>bjones@sampleinc.com</email>
		<phone>845-555-4278</phone>
		<organization>Sample Inc</organization>
		<password>The Password</password>
	</customer>
</livelookup>';
    } else {
        echo "\n" . '<livelookup version="1.0" columns="first_name,last_name">
	<customer>
		<customer_id>12334</customer_id>
		<first_name>Bob</first_name>
		<last_name>Jones</last_name>
		<email>bjones@sampleinc.com</email>
		<phone>845-555-4278</phone>
		<organization>Sample Inc</organization>
		<password>The Password</password>
	</customer>
	<customer>
		<customer_id>56757</customer_id>
		<first_name>Tina</first_name>
		<last_name>Smith</last_name>
		<email>tsmith@sampleinc.com</email>
		<phone>845-555-8932</phone>
		<organization>Sample Inc</organization>
		<password>abcdefg</password>
	</customer>
	<customer>
		<customer_id>95544</customer_id>
		<first_name>Tim</first_name>
		<last_name>Myers</last_name>
		<email>tmyers@sampleinc.com</email>
		<phone>845-555-9812</phone>
		<organization>Sample Inc</organization>
		<password>thyekbg</password>
	</customer>
</livelookup>';
    }
});

/*
 * Portal Routes
 */
$index = (function () {
    $response = require_once cBASEPATH.'/index.php';

    if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
        return $response;
    }

    return response($response, 200, contentTypeHeader());
});

Route::any('/', $index);
Route::any('/index', $index);
Route::any('/index.php', $index);

/*
 * Admin Routes
 */
// Captures edge case of POST requests sent to /admin.php
// Some customers may do this to auto-populate a request page
Route::post('admin.php', 'Admin\AdminBaseController@adminFileCalled');

// Else we handle traffic as normal
Route::group(['prefix' => 'admin'], function () {
    Route::post('maintenance', 'Admin\MaintenanceModeController')->middleware('auth')->name('maintenance');
    Route::any('/', 'Admin\AdminBaseController@adminFileCalled')->name('admin');

    Route::resource('jobs', 'Admin\JobsController')
        ->only(['update', 'destroy'])
        ->names([
            'update' => 'jobs.retry',
            'destroy' => 'jobs.delete',
        ])->middleware('auth');

    Route::resource('tokens', 'Admin\PersonalAccessTokenController')
        ->only(['store', 'destroy'])
        ->names([
            'store' => 'tokens.store',
            'destroy' => 'tokens.destroy',
        ])->middleware('auth');
});

/*
 * API Routes
 */

// Handle API traffic, including the case of `index.php` being used in the URL
Route::group(['prefix' => 'api'], function () {
    Route::any('/', 'Api\ApiBaseController@apiFileCalled')->name('api');
    Route::get('/status', 'Api\ApiBaseController@status')->name('api.status');
});

Route::post('license', 'LicenseController@store')->name('license.store');

/*
 * Widgets
 */
Route::group(['prefix' => 'widgets'], function () {
    Route::any('/', 'WidgetController@handle');
});

/*
 * Additional custom SAML routes
 */
Route::group([
    'prefix' => 'saml2/',
    'middleware' => ['saml'],
], function () {
    Route::prefix('{idpName}')->group(function() {
        // Note: Other routes found in aacotroneo/laravel-saml2 package
        Route::get('/error', array(
            'as' => 'saml2_error',
            'uses' => 'Auth\SamlController@error',
        ));
    });
});

/*
 * Session Check
 */
Route::get('/status', 'HealthCheckController');

/*
 * Session Check
 */
Route::post('/sessioncheck', 'Admin\AdminBaseController@sessionCheck');

/*
 * Dark Mode
 */
Route::post('/darkmode', 'Admin\DarkModeController')->middleware('auth')->name('darkmode');

/*
 * Notifications
 */
Route::delete('/notifications/all', 'NotificationController@destroyAll');
Route::resource('/notifications', 'NotificationController')->only('destroy');

/*
 * Web Install Routes
 */
Route::get('install', function() {
    if (hs_setting('HELPSPOT_INSTALLED', true)) {
        return redirect('/');
    }

    return view('errors.install-needed');
})->name('install');

Route::get('upgraded', function() {
    return view('public.upgraded');
});

// Mobile App Rederect links
Route::get('mobileredirect', function() {
    return header("Location: helpspot://?request=".(int) request('request'));
});
Route::get('mobileredirect.php', function() {
    return header("Location: helpspot://?request=".(int) request('request'));
});
