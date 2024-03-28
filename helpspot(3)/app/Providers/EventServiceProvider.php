<?php

namespace HS\Providers;

use HS\Listeners\LogoutSaml2User;
use HS\Listeners\UnsetStafferStatus;
use HS\Listeners\AuthenticateSaml2User;
use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use Aacotroneo\Saml2\Events\Saml2LogoutEvent;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Logout::class => [
            UnsetStafferStatus::class,
        ],
        Saml2LogoutEvent::class => [
            LogoutSaml2User::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        'HS\Listeners\LoginEventSubscriber',
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
