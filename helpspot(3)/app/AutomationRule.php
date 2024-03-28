<?php

namespace HS;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    protected $table = 'HS_Automation_Rules';

    protected $primaryKey = 'xAutoRule';

    public $timestamps = false;

    protected $guarded = [];

    protected $dates = [
        'dtNextRun',
    ];

    protected $casts = [
        'fDeleted' => 'boolean',
        'fDirectOnly' => 'boolean',
    ];

    /**
     * Active (not-deleted) automation rules
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('fDeleted', 0);
    }

    /**
     * Inactive (deactivated, deleted) automation rules
     * @param $query
     * @return mixed
     */
    public function scopeInactive($query)
    {
        return $query->where('fDeleted', 1);
    }

    /**
     * Scheduled automation rules who are due to be run
     * @param $query
     * @return mixed
     */
    public function scopePending($query)
    {
        return $query->where('dtNextRun', '<=', now());
    }

    /**
     * Automation rules which are scheduled (do not use "direct only")
     * @param $query
     * @return mixed
     */
    public function scopeSchedulable($query)
    {
        return $query->where('fDirectOnly', false);
    }

    /**
     * If this is not "direct only", then
     * it is a scheduled Automation Rule
     * @return bool
     */
    public function isScheduled()
    {
        return ! $this->fDirectOnly;
    }

    /**
     * Calculate and save the next run date
     * @return $this
     */
    public function setNextRunTime()
    {
        $this->dtNextRun = $this->nextRunTime();
        $this->save();
        return $this;
    }

    /**
     * Calculate the next run date
     * @return Carbon|int
     */
    public function nextRunTime()
    {
        switch($this->sSchedule) {
            case 'monthly':
                return Carbon::now()->addMonth()->startOfMonth()->timestamp;
                break;
            case 'weekly':
                return Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY)->timestamp;
                break;
            case 'daily':
                return Carbon::now()->addDay()->startOfDay()->timestamp;
                break;
            case 'twice_daily':
                if (Carbon::now()->format('H') < 12) {
                    // If we're before noon, set time to noon today
                    return Carbon::now()->setTime(12, 0)->timestamp;
                } else {
                    // If we're after noon, go to start of next day (effectively moving us 12 hours past noon)
                    return Carbon::now()->addDay()->startOfDay()->timestamp;
                }
                break;
            case 'every_hour':
                return Carbon::now()->setTime(
                    Carbon::now()->format('H') + 1, // This DOES correctly get us into "tomorrow" if the hour is 23 + 1
                    0
                )->timestamp;
                break;
            case 'every_5_minutes':
                return Carbon::now()->startOfMinute()->addMinutes(5)->timestamp;
                break;
            case 'every_minute':
            default:
                return Carbon::now()->startOfMinute()->addMinute()->timestamp;
        }
    }
}
