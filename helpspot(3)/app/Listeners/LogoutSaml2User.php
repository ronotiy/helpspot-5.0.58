<?php

namespace HS\Listeners;


use Illuminate\Support\Facades\Log;

class LogoutSaml2User
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        Log::info('SAML2 logout');
        auth()->logout();
        request()->session()->invalidate();
    }
}