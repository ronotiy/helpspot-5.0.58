<?php

namespace HS\Meta\Jobs;

use Illuminate\Bus\Queueable;

class CollectInstallationMetadata
{
    use Queueable;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->load();

        if (defined('cHD_META') and hs_setting('cHD_CUSTCONNECT_ACTIVE')) {
            $meta = json_decode(hs_setting('cHD_META'));
            if (! isset($meta->last_update) || $meta->last_update < (time() - 86400)) {
                $chdLivelookupSearches = hs_setting('cHD_LIVELOOKUP_SEARCHES');
                $info = [
                    'industry_use_case' => ($this->isInternal()) ? 'it-help-desk' : 'customer-support',
                    'filters' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Filters'),
                    'requests' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Request WHERE xStatus <> '.hs_setting('cHD_STATUS_SPAM', 2).' AND fTrash = 0'),
                    'request_history' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Request_History'),
                    'forum_topics' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Forums_Topics'),
                    'kb_public' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_KB_Books WHERE fPrivate = 0'),
                    'kb_private' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_KB_Books WHERE fPrivate = 1'),
                    'kb_pages' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_KB_Pages'),
                    'reports' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Saved_Reports'),
                    'responses' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Responses'),
                    'time_tracker' => (hs_setting('cHD_TIMETRACKER') ? 'yes' : 'no'),
                    'custom_fields' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_CustomFields'),
                    'triggers' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Triggers'),
                    'auto_rules' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Automation_Rules'),
                    'mail_rules' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Mail_Rules'),
                    'live_lookup' => (! empty($chdLivelookupSearches) && $chdLivelookupSearches != 'a:0:{}' ? count(hs_unserialize($chdLivelookupSearches)) : 0),
                    'auth' => hs_setting('cAUTHTYPE', 'internal'),
                    'categories' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Category'),
                    'status_types' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_luStatus'),
                    'mailboxes' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Mailboxes'),
                    'active_staff' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Person WHERE fDeleted = 0'),
                    'inactive_staff' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Person WHERE fDeleted = 1'),
                    'permission_groups' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Permission_Groups'),
                    'portal_theme' => hs_setting('cHD_THEME_PORTAL'),
                    'portals' => (int) $GLOBALS['DB']->GetOne('SELECT COUNT(*) FROM HS_Multi_Portal'),
                    'php' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
                    'os' => php_uname(),
                    'db' => config('database.default'),
                    'search_engine' => 'database',
                    'organization_id' => hs_setting('cHD_CUSTOMER_ID'),
                    'customer_url' => hs_setting('cHOST'),
                    'version' => hs_setting('cHD_VERSION'),
                    'last_update' => time(),
                ];
                storeGlobalVar('cHD_META', json_encode($info));
                hsHTTP('https://store.helpspot.com/connect?'.http_build_query($info), ['type'=>'http-post']);
            }
        }
    }

    protected function load()
    {
        /*****************************************
        INCLUDE PATH
         *****************************************/
        set_include_path(cBASEPATH.'/helpspot/pear');

        /*****************************************
        INCLUDE LIBS
         *****************************************/
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
    }

    /**
     * @param float $threshold (percent in decimal format)
     * @return bool
     */
    protected function isInternal($threshold = .5)
    {
        $requests = $GLOBALS['DB']->Execute('SELECT sEmail FROM HS_Request WHERE sEmail != "" ORDER BY xRequest LIMIT 5000');
        $items = collect($requests->ToArray());
        $data = $items->transform(function ($item) {
            return end(explode('@', $item['sEmail']));
        });

        $grouped = [];
        foreach ($data as $key => $item) {
            if (isset($grouped[$item])) {
                $grouped[$item] += 1;
            } else {
                $grouped[$item] = 1;
            }
        }

        // 5000 * .5 to get the number for comparison.
        $cutoff = count($data) * $threshold;
        foreach ($grouped as $count) {
            // if this group has more than the cutoff then it's internal
            if (round($count) >= $cutoff) {
                return true;
            }
        }

        return false;
    }
}
