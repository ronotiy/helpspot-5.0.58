<?php

namespace HS\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class Manager
{
    const CACHE_CUSTOMFIELD_KEY = 'customFields';

    const CACHE_CUSTOMFIELD_MINUTES = 1200; // 20 minutes

    const CACHE_SETTINGS_KEY = 'hs_settings';

    const CACHE_SETTINGS_MINUTES = 300; // 5 minutes

    const CACHE_STATUS_KEY = 'reqStatus';

    const CACHE_STATUS_MINUTES = 3600; // 60 minutes

    const CACHE_ACTIVESTATUS_KEY = 'activeStatus';

    const CACHE_ACTIVESTATUS_MINUTES = 1200; // 20 minutes

    const CACHE_ALLSTATUS_KEY = 'allStatus';

    const CACHE_ALLSTATUS_MINUTES = 1200; // 20 minutes

    const CACHE_USERS_ACTIVE_KEY = 'usersActive';

    const CACHE_USERS_INACTIVE_KEY = 'usersInactive';

    const CACHE_USERS_MINUTES = 1200; // 20 minutes

    const CACHE_ALLUSERS_KEY = 'allUsers';

    const CACHE_ALLUSERS_MINUTES = 1200; // 20 minutes

    const CACHE_STUCKEMAILS_KEY = 'stuckEmails';

    const CACHE_STUCKEMAILS_MINUTES = 300; // 5 minutes

    const CACHE_ALLPORTALS_ACTIVE_KEY = 'allPortalsActive';

    const CACHE_ALLPORTALS_DELETED_KEY = 'allPortalsDeleted';

    const CACHE_ALLPORTALS_MINUTES = 1200; // 20 minutes

    const CACHE_ALLCATEGORIES_KEY = 'allCategories';

    const CACHE_ALLCATEGORIES_MINUTES = 1200; // 20 minutes

    const CACHE_CATEGORIES_ACTIVE_KEY = 'categoriesActive';

    const CACHE_CATEGORIES_DELETED_KEY = 'categoriesDeleted';

    const CACHE_CATEGORIES_MINUTES = 1200; // 20 minutes

    const CACHE_ALLBOOKS_KEY = 'allBooks';

    const CACHE_ALLBOOKS_MINUTES = 3600; // 60 minutes

    const CACHE_BOOKS_PUBLIC_KEY = 'booksPublic';

    const CACHE_BOOKS_PRIVATE_KEY = 'booksPrivate';

    const CACHE_BOOKS_MINUTES = 3600; // 60 minutes

    const CACHE_ASSIGNEDSTAFF_KEY = 'assignedStaff';

    const CACHE_ASSIGNEDSTAFF_MINUTES = 30; // .5 minutes

    const CACHE_MOST_USED_RESPONSES_MINUTES = 180; // 3 hours

    const CACHE_SYSTEM_MINUTES = 1800; // 30 minutes

    const CACHE_HISTORY_COUNT_MINUTES = 1800;

    protected $groups = [
        'users' => [
            self::CACHE_USERS_ACTIVE_KEY,
            self::CACHE_USERS_INACTIVE_KEY,
            self::CACHE_ALLUSERS_KEY,
        ],
        'categories' => [
            self::CACHE_ALLCATEGORIES_KEY,
            self::CACHE_CATEGORIES_ACTIVE_KEY,
            self::CACHE_CATEGORIES_DELETED_KEY,
            self::CACHE_USERS_ACTIVE_KEY,
            self::CACHE_ALLUSERS_KEY,
            self::CACHE_ASSIGNEDSTAFF_KEY,
        ],
        'status' => [
            self::CACHE_STATUS_KEY,
            self::CACHE_ACTIVESTATUS_KEY,
            self::CACHE_ALLSTATUS_KEY,
        ],
        'kb' => [
            self::CACHE_ALLBOOKS_KEY,
            self::CACHE_BOOKS_PUBLIC_KEY,
            self::CACHE_BOOKS_PRIVATE_KEY,
        ],
        'portals' => [
            self::CACHE_ALLPORTALS_ACTIVE_KEY,
            self::CACHE_ALLPORTALS_DELETED_KEY,
        ],
    ];

    /**
     * @param $xPerson
     * @return string
     */
    public static function user_response_usage_key($xPerson)
    {
        return sprintf('most_used_responses_%s', $xPerson);
    }

    /**
     * Generate a request history count cache key,
     * unique per request id
     * @param int $requestId
     * @return string
     */
    public static function history_count_key($requestId)
    {
        return sprintf('history_count_%s', $requestId);
    }

    /**
     * @param string|array $keys
     * @return Manager
     */
    public function forget($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach($keys as $key) {
            Cache::forget($key);

            // TODO: If we do more of these, then create a convention
            if ($key == self::CACHE_SETTINGS_KEY) {
                Artisan::call('queue:restart');
            }
        }

        return $this;
    }

    /**
     * @param $group
     * @return $this
     */
    public function forgetGroup($group)
    {
        if (isset($this->groups[$group])) {
            $this->forget($this->groups[$group]);
        }

        return $this;
    }

    public function forgetFilter($filter)
    {
        Cache::forget($this->filter_key($filter));
    }

    /**
     * Build dynamic cache key name for individual HelpSpot filter
     * Use an int for filter ID, or string for a system filter such as "spam", "trash".
     * @param string|int $filter
     * @return string
     */
    public function filter_key($filter)
    {
        if (! is_numeric($filter)) {
            $filter = mb_strtolower($filter);
        }

        return sprintf('filter_%s', $filter);
    }

    public function key($key)
    {
        return constant(self::class . '::' . $key);
    }
}
