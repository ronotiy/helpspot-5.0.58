<?php

namespace HS\Http\Controllers\Auth\Portal;

use HS\Http\Controllers\Controller;
use HS\PortalLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = cHOST.'/index.php?pg=request.history';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadSettings();
        $this->middleware('guest:portal');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:HS_Portal_Login,sEmail'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ], [
            'email.unique' => lg_portal_er_unique_email
        ],);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return PortalLogin
     */
    protected function create(array $data)
    {
        return PortalLogin::create([
            'sEmail' => $data['email'],
            'sPasswordHash' => bcrypt($data['password']),
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $authAttempt = auth('portal')->attempt([
            'sEmail' => $request->email,
            'password' => trim($request->password)
        ]);

        if ($authAttempt) {
            $xLogin = auth('portal')->id();
        } else {
            $xLogin = false;
        }

        if ($xLogin) {
            if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE') == 'blackbox') {
                session()->put('login_username', $request->email);
            } //this is the username if we're in black box mode
            session()->put('login_sEmail', $request->email);
            session()->put('login_xLogin', $xLogin);
            session()->put('login_ip', hs_clientIP());
        }

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson()
            ? new Response('', 201)
            : redirect($this->redirectPath());
    }
}
