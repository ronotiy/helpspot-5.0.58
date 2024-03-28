<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

class reports
{
    //Filter object
    public $filter;

    //All options passed into report
    public $options;

    //Business hours object
    public $bizhours;

    //Use business hours
    public $usebizhours = false;

    //Filter conditions
    public $conditions = [];

    //List type reports store their RS here. Allows CSV to access raw data
    public $list_rs = false;

    //List type reports store their columns here so CSV has access
    public $list_columns = [];

    //Time
    public $time_from;

    public $time_to;

    //Interval of report
    public $interval = 'day';

    //Grouping
    public $grouping = 'date_day';

    //Timespan report covers
    public $interval_set = [];

    //Report based on closing time vs open time
    public $date_type = 'open';

    //Title of report
    public $title = '';

    //The CSV Object
    public $csv;

    //Option to group above
    public $option_group_above = 20;

    //Saved report title
    public $saved_title = '';

    //Constructor
    public function __construct($options)
    {
        $this->options = $options;

        //Init biz hours object
        $this->bizhours = new business_hours;

        //Set saved title
        $this->saved_title = $this->options['sReport'];

        //set basic time info
        $this->time_from = static::repCreateFromDT((isset($this->options['from']) && ! empty($this->options['from']) ? $this->options['from'] : false));
        $this->time_to = static::repCreateToDT((isset($this->options['to']) && ! empty($this->options['to']) ? $this->options['to'] : false));

        //setup grouping variables
        if (isset($this->options['graph_grouping'])) {
            $this->grouping = $this->options['graph_grouping'];
        }

        if ($this->grouping == 'date_day' || $this->grouping == 'date_hour' || $this->grouping == 'date_month' || $this->grouping == 'date_year'
            || $this->grouping == 'date_agg_hour' || $this->grouping == 'date_agg_day' || $this->grouping == 'date_agg_month') {
            $this->interval = str_replace('date_', '', $this->grouping);
            $this->interval_set = $this->_intervals();
        } else {
            $this->interval = 'nondate'; //Non date groupings use the full from->to range
            $this->interval_set[0] = ['from'=>$this->time_from, 'to'=>$this->time_to];
        }

        //Use close vs open dates
        if (isset($this->options['date_type']) && $this->options['date_type']) {
            $this->date_type = $this->options['date_type'];
        }
        if (isset($this->options['usebizhours']) && $this->options['usebizhours']) {
            $this->usebizhours = $this->options['usebizhours'];
        }
        if (isset($this->options['option_group_above']) && $this->options['option_group_above']) {
            $this->option_group_above = $this->options['option_group_above'];
        }
    }

    public function _initFilter($cols = [], $filter_dates = false, $ttreport = false)
    {
        //Setup conditions
        $this->conditions = $this->options;
        if ($filter_dates) {
            if ($ttreport) {
                $this->conditions['condition_reportTime_1'] = 'betweenTTDates';
            } else {
                $this->conditions['condition_reportTime_1'] = ($this->date_type == 'close' ? 'betweenClosedDates' : 'betweenDates');
            }
            $this->conditions['condition_reportTime_2'] = $filter_dates;
        }

        //If grouping by portal we have to limit results to ones from a portal
        if ($this->grouping == 'xPortal') {
            $this->conditions['condition_portalgroup_1'] = 'fOpenedVia';
            $this->conditions['condition_portalgroup_2'] = 'is';
            $this->conditions['condition_portalgroup_3'] = 7;
        }

        //Setup filter used by most reports
        $rule = new hs_auto_rule();
        $rule->returnrs = true; //return result set instead of doing actions
        $rule->SetAutoRule($this->conditions);

        $this->filter = new hs_filter();
        $this->filter->is_report = true;
        $this->filter->filterDef = $rule->getFilterConditions();
        $this->filter->filterDef['displayColumns'] = $cols;
    }

