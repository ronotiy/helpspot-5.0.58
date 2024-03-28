<?php

namespace HS\Providers;

use Naneau\SemVer\Sort;
use HS\Install\System\Manager;
use Database\Seeders\DatabaseSeeder;
use HS\Install\System\Checks\HasImap;
use HS\Install\System\Checks\HasTidy;
use Illuminate\Support\ServiceProvider;
use HS\Install\System\Checks\PhpVersion;
use HS\Install\Updater\UpdateRepository;
use HS\Install\Updater\Updates\Migrator;
use HS\Install\System\Checks\HasMbString;
use HS\Install\System\Checks\DbConnection;
use HS\Install\System\Checks\MysqlVersion;
use HS\Install\Installer\Actions\CheckSmtp;
use HS\Install\Installer\InstallRepository;
use HS\Install\Tables\Copier\CopierFactory;
use HS\Install\System\Checks\UploadWritable;
use HS\Install\Updater\Updates\VersionSorter;
use HS\Install\Installer\Actions\CheckAccount;
use HS\Install\Installer\Actions\CheckLicense;
use HS\Install\System\Checks\SessionAutostart;
use HS\Install\System\Checks\SystemPermissions;
use HS\Install\System\Checks\DataDirPermissions;
use HS\Install\Updater\Updates\Manager as UpdateManager;

class InstallServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        if (! defined('cBASEPATH')) {
            define('cBASEPATH', base_path('helpspot'));
        }

        if (! defined('instHSVersion')) {
            define('instHSVersion', trim(file_get_contents(cBASEPATH.'/helpspot/version.txt')));
        }

        if (! defined('cHD_VERSION')) {
            define('cHD_VERSION', instHSVersion);
        }

        $this->app->alias('system.check', \HS\Install\System\Manager::class);
        $this->app->alias('install.account', \HS\Install\Installer\Actions\CheckAccount::class);
        $this->app->alias('install.repository', \HS\Install\Installer\InstallRepository::class);
        $this->app->alias('update.repository', \HS\Install\Updater\UpdateRepository::class);
        $this->app->alias('copier', \HS\Install\Tables\Copier\CopierFactory::class);
        $this->app->alias('update.sorter', \HS\Install\Updater\Updates\VersionSorter::class);
        $this->app->alias('update.updates', \HS\Install\Updater\Updates\Manager::class);
        $this->app->alias('update.migrator', \HS\Install\Updater\Updates\Migrator::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('system.check', function ($app) {
            $manager = new Manager;

            // Register each checker
            $manager->check('db', new DbConnection($app['db']));
            $manager->check('mysql', new MysqlVersion($app['db']));
            $manager->check('php', new PhpVersion($app['system.features']));
            $manager->check('imap', new HasImap($app['system.features']));
            $manager->check('upload', new UploadWritable);
            $manager->check('mbstring', new HasMbString($app['system.features']));
            $manager->check('tidy', new HasTidy($app['system.features']));
            $manager->check('session', new SessionAutostart);
            $manager->check('permissions', new SystemPermissions($app['system.features']));
            $manager->check('datadir', new DataDirPermissions);

            return $manager;
        });

        $this->app->singleton('install.account', function ($app) {
            return new CheckAccount($app['validator']);
        });

        $this->app->singleton('install.repository', function ($app) {
            return new InstallRepository(
                $app['db'],
                $app['migrator'],
                new DatabaseSeeder,
                $this->app['config']['database.default'],
                $this->app->databasePath().'/migrations'
            );
        });

        $this->app->singleton('copier', function ($app) {
            return new CopierFactory($app['charset.encoder'], $app['html.cleaner']);
        });

        $this->app->singleton('update.repository', function ($app) {
            return new UpdateRepository(
                $app['log'],
                $app['db'],
                $app['migrator'],
                $app['config']['database.migrations_path_v3tov4'],
                $app['copier'],
                $app->make('Illuminate\Events\Dispatcher')
            );
        });

        $this->app->singleton('update.migrator', function ($app) {
            return new Migrator($app['migration.repository'], $app['db'], $app['files']);
        });

        $this->app->singleton('update.sorter', function ($app) {
            return new VersionSorter(new Sort);
        });

        $this->app->singleton('update.updates', function ($app) {
            $updateManager = new UpdateManager($app, $app['update.sorter']);

            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\MySqlLongFields::class, '4.0.3');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\StatusSettings::class, '4.0.4');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\UniqueIndexes::class, '4.0.5');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\EnablePrivateApi::class, '4.0.7');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\WysiwygSettings::class, '4.1.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\BusinessHoursSettings::class, '4.5.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\PortalSettings::class, '4.7.2');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\MaxRequestHistory::class, '4.8.21');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\HelpSpotFiveSettings::class, '5.0.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\HelpSpotFiveTemplates::class, '5.0.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\HelpSpotFiveAutoRules::class, '5.0.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\HelpSpotFiveMailboxEncryption::class, '5.0.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\HelpSpotFiveAppKey::class, '5.0.0');
            $updateManager->registerUpdate(\HS\Install\Updater\Updates\Tasks\CreatePortalLoginGlobal::class, '5.0.32');

            return $updateManager;
        });
    }
}
