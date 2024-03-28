<?php

namespace HS\Providers;

use HS\License\Decryptor;
use HS\Encryption\Rc4Encryption;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function register()
    {
        if (! defined('cBASEPATH')) {
            define('cBASEPATH', base_path('helpspot'));
        }

        require_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';

        $this->app->singleton('hs.encrypter', function ($app) {
            $key = md5('wookie fulton $2Zgh91');

            $encrypter = new Rc4Encryption(new \Crypt_RC4);
            $encrypter->setKey($key);

            return $encrypter;
        });

        $this->app->bind(\HS\Encryption\EncryptionInterface::class, 'hs.encrypter');

        $this->app->bind(\HS\License\Decryptor::class, function ($app) {
            return new Decryptor($app['hs.encrypter'], $app['config']['app.key']);
        });
    }

    public function provides()
    {
        return ['hs.encrypter'];
    }
}
