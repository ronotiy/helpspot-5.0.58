<?php

namespace HS\Providers;

use HS\PersonalAccessToken;
use HS\Auth\Ldap\LdapUserProvider;
use HS\Auth\Blackbox\BlackboxUserProvider;
use HS\Auth\Blackbox\BlackboxPortalProvider;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'HS\Model' => 'HS\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->registerAuthStrategies();
        $this->setAuthStrategy();
    }

    private function registerAuthStrategies()
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Auth::provider('blackbox', function ($app, $config) {
            return new BlackboxUserProvider($app['hash'], $config['model']);
        });

        Auth::provider('blackbox-portal', function ($app, $config) {
            return new BlackboxPortalProvider($app['hash'], $config['model']);
        });

        Auth::provider('ldap', function ($app, $config) {
            return new LdapUserProvider($app['hash'], $config['model']);
        });
    }

    private function setAuthStrategy()
    {
        // Admin Auth
        if (hs_setting('cAUTHTYPE', 'internal') == 'ldap_ad') {
            $this->setLdapAuth();
        }

        if (hs_setting('cAUTHTYPE', 'internal') == 'blackbox') {
            $this->setBlackboxAuth();
        }

        // Portal Auth
        if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE', 'internal') == 'blackbox') {
            $this->setBlackboxPortalAuth();
        }

        // Admin + Portal Auth (SAML)
        if (hs_setting('cAUTHTYPE', 'internal') == 'saml') {
            $this->setSamlAuth();
        } else {
            $this->fakeSamlAuth();
        }
    }

    private function setLdapAuth()
    {
        // Set use of ldap2 package
        // config()->set('auth.guards.web.driver', 'ldap');
        config()->set('auth.providers.users.driver', 'ldap');

        // Set ldap connection info
        /*
         $ldap_ad_options = array(
            'account_suffix' => $_POST['cHD_LDAP_ACCOUNT_SUFFIX'],
            'base_dn' => $_POST['cHD_LDAP_BASE_DN'],
            'domain_controllers' => array($_POST['cHD_LDAP_DN_CONTROL']),
            'ad_username' => $_POST['cHD_LDAP_USERNAME'],
            'ad_password' => $_POST['cHD_LDAP_PASSWORD'],
            'use_ssl' => $_POST['cHD_LDAP_USESSL'],
            'use_tls' => $_POST['cHD_LDAP_USETLS'],
        );
         */
        $options = hs_unserialize(
            hs_setting('cAUTHTYPE_LDAP_OPTIONS', '')
        );

        config()->set('ldap.hosts', $options['domain_controllers']);
        config()->set('ldap.base_dn', $options['base_dn']);
        config()->set('ldap.username', isset($options['ad_username']) ? $options['ad_username'] : null); // admin username (not used right now)
        config()->set('ldap.password', isset($options['ad_password']) ? $options['ad_password'] : null); // admin password (not used right now)
        config()->set('ldap.account_suffix', $options['account_suffix']);
        config()->set('ldap.port', (isset($options['port']) && ! empty($options['port'])) ? $options['port'] : 389); // TODO: Add custom port option in UI
        config()->set('ldap.use_ssl', (bool) $options['use_ssl']); // Ensure it's boolean
        config()->set('ldap.use_tls', (bool) $options['use_tls']); // Ensure it's boolean
        config()->set('ldap.timeout', (isset($options['timeout']) && ! empty($options['timeout'])) ? $options['timeout'] : 5); // TODO: Add custom timeout option in UI
    }

    private function setBlackboxAuth()
    {
        config()->set('auth.providers.users.driver', 'blackbox');
    }

    private function setBlackboxPortalAuth()
    {
        config()->set('auth.providers.portal.driver', 'blackbox-portal');
    }

    private function setSamlAuth()
    {
        $options = hs_unserialize(
            hs_setting('cAUTHTYPE_SAML_OPTIONS', '')
        );

        config()->set('saml2_settings.routesPrefix', '/saml2');
        config()->set('saml2_settings.routesMiddleware', ['saml']);
        config()->set('saml2_settings.logoutRoute', '/login');
        config()->set('saml2_settings.loginRoute', '/admin');
        config()->set('saml2_settings.errorRoute', '/');
        config()->set('saml2_settings.proxyVars', true); // Removes need for \OneLogin\Saml2\Utils::setBaseUrl(cHOST.'/saml2');

        ###
        # Service Provider (mostly auto-generated)
        ##
        // TODO: Make configurable?
        // config()->set('saml2_settings.sp.NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent');

        ###
        # Identity Provider (user-supplied)
        ##
        config()->set('saml2.hs_idp_settings.idp.entityId', $options['entity_id']);
        config()->set('saml2.hs_idp_settings.idp.singleSignOnService.url', $options['login_url']);
        config()->set('saml2.hs_idp_settings.idp.singleLogoutService.url', $options['logout_url']);
        config()->set('saml2.hs_idp_settings.idp.x509cert', $options['x509_cert']);
    }

    // Required for situations like `php artisan route:list` that may load the SAML controller
    // but error-out when it finds no configurations it needs
    private function fakeSamlAuth()
    {
        config()->set('saml2_settings.routesPrefix', '/saml2');
        config()->set('saml2_settings.routesMiddleware', ['saml']);
        config()->set('saml2_settings.logoutRoute', '/login');
        config()->set('saml2_settings.loginRoute', '/admin');
        config()->set('saml2_settings.errorRoute', '/');
        config()->set('saml2_settings.proxyVars', true); // Removes need for \OneLogin\Saml2\Utils::setBaseUrl(cHOST.'/saml2');

        ###
        # Identity Provider (user-supplied)
        ##
        config()->set('saml2.hs_idp_settings.idp.entityId', 'some-entity-id');
        config()->set('saml2.hs_idp_settings.idp.singleSignOnService.url', 'https://some-login-url');
        config()->set('saml2.hs_idp_settings.idp.singleLogoutService.url', 'https://some-logout-url');
        config()->set('saml2.hs_idp_settings.idp.x509cert', 'some-cert-string');
    }
}
