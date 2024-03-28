<?php

namespace HS\Auth\Ldap;

use Adldap\Adldap;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class LdapUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     * @throws \Adldap\AdldapException
     * @throws \Adldap\Auth\BindException
     * @throws \Adldap\Auth\PasswordRequiredException
     * @throws \Adldap\Auth\UsernameRequiredException
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $ldap = new Adldap;
        $ldap->addProvider(config()->get('ldap'));

        try {
            return $ldap->getDefaultProvider()
                ->auth()
                ->attempt($credentials['sUsername'], $credentials['password']);
        } catch (AdldapException $e) {
            Log::error($e);

            return false;
        }
    }
}
