<?php

namespace HS\Http\Controllers\Auth;

use HS\User;
use HS\PortalLogin;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

use Aacotroneo\Saml2\Saml2Auth;
use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use Aacotroneo\Saml2\Http\Controllers\Saml2Controller;

class SamlController extends Saml2Controller
{
    public function error()
    {
        $errors = session('saml-error');

        return view('auth.saml-error', [
            'samlErrors' => $errors,
            'debug' => config('app.debug'),
        ]);
    }

    /**
     * Process an incoming saml2 assertion request.
     * Fires 'Saml2LoginEvent' event if a valid user is found.
     *
     * NOTE: This is copy/paste from the base class, with additions
     *       as noted below
     * @param Saml2Auth $saml2Auth
     * @param $idpName
     * @return \Illuminate\Http\Response
     */
    public function acs(Saml2Auth $saml2Auth, $idpName)
    {
        $errors = $saml2Auth->acs();

        if (!empty($errors)) {
            logger()->error('Saml2 error_detail', ['error' => $saml2Auth->getLastErrorReason()]);
            session()->flash('saml2_error_detail', [$saml2Auth->getLastErrorReason()]);

            logger()->error('Saml2 error', $errors);
            session()->flash('saml2_error', $errors);
            return redirect(config('saml2_settings.errorRoute')); // todo: Different route if debug is on?
        }
        $user = $saml2Auth->getSaml2User();

        event(new Saml2LoginEvent($idpName, $user, $saml2Auth));

        // BEGIN HELPSPOT CUSTOMIZATION
        $response = $this->authenticatedUser($user, $saml2Auth->getLastMessageId());

        if( $response )
        {
            return $response;
        }
        // END HELPSPOT CUSTOMIZATION

        $redirectUrl = $user->getIntendedUrl();

        if ($redirectUrl !== null) {
            return redirect($redirectUrl);
        } else {

            return redirect(config('saml2_settings.loginRoute'));
        }
    }

    public function authenticatedUser($user, $lastMessageId) {
        // Prevent replay attacks
        $hasReceivedMessageKey = sprintf('saml::message::%s', $lastMessageId);
        if (Cache::has($hasReceivedMessageKey)) {
            return abort(401, "Invalid SAML Authentication");
        } else {
            // ~ 1 month. Ideally is "forever" except memory constraints exists.
            Cache::put($hasReceivedMessageKey, true, 2628000);
        }

        $userData = [
            'id' => $user->getUserId(),
            'attributes' => $user->getAttributes(),
            'assertion' => $user->getRawSamlAssertion()
        ];

        $userQuery = $userData['id'];

        if( is_array($userData['attributes']) && count($userData['attributes']) > 0 )
        {
            $validIdentityClaims = [
                'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
                'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
            ];

            $identities = collect($userData['attributes'])->filter(function($item, $key) use($validIdentityClaims) {
                return in_array($key, $validIdentityClaims);
            })->mapWithKeys(function ($item, $key) {
                return [basename($key) => $item[0]];
            })->toArray();

            // Priority: name, emailaddress, email
            //           "email" isn't technically valid but I've seen it referenced before
            if( isset($identities['name']) ) {
                $userQuery = $identities['name'];
            } elseif( isset($identities['emailaddress']) ) {
                $userQuery = $identities['emailaddress'];
            } elseif( isset($identities['email']) ) {
                $userQuery = $identities['email'];
            }
        }

        $user = User::where('sUsername', $userQuery)
            ->first();

        // We have an admin user, return null so head to
        // SAML intended redirect ('/admin')
        if( $user ) {
            auth()->login( $user );
            return null;
        }

        // If not a staffer, log them in as a "customer" on the portal end
        $portalUser = PortalLogin::where('sUsername', $userQuery)
            ->orWhere('sEmail', $userQuery)
            ->first();

        if( $portalUser ) {
            $this->loginPortalUser($portalUser);
            return redirect()->to(cHOST.'/index.php?pg=request.history');
        } else {
            $data = [
                'sPasswordHash' => Hash::make(Str::random(24)),
            ];
            $data['sUsername'] = (isset($identities['name']))
                ?  $identities['name']
                : null;
            $data['sEmail'] = (isset($identities['emailaddress']))
                ? $identities['emailaddress']
                : $data['sUsername'];

            try {
                $portalUser = PortalLogin::create($data);
                $this->loginPortalUser($portalUser);
                return redirect()->to(cHOST.'/index.php?pg=request.history');
            } catch(\Exception $e) {
                // swallow error and head to saml error
            }
        }

        // No admin user and could not find or create portal user
        session()->flash('saml-error', [
            'attempted' => $userQuery,
            'attributes' => array_merge([
                'nameId' => $userData['id'],
                'attributes' => $userData['attributes']
            ])
        ]);
        return redirect()->route('saml2_error', 'hs');
    }

    protected function loginPortalUser($portalUser) {
        auth('portal')->login($portalUser);

        //this is the username if we're in black box mode
        if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE') == 'blackbox') {
            session()->put('login_username', $_POST['login_email']);
        }
        session()->put('login_sEmail', $portalUser->sEmail);
        session()->put('login_xLogin', $portalUser->xLogin);
        session()->put('login_ip', hs_clientIP());
    }

}