    //Helper method which returns an array of timestamps for the interval to be covered.
    public function _intervals($interval = false, $fromdate = false, $time_to = false)
    {
        $out = [];
        $fromdate = ($fromdate ? $fromdate : $this->time_from);
        $time_to = ($time_to ? $time_to : $this->time_to);
        $interval = ($interval ? $interval : $this->interval);
        $todate = 0;
        $count = 0;

        if ($interval == 'minute') {
            $format = cHD_DATEFORMAT;
            $format_short = '%H:%M';
        } elseif ($interval == 'hour') {
            $format = cHD_DATEFORMAT;
            $format_short = '%H';
        } elseif ($interval == 'day') {
            $format = '%a, '.cHD_SHORTDATEFORMAT;
            $format_short = '%e';
        } elseif ($interval == 'month') {
            $format = '%B, %Y';
            $format_short = '%m';
        //REST OF IMPLEMENTATION BELOW
        } elseif ($interval == 'year') {
            $format = '%Y';
            $format_short = '%Y';
        } elseif ($interval == 'agg_hour') { //aggregate report
            $format = utf8_ucfirst(lg_hour).' %H';
            $format_short = '%H';
        } elseif ($interval == 'agg_day') { //aggregate report
            $format = '%A (%w)';
            $format_short = '%A (%w)';
        } elseif ($interval == 'agg_month') { //aggregate report
            $format = '%b (%m)';
            $format_short = '%b (%m)';
        }

        while ($fromdate < $time_to) {
            //We always return all values for an interval, to do this we must reset fromdate as well as interval
            if ($interval == 'minute') {
                $fromdate = mktime(date('G', $fromdate), date('i', $fromdate), 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
                $todate = mktime(date('G', $fromdate), date('i', $fromdate) + 1, 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
            } elseif ($interval == 'hour' || $interval == 'agg_hour') {
                $fromdate = mktime(date('G', $fromdate), 0, 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
                $todate = mktime(date('G', $fromdate) + 1, 0, 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
            } elseif ($interval == 'day' || $interval == 'agg_day') {
                $fromdate = mktime(0, 0, 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
                $todate = mktime(0, 0, 0, date('n', $fromdate), date('d', $fromdate) + 1, date('Y', $fromdate));
            } elseif ($interval == 'month' || $interval == 'agg_month') {
                $fromdate = mktime(0, 0, 0, date('n', $fromdate), 1, date('Y', $fromdate));
                $todate = mktime(0, 0, 0, date('n', $fromdate) + 1, 1, date('Y', $fromdate));
            } elseif ($interval == 'year') {
                // Use the real from and to dates so we get an accurate total for same year grouping
                if (date('Y', $fromdate) == date('Y', $time_to) and $count == 0) {
                    $fromdate = mktime(0, 0, 0, date('n', $fromdate), date('d', $fromdate), date('Y', $fromdate));
                    $todate = mktime(0, 0, 0, date('n', $time_to), date('d', $time_to), date('Y', $time_to));
                } else {
                    $fromdate = mktime(0, 0, 0, 1, 1, date('Y', $fromdate));
                    $todate = mktime(0, 0, 0, 1, 1, date('Y', $fromdate) + 1);
                }
            }

            $out[] = ['label'=>hs_showCustomDate($fromdate, (isset($format_override) ? $format_override : $format)),
                           'label_short'=>hs_showCustomDate($fromdate, (isset($format_override) ? $format_override : $format_short)),
                           'from'=>$fromdate,
                           'to'=>$todate, ];

            // Adjust the from date if it's same year grouping to keep in sync.
            if ($interval == 'year' and date('Y', $fromdate) == date('Y', $time_to) and $count == 0) {
                $todate = mktime(0, 0, 0, 1, 1, date('Y', $fromdate) + 1);
            }

            $count++;
            $fromdate = $todate;
        }

        return $out;
    }

    public function _buildMeta($data)
    {
        $rs = $data['meta'];

        $max = count($data['data'])
            ? max($data['data'])
            : 0;
        $min = count($data['data'])
            ? min($data['data'])
            : 0;
        $rs['average'] = round(stats_average($data['data']), 1);
        $rs['median'] = stats_median($data['data']);
        $rs['max'] = ($max > 10 ? $max : 10);
        $rs['max_stats'] = $max;
        $rs['min'] = $min;
        $rs['sum'] = array_sum($data['data']);

        return $rs;
    }

    public function _fillOutData($out)
    {
        //For non-date groupings sort and add in values which were not present in result set requests
        if ($this->interval == 'nondate') {
            switch ($this->grouping) {
                case 'xCategory':
                    $cats = $GLOBALS['DB']->GetCol('SELECT DISTINCT sCategory FROM HS_Category WHERE fDeleted = 0');
                    foreach ($cats as $cat) {
                        if (! isset($out['series1']['data'][$cat])) {
                            $out['series1']['data'][$cat] = 0;
                        }
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$cat])) {
                            $out['series2']['data'][$cat] = 0;
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$cat])) {
                            $out['series3']['data'][$cat] = 0;
                        }
                    }

                    break;
                case 'xStatus':
                    $status = $GLOBALS['DB']->GetCol('SELECT DISTINCT sStatus FROM HS_luStatus WHERE fDeleted = 0');
                    foreach ($status as $s) {
                        if (! isset($out['series1']['data'][$s]) && $s != lg_spam) {
                            $out['series1']['data'][$s] = 0;
                        }
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$s]) && $s != lg_spam) {
                            $out['series2']['data'][$s] = 0;
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$s]) && $s != lg_spam) {
                            $out['series3']['data'][$s] = 0;
                        }
                    }

                    break;
                case 'xPersonAssignedTo':
                    $person = $GLOBALS['DB']->GetCol('SELECT DISTINCT '.dbConcat(' ', 'sFname', 'sLname').' AS fullname FROM HS_Person WHERE fDeleted = 0');
                    foreach ($person as $p) {
                        if (! isset($out['series1']['data'][$p])) {
                            $out['series1']['data'][$p] = 0;
                        }
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$p])) {
                            $out['series2']['data'][$p] = 0;
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$p])) {
                            $out['series3']['data'][$p] = 0;
                        }
                    }

                    break;
                case 'fOpenedVia':
                    //Get portals
                    $portals = $GLOBALS['DB']->Execute('SELECT xPortal,sPortalName FROM HS_Multi_Portal');
                    $portals = rsToArray($portals, 'xPortal', false);

                    //Get mailboxes
                    $mailboxes = $GLOBALS['DB']->Execute('SELECT xMailbox,sReplyEmail AS mailboxname FROM HS_Mailboxes');
                    $mailboxes = rsToArray($mailboxes, 'xMailbox', false);

                    foreach ($out['series1']['data'] as $key=>$value) {
                        $ids = explode('#-#', $key);
                        $newkey = '';

                        if ($ids[0] == 7) { //Portal
                            $newkey = ($ids[2] == 0 ? lg_reports_portal.': '.lg_reports_portal_primary : lg_reports_portal.': '.$portals[$ids[2]]['sPortalName'].' ('.$portals[$ids[2]]['xPortal'].')');
                        } elseif ($ids[0] == 1) { //Mailboxes
                            if ($ids[1] > 0) {
                                $email = explode('@', $mailboxes[$ids[1]]['mailboxname']);
                                $newkey = lg_reports_email.': '.$email[0].' ('.$mailboxes[$ids[1]]['xMailbox'].')';
                            } else { //When someone manually selects "email" but not associated with a mailbox
                                $newkey = lg_reports_otheremail;
                            }
                        } else { //Everything else
                            $newkey = $GLOBALS['openedVia'][$ids[0]];
                        }

                        $out['series1']['data'][$newkey] = $out['series1']['data'][$key];
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$newkey])) {
                            $out['series2']['data'][$newkey] = $out['series2']['data'][$key];
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$newkey])) {
                            $out['series3']['data'][$newkey] = $out['series3']['data'][$key];
                        }
                        unset($out['series1']['data'][$key]);
                        unset($out['series2']['data'][$key]);
                        unset($out['series3']['data'][$key]);
                    }

                    //Add in mailboxes, portals and other types with no requests
                    foreach ($GLOBALS['openedVia'] as $k=>$v) {
                        if ($k != 0 && $k != 1 && $k != 7 && ! isset($out['series1']['data'][$v])) {
                            if (! isset($out['series1']['data'][$v])) {
                                $out['series1']['data'][$v] = 0;
                            }
                            if (isset($out['series2']) && ! isset($out['series2']['data'][$v])) {
                                $out['series2']['data'][$v] = 0;
                            }
                            if (isset($out['series3']) && ! isset($out['series3']['data'][$v])) {
                                $out['series3']['data'][$v] = 0;
                            }
                        }
                    }

                    foreach ($portals as $k=>$v) {
                        $newkey = lg_reports_portal.': '.$v['sPortalName'].' ('.$k.')';
                        if (! isset($out['series1']['data'][$newkey])) {
                            $out['series1']['data'][$newkey] = 0;
                        }
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$newkey])) {
                            $out['series2']['data'][$newkey] = 0;
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$newkey])) {
                            $out['series3']['data'][$newkey] = 0;
                        }
                    }

                    foreach ($mailboxes as $k=>$v) {
                        $email = explode('@', $mailboxes[$k]['mailboxname']);
                        $newkey = lg_reports_email.': '.$email[0].' ('.$k.')';
                        if (! isset($out['series1']['data'][$newkey])) {
                            $out['series1']['data'][$newkey] = 0;
                        }
                        if (isset($out['series2']) && ! isset($out['series2']['data'][$newkey])) {
                            $out['series2']['data'][$newkey] = 0;
                        }
                        if (isset($out['series3']) && ! isset($out['series3']['data'][$newkey])) {
                            $out['series3']['data'][$newkey] = 0;
                        }
                    }

                    break;
            }

            //Handle custom fields
            if (utf8_strpos($this->grouping, 'Custom') !== false) {
                if (is_array($GLOBALS['customFields'])) {
                    foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                        if ($this->grouping == 'Custom'.$k) {
                            $items = hs_unserialize($fvalue['listItems']);
                            foreach ($items as $p) {
                                if (is_array($p)) {
                                    $this->_drillDownOptions($p, $out);
                                } else {
                                    if (! isset($out['series1']['data'][$p])) {
                                        $out['series1']['data'][$p] = 0;
                                    }
                                    if (isset($out['series2']) && ! isset($out['series2']['data'][$p])) {
                                        $out['series2']['data'][$p] = 0;
                                    }
                                    if (isset($out['series3']) && ! isset($out['series3']['data'][$p])) {
                                        $out['series3']['data'][$p] = 0;
                                    }
                                }
                            }

                            break;
                        }
                    }
                }
            }

            //Sort
            ksort($out['series1']['data']);
            if (isset($out['series2'])) {
                ksort($out['series2']['data']);
            }
            if (isset($out['series3'])) {
                ksort($out['series3']['data']);
            }
        }

        return $out;
    }

    /**
     * Recursive Drill down report series.
     *
     * @param array $data
     * @param array $out
     *
     * @return array
     */
    public function _drillDownOptions(array $data, array $out)
    {
        foreach ($data as $key => $val) {
            if (! isset($out['series1']['data'][$key])) {
                $out['series1']['data'][$key] = 0;
            }
            if (isset($out['series2']) && ! isset($out['series2']['data'][$key])) {
                $out['series2']['data'][$key] = 0;
            }
            if (isset($out['series3']) && ! isset($out['series3']['data'][$key])) {
                $out['series3']['data'][$key] = 0;
            }
            if (is_array($val)) {
                $out = $this->_drillDownOptions($val, $out);
            }
        }

        return $out;
    }

    public function _getKey($int, $row_label = '')
    {
        $key = (isset($int['label_short']) ? $int['label_short'] : '');
        $key .= (isset($int['label']) ? '|'.str_replace('|', ' - ', $int['label']) : str_replace('|', ' - ', $this->empty_group_label($row_label)));

        return $key;
    }

    //Get from time
    public static function repCreateFromDT($date = false)
    {
        if ($date and ! is_numeric($date)) {
            $date = jsDateToTime($date, hs_setting('cHD_POPUPCALSHORTDATEFORMAT'));
        }

        //Base date. Either passed in value or date -14 from today (calc'd as 13 days + today)
        $base = ($date ? $date : mktime(0, 0, 0, date('m'), date('j') - 13, date('Y')));

        //Return timestamp
        return mktime(0, 0, 0, date('m', $base), date('j', $base), date('Y', $base));
    }

    //Get to time
    public static function repCreateToDT($date = false)
    {
        if ($date and ! is_numeric($date)) {
            $date = jsDateToTime($date, hs_setting('cHD_POPUPCALSHORTDATEFORMAT'));
        }

        //Base date. Either passed in value or 11:59:59 of today
        $base = ($date ? $date : time());

        //Return timestamp
        return mktime(0, -1, 0, date('m', $base), date('j', $base) + 1, date('Y', $base));
    }

    public function empty_group_label($value)
    {
        if (empty($value)) {
            switch ($this->grouping) {
                case 'xCategory':
                case 'xPersonAssignedTo':
                    return lg_reports_emptygroup_unassigned;

                    break;
                case 'note_creator':
                    return lg_reports_emptygroup_customer;

                    break;
                default:
                    return lg_reports_emptygroup_empty;

                    break;
            }
        } else {
            return $value;
        }
    }

    public function productivity_csv($report)
    {
        $this->csv = new hscsv;
        //CSV Data
        $data = $this->$report();
        //CSV Meta
        $this->csv->setTitle($this->getTitle());

        //Write headers
        $headers = [lg_reports_col_grouping];
        $headers[] = $data['series1']['name'];
        $headers[] = $data['series2']['name'];
        $headers[] = $data['series3']['name'];
        $headers[] = $data['series4']['name'];
        $headers[] = $data['series5']['name'];
        $headers[] = $data['series6']['name'];
        $headers[] = $data['series7']['name'];

        //Need to force quotes on the header due to this odd bug: http://support.microsoft.com/kb/323626
        $this->csv->writeRow($headers, false, true);

        //Write data cells
        foreach ($data['series1']['data'] as $k=>$v) {
            $row = [$data['dates'][$k]];
            $row[] = $v;
            $row[] = $data['series2']['data'][$k];
            $row[] = $data['series3']['data'][$k];
            $row[] = $data['series4']['data'][$k];
            $row[] = $data['series5']['data'][$k];
            $row[] = $data['series6']['data'][$k];
            $row[] = $data['series7']['data'][$k];

            $this->csv->writeRow($row, false);
        }
    }

    public function matrix_csv()
    {
        $this->csv = new hscsv;
        //CSV Data
        $data = $this->report_matrix();
        //CSV Meta
        $this->csv->setTitle($this->getTitle());

        //Write headers
        $headers = [];
        $headers[0] = ''; //First X header is empty
        foreach ($data['x_categories'] as $x) {
            $headers[] = $x;
        }

        //Need to force quotes on the header due to this odd bug: http://support.microsoft.com/kb/323626
        $this->csv->writeRow($headers, false, true);

        //Write data cells
        foreach ($data['grid_points'] as $y_index=>$x_values_array) {
            $row = [$data['y_categories'][$y_index]];

            foreach ($x_values_array as $x_index=>$value) {
                array_push($row, $value);
            }

            $this->csv->writeRow($row, false);
        }
    }

    public function create_csv($report)
    {
        $this->csv = new hscsv;
        //CSV Data
        $data = $this->$report();
        //CSV Meta
        $this->csv->setTitle($this->getTitle());

        //Write headers
        $headers = [lg_reports_col_grouping];
        if ($data['series1']['meta']['ylabel']) {
            $headers[] = $data['series1']['meta']['ylabel'];
        }
        if ($data['series2']['meta']['ylabel']) {
            $headers[] = $data['series2']['meta']['ylabel'];
        }
        if ($data['series3']['meta']['ylabel']) {
            $headers[] = $data['series3']['meta']['ylabel'];
        }

        //Need to force quotes on the header due to this odd bug: http://support.microsoft.com/kb/323626
        $this->csv->writeRow($headers, false, true);

        //Write data cells
        foreach ($data['series1']['data'] as $k=>$v) {
            $function = false;

            if (utf8_strpos($k, '|') !== false) {
                $label = explode('|', $k);
                $label = $label[1];
            } else {
                $label = $k;
            }

            $row = [$label, $v];

            if (isset($data['series2']['data'][$k])) {
                array_push($row, $data['series2']['data'][$k]);
            }

            if (isset($data['series3']['data'][$k])) {
                array_push($row, $data['series3']['data'][$k]);
            }

            $this->csv->writeRow($row, $function);
        }
    }

    public function list_create_csv($report)
    {
        $this->csv = new hscsv;
        //Run report, ignore output
        $this->$report();

        //CSV Meta
        $this->csv->setTitle($this->getTitle());

        //Write headers
        foreach ($this->list_columns as $k=>$col) {
            $headers[] = $col['label'];
        }

        //Need to force quotes on the header due to this odd bug: http://support.microsoft.com/kb/323626
        $this->csv->writeRow($headers, false, true);

        //Write data cells
        $this->list_rs->Move(0);
        while ($data = $this->list_rs->FetchRow()) {
            $row = [];

            foreach ($this->list_columns as $k=>$col) {
                if (isset($col['function']) && ! empty($col['function'])) {
                    $f = $col['function'];
                    $row[] = $f($data[$col['fields']]);
                } else {
                    $row[] = $data[$col['fields']];
                }
            }

            $this->csv->writeRow($row, $function);
        }
    }

    public function getTitle()
    {
        if (! empty($this->saved_title)) {
            return $this->saved_title.' ('.$this->title.')';
        } else {
            return $this->title;
        }
    }

    //Reports
    public function report_over_time()
    {
        $this->title = lg_reports_reqs_over_time;

        $out = ['meta'=>['series_title'=>lg_reports_reqs_over_time,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_reqs_over_time_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'areaspline',
                                                    'plotLineData'=>true, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $k=>$int) {
            $this->_initFilter(['report_count', 'report_grouping_'.$this->grouping], $int['from'].','.$int['to']);

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $val = $row['report_count'];
                    $key = $this->_getKey($int, $row['label']);

                    //Agg types need to be added together on same key
                    $out['series1']['data'][$key] += $val;
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }
        }

        $out = $this->_fillOutData($out);

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_first_response()
    {
        $this->title = lg_reports_speed_to_first;
        $requests = [];

        $out = ['meta'=>['series_title'=>lg_reports_speed_to_first,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_speed_to_first_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'column',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series2'=>['meta'=>['ylabel'=>lg_reports_chartlabel_median.' ('.constant('lg_reports_speedby_'.$this->options['speedby']).')',
                                                    'tooltip'=>lg_reports_chartlabel_median,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series3'=>['meta'=>['ylabel'=>lg_reports_chartlabel_average.' ('.constant('lg_reports_speedby_'.$this->options['speedby']).')',
                                                    'tooltip'=>lg_reports_chartlabel_average,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $int_key=>$int) {
            $this->_initFilter(['report_firstresponse'], $int['from'].','.$int['to']);

            //Array of individual requests in a timespan and the seconds between open and first response
            $key = $this->_getKey($int);
            $requests[$key] = [];

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                    if ($row['dtGMTOpened'] < $row['dtfirstupdate']) {
                        if ($this->usebizhours) {
                            $requests[$key][$row['xRequest']] = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtfirstupdate']);
                        } else {
                            $requests[$key][$row['xRequest']] = $row['dtfirstupdate'] - $row['dtGMTOpened'];
                        }
                    }
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }

            //Process new rs data
            foreach ($requests as $k=>$v) {
                //Setup minutes vs hours
                $speedby = ($this->options['speedby'] == 'hour' ? 3600 : 60);

                //Determine the count
                $out['series1']['data'][$k] = (empty($requests[$k]) ? 0 : count($requests[$k]));

                //Do median, divide by 3600 to make into hours
                $out['series2']['data'][$k] = (empty($requests[$k]) ? 0 : round((stats_median($requests[$k]) / $speedby), 2));

                //Do avg
                $out['series3']['data'][$k] = (empty($requests[$k]) ? 0 : round((stats_average($requests[$k]) / $speedby), 2));
            }
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_first_assignment()
    {
        $this->title = lg_reports_speed_to_first_assignment;
        $requests = [];

        $out = ['meta'=>['series_title'=>lg_reports_speed_to_first_assignment,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_speed_to_first_assignment_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'column',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series2'=>['meta'=>['ylabel'=>lg_reports_chartlabel_median.' ('.constant('lg_reports_speedby_'.$this->options['speedby']).')',
                                                    'tooltip'=>lg_reports_chartlabel_median,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series3'=>['meta'=>['ylabel'=>lg_reports_chartlabel_average.' ('.constant('lg_reports_speedby_'.$this->options['speedby']).')',
                                                    'tooltip'=>lg_reports_chartlabel_average,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $int_key=>$int) {
            $this->_initFilter(['report_firstassign'], $int['from'].','.$int['to']);

            //Array of individual requests in a timespan and the seconds between open and first assign
            $key = $this->_getKey($int);
            $requests[$key] = [];

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                    if ($row['dtGMTOpened'] < $row['dtfirstassign']) {
                        if ($this->usebizhours) {
                            $requests[$key][$row['xRequest']] = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtfirstassign']);
                        } else {
                            $requests[$key][$row['xRequest']] = $row['dtfirstassign'] - $row['dtGMTOpened'];
                        }
                    }
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }

            //Process new rs data
            foreach ($requests as $k=>$v) {
                //Setup minutes vs hours
                $speedby = ($this->options['speedby'] == 'hour' ? 3600 : 60);

                //Determine the count
                $out['series1']['data'][$k] = (empty($requests[$k]) ? 0 : count($requests[$k]));

                //Do median, divide by 3600 to make into hours
                $out['series2']['data'][$k] = (empty($requests[$k]) ? 0 : round((stats_median($requests[$k]) / $speedby), 2));

                //Do avg
                $out['series3']['data'][$k] = (empty($requests[$k]) ? 0 : round((stats_average($requests[$k]) / $speedby), 2));
            }
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_replies_by_count_closed()
    {
        $this->options['condition_fopen_1'] = 'fOpen';
        $this->options['condition_fopen_2'] = 0;

        return $this->report_replies_by_count();
    }

    public function report_replies_by_count()
    {
        $this->title = lg_reports_replies_to_close;
        $max_replies = 0;

        $out = ['meta'=>['series_title'=>lg_reports_replies_to_close,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_replies_to_close_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'xlabel'=>lg_reports_chartlabel_replies,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'column',
                                                    'stacked'=>false,
                                                    'plotLineData'=>false, ],
                                      'data'=>[], ], ];

        $this->_initFilter(['report_repliestoclose'], $this->time_from.','.$this->time_to);
        $rs = $this->filter->outputResultSet();
        if (is_object($rs)) {
            //Since SQL Server can't group by an alias column using an aggregate we need to build our own grouping here
            $grouped_result = [];
            while ($row = $rs->FetchRow()) {
                $grouped_result[$row['pub_hist_count']] = $grouped_result[$row['pub_hist_count']] + 1;
            }

            foreach ($grouped_result as $label=>$ct) {
                $val = $ct;
                $key = $label.'|'.lg_reports_chartlabel_replies.': '.$label;
                $max_replies = $max_replies < $label ? $label : $max_replies;

                $out['series1']['data'][$key] = $val;
            }
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }

        //Add 0's for ones with no labels
        for ($i = 0; $i < $max_replies; $i++) {
            $label = $i.'|'.lg_reports_chartlabel_replies.': '.$i;
            if (! isset($out['series1']['data'][$label])) {
                $out['series1']['data'][$label] = 0;
            }
        }

        uksort($out['series1']['data'], 'strnatcmp');

        //combine those over the max specified
        if ($max_replies > $this->option_group_above) {
            $newarray = [];
            $gt = ('>'.$this->option_group_above.'|'.lg_reports_greaterthan.' '.$this->option_group_above);
            foreach ($out['series1']['data'] as $key=>$val) {
                $labels = explode('|', $key);
                if ($labels[0] <= $this->option_group_above) {
                    $newarray[$key] = $val;
                } else {
                    $newarray[$gt] = $newarray[$gt] + $val;
                }
            }
            $out['series1']['data'] = $newarray;
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_interactions()
    {
        $this->title = lg_reports_interactions;
        $max_replies = 0;

        $out = ['meta'=>['series_title'=>lg_reports_interactions,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_interactions_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_staffpub,
                                                    'tooltip'=>lg_reports_chartlabel_staffpub,
                                                    'type'=>'column',
                                                    'stacked'=>true, ],
                                      'data'=>[], ],
                     'series2'=>['meta'=>['ylabel'=>lg_reports_chartlabel_staffpriv,
                                                    'tooltip'=>lg_reports_chartlabel_staffpriv,
                                                    'type'=>'column',
                                                    'stacked'=>true, ],
                                      'data'=>[], ],
                     'series3'=>['meta'=>['ylabel'=>lg_reports_chartlabel_customer,
                                                    'tooltip'=>lg_reports_chartlabel_customer,
                                                    'type'=>'column',
                                                    'stacked'=>true, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $k=>$int) {
            if ($this->grouping == 'note_creator') { //grouped by creator
                $staffpubrs = $GLOBALS['DB']->Execute('
						SELECT COUNT(*) as report_count, '.dbConcat(' ', 'sFname', 'sLname').' AS label
						FROM HS_Request_History LEFT OUTER JOIN HS_Person ON HS_Request_History.xPerson = HS_Person.xPerson
						WHERE fPublic = 1 AND HS_Request_History.xPerson > 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?
						GROUP BY sFname,sLname', [0, $int['from'], $int['to']]);

                $staffprivrs = $GLOBALS['DB']->Execute('
						SELECT COUNT(*) as report_count, '.dbConcat(' ', 'sFname', 'sLname').' AS label
						FROM HS_Request_History LEFT OUTER JOIN HS_Person ON HS_Request_History.xPerson = HS_Person.xPerson
						WHERE fPublic = 0 AND HS_Request_History.xPerson > 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?
						GROUP BY sFname,sLname', [0, $int['from'], $int['to']]);

                $custrs = $GLOBALS['DB']->Execute('
						SELECT COUNT(*) as report_count, '.dbConcat(' ', 'sFname', 'sLname').' AS label
						FROM HS_Request_History LEFT OUTER JOIN HS_Person ON HS_Request_History.xPerson = HS_Person.xPerson
						WHERE HS_Request_History.xPerson = 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?
						GROUP BY sFname,sLname', [0, $int['from'], $int['to']]);

                while ($row = $staffpubrs->FetchRow()) {
                    $key = $this->_getKey($int, $row['label']);
                    $out['series1']['data'][$key] = $row['report_count'];
                    if (! isset($out['series2']['data'][$key])) {
                        $out['series2']['data'][$key] = 0;
                    }
                    if (! isset($out['series3']['data'][$key])) {
                        $out['series3']['data'][$key] = 0;
                    }
                }

                while ($row = $staffprivrs->FetchRow()) {
                    $key = $this->_getKey($int, $row['label']);
                    if (! isset($out['series1']['data'][$key])) {
                        $out['series1']['data'][$key] = 0;
                    }
                    $out['series2']['data'][$key] = $row['report_count'];
                    if (! isset($out['series3']['data'][$key])) {
                        $out['series3']['data'][$key] = 0;
                    }
                }

                while ($row = $custrs->FetchRow()) {
                    $key = $this->_getKey($int, $row['label']);
                    if (! isset($out['series1']['data'][$key])) {
                        $out['series1']['data'][$key] = 0;
                    }
                    if (! isset($out['series2']['data'][$key])) {
                        $out['series2']['data'][$key] = 0;
                    }
                    $out['series3']['data'][$key] = $row['report_count'];
                }
            } else { //grouped by date
                //Series 1, staff public
                $rs = $GLOBALS['DB']->GetOne('
						SELECT COUNT(*) as report_count
						FROM HS_Request_History
						WHERE fPublic = 1 AND xPerson > 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?', [0, $int['from'], $int['to']]);

                $key = $this->_getKey($int);
                $out['series1']['data'][$key] = intval($rs);

                //Series 2, staff private
                $rs = $GLOBALS['DB']->GetOne('
						SELECT COUNT(*) as report_count
						FROM HS_Request_History
						WHERE fPublic = 0 AND xPerson > 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?', [0, $int['from'], $int['to']]);

                $key = $this->_getKey($int);
                $out['series2']['data'][$key] = intval($rs);

                //Series 3, customers
                $rs = $GLOBALS['DB']->GetOne('
						SELECT COUNT(*) as report_count
						FROM HS_Request_History
						WHERE xPerson = 0
								AND '.dbStrLen('tNote').' > ? AND ? <= HS_Request_History.dtGMTChange AND HS_Request_History.dtGMTChange < ?', [0, $int['from'], $int['to']]);

                $key = $this->_getKey($int);
                $out['series3']['data'][$key] = intval($rs);
            }
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);
        $out['series2']['meta'] = $this->_buildMeta($out['series2']);
        $out['series3']['meta'] = $this->_buildMeta($out['series3']);

        //Because this is a stack we need to get the max for all combined
        foreach ($out['series1']['data'] as $k=>$v) {
            $interval_max = $out['series1']['data'][$k] + $out['series2']['data'][$k] + $out['series3']['data'][$k];
            $out['series1']['meta']['max'] = ($interval_max > $out['series1']['meta']['max'] ? $interval_max : $out['series1']['meta']['max']);
        }

        return $out;
    }

    public function report_resolution_speed()
    {
        $this->title = lg_reports_resolution_speed;
        $requests = [];

        $out = ['meta'=>['series_title'=>lg_reports_resolution_speed,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_resolution_speed_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'column',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series2'=>['meta'=>['ylabel'=>lg_reports_chartlabel_medianwinfo,
                                                    'tooltip'=>lg_reports_chartlabel_median,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series3'=>['meta'=>['ylabel'=>lg_reports_chartlabel_averagewinfo,
                                                    'tooltip'=>lg_reports_chartlabel_average,
                                                    'type'=>'line',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $int_key=>$int) {
            $this->_initFilter(['report_resolution_speed', 'report_grouping_'.$this->grouping], $int['from'].','.$int['to']);

            $tk = $this->_getKey($int);
            if (! isset($requests[$tk])) {
                $requests[$tk] = [];
            }

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $key = $this->_getKey($int, $row['label']);

                    //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                    //In this report this will also limit the results to closed requests only
                    if ($row['dtGMTOpened'] < $row['dtGMTClosed']) {
                        if ($this->usebizhours) {
                            $requests[$key][$row['xRequest']] = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtGMTClosed']);
                        } else {
                            $requests[$key][$row['xRequest']] = ($row['dtGMTClosed'] - $row['dtGMTOpened']);
                        }
                    }
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }

            //Process new rs data
            foreach ($requests as $k=>$v) {
                //Determine the count
                $out['series1']['data'][$k] = count($requests[$k]);

                //Do median, divide by 3600 to make into hours
                $out['series2']['data'][$k] = round((stats_median($requests[$k]) / 3600), 2);

                //Do avg
                $out['series3']['data'][$k] = round((stats_average($requests[$k]) / 3600), 2);
            }
        }

        $out = $this->_fillOutData($out);

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_tt_over_time()
    {
        $this->title = lg_reports_tt_over_time;

        $out = ['meta'=>['series_title'=>lg_reports_tt_over_time,
                                   'series_subtitle'=>hs_showShortDate($this->time_from).' '.lg_to.' '.hs_showShortDate($this->time_to),
                                   'desc'=>lg_reports_tt_over_time_desc, ],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_trackertime,
                                                    'tooltip'=>lg_reports_chartlabel_trackertime_hrs,
                                                    'type'=>'areaspline',
                                                    'plotLineData'=>true, ],
                                      'data'=>[], ], ];

        foreach ($this->interval_set as $k=>$int) {
            $this->_initFilter(['report_timetrack_'.$this->options['billable'], 'report_grouping_'.$this->grouping], $int['from'].','.$int['to'], true);

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    $val = $row['timetrack'];
                    $key = $this->_getKey($int, $row['label']);

                    //Turn seconds into hour decimal
                    $out['series1']['data'][$key] = round((intval($val) / 60 / 60), 2);
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }
        }

        //For non-date groupings sort and add in values which were not present in result set requests
        if ($this->interval == 'nondate') {
            switch ($this->grouping) {
                case 'xPersonTracker':
                    $person = $GLOBALS['DB']->GetCol('SELECT DISTINCT '.dbConcat(' ', 'sFname', 'sLname').' AS fullname FROM HS_Person WHERE fDeleted = 0');
                    foreach ($person as $p) {
                        if (! isset($out['series1']['data'][$p])) {
                            $out['series1']['data'][$p] = 0;
                        }
                    }

                    break;
            }

            //Sort
            ksort($out['series1']['data']);
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function report_time_events()
    {
        $this->title = lg_reports_tt_events;

        $this->_initFilter(['report_time_events_'.$this->options['billable']], $this->time_from.','.$this->time_to, true);
        $this->list_rs = $this->filter->outputResultSet();

        // action() helper will urlencode parameters, but  we need to
        // make sure it contains '%s' instead of its url-encoded form
        $reportxRequestAdminUrl = str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '%s']));
        if (is_object($this->list_rs)) {
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_eventdate, 'sort'=>0, 'width'=>'100', 'fields'=>'dtGMTDate', 'function'=>'hs_showShortDate'];
            $this->list_columns[] = ['type'=>'link', 'label'=>lg_reports_xrequest, 'sort'=>0, 'width'=>'20', 'fields'=>'xRequest', 'code'=>'<a href="'.$reportxRequestAdminUrl.'" target="">%s</a>', 'linkfields'=>['xRequest', 'xRequest']];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_person, 'sort'=>0, 'width'=>'100', 'fields'=>'personname', 'nowrap'=>true];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_customer, 'sort'=>0, 'width'=>'100', 'fields'=>'customername', 'nowrap'=>true];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_customerid, 'sort'=>0, 'width'=>'100', 'fields'=>'sUserId', 'nowrap'=>true];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_desc, 'sort'=>0, 'fields'=>'tDescription', 'function'=>'utf8_strip'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_hoursmin, 'sort'=>0, 'width'=>'20', 'fields'=>'iSeconds', 'function'=>'parseSecondsToTime'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_seconds, 'sort'=>0, 'width'=>'20', 'fields'=>'iSeconds'];
            $this->list_columns[] = ['type'=>'bool', 'label'=>lg_reports_billable, 'sort'=>0, 'width'=>'20', 'fields'=>'fBillable'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_searches_no_results()
    {
        $this->title = lg_report_searches_no_results;

        $this->list_rs = $GLOBALS['DB']->SelectLimit('
						  SELECT sSearch, iResultCount, COUNT(sSearch) AS thecount, HS_Multi_Portal.sPortalName
						  FROM HS_Search_Queries
						  	LEFT OUTER JOIN HS_Multi_Portal ON HS_Search_Queries.xPortal = HS_Multi_Portal.xPortal
						  WHERE '.$this->time_from.' < dtGMTPerformed and dtGMTPerformed <= '.$this->time_to.'
						  GROUP BY sSearch, sPortalName, iResultCount
						  ORDER BY iResultCount ASC, thecount DESC', 200, 0);

        if (is_object($this->list_rs)) {
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchquery, 'sort'=>0, 'fields'=>'sSearch'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchportal, 'sort'=>0, 'width'=>'140', 'fields'=>'sPortalName'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchcount, 'sort'=>0, 'width'=>'90', 'fields'=>'thecount'];
            $this->list_columns[] = ['type'=>'number', 'label'=>lg_reports_resultcount, 'sort'=>0, 'width'=>'80', 'fields'=>'iResultCount'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_searches()
    {
        $this->title = lg_report_searches_agg;

        $this->list_rs = $GLOBALS['DB']->SelectLimit('
						  SELECT sSearch, COUNT(sSearch) AS thecount, HS_Multi_Portal.sPortalName
						  FROM HS_Search_Queries
						  	LEFT OUTER JOIN HS_Multi_Portal ON HS_Search_Queries.xPortal = HS_Multi_Portal.xPortal
						  WHERE '.$this->time_from.' < dtGMTPerformed and dtGMTPerformed <= '.$this->time_to.'
						  GROUP BY sSearch, sPortalName
						  ORDER BY thecount DESC', 200, 0);

        if (is_object($this->list_rs)) {
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchquery, 'sort'=>0, 'fields'=>'sSearch'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchportal, 'sort'=>0, 'width'=>'140', 'fields'=>'sPortalName'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_searchcount, 'sort'=>0, 'width'=>'90', 'fields'=>'thecount'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_kb_helpful()
    {
        $this->title = lg_reports_kb_helpful;
        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        if ($this->options['helpful_type'] == 'helpful') {
            $this->list_rs = apiGetMostHelpful('500');
        } else {
            $this->list_rs = apiGetLeastHelpful('500');
        }

        if (is_object($this->list_rs)) {
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_kb_helpful_book, 'sort'=>0, 'width'=>'200', 'fields'=>'sBookName'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_kb_helpful_page, 'sort'=>0, 'fields'=>'sPageName'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_kb_helpful_type_helpful, 'sort'=>0, 'width'=>'100', 'fields'=>'iHelpful'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_kb_helpful_type_not, 'sort'=>0, 'width'=>'100', 'fields'=>'iNotHelpful'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_responses()
    {
        $this->title = lg_reports_responses;
        $responseby = $this->options['responses_by'];

        $this->list_rs = $GLOBALS['DB']->Execute('
						  SELECT HS_Stats_Responses.xResponse, HS_Responses.sResponseTitle, COUNT(HS_Stats_Responses.xResponse) AS report_count, '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS fullname
						  FROM HS_Stats_Responses
						  	   INNER JOIN HS_Responses ON HS_Stats_Responses.xResponse = HS_Responses.xResponse
						  	   INNER JOIN HS_Person ON HS_Responses.xPerson = HS_Person.xPerson
						  WHERE ( ? <= dtGMTOccured AND dtGMTOccured < ? )
						  		'.(! empty($responseby) ? ' AND HS_Stats_Responses.xPerson = ?' : '').'
						  GROUP BY HS_Stats_Responses.xResponse,sResponseTitle,sFname,sLname
						  ORDER BY report_count DESC', (! empty($responseby) ? [$this->time_from, $this->time_to, $responseby] : [$this->time_from, $this->time_to]));

        if (is_object($this->list_rs)) {
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_response_title, 'sort'=>0, 'fields'=>'sResponseTitle'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_response_creator, 'sort'=>0, 'width'=>'200', 'fields'=>'fullname'];
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_response_count, 'sort'=>0, 'width'=>'20', 'fields'=>'report_count'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_customer_activity()
    {
        $this->title = lg_reports_customer_activity;
        $unique_by = $this->options['unique_by'];

        $this->_initFilter(['report_customer_activity'], $this->time_from.','.$this->time_to);
        $this->filter->idsOnly = true;
        $ids = $this->filter->outputReqIDs();

        if ($unique_by == 'fullname') {
            $select = dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname';
            $this->list_columns[0] = $GLOBALS['filterCols']['fullname'];
        } elseif ($unique_by == 'sEmail') {
            $select = 'HS_Request.sEmail';
            $this->list_columns[0] = $GLOBALS['filterCols']['sEmail'];
        } elseif ($unique_by == 'sUserId') {
            $select = 'HS_Request.sUserId';
            $this->list_columns[0] = $GLOBALS['filterCols']['sUserId'];
        }

        $this->list_rs = $GLOBALS['DB']->SelectLimit('
						  SELECT COUNT(*) AS report_count, '.$select.'
						  FROM HS_Request
						  WHERE '.(! empty($ids) ? 'HS_Request.xRequest IN ('.implode(',', $ids).')' : '1=0').'
						  GROUP BY '.$unique_by.'
						  ORDER BY report_count DESC', $this->options['limit'], 0, []);

        if (is_object($this->list_rs)) {
            unset($this->list_columns[0]['width']);
            $this->list_columns[0]['sort'] = 0;
            $this->list_columns[] = ['type'=>'string', 'label'=>lg_reports_customer_requests, 'sort'=>0, 'width'=>'20', 'fields'=>'report_count'];

            return recordSetTable($this->list_rs, $this->list_columns);
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }
    }

    public function report_productivity_resolution()
    {
        $this->title = lg_reports_resolution_speed;

        $requests = [];

        $out = ['series1'=>['meta'=>['start'=>0,
                                                    'end'=>3600, ],
                                        'name'=>'0 - 1 '.lg_hours,
                                        'data'=>[], ],
                     'series2'=>['meta'=>['start'=>3600,
                                                    'end'=>10800, ],
                                        'name'=>'1 - 3 '.lg_hours,
                                        'data'=>[], ],
                     'series3'=>['meta'=>['start'=>10800,
                                                    'end'=>21600, ],
                                        'name'=>'3 - 6 '.lg_hours,
                                        'data'=>[], ],
                     'series4'=>['meta'=>['start'=>21600,
                                                    'end'=>43200, ],
                                        'name'=>'6 - 12 '.lg_hours,
                                        'data'=>[], ],
                     'series5'=>['meta'=>['start'=>43200,
                                                    'end'=>86400, ],
                                        'name'=>'12 - 24 '.lg_hours,
                                        'data'=>[], ],
                     'series6'=>['meta'=>['start'=>86400,
                                                    'end'=>172800, ],
                                        'name'=>'24 - 48 '.lg_hours,
                                        'data'=>[], ],
                     'series7'=>['meta'=>['start'=>172800,
                                                    'end'=>9999999999, ],
                                        'name'=>'2+ '.lg_days,
                                        'data'=>[], ], ];

        foreach ($this->interval_set as $int_key=>$int) {
            $this->_initFilter(['report_resolution_speed', 'report_grouping_'.$this->grouping], $int['from'].','.$int['to']);

            //Array of individual requests in a timespan and the seconds between open and first response
            $tk = $this->_getKey($int);
            $requests[$tk] = [];

            // It's possible some dates could have no requests, that will throw off the data
            // So we'll first create the array with 0'd out data for all dates
            foreach ($out as $series=>$series_info) {
                $out[$series]['data'][$int_key] = 0;
            }

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                    //In this report this will also limit the results to closed requests only
                    if ($row['dtGMTOpened'] < $row['dtGMTClosed']) {
                        if ($this->usebizhours) {
                            $resolution_time = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtGMTClosed']);
                        } else {
                            $resolution_time = $row['dtGMTClosed'] - $row['dtGMTOpened'];
                        }

                        // Go over each series and add this request into the count appropriate for that series
                        foreach ($out as $series=>$series_info) {
                            if ($series_info['meta']['start'] <= $resolution_time and $resolution_time < $series_info['meta']['end']) {
                                $out[$series]['data'][$int_key]++;
                            }

                            if (! isset($out[$series]['data'][$int_key])) {
                                $out[$series]['data'][$int_key] = 0;
                            }
                        }
                    }
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }
        }

        // Provide the x dates here
        foreach ($this->interval_set as $interval) {
            $out['dates'][] = $interval['label'];
        }

        return $out;
    }

    public function report_productivity_replyspeed()
    {
        $this->title = lg_reports_speed_to_first;
        $requests = [];

        $out = ['series1'=>['meta'=>['start'=>0,
                                                    'end'=>840, ], // 14 full minutes, since < 15
                                        'name'=>'0 - 15 '.lg_minute,
                                        'data'=>[], ],
                     'series2'=>['meta'=>['start'=>840,
                                                    'end'=>1800, ],
                                        'name'=>'15 - 30 '.lg_minute,
                                        'data'=>[], ],
                     'series3'=>['meta'=>['start'=>1800,
                                                    'end'=>3600, ],
                                        'name'=>'30 - 60 '.lg_minute,
                                        'data'=>[], ],
                     'series4'=>['meta'=>['start'=>3600,
                                                    'end'=>10800, ],
                                        'name'=>'1 - 3 '.lg_hours,
                                        'data'=>[], ],
                     'series5'=>['meta'=>['start'=>10800,
                                                    'end'=>21600, ],
                                        'name'=>'3 - 6 '.lg_hours,
                                        'data'=>[], ],
                     'series6'=>['meta'=>['start'=>21600,
                                                    'end'=>43200, ],
                                        'name'=>'6 - 12 '.lg_hours,
                                        'data'=>[], ],
                     'series7'=>['meta'=>['start'=>43200,
                                                    'end'=>9999999999, ],
                                        'name'=>'12+ '.lg_hours,
                                        'data'=>[], ], ];

        foreach ($this->interval_set as $int_key=>$int) {
            $this->_initFilter(['report_firstresponse'], $int['from'].','.$int['to']);

            //Array of individual requests in a timespan and the seconds between open and first response
            $key = $this->_getKey($int);
            $requests[$key] = [];

            // It's possible some dates could have no requests, that will throw off the data
            // So we'll first create the array with 0'd out data for all dates
            foreach ($out as $series=>$series_info) {
                $out[$series]['data'][$int_key] = 0;
            }

            $rs = $this->filter->outputResultSet();
            if (is_object($rs)) {
                while ($row = $rs->FetchRow()) {
                    //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                    if ($row['dtGMTOpened'] < $row['dtfirstupdate']) {
                        if ($this->usebizhours) {
                            $response_time = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtfirstupdate']);
                        } else {
                            $response_time = $row['dtfirstupdate'] - $row['dtGMTOpened'];
                        }

                        // Go over each series and add this request into the count appropriate for that series
                        foreach ($out as $series=>$series_info) {
                            if ($series_info['meta']['start'] <= $response_time and $response_time < $series_info['meta']['end']) {
                                $out[$series]['data'][$int_key]++;
                            }

                            if (! isset($out[$series]['data'][$int_key])) {
                                $out[$series]['data'][$int_key] = 0;
                            }
                        }
                    }
                }
            } else {
                //Set error header
                header('HTTP/1.1 400 Bad Request');
            }
        }

        // Provide the x dates here
        foreach ($this->interval_set as $interval) {
            $out['dates'][] = $interval['label'];
        }

        return $out;
    }

    public function report_matrix()
    {
        $data_points = [];
        $grid_points = [];
        $totals = [];
        $this->title = lg_reports_matrix;
        $x_axis_field = $this->options['xaxis'];
        $y_axis_field = $this->options['yaxis'];
        $dates = ['hour', 'day', 'month', 'year'];

        // Get the Y axis elements to display and filter against
        $x_values = $this->_getMatrixAxis($x_axis_field);
        $y_values = $this->_getMatrixAxis($y_axis_field);

        // The labels used in Matrix
        $x_categories = array_values($x_values);
        $y_categories = array_values($y_values);

        // Handle the special case of grouping by reporting tags in either axis
        // Rename the field to reportingTags which is what other parts of HS expect
        if (strpos($x_axis_field, 'category_tags_') !== false) {
            $x_axis_field = 'reportingTags';
        }
        if (strpos($y_axis_field, 'category_tags_') !== false) {
            $y_axis_field = 'reportingTags';
        }

        // Set the filter conditions that will apply x and y access fields to the filter
        // Here we're setting up the filter results to be the results of just 1 box in the matrix.
        // We'll run a separate filter query for each box in the matrix.
        // It's probably possible to build a giant sql query to do it. Maybe something to explore, but in the past
        // I've found that lots of smaller report queries work much better for speed than giant ones.

        // Get the array keys which hold the actual values that the filters can use
        $x_filter_values = array_keys($x_values);
        $y_filter_values = array_keys($y_values);

        // Here we go! We're going to loop over the X axes, then over the Y, in the middle run a filter to find the
        // value for that point in the matrix.
        for ($x = 0; $x < count($x_values); $x++) {
            for ($y = 0; $y < count($y_values); $y++) {

                // Reset the conditions array with any conditions
                // sent in from the report. In the UI that's the "filters" area.
                $conditions = $this->options;

                // Setup a new rule instance which is used to create conditions for filters
                // Filters are used in this report to find the data for each point
                $rule = new hs_auto_rule();
                $rule->returnrs = true; //return result set instead of doing actions

                // Dates have to be setup correctly to use the filters betweenDates option.
                // This has to be done on both X and Y if either contains a date
                if (in_array($x_axis_field, $dates)) {
                    $x_axis_field = 'betweenDates';
                }

                if (in_array($y_axis_field, $dates)) {
                    $y_axis_field = 'betweenDates';
                }

                // This square of the matrix. The intersection of the X/Y
                // This is where the logic is for the x and y access field selections
                $conditions = array_merge(
                    $conditions,
                    $this->_buildMatrixConditions('x', $x_axis_field, $x_filter_values[$x]),
                    $this->_buildMatrixConditions('y', $y_axis_field, $y_filter_values[$y])
                );

                // We always provide an outer date range to the queries so that no requests are
                // ever counted from beyond the date range given from the UI
                $conditions['condition_matrixtime_1'] = 'betweenDates';
                $conditions['condition_matrixtime_2'] = $this->time_from.','.$this->time_to;

                // Init the filter with conditions and run it.
                $rule->SetAutoRule($conditions);

                $this->filter = new hs_filter();
                $this->filter->is_report = true;
                $this->filter->filterDef = $rule->getFilterConditions();

                // We only want the returned count
                $this->filter->countOnly = true;

                // Get the filters results which will be a single number as we're returning count only
                $result = $this->filter->outputCountTotal();

                // The X, Y, VALUE coordinate required by the heatmap
                $data_points[] = [$x, $y, $result];

                // Store in this alternate format that's easier to deal with for building the
                // grid view and CSV export
                $grid_points[$y][$x] = $result;

                // Calculate the totals
                $totals['y'][$y] += $result;
                $totals['x'][$x] += $result;
            }
        }

        return [
            'x_categories' => $x_categories,
            'y_categories' => $y_categories,
            'data_points' => $data_points,
            'grid_points' => $grid_points,
            'totals' => $totals,
        ];
    }

    public function dash_requests()
    {
        $requests = [];

        $out = ['meta'=>['series_title'=>lg_report_dash_requests],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requeststoday,
                                                    'tooltip'=>lg_reports_chartlabel_requeststoday,
                                                    'type'=>'areaspline',
                                                    'stacked'=>false, ],
                                      'data'=>[], ],
                     'series2'=>['meta'=>['ylabel'=>lg_reports_chartlabel_last.' '.strftime_win32('%A', $this->time_from),
                                                    'tooltip'=>lg_reports_chartlabel_last.' '.strftime_win32('%A', $this->time_from),
                                                    'type'=>'areaspline',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        //Today
        foreach ($this->interval_set as $int_key=>$int) {
            $key = $this->_getKey($int);
            $this->_initFilter(['report_count'], $int['from'].','.$int['to']);
            $rs = $this->filter->outputResultSet();
            while ($row = $rs->FetchRow()) {
                //Don't plot times past right now
                if ($int['label_short'] <= date('H')) {
                    $out['series1']['data'][$key] = (empty($row['report_count']) ? 0 : $row['report_count']);
                }
            }
        }

        //Last week, same day
        $this->time_from = mktime(0, 0, 0, date('m', $this->time_from), date('j', $this->time_from) - 7, date('Y', $this->time_from));
        $this->time_to = mktime(23, 59, 59, date('m', $this->time_to), date('j', $this->time_to) - 7, date('Y', $this->time_to));
        $this->interval_set = $this->_intervals();
        foreach ($this->interval_set as $int_key=>$int) {
            $key = $this->_getKey($int);
            $this->_initFilter(['report_count'], $int['from'].','.$int['to']);
            $rs = $this->filter->outputResultSet();
            while ($row = $rs->FetchRow()) {
                $out['series2']['data'][$key] = (empty($row['report_count']) ? 0 : $row['report_count']);
            }
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);
        $series2meta = $this->_buildMeta($out['series2']);

        //Add counts to labels
        $out['series1']['meta']['tooltip'] = $out['series1']['meta']['tooltip'].' ('.$out['series1']['meta']['sum'].')';
        $out['series2']['meta']['tooltip'] = $out['series2']['meta']['tooltip'].' ('.$series2meta['sum'].')';

        return $out;
    }

    public function dash_first_response()
    {
        $this->_initFilter(['report_firstresponse'], $this->time_from.','.$this->time_to);
        $requests = [];

        $rs = $this->filter->outputResultSet();
        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                //Merged requests can sometimes end up with an update that occured before the open date. We skip these.
                if ($row['dtGMTOpened'] < $row['dtfirstupdate']) {
                    $requests[$row['xRequest']] = $this->bizhours->getBizTime($row['dtGMTOpened'], $row['dtfirstupdate']);
                }
            }
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }

        //Process new rs data
        $out = ['median'=>0, 'avg'=>0];
        foreach ($requests as $k=>$v) {
            //print_r($requests[$k]);exit;
            //Do median, divide by 3600 to make into hours
            $out['median'] = (empty($requests) ? 0 : round((stats_median($requests) / 3600), 2));

            //Do avg
            $out['avg'] = (empty($requests) ? 0 : round((stats_average($requests) / 3600), 2));
        }

        return $out;
    }

    public function dash_workload()
    {
        $out = ['meta'=>['series_title'=>lg_todayboard_staffassignment],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'bar',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        $where = $this->_addRestrictions('WHERE HS_Request.fOpen = 1 AND
					HS_Request.fTrash = 0 AND
					HS_Request.xStatus <> '.cHD_STATUS_SPAM.' AND
					HS_Request.xPersonAssignedTo = HS_Person.xPerson AND
					HS_Person.fDeleted = 0');

        $rs = $GLOBALS['DB']->SelectLimit('SELECT COUNT(HS_Request.xRequest) AS ct, HS_Person.sFname, HS_Person.sLname
										   FROM HS_Request, HS_Person
										   '.$where.'
										   GROUP BY sFname,sLname
										   ORDER BY ct DESC', 10, 0);

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                $out['series1']['data'][$row['sFname'].' '.$row['sLname']] = $row['ct'];
            }
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    public function dash_categories()
    {
        $out = ['meta'=>['series_title'=>lg_todayboard_category],
                     'series1'=>['meta'=>['ylabel'=>lg_reports_chartlabel_requests,
                                                    'tooltip'=>lg_reports_chartlabel_requests,
                                                    'type'=>'pie',
                                                    'stacked'=>false, ],
                                      'data'=>[], ], ];

        $where = $this->_addRestrictions('WHERE HS_Request.fTrash = 0
				AND HS_Request.xStatus <> '.cHD_STATUS_SPAM.'
				AND HS_Request.xCategory = HS_Category.xCategory
				AND ( ? <= dtGMTOpened AND dtGMTOpened < ? )');

        $rs = $GLOBALS['DB']->SelectLimit('SELECT COUNT(HS_Request.xRequest) AS ct, HS_Category.sCategory
										   FROM HS_Request, HS_Category '.$where.'
										   GROUP BY sCategory
										   ORDER BY ct DESC', 5, 0, [$this->time_from, $this->time_to]);

        if (is_object($rs)) {
            while ($row = $rs->FetchRow()) {
                $out['series1']['data'][$row['sCategory']] = $row['ct'];
            }
        } else {
            //Set error header
            header('HTTP/1.1 400 Bad Request');
        }

        //Add average/max data
        $out['series1']['meta'] = $this->_buildMeta($out['series1']);

        return $out;
    }

    /**
     * Add where restrictions based on the user.
     * @param $where
     * @return string
     */
    public function _addRestrictions($where)
    {
        global $user;
        //if in limited access mode enforce the category restriction no matter what the filter is
        if (perm('fLimitedToAssignedCats')) {
            $where = $where .' AND '. apiGetUserAssignedCatsWhere($user);
        }

        //if can view own requests only then limit to those
        if (perm('fCanViewOwnReqsOnly')) {
            $where = $where.' AND HS_Request.xPersonAssignedTo = '.$user['xPerson'];
        }

        return $where;
    }

    public function _getMatrixAxis($field)
    {

        // $r has keys as possible values for the field and the value is the label
        $r = [];

        switch ($field) {
            case 'hour':
            case 'day':
            case 'month':
            case 'year':
                $interval = $this->_intervals($field, $this->time_from, $this->time_to);
                foreach ($interval as $date) {
                    $r[$date['from'].','.$date['to']] = $date['label'];
                }

                break;
            case 'xStatus':
                if ($this->options['active_only']) {
                    return apiGetActiveStatus();
                } else {
                    $r = apiGetAllStatus();
                }
                $r = rsToColumn($r, 'xStatus', 'sStatus');
                arsort($r);

                break;
            case 'xCategory':
                if ($this->options['active_only']) {
                    $r = apiGetAllCategories(0, '');
                } else {
                    $r = apiGetAllCategoriesComplete();
                }
                $r = rsToColumn($r, 'xCategory', 'sCategory');
                arsort($r);

                break;
            case 'fOpenedVia':
                $r = $GLOBALS['openedVia'];
                arsort($r);

                break;
            case 'xPersonAssignedTo':
                if ($this->options['active_only']) {
                    $r = apiGetAllUsers(0, 'fullname');
                    $r = rsToColumn($r, 'xPerson', 'fullname');
                } else {
                    $r = apiGetAllUsersComplete();

                    foreach ($r as $xperson=>$person) {
                        $r[$xperson] = $person['fullname'];
                    }
                }
                arsort($r);

                break;
            case 'xOpenedViaId':
                if ($this->options['active_only']) {
                    $r = $GLOBALS['DB']->Execute('SELECT HS_Mailboxes.* FROM HS_Mailboxes WHERE fDeleted = ?', [0]);
                } else {
                    $r = $GLOBALS['DB']->Execute('SELECT HS_Mailboxes.* FROM HS_Mailboxes');
                }
                $r = rsToColumn($r, 'xMailbox', 'sReplyEmail');
                arsort($r);

                break;
            case 'xPortal':
                if ($this->options['active_only']) {
                    $r = apiGetAllPortals(0);
                } else {
                    $r = apiGetAllPortalsComplete();
                }

                $r = rsToColumn($r, 'xPortal', 'sPortalName');

                // The default portal isn't in the database so we manually add it here.
                // Note the comment in _buildMatrixConditions which works in conjunction with this
                $r[0] = lg_primaryportal;
                arsort($r);

                break;
        }

        // Handle reporting tags
        if (strpos($field, 'category_tags_') !== false) {
            $category_id = str_replace('category_tags_', '', $field);
            $r = apiGetReportingTags($category_id);
            arsort($r);
        }

        // Handle custom fields
        if (utf8_strpos($field, 'Custom') !== false) {
            if (is_array($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                    if ($field == 'Custom'.$k) {

                        // We may want to change this to a SelectLimit but going to try leaving it for now.
                        // We limit to the overall date range to keep from killing the db and somewhat more accurate results
                        $query = $GLOBALS['DB']->Execute('SELECT DISTINCT Custom'.$k.' FROM HS_Request WHERE Custom'.$k.' <> ? and (? <= dtGMTOpened AND dtGMTOpened < ?) ORDER BY Custom'.$k.' DESC', ['', $this->time_from, $this->time_to]);

                        // Now we'll format the data
                        switch ($fvalue['fieldType']) {
                            case 'numtext':
                            case 'text':
                            case 'regex':
                            case 'ajax':
                            case 'decimal':
                            case 'select':
                            case 'drilldown':

                                $r = rsToColumn($query, 'Custom'.$k, 'Custom'.$k);
                                arsort($r);

                                break;

                            case 'date':
                                // We have to make proper labels for dates

                                $r = rsToColumn($query, 'Custom'.$k, 'Custom'.$k, 'hs_showShortDate');

                                break;
                        }
                    }
                }
            }
        }

        return $r;
    }

    public function _buildMatrixConditions($axis, $field, $value)
    {
        $condition['condition_'.$axis.'_report_matrix_1'] = $field;

        // Some fields are formated with 2 more conditions, others only 1.
        if (in_array($field, ['betweenDates', 'reportingTags'])) {
            $condition['condition_'.$axis.'_report_matrix_2'] = $value;
        } else {
            $condition['condition_'.$axis.'_report_matrix_2'] = 'is';
            $condition['condition_'.$axis.'_report_matrix_3'] = $value;
        }

        // If this is a portal column, we have to limit it to web form request for accurate results
        if ($field == 'xPortal') {
            $condition['condition_portalgroup_1'] = 'fOpenedVia';
            $condition['condition_portalgroup_2'] = 'is';
            $condition['condition_portalgroup_3'] = 7;
        }

        return $condition;
    }

}
