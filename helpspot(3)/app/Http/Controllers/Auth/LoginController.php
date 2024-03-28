<?php

namespace HS\Http\Controllers\Auth;

use Illuminate\Http\Request;
use HS\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        login as private traitLogin;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadSettings();
        $this->middleware('guest')->except('logout');
    }

    /**
     * Override AuthenticatesUsers::login() to prevent logging in as a SAML override when debug is off
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        if (hs_setting('cAUTHTYPE', 'internal') == 'saml' && ! config('app.debug')) {
            return abort(404);
        }

        return with($this->traitLogin($request), function(Response $response) use($request) {
            if($request->ajax()) {
                $status = strpos($response->headers->get('location'), route('login')) === false
                    ? 200
                    : 401;

                $response->headers->remove('location');

                return response([
                    'csrf' => csrf_token(),
                ], $status, $response->headers->all());
            }

            // Successful log, isn't an AJAX request, and is a login from mobile app
            if (auth()->check() && Cookie::get('mobile-transact')) {
                return redirect()->route('mobile-auth');
            }

            return $response;
        });
    }

    public function showLoginForm()
    {
        if (request()->input('mobileauth')) {
            Cookie::queue('mobile-transact', 'true', 60);
        }

        if (hs_setting('cAUTHTYPE', 'internal') == 'saml') {
            return redirect()->route('saml2_login', 'hs');
        }

        return view('auth.login', array_merge(
            $this->getLatestNews(),
            ['mobileauth' => request()->input('mobileauth')]
        ));
    }

    public function showAltLoginForm()
    {
        if (request()->input('mobileauth')) {
            Cookie::queue('mobile-transact', 'true', 60);
        }

        return view('auth.saml-altlogin', array_merge(
            $this->getLatestNews(),
            ['mobileauth' => request()->input('mobileauth')]
        ));
    }

    protected function getLatestNews()
    {
        $article = getHelpSpotRssArticleLink();
        if (! $article) {
            return ['article_link' => '', 'article_title' => ''];
        }
        return [
            'article_link'=> $article['link'].'?m='.createBlogLinkMetaQueryString(),
            'article_title' => $article['title'],
        ];
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        // If we're using internal auth
        if (hs_setting('cAUTHTYPE', 'internal') == 'internal'
            // Or if we're using saml, and debug is enabled, then we'll assume reaching here
            // is when using the /altlogin form to use internal auth as part of debugging SAML auth
            || (config('app.debug') && hs_setting('cAUTHTYPE', 'internal') == 'saml') ) {
            return 'sEmail';
        }

        return 'sUsername';
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        if (hs_setting('cAUTHTYPE', 'internal') == 'saml') {
            return redirect()->route('altlogin');
        }

        return $this->loggedOut($request) ?: redirect('/login');
    }
}
