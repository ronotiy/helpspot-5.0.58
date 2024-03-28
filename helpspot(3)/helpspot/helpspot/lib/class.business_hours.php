<?php

// SECURITY: Don't allow direct calls
//if (!defined('cBASEPATH')) die();

class business_hours
{
    //Array of working hours for each day, default to 9->5 M->F (store as 24hr clock in decimal format)
    public $bizhours = [
                        0 => false,
                        1 => ['start'=>9, 'end'=>17],
                        2 => ['start'=>9, 'end'=>17],
                        3 => ['start'=>9, 'end'=>17],
                        4 => ['start'=>9, 'end'=>17],
                        5 => ['start'=>9, 'end'=>17],
                        6 => false,
                    ];

    //Array of holidays. Key is date, array is hours. ex: '05.10.2009' => array('start'=>9,'end'=>12,'date_ts'=>1242052473,'name'=>'Labor Day')
    public $holidays = [];

    //Constructor
    public function __construct($bizhours = false)
    {
        if ($bizhours) {
            $this->bizhours = $bizhours;
        } elseif (hs_setting('cHD_BUSINESS_HOURS')) {
            $bh = hs_unserialize(hs_setting('cHD_BUSINESS_HOURS'));

            if (! isset($bh['holidays'])) {
                $bh['holidays'] = [];
            }

            $this->bizhours = $bh['bizhours'];
            $this->holidays = $bh['holidays'];
        }
    }

    //Determine if a TS falls inside or outside business hours
    public function inBizHours($ts)
    {
        $isbiz = false;

        //Find day of the week and date of TS
        $dow = date('w', $ts);
        $hour = date('G', $ts) + (date('i', $ts) / 60); //turn into decimal minutes
        $date = date('Y.m.d', $ts);

        //Check days of week
        if ($this->bizhours[$dow]) { //check if this day of week has hours at all
            if ($this->bizhours[$dow]['start'] <= $hour && $hour <= $this->bizhours[$dow]['end']) { //check that we're in a valid hour of the day
                $isbiz = true;
            }
        }

        //Check date against any holidays
        if (isset($this->holidays[$date])) {
            //Note $this->holidays[$date] can = false if it's a holiday with no biz hours at all
            if ($this->holidays[$date] != false && $this->holidays[$date]['start'] <= $hour && $hour <= $this->holidays[$date]['end']) { //check that we're in a valid hour of the day
                $isbiz = true;
            } else {
                $isbiz = false; //If this is a holiday but not in special hours then need to override here to make sure we return not in biz hours even though may have been true above in normal hour check
            }
        }

        return $isbiz;
    }

