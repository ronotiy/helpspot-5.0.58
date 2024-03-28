<?php

namespace HS\Providers;

use Dotenv\Dotenv;
use HS\MultiPortal;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\QueryException;
use Dotenv\Exception\InvalidPathException;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // These variables are only set on error, so it's likely
        // after we finish bootstrapping
        Bugsnag::registerCallback(function ($report) {
            $report->setMetaData([
                'account' => [
                    'name' => hs_setting('cHD_ORGNAME', 'unknown'),
                    'org_id' => hs_setting('cHD_CUSTOMER_ID', 1),
                    'version' => hs_setting('cHD_VERSION', 'v5.unknown'),
                ],
            ]);
        });

        if (! defined('cBASEPATH')) {
            define('cBASEPATH', base_path('helpspot'));
        }

        if (! defined('cHOST')) {
            define('cHOST', rtrim(config('app.url'), '/'));
        }

        // If we want to force maintenance mode via "MAINTENANCE_MODE=true" in .env
        // then we set "artisan down" here. The command can be safely be run run
        // if already in maintenance mode
        if (config('helpspot.maintenance_mode', false)) {
            Artisan::call('down');
        }

        // If we're using the database cache driver, but the table
        // doesn't exist yet, fallback to file driver
        app()->resolving('cache', function() {
            if (config('cache.default') == 'database') {
                if (! Schema::hasTable(config('cache.stores.database.table'))) {
                    config()->set('cache.default', 'file');
                }
            }
        });

        // If we have a portal being used as a primary portal
        if (! defined('cMULTIPORTAL')) {
            try {
                $primaryPortal = MultiPortal::asPrimary()->first();
                if ($primaryPortal) {
                    define('cMULTIPORTAL', $primaryPortal->xPortal);
                }
            } catch(\Exception $e) {
                // swallow so we don't fail on upgrades.
            }
        }

        // Secondary portals (not used as primary) can optionally
        // have their own .env file that over-rides the base .env file
        if (defined('cMULTIPORTAL')) {
            $portal = MultiPortal::active()
                ->where('xPortal', cMULTIPORTAL)
                ->where('fIsPrimaryPortal', 0)
                ->first();

            if ($portal && is_dir($portal->sPortalPath) && $portal->sPortalPath != public_path()) {
                try {
                    $env = Dotenv::create(Env::getRepository(), $portal->sPortalPath);
                    $env->load();
                } catch (InvalidPathException $e) {
                    // swallow if portal directory does not exist
                }
            }
        }

        // Util has initDB and initSettings
        // initSettings calls items in api.lib.php.
        // ADODB calls some utf8 helpers
        //   The dependency chain stops there. Phew.
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';

        // Database must be loaded before other init methods
        $this->initHelpspotDatabase();

        try {
            // Successful loading of HS_Settings determines
            // if HelpSpot is installed or not
            $this->initHelpspotSettings();

            Bugsnag::setAppVersion(hs_setting('cHD_VERSION'));

            $this->setSMTPConfig(); // Relies on HS_Settings loading
            if (! defined('HELPSPOT_INSTALLED')) {
                define('HELPSPOT_INSTALLED', true);
            }
        } catch (QueryException $e) {
            // HTTP Middleware will handle redirect to web-based installer
            if (! defined('HELPSPOT_INSTALLED')) {
                define('HELPSPOT_INSTALLED', false);
            }
        }

        //ensure compatibility with MySQL 5.6
        Schema::defaultStringLength(191);

        $this->initHelpSpotOrg(); // Has a fallback in case setting is not loaded from HS_Settings
        $this->initLanguage();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
         * HelpSpot Database Bootstrapping for our updated
         * Query Grammars, which help handle field types
         * not otherwise handled by laravel's database
         */
        $this->app->resolving('db', function ($db) {
            try {
                $grammar = defaultConnectionDetail('grammar');
                $db->connection()->setSchemaGrammar(new $grammar);
            } catch (\PDOException $e) {
                throw new \Exception('HelpSpot could not connect to the database, likely due to incorrect credentials supplied in .env. The error received from the database was: ' . $e->getMessage());
            }
        });
    }

    private function initHelpspotDatabase()
    {
        hsInitDB();
    }

    private function initHelpspotSettings()
    {
        hsInitSettings();
    }

    private function initHelpSpotOrg()
    {
        config()->set('app.name', hs_setting('cHD_ORGNAME', 'HelpSpot'));
    }

    private function setSMTPConfig()
    {
        if (hs_setting('cHD_MAIL_OUTTYPE') == 'mail') {
            config()->set('mail.default', 'sendmail');
        } else {
            // cHD_MAIL_OUTTYPE == 'smtp'
            $smtp = hs_unserialize(cHD_MAIL_SMTPCONN);

            // Set these to null if we aren't authenticating
            $username = ($smtp['cHD_MAIL_SMTPAUTH']) ? $smtp['cHD_MAIL_SMTPUSER'] : null;
            $password = ($smtp['cHD_MAIL_SMTPAUTH']) ? $smtp['cHD_MAIL_SMTPPASS'] : null;

            config()->set('mail.default', 'smtp');
            config()->set('mail.mailers.smtp.host', $smtp['cHD_MAIL_SMTPHOST']);
            config()->set('mail.mailers.smtp.port', $smtp['cHD_MAIL_SMTPPORT']);
            config()->set('mail.mailers.smtp.encryption', $smtp['cHD_MAIL_SMTPPROTOCOL']);
            config()->set('mail.mailers.smtp.username', $username);
            config()->set('mail.mailers.smtp.password', $password);
            config()->set('mail.mailers.smtp.timeout', $smtp['cHD_MAIL_SMTPTIMEOUT'] ?? null);
            config()->set('mail.from', [
                'address' => cHD_NOTIFICATIONEMAILACCT,
                'name' => cHD_NOTIFICATIONEMAILNAME,
            ]);
        }
    }

    private function initLanguage()
    {
        $lang = hs_setting('cHD_LANG', 'english-us');

        require_once cBASEPATH.'/helpspot/lang/'.$lang.'/lg.general.php';
        require_once cBASEPATH.'/helpspot/lang/'.$lang.'/lg.pg.request.php';
    }
}
