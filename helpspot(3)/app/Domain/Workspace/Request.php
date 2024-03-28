<?php

namespace HS\Domain\Workspace;

use HS\User;
use HS\Cache\Manager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $table = 'HS_Request';

    protected $primaryKey = 'xRequest';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * History for public viewing, such as in emails with {{ fullpublichistory }}
     * These should not know about pinned request history
     * @return HasMany
     */
    public function publicHistory()
    {
        return $this->hasMany(History::class, 'xRequest')
            ->where('fPublic', 1)
            ->orderBy('xRequestHistory', 'DESC');
    }

    /**
     * History as HelpSpot Staff see it, including pinned request history
     * @return HasMany
     */
    public function history()
    {
        return $this->hasMany(History::class, 'xRequest')
            ->orderBy('fPinned', 'DESC')
            ->orderBy('xRequestHistory', 'DESC');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'xCategory');
    }

    /**
     * Find if a request has hit it's history limit
     * @param $requestId
     * @return bool
     */
    public static function reachedHistoryLimit($requestId)
    {
        // On new requests and possible other places? this could be an empty string.
        if (! is_numeric($requestId)) {
            return 0;
        }

        $currentHistory = Cache::remember(Manager::history_count_key($requestId), Manager::CACHE_HISTORY_COUNT_MINUTES, function () use ($requestId) {
            try {
                return static::findOrFail($requestId)
                    ->history()->count();
            } catch (\Exception $e) {
                return 0;
            }
        });

        return $currentHistory >= hs_setting('cHD_MAX_REQUEST_HISTORY', 1500);
    }

    public function assigned()
    {
        return $this->hasOne(User::class, 'xPerson', 'xPersonAssignedTo');
    }

    public function customerFullName()
    {
        return trim($this->sFirstName . ' ' . $this->sLastName);
    }
}