    /**
     * Is the timestamp in a holiday?
     *
     * @param $ts
     * @return bool
     */
    public function isHoliday($ts)
    {
        $hour = date('G', $ts) + (date('i', $ts) / 60); //turn into decimal minutes
        $date = date('Y.m.d', $ts);

        if (isset($this->holidays[$date])) {
            //Note $this->holidays[$date] can = false if it's a holiday with no biz hours at all
            if ($this->holidays[$date] != false && $this->holidays[$date]['start'] <= $hour && $hour <= $this->holidays[$date]['end']) { //check that we're in a valid hour of the day
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    //Makes sure the start and end times entered in are in valid biz hours, if not move them to remove non-biz hours from calculations
    //returns the number of business seconds between 2 dates
    public function getBizTime($start, $end)
    {
        $seconds = 0;
        $inbiz_start = $this->inBizHours($start);
        $inbiz_end = $this->inBizHours($end);

        if ($inbiz_start && $inbiz_end) {				//Logic - Both are in biz hours
            $seconds = $this->bizHoursBetween($start, $end);
        } elseif ($inbiz_start && ! $inbiz_end) {		//Logic - if start time IN biz hours but end time NOT then find nearest previous biz hour and use that as end
            $seconds = $this->bizHoursBetween($start, $this->findNextPrevBizHour($end, 'prev'));
        } elseif (! $inbiz_start && $inbiz_end) {		//Logic - if start time NOT in biz hours but end time IS then fix
            if ($this->isHoliday($start)) {
                return 0;
            }
            $seconds = $this->bizHoursBetween($this->findNextPrevBizHour($start, 'next'), $end);
        } elseif (! $inbiz_start && ! $inbiz_end) { //Logic - if neither time is in biz hours fix (move them both forward I presume?)
            if ($this->isHoliday($start)) {
                return 0;
            }
            $seconds = $this->bizHoursBetween($this->findNextPrevBizHour($start, 'next'), $this->findNextPrevBizHour($end, 'prev'));
        }

        return $seconds > 0 ? $seconds : 0;
    }

    //Find the last business hour from a given timestamp
    public function findNextPrevBizHour($ts, $type)
    {
        $newts = $ts;
        $loop = 100; //max out at looking back/forward 100 days for day with biz hours. Shouldn't be any reason to look farther back/forward than this.

        //Loop over days until we find one with business hours
        do {
            $dow = date('w', $newts);
            $date = date('Y.m.d', $newts);
            $hour = date('G', $newts) + (date('i', $newts) / 60); //turn into decimal minutes
            $holiday_hours = isset($this->holidays[$date]) && isset($this->holidays[$date]['start']) ? true : false; //If it's a holiday and if there are hours during this holiday

            //Check if this is a day with business hours
            if ($this->bizhours[$dow] && (! isset($this->holidays[$date]) || $holiday_hours)) { //Day has hours and is not a holiday or not a holiday with no hours.
                //Figure out if we're using holiday time or not
                if ($holiday_hours) {
                    $time = $this->holidays[$date];
                } else {
                    $time = $this->bizhours[$dow];
                }

                //It also is ahead of the time hours start on that day, if not we need to go back a day. For ex: time is 8am, but hours don't stat to 9. we need to go back to the prev day
                if ($type == 'prev') {
                    if ($time['start'] <= $hour) {
                        $hour = floor($time['end']);
                        $min = explode('.', $time['end']);
                        $min = (isset($min[1]) ? floatval('.'.$min[1]) * 60 : 0);
                        $newts = mktime($hour, $min, 0, date('n', $newts), date('j', $newts), date('Y', $newts));

                        //Breat the loop
                        break;
                    }
                } else {
                    if ($time['start'] >= $hour) {
                        $hour = floor($time['start']);
                        $min = explode('.', $time['start']);
                        $min = (isset($min[1]) ? floatval('.'.$min[1]) * 60 : 0);
                        $newts = mktime($hour, $min, 0, date('n', $newts), date('j', $newts), date('Y', $newts));

                        //Breat the loop
                        break;
                    }
                }
            }

            //Move the TS one day back and try again. If we've gotten here it means we've moving back/forward a day so set to midnight of the day we're moving to
            if ($type == 'prev') {
                $newts = mktime(23, 59, 59, date('n', $newts), date('j', $newts) - 1, date('Y', $newts));
            } else {
                $newts = mktime(0, 0, 0, date('n', $newts), date('j', $newts) + 1, date('Y', $newts));
            }

            $loop--;
        } while ($loop > 0);

        return $newts;
    }

    //Calculate the business hours between 2 times
    //getBizTime already cleans up the time so the start/end times passed in here are in biz hours for sure so no need to check
    public function bizHoursBetween($start, $end)
    {
        $seconds = 0;
        $days = $this->daysBetweenDates($start, $end);
        $current_day = $start;
        $start_in_seconds = ((date('G', $start) * 60 * 60) + (date('i', $start) * 60));
        $end_in_seconds = ((date('G', $end) * 60 * 60) + (date('i', $end) * 60));

        if ($end < $start) {
            return 0;
        }

        //Loop over the number of days between the start and end and find biz time in each day
        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y.m.d', $current_day);

            //Determine start and end time for this day based on normal and holiday schedule and convert to seconds
            if (isset($this->holidays[$date])) {
                $start_bizday = isset($this->holidays[$date]['start']) ? ($this->holidays[$date]['start'] * 60 * 60) : 0;
                $end_bizday = isset($this->holidays[$date]['end']) ? ($this->holidays[$date]['end'] * 60 * 60) : 0;
            } else {
                $dow = date('w', $current_day); //since it's not a holiday find the dow
                $start_bizday = isset($this->bizhours[$dow]['start']) ? ($this->bizhours[$dow]['start'] * 60 * 60) : 0;
                $end_bizday = isset($this->bizhours[$dow]['end']) ? ($this->bizhours[$dow]['end'] * 60 * 60) : 0;
            }

            /*
            FIND PROPER START/END TIMES FOR EACH TYPE OF DAY
            */

            //Find hours on first day, may be partial
            if ($i == 0) {
                $calc_start = $start_in_seconds;
                if ($days == 0) { //There's only 1 day
                    $calc_end = $end_in_seconds;
                } else {
                    $calc_end = $end_bizday;
                }
                //Find hours on last day, may be partial
            } elseif ($i == $days && $i > 0) { //only go through this block on last day, where more than 1 day not when there's only 1 day which is above
                $calc_start = $start_bizday;
                $calc_end = $end_in_seconds;
            //In between day
            } else { //This item spans a biz day so we take all those biz hours and count them
                $calc_start = $start_bizday;
                $calc_end = $end_bizday;
            }

            $seconds = $seconds + ($calc_end - $calc_start);

            //Increment to the next day. This may be off by an hour during DST but doesn't matter bc just used to find the next date not used in actual hour calculation. This is much faster than having another date function in the loop.
            $current_day = $current_day + 86400;
        }

        return $seconds;
    }

    //Find the number of days between 2 ts
    public function daysBetweenDates($start, $end)
    {
        if (date('Y', $end) != date('Y', $start)) {
            $firstyear = date('Y', $start);
            $enddate = strtotime('31 dec '.$firstyear);
            $dayend = date('z', $enddate);
            $rem = $dayend - date('z', $start) + 1; //+1 because we don't want diff we want remaining

            for ($year = date('Y', $start) + 1; $year < date('Y', $end); $year++) {
                $enddate = strtotime('31 dec '.$year);
                $rem += date('z', $enddate) + 1; //+1 because we don't want diff we want remaining
            }

            $res = $rem + date('z', $end);
        } else {
            $res = date('z', $end) - date('z', $start);
        }

        return $res;
    }
}
