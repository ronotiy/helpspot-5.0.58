<?php

namespace HS\Listeners;

use HS\LoginAttempts;

class LoginEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin($event)
    {
        $login = new LoginAttempts();
        $login->sUsername = $event->user->sEmail;
        $login->dtDateAdded = time();
        $login->fValid = 1;
        $login->save();
    }

    /**
     * Handle user logout events.
     */
    public function handleFailedLogin($event)
    {
        $login = new LoginAttempts();
        $login->sUsername = (isset($event->credentials['sEmail']) ? $event->credentials['sEmail'] : $event->credentials['sUsername']);
        $login->dtDateAdded = time();
        $login->fValid = 0;
        $login->save();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'Illuminate\Auth\Events\Login',
            'HS\Listeners\LoginEventSubscriber@handleUserLogin'
        );
        $events->listen(
            'Illuminate\Auth\Events\Failed',
            'HS\Listeners\LoginEventSubscriber@handleFailedLogin'
        );
    }
}
