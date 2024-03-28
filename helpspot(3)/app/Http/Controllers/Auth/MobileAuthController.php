<?php

namespace HS\Http\Controllers\Auth;

use Illuminate\Http\Request;
use HS\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class MobileAuthController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $response = response()->view('auth.mobile-auth');

        if (auth()->check() && Cookie::get('mobile-transact')) {
            /** @var $token \Laravel\Sanctum\NewAccessToken */
            $token = auth()->user()->createToken('HelpSpot Mobile '.date('Y-m-d H:i:s'));
            $response->headers->add(['X-HelpSpot-Token' => $token->plainTextToken,]);
            Cookie::queue('X-HelpSpot-Token', $token->plainTextToken, 0); // Expire immediately
            Cookie::queue(Cookie::forget('mobile-transact'));
        }

        return $response;
    }
}
