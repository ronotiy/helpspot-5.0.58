<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

use Carbon\Carbon;

class hs_filter
{
    // Filter definition to run
    public $filterDef;

    // Number of requests matching filter
    public $filterCount = '';

    /**
     * Result set of run filter.
     * @var \HS\Database\RecordSet
     */
    public $resultSet;

    // Override default sort order
    public $overrideSort = '';

    // Where to start record set in limit
    public $paginate = 0;

    // Number of results to return
    public $paginate_length = 10;

    // Flag to tell the class if we're only looking for a count
    public $countOnly = false;

    // Flag to return id's only
    public $idsOnly = false;

    //Flag that this filter is being used by a report
    public $is_report = false;

    //Flag that this filter is being used to generate a CSV
    public $is_csv = false;

    // Allow the filter to be run without select limit
    public $is_no_limit = false;

    // Flag to tell the class if it's OK to use the count cache.
    public $useCountCache = false;

    // Variables to bind to
    public $bindValues = [];

    // SQL - ability to customize as needed. Only currently changed for MyQ
    public $sql = '';

    // Holds additional JOINS that are registered by the where logic
    public $joins = [];

    // The time it takes to execute the filter sql
    public $run_time = 0;

    // CONSTRUCTOR
    public function __construct($filterDef = '', $countOnly = false)
    {
        if (! empty($filterDef)) {
            $this->filterDef = $filterDef;
        }

        $this->countOnly = $countOnly;

        $this->paginate_length = hs_setting('cHD_MAXSEARCHRESULTS');
    }

    // LOGIC METHODS
    public function _runFilter()
    {
        global $user;
        $where = '';
        $orderby = '';
        $groupby = '';

        $timer = new hsTimer;

        //Determine if urgents should be ordered to top or not
        $urgentorder = ! empty($this->filterDef['urgentinline']) ? '' : 'fUrgent DESC,';

        //Select values
        if ($this->countOnly) {
            $select_values = 'COUNT(HS_Request.xRequest) AS thecount';
        } elseif ($this->idsOnly) {
            $select_values = 'HS_Request.xRequest';
        } elseif ($this->is_report) {
            $select_values = '';
            $allow_grouping = (in_array('report_resolution_speed', $this->filterDef['displayColumns']) ? false : true);

            //Setup report type columns
            if (in_array('report_count', $this->filterDef['displayColumns'])) {
                $select_values .= ' COUNT(HS_Request.xRequest) AS report_count ';
            }

            //Speed to resolution (must be above groupings, groupings shared by report_count and this)
            if (in_array('report_resolution_speed', $this->filterDef['displayColumns'])) {
                $select_values .= ' HS_Request.xRequest, HS_Request.dtGMTOpened, HS_Request.dtGMTClosed';
            }

            //Timetracker value
            if (in_array('report_timetrack_all', $this->filterDef['displayColumns'])) {
                $select_values .= ' SUM(HS_Time_Tracker.iSeconds) AS timetrack ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Request.xRequest = HS_Time_Tracker.xRequest';
            }

            if (in_array('report_timetrack_billable', $this->filterDef['displayColumns'])) {
                $select_values .= ' SUM(HS_Time_Tracker.iSeconds) AS timetrack ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Request.xRequest = HS_Time_Tracker.xRequest AND HS_Time_Tracker.fBillable = 1';
            }

            if (in_array('report_timetrack_not_billable', $this->filterDef['displayColumns'])) {
                $select_values .= ' SUM(HS_Time_Tracker.iSeconds) AS timetrack ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Request.xRequest = HS_Time_Tracker.xRequest AND HS_Time_Tracker.fBillable = 0';
            }

            //Reports with non-date grouping
            if (in_array('report_grouping_xCategory', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,HS_Category.sCategory AS label';
                if ($allow_grouping) {
                    $groupby = 'HS_Category.sCategory';
                }
                $this->joins[] = 'LEFT OUTER JOIN HS_Category ON HS_Request.xCategory = HS_Category.xCategory';
            }

            if (in_array('report_grouping_xStatus', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,HS_luStatus.sStatus AS label';
                if ($allow_grouping) {
                    $groupby = 'HS_luStatus.sStatus';
                }
                $this->joins[] = 'INNER JOIN HS_luStatus ON HS_Request.xStatus = HS_luStatus.xStatus';
            }

            if (in_array('report_grouping_xPersonAssignedTo', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,'.dbConcat(' ', 'sFname', 'sLname').' AS label';
                if ($allow_grouping) {
                    $groupby = 'sFname,sLname';
                }
                $this->joins[] = 'LEFT OUTER JOIN HS_Person ON HS_Request.xPersonAssignedTo = HS_Person.xPerson';
            }

            if (in_array('report_grouping_fOpenedVia', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,'.dbConcat('#-#', 'fOpenedVia', 'xOpenedViaId', 'xPortal').' AS label';
                if ($allow_grouping) {
                    $groupby = 'fOpenedVia,xOpenedViaId,xPortal';
                }
            }

            //Custom fields in over time report
            if (is_array($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $k=>$fvalue) {
                    if (in_array('report_grouping_Custom'.$k, $this->filterDef['displayColumns'])) {
                        $select_values .= ' ,Custom'.$k.' AS label';
                        if ($allow_grouping) {
                            $groupby = 'Custom'.$k;
                        }
                    }

                    if ($this->grouping == 'Custom'.$k) {
                        $items = hs_unserialize($fvalue['listItems']);
                        foreach ($items as $p) {
                            if (! isset($out['series1']['data'][$p])) {
                                $out['series1']['data'][$p] = 0;
                            }
                        }

                        break;
                    }
                }
            }

            //Speed to first response
            if (in_array('report_firstresponse', $this->filterDef['displayColumns'])) {
                $select_values .= 'DISTINCT HS_Request.xRequest, HS_Request.dtGMTOpened, (SELECT MIN(HS_Request_History.dtGMTChange) FROM HS_Request_History WHERE HS_Request_History.fInitial = 0 AND HS_Request_History.xPerson > 0 AND HS_Request_History.fPublic = 1 AND HS_Request_History.xRequest = HS_Request.xRequest) AS dtfirstupdate';
                $this->joins[] = 'INNER JOIN HS_Request_History ON HS_Request.xRequest = HS_Request_History.xRequest AND HS_Request_History.fInitial = 0 AND HS_Request_History.fPublic = 1 AND HS_Request_History.xPerson > 0 AND HS_Request.fTrash = 0 AND HS_Request.xPersonOpenedBy = 0';
            }

            //Speed to first assign
            if (in_array('report_firstassign', $this->filterDef['displayColumns'])) {
                $select_values .= ' HS_Request.xRequest, HS_Request.dtGMTOpened, (SELECT MIN(HS_Assignment_Chain.dtChange) FROM HS_Assignment_Chain WHERE HS_Assignment_Chain.xRequest = HS_Request.xRequest) AS dtfirstassign';
            }

            //Replies to Close
            if (in_array('report_repliestoclose', $this->filterDef['displayColumns'])) {
                $select_values .= 'HS_Request.xRequest ,(SELECT COUNT(*) FROM HS_Request_History WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xPerson > 0 AND HS_Request_History.xRequest = HS_Request.xRequest) AS pub_hist_count';
                //$groupby = 'label'; #can't group here bc SQL Server can't group by the alias and also can't group by the expression if it contains an aggregate function
                //$this->joins[] = 'INNER JOIN HS_luStatus ON HS_Request.xStatus = HS_luStatus.xStatus';
            }

            if (in_array('report_grouping_xPersonTracker', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,'.dbConcat(' ', 'sFname', 'sLname').' AS label';
                $groupby = 'sFname,sLname';
                $this->joins[] = 'LEFT OUTER JOIN HS_Person ON HS_Time_Tracker.xPerson = HS_Person.xPerson';
            }

            if (in_array('report_grouping_sUserId', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,sUserId AS label';
                $groupby = 'sUserId';
            }

            if (in_array('report_grouping_sEmail', $this->filterDef['displayColumns'])) {
                $select_values .= ' ,sEmail AS label';
                $groupby = 'sEmail';
            }

            //Time Events
            if (in_array('report_time_events_all', $this->filterDef['displayColumns'])) {
                $select_values .= ' HS_Time_Tracker.* ,'.dbConcat(' ', 'sFname', 'sLname').' AS personname, HS_Request.sUserId, '.dbConcat(' ', 'sFirstName', 'sLastName').' AS customername ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Time_Tracker.xRequest = HS_Request.xRequest';
                $this->joins[] = 'INNER JOIN HS_Person ON HS_Time_Tracker.xPerson = HS_Person.xPerson';
            }

            if (in_array('report_time_events_billable', $this->filterDef['displayColumns'])) {
                $select_values .= ' HS_Time_Tracker.* ,'.dbConcat(' ', 'sFname', 'sLname').' AS personname, HS_Request.sUserId, '.dbConcat(' ', 'sFirstName', 'sLastName').' AS customername ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Time_Tracker.xRequest = HS_Request.xRequest AND HS_Time_Tracker.fBillable = 1';
                $this->joins[] = 'INNER JOIN HS_Person ON HS_Time_Tracker.xPerson = HS_Person.xPerson';
            }

            if (in_array('report_time_events_not_billable', $this->filterDef['displayColumns'])) {
                $select_values .= ' HS_Time_Tracker.* ,'.dbConcat(' ', 'sFname', 'sLname').' AS personname, HS_Request.sUserId, '.dbConcat(' ', 'sFirstName', 'sLastName').' AS customername ';
                $this->joins[] = 'INNER JOIN HS_Time_Tracker ON HS_Time_Tracker.xRequest = HS_Request.xRequest AND HS_Time_Tracker.fBillable = 0';
                $this->joins[] = 'INNER JOIN HS_Person ON HS_Time_Tracker.xPerson = HS_Person.xPerson';
            }

            //Check for reporting tag grouping
            foreach ($this->filterDef['displayColumns'] as $k=>$col) {
                if (strpos($col, 'report_grouping_category_tags_') !== false) {
                    $categoryid = str_replace('report_grouping_category_tags_', '', $col);
                    $select_values .= ' , HS_Category_ReportingTags.sReportingTag AS label';
                    if ($allow_grouping) {
                        $groupby = 'HS_Category_ReportingTags.sReportingTag';
                    }
                    $this->joins[] = 'INNER JOIN HS_Request_ReportingTags ON HS_Request.xRequest = HS_Request_ReportingTags.xRequest';
                    $this->joins[] = 'INNER JOIN HS_Category_ReportingTags ON HS_Request_ReportingTags.xReportingTag = HS_Category_ReportingTags.xReportingTag AND HS_Category_ReportingTags.xCategory = '.$categoryid;
                }
            }
        } else {
            $select_values = 'HS_Request.*, HS_Category.sCategory, HS_luStatus.sStatus,'.dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname ';

            //Add person name for assigned to grouping
            if (! empty($this->filterDef['groupBy']) && $this->filterDef['groupBy'] == 'sPersonAssignedTo') {
                $select_values = $select_values.','.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS sPersonAssignedTo ';
            }

            //Add select values for each type of special calculated field

            //Special columns
            if (in_array('reqsummary', $this->filterDef['displayColumns'])) {
                $select_values .= ' , (SELECT '.dbConcat(' #-#', 'HS_Request.sTitle', 'SUBSTRING(HS_Request_History.tNote, 1, 500)').'
													FROM HS_Request_History
													WHERE HS_Request_History.fInitial = 1 AND HS_Request_History.xRequest = HS_Request.xRequest) AS tNote';
            }
            /*
            if(in_array('lastpublicnote', $this->filterDef['displayColumns'])){
                //SUBSTRING seems to be only way to make this work for mssql
                if(config('database.default') == 'sqlsrv'){
                    $note = 'SELECT SUBSTRING(HS_Request_History.tNote, 1, 500)';
                }else{
                    $note = 'SELECT HS_Request_History.tNote';
                }
                $select_values .= ' , ('.dbSelectLimit($note.'
                                                    FROM HS_Request_History
                                                    WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xRequest = HS_Request.xRequest
                                                    ORDER BY HS_Request_History.dtGMTChange DESC',0,1).' ) AS lastpublicnote';
            }

            if(in_array('lastpublicnoteby', $this->filterDef['displayColumns'])){
                $select_values .= ' , ('.dbSelectLimit('SELECT HS_Request_History.xPerson
                                                    FROM HS_Request_History
                                                    WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xRequest = HS_Request.xRequest
                                                    ORDER BY HS_Request_History.dtGMTChange DESC',0,1).' ) AS lastpublicnoteby';
            }
            */
            if (in_array('attachment', $this->filterDef['displayColumns'])) {
                $select_values .= ' , (SELECT COUNT(*)
                                      FROM HS_Documents
                                      JOIN HS_Request_History on HS_Documents.xRequestHistory = HS_Request_History.xRequestHistory
									  WHERE HS_Request_History.xRequest = HS_Request.xRequest
									  GROUP BY HS_Request_History.xRequest) AS attachment_ct';
            }

            if (in_array('isunread', $this->filterDef['displayColumns'])) {
                $select_values .= ', (SELECT COUNT(*)
									  FROM HS_Request_History
									  WHERE HS_Request_History.xRequest = HS_Request.xRequest
									  GROUP BY HS_Request_History.xRequest) AS history_ct';
            }

            //Calculated columns
            if (isset($this->filterDef['timetrack']) || in_array('timetrack', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'timetrack' || strpos($this->overrideSort, 'timetrack') != false) {
                $select_values .= ', (SELECT SUM(iSeconds)
													FROM HS_Time_Tracker
													WHERE HS_Time_Tracker.xRequest = HS_Request.xRequest
													GROUP BY HS_Time_Tracker.xRequest ) AS timetrack';
            }

            if (isset($this->filterDef['lastupdate']) || in_array('lastupdate', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'lastupdate' || strpos($this->overrideSort, 'lastupdate') != false) {
                $select_values .= ', (SELECT MAX(dtGMTChange)
													FROM HS_Request_History
													WHERE HS_Request_History.xRequest = HS_Request.xRequest
													GROUP BY HS_Request_History.xRequest) AS lastupdate';
            }

            if (isset($this->filterDef['lastpubupdate']) || in_array('lastpubupdate', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'lastpubupdate' || strpos($this->overrideSort, 'lastpubupdate') != false) {
                $select_values .= ', (SELECT MAX(dtGMTChange)
													FROM HS_Request_History
													WHERE HS_Request_History.xRequest = HS_Request.xRequest AND HS_Request_History.fPublic = 1
													GROUP BY HS_Request_History.xRequest) AS lastpubupdate';
            }

            if (isset($this->filterDef['lastcustupdate']) || in_array('lastcustupdate', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'lastcustupdate' || strpos($this->overrideSort, 'lastcustupdate') != false) {
                $select_values .= ', (SELECT MAX(dtGMTChange)
													FROM HS_Request_History
													WHERE HS_Request_History.xRequest = HS_Request.xRequest AND xPerson = 0
													GROUP BY HS_Request_History.xRequest) AS lastcustupdate';
            }

            if (isset($this->filterDef['ctPublicUpdates']) || in_array('ctPublicUpdates', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'ctPublicUpdates' || strpos($this->overrideSort, 'ctPublicUpdates') != false) {
                $select_values .= ', (SELECT COUNT(*)
													FROM HS_Request_History
													WHERE HS_Request_History.xRequest = HS_Request.xRequest AND fPublic = 1
													GROUP BY HS_Request_History.xRequest) AS ctPublicUpdates';
            }

            if (in_array('speedtofirstresponse', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'speedtofirstresponse' || strpos($this->overrideSort, 'speedtofirstresponse') != false) {
                $select_values .= ', (SELECT (MIN(HS_Request_History.dtGMTChange) - HS_Request.dtGMTOpened)
													FROM HS_Request_History
													WHERE HS_Request_History.fInitial = 0 AND HS_Request_History.xPerson > 0 AND HS_Request_History.fPublic = 1 AND HS_Request.xPersonOpenedBy = 0 AND HS_Request_History.xRequest = HS_Request.xRequest) AS speedtofirstresponse';
            }

            if (in_array('speedtofirstresponse_biz', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'speedtofirstresponse_biz' || strpos($this->overrideSort, 'speedtofirstresponse_biz') != false) {
                $select_values .= ', (SELECT MIN(HS_Request_History.dtGMTChange)
													FROM HS_Request_History
													WHERE HS_Request_History.fInitial = 0 AND HS_Request_History.xPerson > 0 AND HS_Request_History.fPublic = 1 AND HS_Request.xPersonOpenedBy = 0 AND HS_Request_History.xRequest = HS_Request.xRequest) AS speedtofirstresponse_biz';
            }

            if (in_array('thermostat_nps_score', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'thermostat_nps_score' || strpos($this->overrideSort, 'thermostat_nps_score') != false) {
                $select_values .= ', (SELECT iScore
									  FROM HS_Thermostat
									  WHERE HS_Thermostat.xRequest = HS_Request.xRequest
									  AND HS_Thermostat.type = \'nps\'
									 ) AS thermostat_nps_score';
            }

            if (in_array('thermostat_csat_score', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'thermostat_csat_score' || strpos($this->overrideSort, 'thermostat_csat_score') != false) {
                $select_values .= ', (SELECT iScore
									  FROM HS_Thermostat
									  WHERE HS_Thermostat.xRequest = HS_Request.xRequest
									  AND HS_Thermostat.type = \'csat\'
									 ) AS thermostat_csat_score';
            }
            if (in_array('thermostat_feedback', $this->filterDef['displayColumns']) || $this->filterDef['orderBy'] == 'thermostat_feedback' || strpos($this->overrideSort, 'thermostat_feedback') != false) {
                $select_values .= ', (SELECT tFeedback
									  FROM HS_Thermostat
									  WHERE HS_Thermostat.xRequest = HS_Request.xRequest
									 ) AS thermostat_feedback';
            }
        }

        //Main filter query
        //We don't join to status/category on count only queries
        // LEFT OUTER JOIN HS_luStatus ON HS_Request.xStatus = HS_luStatus.xStatus
        $this->sql = 'SELECT '.$select_values.'
					  FROM HS_Request
					  '.(! $this->countOnly && ! $this->idsOnly && ! $this->is_report ? 'INNER JOIN HS_luStatus ON HS_Request.xStatus = HS_luStatus.xStatus' : '').'
					  '.(! $this->countOnly && ! $this->idsOnly && ! $this->is_report ? 'LEFT OUTER JOIN HS_Category ON HS_Request.xCategory = HS_Category.xCategory' : '').'
					  '.(! $this->countOnly && ! $this->idsOnly && ! $this->is_report && ! empty($this->filterDef['groupBy']) && $this->filterDef['groupBy'] == 'sPersonAssignedTo' ? 'LEFT OUTER JOIN HS_Person ON HS_Request.xPersonAssignedTo = HS_Person.xPerson' : '').'
					  %s
					  WHERE %s
					  '.($groupby ? 'GROUP BY '.$groupby : '');

        //Only have order by cluase on full filters
        if (! $this->countOnly && ! $this->idsOnly && ! $this->is_report) {
            $this->sql .= ' ORDER BY %s';
        }

        //Build WHERE sql
        // Note: This function call appends to $where and returns a new $where
        //       The use of list($where...) over-writes the $where variable
        list($where, $whereBinds) = $this->queryFromFilterDef($where);
        foreach($whereBinds as $b) {
            $this->bindV($b);
        }

        //cleanup extra AND/OR
        $where = (utf8_substr($where, -5, 5) == ' AND ') ? utf8_substr($where, 0, -5) : $where;
        $where = (utf8_substr($where, -4, 4) == ' OR ') ? utf8_substr($where, 0, -4) : $where;

        //if in limited access mode enforce the category restriction no matter what the filter is
        if (perm('fLimitedToAssignedCats')) {
            $where = ! empty($where) ? '('.$where.') AND ' : '';
            $where = $where . apiGetUserAssignedCatsWhere($user);
        }

        //if can view own requests only then limit to those
        if (perm('fCanViewOwnReqsOnly')) {
            $where = ! empty($where) ? '('.$where.') AND ' : '';
            $where = $where.' HS_Request.xPersonAssignedTo = '.$user['xPerson'];
        }

        //don't show reqs marked as spam
        if ($this->filterDef['sFilterName'] != lg_spam) {
            $where = ! empty($where) ? '('.$where.') AND ' : '';
            $where = $where.' HS_Request.xStatus <> ? ';
            $this->bindV(hs_setting('cHD_STATUS_SPAM', 2));
        }

        //don't show reqs marked as trash
        if ($this->filterDef['sFilterName'] != lg_trash) {
            $where = ! empty($where) ? ' ('.$where.') AND ' : '';
            $where = $where.' HS_Request.fTrash <> ? ';
            $this->bindV(1);
        }

        // If we're running under virtual archive limit our results to only
        // the last X days. This helps mitigate heavy or badly created filters.
        // We don't apply this if the filter is looking in a specific date range
        if ($this->_useVirtualArchive()) {
            $where = ! empty($where) ? ' ('.$where.') AND ' : '';
            $vadate = mktime(0, 0, 0, date('m'), (date('d') - hs_setting('cHD_VIRTUAL_ARCHIVE')), date('Y'));
            $where = $where.' HS_Request.dtGMTOpened > ? ';
            $this->bindV($vadate);
        }

        //setup ordering and grouping
        if (! $this->countOnly && ! $this->idsOnly && ! $this->is_report) {
            if (empty($this->overrideSort)) {
                //Handle calculated fields vs standard request fields
                if (in_array($this->filterDef['orderBy'], ['timetrack', 'lastupdate', 'lastpubupdate', 'lastcustupdate', 'ctPublicUpdates', 'speedtofirstresponse', 'speedtofirstresponse_biz', 'thermostat_nps_score', 'thermostat_csat_score'])) {
                    $orderby = $this->filterDef['orderBy'];
                    $orderby .= isset($this->filterDef['orderByDir']) ? ' '.$this->filterDef['orderByDir'] : ' DESC';
                } elseif ($this->filterDef['orderBy'] == 'xStatus') {
                    $orderby = 'HS_luStatus.sStatus';
                    $orderby .= isset($this->filterDef['orderByDir']) ? ' '.$this->filterDef['orderByDir'] : ' DESC';
                } else {
                    $orderby = isset($this->filterDef['orderBy']) ? 'HS_Request.'.$this->filterDef['orderBy'] : 'HS_Request.xRequest';
                    $orderby .= isset($this->filterDef['orderByDir']) ? ' '.$this->filterDef['orderByDir'] : ' DESC';
                }
            } elseif (! empty($this->overrideSort)) {
                //Handle odd sort cases where we need to manually sort in the filter code down below
                $orderby = ' '.$this->overrideSort;
            }

            //setup grouping. It inserts the grouping in front of the order
            if (! empty($this->filterDef['groupBy'])) {
                if (utf8_substr($this->filterDef['groupBy'], 0, 4) !== 'time') {
                    $orderby = ' '.$this->filterDef['groupBy'].' '.$this->filterDef['groupByDir'].','.$urgentorder.' '.$orderby;
                } else {
                    $orderby = $urgentorder.' dtGMTOpened '.$this->filterDef['groupByDir'].','.$orderby;
                }
            } else {
                //If not using grouping this puts urgent in front of order by
                $orderby = $urgentorder.' '.$orderby;
            }
        }

        //Build additional joins if any
        $joins = '';
        if (! empty($this->joins)) {
            foreach ($this->joins as $k=>$v) {
                $joins .= $v."\n";
            }
        }

        if ($this->countOnly) {
            $this->resultSet = $GLOBALS['DB']->GetOne(sprintf($this->sql, $joins, $where), $this->bindValues);
        } elseif ($this->is_report || $this->idsOnly || $this->is_csv || $this->is_no_limit) {
            $this->resultSet = $GLOBALS['DB']->Execute(sprintf($this->sql, $joins, $where, $orderby), $this->bindValues);
        } else {
            $this->resultSet = $GLOBALS['DB']->SelectLimit(sprintf($this->sql, $joins, $where, $orderby), $this->paginate_length, $this->paginate, $this->bindValues);
        }

        //Get stats and add additional columns as needed.
        $this->doPreParse();

        if (is_object($this->resultSet) && ! $this->countOnly && ! $this->idsOnly && ! $this->is_report) {
            //save count
            $this->filterCount = $this->resultSet->RecordCount();
        }

        // Log filter time
        $this->run_time = $timer->stop_n_show();
        if (is_numeric($this->filterDef['xFilter']) && ! $this->idsOnly && ! $this->is_report) {
            if ($this->countOnly) {
                $type = 'count';
            } else {
                $type = 'view';
            }

            $GLOBALS['DB']->Execute('INSERT INTO HS_Filter_Performance(xFilter,dTime,dtRunAt,sType) VALUES (?,?,?,?)', [$this->filterDef['xFilter'], $this->run_time, time(), $type]);
        }
    }

    //SQL LOGIC
    public function wheresql($k, $v, $stop_operator = false, $whitelist=[])
    {
        global $user;

        // If the $whitelist is populated, we'll only generate
        // WHERE sql conditionals for allowed items
        $onlyAppendIfWhitelistIsUsed = count($whitelist) > 0;
        $whereInRequestIds = [];
        $binds = [];

        $t = '';
        $where = '';
        $k = trim($k);
        if ($v !== '') {
            // If the $whitelist is populated, only continue
            // if the filter condition type is whitelisted
            if ($onlyAppendIfWhitelistIsUsed && ! in_array($k, $whitelist)) {
                return [null, null];
            }
            switch ($k) {
                case 'sSearch':

                    // Create a function to run DB search as this
                    // may get called more in multiple scenarios
                    $performDbSearch = function () use ($v) {
                        $ftRequestHistory = dbFullText('tNote', $v);
                        $ftRequestTitle = dbFullText('sTitle', $v);

                        //Due to MySQL's bad subselect handling we run a query to get the IN values separately.
                        $vadate = mktime(0, 0, 0, date('m'), (date('d') - hs_setting('cHD_VIRTUAL_ARCHIVE')), date('Y'));
                        $va = $this->_useVirtualArchive() ? ' dtGMTOpened > '.$vadate.' AND ' : '';

                        // We'll add certain query parameters from the defined filter definitions (we cherry pick common filter definitions)
                        // to the fulltext search query to attempt to cut down on the number of rows that fulltext search must search
                        // The array is a "whitelist" of filter conditions that will be allowed to be used to generate the where SQL
                        list($fulltextWhere, $whereBinds) = $this->queryFromFilterDef($va, [
                            "fOpen",
                            "fUrgent",
                            "fNotUrgent",
                            "xCategory",
                            "xStatus",
                            "xRequest",
                            "xPersonAssignedTo",
                            "xPortal",
                            "sUserId",
                            "sEmail",
                            "sFirstName",
                            "sLastName",
                            "sTitle",
                            "fTrash",
                            "fOpenedVia",
                            "xOpenedViaId",
                            "betweenDates",
                            "beforeDate",
                            "afterDate",
                        ]);

                        $titleBinds = array_merge($whereBinds, $ftRequestTitle['args']);
                        $historyBinds = array_merge($whereBinds, $ftRequestHistory['args']);

                        if (config('database.default') == 'mssql' || config('database.default') == 'sqlsrv') {
                            $in_values_history = $GLOBALS['DB']->GetCol('SELECT TOP 5000 HS_Request_History.xRequest
                                                                            FROM HS_Request_History
                                                                            JOIN HS_Request on HS_Request.xRequest = HS_Request_History.xRequest
                                                                            WHERE '.$fulltextWhere.' '.$ftRequestHistory['sql'], $historyBinds);

                            $in_values_title = $GLOBALS['DB']->GetCol('SELECT TOP 5000  xRequest FROM HS_Request WHERE '.$fulltextWhere.' '.$ftRequestTitle['sql'], $titleBinds);
                        } else {
                            $in_values_history = $GLOBALS['DB']->GetCol('SELECT HS_Request_History.xRequest FROM HS_Request_History
                                                                            JOIN HS_Request on HS_Request.xRequest = HS_Request_History.xRequest
                                                                            WHERE '.$fulltextWhere.' '.$ftRequestHistory['sql'].'
                                                                            LIMIT 5000', $historyBinds);

                            $in_values_title = $GLOBALS['DB']->GetCol('SELECT xRequest FROM HS_Request WHERE '.$fulltextWhere.' '.$ftRequestTitle['sql'].' LIMIT 5000', $titleBinds);
                        }

                        if (! is_array($in_values_history)) {
                            $in_values_history = [];
                        }

                        if (! is_array($in_values_title)) {
                            $in_values_title = [];
                        }

                        $in_values = array_merge($in_values_history, $in_values_title);

                        //If there's no requests for this search then don't return anything
                        if ($in_values !== false && count($in_values) > 0) {
                            $t = 'HS_Request.xRequest IN ('.implode(',', $in_values).') and HS_Request.fTrash <> 1';
                        } else {
                            $t = ' 1=0 '; //this is needed or under certain conditions the filter could end up matching all requests by having no constraints. For instance if this is the only condition.
                        }

                        return $t;
                    };

                    $t = $performDbSearch();
                    break;
                case 'wheresql':
                    $t = $v;

                    break;
                case 'fOpen':
                    if ($v !== '-1') { //If using all then simply ignore this condition
                        $t = ' fOpen = ? ';
                        $binds[] = $v;
                    }

                    break;
                case 'fUrgent':
                    $t = ' fUrgent = ? '; $binds[] = $v;

                    break;
                case 'fNotUrgent':
                    $t = ' fUrgent = ? '; $binds[] = $v;

                    break;
                case 'xCategory':
                    $t = ' HS_Request.xCategory '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'reportingTags':
                    if (is_array($v)) {
                        $r = 0;
                        $list = '';
                        foreach ($v as $reptag) {
                            $r++;
                            $list .= '?';
                            $list .= $r < count($v) ? ',' : '';
                            $binds[] = $reptag;
                        }
                    } else { //it's just a single ID string which is what we get when using nesting
                        $list = '?';
                        $binds[] = $v;
                    }

                    $t = 'HS_Request.xRequest IN (SELECT xRequest FROM HS_Request_ReportingTags WHERE HS_Request_ReportingTags.xReportingTag IN ('.$list.'))';

                    break;
                case 'xStatus':
                    $t = ' HS_Request.xStatus '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'xRequest':
                    $t = ' HS_Request.xRequest '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'xPersonAssignedTo':
                    if ($v['value'] == 'loggedin') {
                        $v['value'] = $user['xPerson'];
                    }
                    $t = ' xPersonAssignedTo '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'xPersonOpenedBy':
                    if ($v['value'] == 'loggedin') {
                        $v['value'] = $user['xPerson'];
                    }
                    $t = ' xPersonOpenedBy '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'xOpenedViaId':
                    $t = ' xOpenedViaId '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'xPortal':
                    //If searching for default portal we must add fOpenedVia bc 0 is in every non secondary portal row
                    if ($v['value'] == 0) {
                        $t = 'fOpenedVia = 7 AND xPortal = 0';
                    } else {
                        $t = 'xPortal '.$this->opTypeBind($v, 'is', $binds).' ? ';
                    }

                    break;
                case 'betweenDates':
                    $v = explode(',', $v);
                    $t = ' ( ? <= dtGMTOpened AND dtGMTOpened < ? ) ';
                    $binds[] = $v[0];
                    $binds[] = $v[1];

                    break;
                case 'betweenClosedDates':
                    $v = explode(',', $v);
                    $t = ' ( ? <= dtGMTClosed AND dtGMTClosed < ? ) ';
                    $binds[] = $v[0];
                    $binds[] = $v[1];

                    break;
                case 'betweenTTDates':
                    $v = explode(',', $v);
                    $t = ' ( ? <= HS_Time_Tracker.dtGMTDate AND HS_Time_Tracker.dtGMTDate < ? ) ';
                    $binds[] = $v[0];
                    $binds[] = $v[1];

                    break;
                case 'beforeDate':
                    $date = is_numeric($v) ? $v : hs_strtotime($v, date('U')); //if timestamp use, else convert to ts from date
                    $date = mktime(0, 0, 0, date('m', $date), date('d', $date), date('Y', $date)); //set to midnight
                    $t = ' dtGMTOpened < ? ';
                    $binds[] = $date;

                    break;
                case 'afterDate':
                    $date = is_numeric($v) ? $v : hs_strtotime($v, date('U')); //if timestamp use, else convert to ts from date
                    $date = mktime(23, 59, 59, date('m', $date), date('d', $date), date('Y', $date)); //set to last minute of date

                    $t = ' dtGMTOpened > ? '; $binds[] = $date;

                    break;
                case 'closedBeforeDate':
                    $date = is_numeric($v) ? $v : hs_strtotime($v, date('U')); //if timestamp use, else convert to ts from date
                    $date = mktime(0, 0, 0, date('m', $date), date('d', $date), date('Y', $date)); //set to midnight

                    $t = ' dtGMTClosed < ? AND dtGMTClosed <> 0'; $binds[] = $date;

                    break;
                case 'closedAfterDate':
                    $date = is_numeric($v) ? $v : hs_strtotime($v, date('U')); //if timestamp use, else convert to ts from date
                    $date = mktime(23, 59, 59, date('m', $date), date('d', $date), date('Y', $date)); //set to last minute of date

                    $t = ' dtGMTClosed > ? '; $binds[] = $date;

                    break;
                case 'relativedate':
                    $dates = $this->relativeDateRange($v);

                    $t = ' ( dtGMTOpened >= ? AND dtGMTOpened <= ? ) '; $binds[] = $dates['start']; $binds[] = $dates['end'];

                    break;
                case 'relativedatetoday':
                    $dates = $this->relativeDateRange($v);

                    $t = 'HS_Request.xRequest IN (SELECT xRequest FROM HS_Request_History
							WHERE HS_Request_History.dtGMTChange >= ? AND HS_Request_History.dtGMTChange <= ?)';

                    $binds[] = $dates['start'];
                    $binds[] = $dates['end'];

                    break;
                case 'relativedateclosed':
                    $dates = $this->relativeDateRange($v);

                    $t = ' ( dtGMTClosed >= ? AND dtGMTClosed <= ? ) ';  $binds[] = $dates['start']; $binds[] = $dates['end'];

                    break;
                case 'relativedatelastpub':
                    $dates = $this->relativeDateRange($v);

                    $t = 'HS_Request.xRequest IN (SELECT xRequest FROM HS_Request_History WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.dtGMTChange >= ? AND HS_Request_History.dtGMTChange <= ?)';

                    $binds[] = $dates['start'];
                    $binds[] = $dates['end'];

                    break;
                case 'relativedatelastcust':
                    $dates = $this->relativeDateRange($v);

                    $t = 'HS_Request.xRequest IN (SELECT xRequest FROM HS_Request_History WHERE HS_Request_History.xPerson = 0 AND HS_Request_History.fInitial <> 1 AND HS_Request_History.fPublic = 1 AND HS_Request_History.dtGMTChange >= ? AND HS_Request_History.dtGMTChange <= ?)';

                    $binds[] = $dates['start'];
                    $binds[] = $dates['end'];

                    break;
                case 'fOpenedVia':
                    $t = ' fOpenedVia '.$this->opTypeBind($v, 'is', $binds).' ? ';

                    break;
                case 'dtSinceCreated':
                    //Doing it this way allows us to avoid in DB math which in MySQL was causing full table scan
                    $type = trim($v['op']);
                    $cutoff = time() - ($v['value'] * 60); //Precalc for speed

                    if ($type == 'less_than') {
                        $t = 'dtGMTOpened > ? ';
                        $binds[] = $cutoff;
                    } else {
                        $t = 'dtGMTOpened < ? ';
                        $binds[] = $cutoff;
                    }

                    break;
                case 'dtSinceClosed':
                    //Doing it this way allows us to avoid in DB math which in MySQL was causing full table scan
                    $type = trim($v['op']);
                    $cutoff = time() - ($v['value'] * 60); //Precalc for speed

                    if ($type == 'less_than') {
                        $t = 'fOpen = 0 AND dtGMTClosed > ? ';
                        $binds[] = $cutoff;
                    } else {
                        $t = 'fOpen = 0 AND dtGMTClosed < ? ';
                        $binds[] = $cutoff;
                    }

                    break;
                case 'lastupdate':
                    $v['value'] = $v['value'] * 60; //Turn minutes into seconds

                    $t = ' (('.time().' - (SELECT MAX(dtGMTChange)
												FROM HS_Request_History
												WHERE HS_Request_History.xRequest = HS_Request.xRequest
												GROUP BY HS_Request_History.xRequest)) '.$this->opTypeBind($v, 'greater_than', $binds).' ? ) ';

                    break;
                case 'lastpubupdate':
                    $v['value'] = $v['value'] * 60; //Turn minutes into seconds

                    $t = ' (('.time().' - (SELECT MAX(dtGMTChange)
												FROM HS_Request_History
												WHERE HS_Request_History.xRequest = HS_Request.xRequest AND HS_Request_History.fPublic = 1
												GROUP BY HS_Request_History.xRequest)) '.$this->opTypeBind($v, 'greater_than', $binds).' ? ) ';

                    break;
                case 'lastcustupdate':
                    $v['value'] = $v['value'] * 60; //Turn minutes into seconds

                    $t = ' (('.time().' - (SELECT MAX(dtGMTChange)
												FROM HS_Request_History
												WHERE HS_Request_History.xRequest = HS_Request.xRequest AND xPerson = 0
												GROUP BY HS_Request_History.xRequest)) '.$this->opTypeBind($v, 'greater_than', $binds).' ? ) ';

                    break;
                case 'ctPublicUpdates':
                    $t = ' ( (SELECT COUNT(*)
								FROM HS_Request_History
								WHERE HS_Request_History.xRequest = HS_Request.xRequest AND fPublic = 1
								GROUP BY HS_Request_History.xRequest) '.$this->opTypeBind($v, 'is', $binds).' ? ) ';

                    break;
                case 'speedtofirstresponse':
                    //Put minutes into seconds
                    $v['value'] = $v['value'] * 60;

                    //Second subquery makes sure only speeds > 0 are returned. Otherwise unresponded to requests would also return
                    $t = ' ( (SELECT (MIN(HS_Request_History.dtGMTChange) - HS_Request.dtGMTOpened)
									FROM HS_Request_History
									WHERE HS_Request_History.fInitial = 0 AND HS_Request_History.xPerson > 0 AND HS_Request_History.fPublic = 1 AND HS_Request.xPersonOpenedBy = 0 AND HS_Request_History.xRequest = HS_Request.xRequest)
									'.$this->opTypeBind($v, 'greater_than', $binds).' ?
								AND
							(SELECT (MIN(HS_Request_History.dtGMTChange) - HS_Request.dtGMTOpened)
									FROM HS_Request_History
									WHERE HS_Request_History.fInitial = 0 AND HS_Request_History.xPerson > 0 AND HS_Request_History.fPublic = 1 AND HS_Request.xPersonOpenedBy = 0 AND HS_Request_History.xRequest = HS_Request.xRequest)
									> 0) ';

                    break;
                case 'isunread':
                    $t = ' ( (SELECT COUNT(*)
							  FROM HS_Request_History
							  WHERE HS_Request_History.xRequest = HS_Request.xRequest
							  GROUP BY HS_Request_History.xRequest) > HS_Request.iLastReadCount ) ';

                    break;
                case 'sUserId':
                    $t = ' sUserId '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'sEmail':
                    $t = ' HS_Request.sEmail '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'sFirstName':
                    $t = ' sFirstName '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'sLastName':
                    $t = ' sLastName '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'sPhone':
                    $t = ' sPhone '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'sTitle':
                    $t = ' sTitle '.$this->opTypeBind($v, 'contains', $binds).' ? ';

                    break;
                case 'from':
                    if (isset($this->filterDef['report']) && $this->filterDef['report'] == 'r4') {
                        //Special case for closed requests report
                        $t = '  ? <= dtGMTClosed  ';
                        $binds[] = $v;
                    } else {
                        $t = '  ? <= dtGMTOpened  ';
                        $binds[] = $v;
                    }

                    break;
                case 'to':
                    if (isset($this->filterDef['report']) && $this->filterDef['report'] == 'r4') {
                        //Special case for closed requests report
                        $t = ' dtGMTClosed < ? ';
                        $binds[] = $v;
                    } else {
                        $t = ' dtGMTOpened < ? ';
                        $binds[] = $v;
                    }

                    break;
                case 'iLastReplyBy':
                    //Support specific users or any user
                    if ($v == 'any') {
                        $op = ' > 0';
                    } else {
                        $op = ' = ?';
                        $binds[] = $v;
                    }
                    $t = ' iLastReplyBy '.$op;

                    break;
                case 'fTrash':
                    $t = ' fTrash = ? ';
                    $binds[] = $v;

                    break;
                case 'updatedby':
                    if ($v == 'loggedin') {
                        $v = $user['xPerson'];
                    }
                    $t = 'HS_Request.xRequest in (select distinct xRequest from HS_Request_History where xPerson'.$this->opTypeBind($v, 'is', $binds).' ? )';

                    break;
                case 'acWasEver':
                case 'acReassignedBy':
                    if ($v == 'loggedin') {
                        $v = $user['xPerson'];
                    }

                    $col = ($k == 'acWasEver' ? 'xPerson' : 'xChangedByPerson');

                    $t = 'HS_Request.xRequest IN (SELECT DISTINCT xRequest FROM HS_Assignment_Chain WHERE HS_Assignment_Chain.'.$col.' = ?)';

                    $binds[] = $v;

                    break;
                case 'acFromTo':
                    if ($v['op'] == 'loggedin') {
                        $v['op'] = $user['xPerson'];
                    }
                    if ($v['value'] == 'loggedin') {
                        $v['value'] = $user['xPerson'];
                    }

                    $t = 'HS_Request.xRequest IN (SELECT DISTINCT xRequest FROM HS_Assignment_Chain WHERE HS_Assignment_Chain.xPreviousPerson = ? AND HS_Assignment_Chain.xPerson = ?)';

                    $binds[] = $v['op'];
                    $binds[] = $v['value'];

                    break;
                case 'thermostat_nps_score':
                    if (in_array($v['op'], ['is', 'less_than', 'greater_than'])) {
                        $operation = $this->opTypeBind($v, '', $binds).'?';
                    } elseif (in_array($v['op'], ['type'])) {
                        if ($v['value'] == 'promoter') {
                            $operation = ' IN (9, 10) ';
                        } elseif ($v['value'] == 'passive') {
                            $operation = ' IN (7, 8) ';
                        } elseif ($v['value'] == 'detractor') {
                            $operation = ' IN (0, 1, 2, 3, 4, 5, 6) ';
                        } else {
                            $operation = ' = 555'; // A gibberish value
                        }
                    }
                    $subselect = "(SELECT iScore FROM HS_Thermostat WHERE HS_Thermostat.xRequest = HS_Request.xRequest AND type = 'nps')";
                    $t = $subselect . $operation;

                    break;
                case 'thermostat_csat_score':
                    if (in_array($v['op'], ['is', 'less_than', 'greater_than'])) {
                        $operation = $this->opTypeBind($v, '', $binds).'?';
                    } elseif (in_array($v['op'], ['type'])) {
                        if ($v['value'] == 'satisfied') {
                            $operation = ' IN (4, 5) ';
                        } elseif ($v['value'] == 'dissatisfied') {
                            $operation = ' IN (1, 2, 3) ';
                        } else {
                            $operation = ' = 555'; // A gibberish value
                        }
                    }
                    $subselect = "(SELECT iScore FROM HS_Thermostat WHERE HS_Thermostat.xRequest = HS_Request.xRequest AND type = 'csat')";
                    $t = $subselect . $operation;
                    break;
                case 'thermostat_feedback':
                    if ($v == 'yes') {
                        $operation = $this->opTypeBind('', 'is_not', $binds).'?';
                    } else {
                        $operation = $this->opTypeBind('', 'null', $binds);
                    }
                    $t = 'HS_Request.xRequest in (SELECT xRequest FROM HS_Thermostat WHERE tFeedback'.$operation.')';

                    break;
            }

            // Handle custom fields since this loop won't work in switch statement
            if (is_array($GLOBALS['customFields'])) {
                foreach ($GLOBALS['customFields'] as $fvalue) {
                    $fid = 'Custom'.$fvalue['fieldID'];
                    if ($k == $fid) { 	//check if current field being looped on is one of the custom fields
                        if ($fvalue['fieldType'] == 'checkbox' && ! hs_empty($v)) {
                            $t = ' '.$fid.' = ? ';
                            $binds[] = $v;
                        } elseif ($fvalue['fieldType'] == 'lrgtext' && ! hs_empty($v)) {
                            // Create a function to run DB search as this
                            // may get called more in multiple scenarios
                            $performDbSearch = function () use ($fid, $v) {
                                $ft = dbFullText($fid, $v);
                                $t = $ft['sql'];
                                foreach ($ft['args'] as $arg) {
                                    $binds[] = $arg;
                                }

                                return $t;
                            };

                            $t = $performDbSearch();
                        } elseif ($fvalue['fieldType'] == 'select' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        } elseif ($fvalue['fieldType'] == 'text' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        } elseif ($fvalue['fieldType'] == 'numtext' && ! hs_empty($v)) {
                            //handle ranges vs single number
                            /*
                            if(strpos($v,'-')){
                                $ar = explode('-',$v);
                                $t = ' '.$fid.' >= ? '; $this->bindV($ar[0]);
                                $t = $t . ' AND '.$fid.' <= ? '; $this->bindV($ar[1]);
                            }else{
                            */
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        //}
                        } elseif ($fvalue['fieldType'] == 'drilldown' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        } elseif ($fvalue['fieldType'] == 'date' && ! hs_empty($v)) {
                            //handle relative dates and change of format for date (not dt) fields where hour is set to noon instead of midnight (rev 2108)
                            if ($v['op'] == 'is') {
                                $ts = is_numeric($v['value']) ? $v['value'] : hs_strtotime($v['value'], date('U'));
                                $date_start = mktime(0, 0, 0, date('m', $ts), date('d', $ts), date('Y', $ts));
                                $date_end = mktime(23, 59, 59, date('m', $ts), date('d', $ts), date('Y', $ts));
                                $t = ' ( '.$fid.' >= ? AND '.$fid.' <= ? ) ';
                                $binds[] = $date_start;
                                $binds[] = $date_end;
                            } elseif (in_array($v['op'], ['less_than', 'greater_than'])) {
                                $v['value'] = is_numeric($v['value']) ? $v['value'] : hs_strtotime($v['value'], date('U'));

                                if ($v['op'] == 'less_than') {
                                    $date = new \DateTime();
                                    $date->setTimestamp($v['value']);
                                    $date->setTime(23, 59, 59);
                                    $v['value'] = $date->getTimestamp();
                                } elseif ($v['op'] == 'greater_than') {
                                    $date = new \DateTime();
                                    $date->setTimestamp($v['value']);
                                    $date->setTime(0, 0, 0);
                                    $v['value'] = $date->getTimestamp();
                                }

                                $t = ' '.$fid.' '.$this->opTypeBind($v, 'greater_than', $binds).' ? ';
                            } else {
                                $dates = $this->relativeDateRange($v['op']);
                                $t = ' ( '.$fid.' >= ? AND '.$fid.' <= ? ) ';
                                $binds[] = $dates['start'];
                                $binds[] = $dates['end'];
                            }
                        } elseif ($fvalue['fieldType'] == 'datetime' && ! hs_empty($v)) {
                            //handle relative dates
                            if (in_array($v['op'], ['is', 'less_than', 'greater_than'])) {
                                $v['value'] = is_numeric($v['value']) ? $v['value'] : hs_strtotime($v['value'], date('U'));
                                $t = ' '.$fid.' '.$this->opTypeBind($v, 'greater_than', $binds).' ? ';
                            } else {
                                $dates = $this->relativeDateRange($v['op']);
                                $t = ' ( '.$fid.' >= ? AND '.$fid.' <= ? ) ';
                                $binds[] = $dates['start'];
                                $binds[] = $dates['end'];
                            }
                        } elseif ($fvalue['fieldType'] == 'regex' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        } elseif ($fvalue['fieldType'] == 'ajax' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        } elseif ($fvalue['fieldType'] == 'decimal' && ! hs_empty($v)) {
                            $t = ' '.$fid.' '.$this->opTypeBind($v, 'is', $binds).' ? ';
                        }

                        // if we are using the "is_not" filter with a blank value we need to check not blank and not null values
                        if (isset($v['op']) && $v['op'] == 'is_not' && $v['value']=='') {
                            $t = ' ('.$t.' && '.$fid.' is not NULL )';
                        }
                        // if we are using the "is_not" filter with a string value we need to for fields that don't equal that value or are null values
                        elseif (isset($v['op']) && $v['op'] == 'is_not' && $v['value']!='') {
                            $t = ' ('.$t.' || '.$fid.' is NULL )';
                        }

                        // if we are using the "is" filter and the value is empty then change the query to use IS NULL as well as empty string
                        if (isset($v['op']) && $v['op'] == 'is' && $v['value']=='') {
                            $t = ' ('.$t.' || '.$fid.' is NULL )';
                        }
                    }
                }
            }

            //We don't want to append the operator
            if ($stop_operator) {
                return [$t, $binds];
            }

            if (! empty($t)) {
                if (! isset($this->filterDef['anyall']) || $this->filterDef['anyall'] == 'all') {
                    $where .= $t.' AND ';
                } else {
                    $where .= $t.' OR ';
                }
            }
        }

        return [$where, $binds];
    }

    // OUTPUT METHODS
    public function outputResultSet()
    {
        $this->_runFilter();

        return $this->resultSet;
    }

    public function outputCountTotal()
    {
        global $user;

        $cacheMinutes = ($this->filterDef['fCacheNever']) ? 1 : $this->filterDef['iCachedMinutes'];
        $cacheKey = \Facades\HS\Cache\Manager::filter_key($this->filterDef['xFilter']);

        // Append to cache key for filters specific to logged in user
        foreach($this->filterDef as $filter => $def) {
            if (in_array($filter, ['xPersonAssignedTo', 'xPersonOpenedBy', 'updatedby', 'acReassignedBy', 'acFromTo']) && is_array($def)) {
                foreach($def as $opval) {
                    if($opval['value'] == 'loggedin') {
                        $cacheKey .= '_'.$user['xPerson'];
                        break 2; // We don't need to keep adding to the $cacheKey
                    }
                }
            }
        }

        // If we're just looking for a count and it's cached and we can use the cache return it
        if ($this->countOnly && $this->useCountCache) {
            // fCacheNever now means "limit caching", so we set it to 1 minute
            return \Illuminate\Support\Facades\Cache::remember($cacheKey, $cacheMinutes*60, function () {
                $this->_runFilter(); // runFilter no longer short-circuits with a cached count result
                if ($this->resultSet) {
                    return $this->resultSet;
                } else {
                    return 0;
                }
            });
        } else {
            if (empty($this->filterCount)) {
                $this->_runFilter();

                if (is_numeric($this->resultSet)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $this->resultSet, $cacheMinutes*60);

                    return $this->resultSet;
                }

                if (is_object($this->resultSet)) {
                    // Bust/replace cache when filter is run without cache (e.g. when viewing a filter)
                    // in other words, when $this->useCountCache == false
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $this->resultSet->RecordCount(), $cacheMinutes*60);

                    return $this->resultSet->RecordCount();
                } else {
                    return 0;
                }
            } else {
                return $this->filterCount;
            }
        }
    }

    //Used for system filters (trash/spam) as these must be cached outside of the custom filter caching which uses the HS_Filters tables that these are not a part of
    public function cacheOutputCountTotal($type, $forceRefresh = false)
    {
        if ($forceRefresh) {
            \Facades\HS\Cache\Manager::forgetFilter($type);
        }

        return \Illuminate\Support\Facades\Cache::remember(\Facades\HS\Cache\Manager::filter_key($type), \Facades\HS\Cache\Manager::key('CACHE_SYSTEM_MINUTES'), function () use ($type) {
            $this->_runFilter(); // runFilter no longer short-circuits with a cached count result
            if ($this->resultSet) {
                return $this->resultSet;
            } else {
                return 0;
            }
        });
    }

    public function outputReqIDs()
    {
        $ar = [];
        $this->_runFilter();

        while ($row = $this->resultSet->FetchRow()) {
            $ar[] = $row['xRequest'];
        }

        return $ar;
    }

    public function outputExcel()
    {
        $this->_runFilter();

        if (is_object($this->resultSet)) {
            $function = '';
            $cols = [];

            $csv = new hscsv($this->filterDef['sFilterName']);

            //Create array for headers
            foreach ($this->filterDef['displayColumns'] as $k=>$v) {
                $cols[$v] = empty($GLOBALS['filterCols'][$v]['label']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
            }

            //Write header row
            //Need to force quotes on the header due to this odd bug: http://support.microsoft.com/kb/323626
            $csv->writeRow($cols, false, true);

            //Write data cells
            while ($r = $this->resultSet->FetchRow()) {
                $values = [];

                foreach ($this->filterDef['displayColumns'] as $k=>$v) {
                    $item = $GLOBALS['filterCols'][$v];

                    if (is_array($item['fields'])) {
                        $values[$v] = $r[$item['fields'][0]];
                    } elseif ($v == 'timetrack') {
                        // Only shows the seconds for the time tracker. Not the formatted time. See #349
                        $values[$v] = $r[$item['fields']];
                        unset($item['function']);
                    } elseif ($item['type'] == 'bool') {
                        $values[$v] = boolShow($r[$item['fields']], lg_yes, lg_no);
                    } elseif ($item['fields'] == 'fOpenedVia') {
                        $values[$v] = $GLOBALS['openedVia'][$r[$item['fields']]];
                    } elseif ($v == 'reqsummary') {
                        //Special case to strip subject line from non-email requests
                        if ($r['fOpenedVia'] != 1 && ! empty($r[$item['fields']])) {
                            $t = explode('#-#', $r[$item['fields']]);
                            if (isset($t[1])) {
                                $values[$v] = '#-#'.$t[1]; //initRequestClean function expects #-# to be present
                            }
                        } else {
                            $values[$v] = $r[$item['fields']];
                        }
                    } else {
                        $values[$v] = $r[$item['fields']];
                    }

                    if (isset($item['function'])) {
                        //Make sure 0 date values are sent to excel as 0
                        if ($values[$v] == 0 && ($item['function'] == 'hs_showShortDate' || $item['function'] == 'hs_showDate')) {
                            $values[$v] = 0;
                        } else {
                            if (! isset($item['function_args'])) {
                                $f = $item['function'];
                                $values[$v] = $f($values[$v]);
                            } else {
                                $args = [];
                                foreach ($item['function_args'] as $arg) {
                                    $args[] = $r[$arg];
                                }
                                $values[$v] = call_user_func_array($item['function'], $args);
                            }
                        }
                    }

                    $values[$v] = utf8_trim(strip_tags(hs_html_entity_decode($values[$v])));
                }

                $csv->writeRow($values);
            }

            //Send the file
            $csv->output();
        } else {
            return false;
        }
    }

    // HELPER/LOOKUP METHODS
    public function useSystemFilter($filter)
    {
        global $user;
        $this->filterDef = [];	//reset filterDef

        //Figure out current columns
        if (! hs_empty($user['tWorkspace'])) {
            $cols = hs_unserialize($user['tWorkspace']);
        }

        switch ($filter) {
            case 'inbox':
                //Show open and unassigned requests
                $this->filterDef['displayColumns'] = isset($cols['inbox']) ? array_merge(['takeit'], $cols['inbox']) : array_merge(['takeit'], $GLOBALS['defaultWorkspaceCols']);
                $this->filterDef['displayColumnsWidths'] = isset($cols['inbox_widths']) ? $cols['inbox_widths'] : [];
                $this->filterDef['sFilterName'] = lg_helpdesknav;
                $this->filterDef['fOpen'] = 1;
                $this->filterDef['xPersonAssignedTo'] = 0;

                break;
            case 'myq':
                //Show reqs assigned to user
                $this->filterDef['displayColumns'] = isset($cols['myq']) ? array_merge(['isunread', 'view'], $cols['myq']) : array_merge(['isunread', 'view'], $GLOBALS['defaultWorkspaceCols']);
                $this->filterDef['displayColumnsWidths'] = isset($cols['myq_widths']) ? $cols['myq_widths'] : [];
                $this->filterDef['sFilterName'] = lg_myq;
                $this->filterDef['fOpen'] = 1;
                $this->filterDef['xPersonAssignedTo'] = $user['xPerson'];
                /*
                //change sql to support read/unread
                if($this->countOnly){
                    $select_values = 'COUNT(DISTINCT HS_Request.xRequest) AS thecount';
                }else{
                    $select_values = 'DISTINCT HS_Request.*, HS_Category.sCategory, HS_luStatus.sStatus, '.dbConcat(' ','HS_Request.sFirstName','HS_Request.sLastName').' AS fullname';
                }

                $this->sql = 'SELECT '.$select_values.'
                              FROM HS_luStatus,HS_Request
                              LEFT OUTER JOIN HS_Category ON HS_Request.xCategory = HS_Category.xCategory
                              WHERE HS_Request.xStatus = HS_luStatus.xStatus AND %s ORDER BY HS_Request.fUrgent DESC, %s';
                */
                break;
            case 'myq_unread':
                //Show reqs assigned to user
                //$this->filterDef['displayColumns'] = isset($cols['myq']) ? array_merge(array('isunread','view'),$cols['myq']) : array_merge(array('isunread','view'),$GLOBALS['defaultWorkspaceCols']);
                //$this->filterDef['sFilterName'] = lg_myq;
                $this->filterDef['fOpen'] = 1;
                $this->filterDef['xPersonAssignedTo'] = $user['xPerson'];
                $this->filterDef['isunread'] = 1; //special where clause that returns the number of unread requests in the myq
                break;
            case 'spam':
                //Show spam
                $this->filterDef['displayColumns'] = ['view', 'fOpenedVia', 'fullname', 'reqsummary', 'dtGMTOpened'];
                $this->filterDef['sFilterName'] = lg_spam;
                $this->filterDef['xStatus'] = hs_setting('cHD_STATUS_SPAM', 2);

                break;
            case 'trash':
                //Show trash
                $this->filterDef['displayColumns'] = ['view', 'fOpenedVia', 'fullname', 'reqsummary', 'dtGMTTrashed'];
                $this->filterDef['sFilterName'] = lg_trash;
                $this->filterDef['fTrash'] = 1;
                $this->filterDef['orderBy'] = 'dtGMTTrashed';
                $this->filterDef['orderByDir'] = 'ASC';

                break;
        }
    }

    public function wildcard($string)
    {
        $string = trim($string);
        $size = strlen($string) - 1;

        if ($string[0] == '*') {
            $string[0] = '%';
        }
        if ($string[$size] == '*') {
            $string[$size] = '%';
        }

        return $string;
    }

    //Used for types where things other than equal make sense.
    public function opTypeBind($value, $default = '', &$binds)
    {
        //If an array is the new style used in automation. It has 2 keys: op,value
        //If not an array then use the default type and value is the string value
        if (is_array($value)) {
            $type = trim($value['op']);
            $value = trim($value['value']);
            $is_array = true;
        } else {
            $type = $default;
            $value = $value;
            $is_array = false;
        }

        switch ($type) {
            // here we return to skip $this->bindV because
            // this clause should not have any bound parameters
            case 'null':
                return ' IS NULL';
            case 'is':
                $out = ' = ';

                break;
            case 'is_not':
                $out = ' <> ';

                break;
            case 'in':
                $out = ' IN ';

                break;
            case 'not_in':
                $out = ' NOT IN ';

                break;
            case 'begins_with':
                $out = ' '.dbLike().' ';
                if ($is_array) {
                    $value = $value.'*';
                }	//Only modify if in array format. Otherwise we could mess up filters which already store the wildcard.
                break;
            case 'ends_with':
                $out = ' '.dbLike().' ';
                if ($is_array) {
                    $value = '*'.$value;
                }

                break;
            case 'contains':
                $out = ' '.dbLike().' ';
                if ($is_array) {
                    $value = '*'.$value.'*';
                }

                break;
            case 'not_contain':
                $out = ' '.dbNotLike().' ';
                if ($is_array) {
                    $value = '*'.$value.'*';
                }

                break;
            case 'less_than':
                $out = ' < ';

                break;
            case 'greater_than':
                $out = ' > ';

                break;
            case 'greater_than_equalto':
                $out = ' >= ';

                break;
            case 'less_than_equalto':
                $out = ' <= ';

                break;
        }

        // This adds to $binds as it's "pass-by-reference".
        // It's...a bit dirty
        $binds[] = $this->wildcard($value);

        return $out;
    }

    public function doPreParse()
    {
        global $user;
        $reqids = [];

        if (is_object($this->resultSet) && $this->resultSet->RecordCount() > 0) {

            //ADD IN REPORTING TAGS - this is very intensive and should only be used when no other methos is available.
            //Keep an eye on group_concat for mysql. If other db's ever support something similar we could replace this with a subselect and that.
            if (in_array('reportingTags', $this->filterDef['displayColumns'])) {
                //loop to build array indexed by xRequest for secondary query below
                while ($row = $this->resultSet->FetchRow()) {
                    $reqids[] = $row['xRequest'];
                }
                //Reset dataset
                $this->resultSet->Move(0);

                $rs = $GLOBALS['DB']->Execute('SELECT HS_Request_ReportingTags.xRequest, HS_Category_ReportingTags.sReportingTag
												FROM HS_Request_ReportingTags, HS_Category_ReportingTags
												WHERE HS_Request_ReportingTags.xRequest IN ('.implode(',', $reqids).') AND HS_Request_ReportingTags.xReportingTag = HS_Category_ReportingTags.xReportingTag');

                if ($rs) {
                    $temp = [];
                    while ($row = $rs->FetchRow()) {
                        $temp[$row['xRequest']] = (empty($temp[$row['xRequest']]) ? $row['sReportingTag'] : $temp[$row['xRequest']].', '.$row['sReportingTag']);
                    }
                    //Insert into this filter set
                    $this->RSFilter('reportingTags', $temp);
                }
            }

            // Doing last public note in this fashion proves to be more reliable
            // than former subselect method. We do both lastpublicnote and lastpublicnoteby
            // at the same time.
            if (in_array('lastpublicnote', $this->filterDef['displayColumns']) || in_array('lastpublicnoteby', $this->filterDef['displayColumns']) || in_array('lastupdateby', $this->filterDef['displayColumns'])) {
                //loop to build array indexed by xRequest for secondary query below
                while ($row = $this->resultSet->FetchRow()) {
                    $reqids[] = $row['xRequest'];
                }

                //Reset dataset
                $this->resultSet->Move(0);

                // pull out lastupdateby first but only if they request it.
                $tempanyby = [];
                if (in_array('lastupdateby', $this->filterDef['displayColumns'])) {
                    $all_notes = $GLOBALS['DB']->Execute('SELECT xRequestHistory, xRequest, dtGMTChange, xPerson
												FROM HS_Request_History
												WHERE HS_Request_History.xRequest IN ('.implode(',', $reqids).')');
                    while ($row = $all_notes->FetchRow()) {
                        $tempanyby[$row['xRequest']] = $row['xPerson'];
                    }
                }

                // Get all the public notes so we can figure out the latest
                $all_notes = $GLOBALS['DB']->Execute('SELECT xRequestHistory, xRequest, dtGMTChange
												FROM HS_Request_History
												WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xRequest IN ('.implode(',', $reqids).')');

                // Figure out the latest and go back and get the actual notes
                $latest = [];
                $time_track = [];
                if (hs_rscheck($all_notes)) {
                    while ($row = $all_notes->FetchRow()) {
                        if (! isset($time_track[$row['xRequest']]) || $time_track[$row['xRequest']] < $row['dtGMTChange']) {
                            $latest[$row['xRequest']] = $row['xRequestHistory'];
                            $time_track[$row['xRequest']] = $row['dtGMTChange'];
                        }
                    }

                    $rs = $GLOBALS['DB']->Execute('SELECT fPublic, HS_Request_History.xRequest, HS_Request_History.dtGMTChange, HS_Request_History.xPerson, SUBSTRING(HS_Request_History.tNote, 1, 200) as tNote
													FROM HS_Request_History
													WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xRequestHistory IN ('.implode(',', $latest).')');

                    if ($rs) {
                        $temp = [];
                        $tempby = [];

                        // Setup temp array with an empty string for each request
                        foreach ($reqids as $v) {
                            $temp[$v] = '';
                            $tempby[$v] = 0;
                        }

                        // Populate note data
                        while ($row = $rs->FetchRow()) {
                            $temp[$row['xRequest']] = $row['tNote'];
                            $tempby[$row['xRequest']] = $row['xPerson'];
                        }

                        //Insert into this filter set
                        $this->RSFilter('lastpublicnote', $temp);
                        $this->RSFilter('lastpublicnoteby', $tempby);
                        $this->RSFilter('lastupdateby', $tempanyby);
                    }
                }
            }

            /*
            //THIS IS NO LONGER NEEDED, SUBSELECTS NOW USES
            //loop to build array indexed by xRequest for secondary queries below
            while($row = $this->resultSet->FetchRow()){
                $reqids[]   = $row['xRequest'];
            }
            //Reset dataset
            $this->resultSet->Move(0);

            //This allows these special queries below to be run even if the column associated with them isn't in displaycolumns array
            $orderby = isset($this->filterDef['orderBy']) ? $this->filterDef['orderBy'] : '';

            //EXTRA COLUMNS
            //-These cols can not be added in the main query above so they're added here. Only add if col is to be displayed

            //Create subject lines with email title in front
            if(in_array('reqsummary', $this->filterDef['displayColumns'])){
                $rs = $GLOBALS['DB']->Execute( 'SELECT '.dbConcat(' #-#','HS_Request.sTitle','SUBSTRING(HS_Request_History.tNote, 1, 500)').' AS tNote, HS_Request_History.xRequest
                                                FROM HS_Request, HS_Request_History
                                                WHERE HS_Request.xRequest = HS_Request_History.xRequest AND HS_Request_History.fInitial = 1 AND
                                                      HS_Request_History.xRequest IN ('.implode(',',$reqids).')' );

                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = hs_strip_tags($row['tNote']);
                    }
                    //Insert into this filter set
                    $this->RSFilter('tNote', $temp);
                }
            }

            //Show time tracker time on a request
            if(in_array('timetrack', $this->filterDef['displayColumns'])){

                $rs = $GLOBALS['DB']->Execute( 'SELECT xRequest, SUM(iSeconds) AS timetrack
                                                FROM HS_Time_Tracker
                                                WHERE HS_Time_Tracker.xRequest IN ('.implode(',',$reqids).')
                                                GROUP BY xRequest' );

                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = $row['timetrack'];
                    }
                    //Insert into this filter set
                    $this->RSFilter('timetrack', $temp);
                }
            }

            //Show the last update of any kind
            if(in_array('lastupdate', $this->filterDef['displayColumns']) || isset($this->filterDef['lastupdate']) || $orderby == 'lastupdate'){
                $rs = $GLOBALS['DB']->Execute( 'SELECT xRequest, MAX(dtGMTChange) AS lastupdate
                                                FROM HS_Request_History
                                                WHERE HS_Request_History.xRequest IN ('.implode(',',$reqids).')
                                                GROUP BY xRequest' );

                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = $row['lastupdate'];
                    }

                    //Insert into this filter set
                    $this->RSFilter('lastupdate', $temp);

                    //If there's a filter on this column then do it
                    $this->doTimeSinceFilter('lastupdate');
                }
            }


            //Show the last public update
            if(in_array('lastpubupdate', $this->filterDef['displayColumns']) || isset($this->filterDef['lastpubupdate']) || $orderby == 'lastpubupdate'){
                $rs = $GLOBALS['DB']->Execute( 'SELECT xRequest, MAX(dtGMTChange) AS lastpubupdate
                                                FROM HS_Request_History
                                                WHERE HS_Request_History.xRequest IN ('.implode(',',$reqids).') AND fPublic = 1
                                                GROUP BY xRequest');
                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = $row['lastpubupdate'];
                    }
                    //Insert into this filter set
                    $this->RSFilter('lastpubupdate', $temp);

                    //If there's a filter on this column then do it
                    $this->doTimeSinceFilter('lastpubupdate');
                }
            }

            //Show the last customer update
            if(in_array('lastcustupdate', $this->filterDef['displayColumns']) || isset($this->filterDef['lastcustupdate']) || $orderby == 'lastcustupdate'){
                $rs = $GLOBALS['DB']->Execute( 'SELECT xRequest, MAX(dtGMTChange) AS lastcustupdate
                                                FROM HS_Request_History
                                                WHERE HS_Request_History.xRequest IN ('.implode(',',$reqids).') AND xPerson = 0
                                                GROUP BY xRequest');
                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = $row['lastcustupdate'];
                    }
                    //Insert into this filter set
                    $this->RSFilter('lastcustupdate', $temp);

                    //If there's a filter on this column then do it
                    $this->doTimeSinceFilter('lastcustupdate');
                }
            }

            //Show the number of public updatese
            if(in_array('ctPublicUpdates', $this->filterDef['displayColumns']) || isset($this->filterDef['ctPublicUpdates']) || $orderby == 'ctPublicUpdates'){
                $rs = $GLOBALS['DB']->Execute( 'SELECT xRequest, COUNT(*) AS ctPublicUpdates
                                                FROM HS_Request_History
                                                WHERE HS_Request_History.xRequest IN ('.implode(',',$reqids).') AND fPublic = 1
                                                GROUP BY xRequest');
                if($rs){
                    $temp = array();
                    while($row = $rs->FetchRow()){
                        $temp[$row['xRequest']] = $row['ctPublicUpdates'];
                    }
                    //Insert into this filter set
                    $this->RSFilter('ctPublicUpdates', $temp);

                    //If there's a filter on this column then do it
                    if(isset($this->filterDef['ctPublicUpdates'])){
                        $newrs = array();
                        while($row = $this->resultSet->FetchRow()){

                            //Go over each public update condition
                            foreach($this->filterDef['ctPublicUpdates'] AS $k=>$oparray){
                                if($oparray['op'] == 'is' && $row['ctPublicUpdates'] == (int)$oparray['value']) $newrs[] = $row;
                                if($oparray['op'] == 'greater_than' && $row['ctPublicUpdates'] > (int)$oparray['value']) $newrs[] = $row;
                                if($oparray['op'] == 'less_than' && $row['ctPublicUpdates'] < (int)$oparray['value']) $newrs[] = $row;
                            }
                        }
                        $this->resultSet = new array2recordset($newrs);
                    }
                }
            }
            */
        }
    }

    /*
    function doTimeSinceFilter($field){
        if(isset($this->filterDef[$field])){
            $newrs = array();
            while($row = $this->resultSet->FetchRow()){

                //Go over each public update condition.
                //Notes: "$row[$field] != 0" - keeps ones with no updates from showing
                //Notes: ((time() - $row[$field]) / 60) - current time minus last update time then divide by 60 seconds to get minute difference which we can compare against the minutes the user specified
                foreach($this->filterDef[$field] AS $k=>$oparray){
                    if($oparray['op'] == 'greater_than' && $row[$field] != 0 && ((time() - $row[$field]) / 60) > $oparray['value']) $newrs[] = $row;
                    if($oparray['op'] == 'less_than' 	&& $row[$field] != 0 && ((time() - $row[$field]) / 60) < $oparray['value']) $newrs[] = $row;
                }
            }
            $this->resultSet = new array2recordset($newrs);
        }
    }
    */

    public function bindV($value)
    {
        array_push($this->bindValues, $value);
    }

    /**
     * Originally copied/modified from adodb/rsfilter.inc.php,
     *   now adapted to helpspot 5's App\Database\RecordSet
     * This adds fields to resultset (RecordSet object), taking the new field name and an array of data keyed by xRequest.
     * @param $newfield
     * @param $data
     */
    public function RSFilter($newfield, &$data)
    {
        // Convert result set to array and add new field
        $this->resultSet->Walk(function (&$item, $key) use ($newfield, &$data) {
            $item = (array) $item;
            $item[$newfield] = isset($data[$item['xRequest']]) ? $data[$item['xRequest']] : '';
        });

        // Manual sorting for fields that require this
        // TODO: I don't believe this condition is ever reached!
        //       The items that use RSFilter now are not sortable fields (which wasn't always the case)
        if (strpos($this->overrideSort, $newfield) !== false) {
            // array_multisort needs this array of the column you want to sort
            $order = $this->resultSet->Map(function ($item, $key) use ($newfield) {
                return $item[$newfield];
            });

            // Reorder
            if (strpos(strtolower($this->overrideSort), 'desc') === false) {
                $this->resultSet->Multisort($order, SORT_ASC);
            } else {
                $this->resultSet->Multisort($order, SORT_DESC);
            }
        }

        $this->resultSet->Move(0);
    }

    public function relativeDateRange($v)
    {
        $start = 0;
        $end = 0;

        if ($v == 'today') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'tomorrow') {
            $start = Carbon::now()->addDay()->startOfDay()->timestamp;
            $end = Carbon::now()->addDay()->endOfDay()->timestamp;
        } elseif ($v == 'yesterday') {
            $start = Carbon::now()->subDay()->startOfDay()->timestamp;
            $end = Carbon::now()->subDay()->endOfDay()->timestamp;
            //all past times are 1 less than value as they're inclusive of today
        } elseif ($v == 'past_7') {
            $start = Carbon::now()->subDays(6)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'past_14') {
            $start = Carbon::now()->subDays(13)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'past_30') {
            $start = Carbon::now()->subDays(29)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'past_60') {
            $start = Carbon::now()->subDays(59)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'past_90') {
            $start = Carbon::now()->subDays(89)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'past_365') {
            $start = Carbon::now()->subDays(364)->startOfDay()->timestamp;
            $end = Carbon::now()->endOfDay()->timestamp;
        } elseif ($v == 'this_week') {
            // Carbon starts the week on Monday, however we start on Sunday, so we ->subDay()
            $start = Carbon::now()->startOfWeek()->subDay()->startOfDay()->timestamp;
            $end = Carbon::now()->endOfWeek()->subDay()->endOfDay()->timestamp;
        } elseif ($v == 'this_month') {
            $start = Carbon::now()->startOfMonth()->startOfDay()->timestamp;
            $end = Carbon::now()->endOfMonth()->endOfDay()->timestamp;
        } elseif ($v == 'this_year') {
            $start = Carbon::now()->startOfYear()->startOfDay()->timestamp;
            $end = Carbon::now()->endOfYear()->endOfDay()->timestamp;
        } elseif ($v == 'last_week') {
            $start = Carbon::now()->subWeek()->startOfWeek()->subDay()->startOfDay()->timestamp;
            $end = Carbon::now()->subWeek()->endOfWeek()->subDay()->endOfDay()->timestamp;
        } elseif ($v == 'last_month') {
            $start = Carbon::now()->subMonth()->startOfMonth()->startOfDay()->timestamp;
            $end = Carbon::now()->subMonth()->endOfMonth()->endOfDay()->timestamp;
        } elseif ($v == 'last_year') {
            $start = Carbon::now()->subYear()->startOfYear()->startOfDay()->timestamp;
            $end = Carbon::now()->subYear()->endOfYear()->endOfDay()->timestamp;
        } elseif ($v == 'next_7') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->addDays(6)->endOfDay()->timestamp;
        } elseif ($v == 'next_14') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->addDays(13)->endOfDay()->timestamp;
        } elseif ($v == 'next_30') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->addDays(29)->endOfDay()->timestamp;
        } elseif ($v == 'next_90') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->addDays(89)->endOfDay()->timestamp;
        } elseif ($v == 'next_365') {
            $start = Carbon::now()->startOfDay()->timestamp;
            $end = Carbon::now()->addDays(364)->endOfDay()->timestamp;
        } elseif ($v == 'next_week') {
            $start = Carbon::now()->addWeek()->startOfWeek()->subDay()->startOfDay()->timestamp;
            $end = Carbon::now()->addWeek()->endOfWeek()->subDay()->endOfDay()->timestamp;
        } elseif ($v == 'next_month') {
            $start = Carbon::now()->addMonth()->startOfMonth()->startOfDay()->timestamp;
            $end = Carbon::now()->addMonth()->endOfMonth()->endOfDay()->timestamp;
        } elseif ($v == 'next_year') {
            $start = Carbon::now()->addYear()->startOfYear()->startOfDay()->timestamp;
            $end = Carbon::now()->addYear()->endOfYear()->endOfDay()->timestamp;
        } elseif ($v == 'date_is_set') {
            $start = 1;
            $end = 99999999999;
        } elseif ($v == 'date_is_not_set') {
            $start = '';
            $end = '';
        }

        return ['start' => $start, 'end' => $end];
    }

    public function countCacheValid()
    {
        //If caching is off or this filter should never be cached then return false
        if ($this->filterDef['fCacheNever'] == 1 || hs_setting('cHD_FILTER_COUNT_CACHE') == 0) {
            return false;
        }

        if ($this->filterDef['dtCachedCountAt'] < (time() - hs_setting('cHD_FILTER_COUNT_CACHE'))) {
            return false;
        } else {
            return true;
        }
    }

    public function _useVirtualArchive()
    {
        return  hs_setting('cHD_VIRTUAL_ARCHIVE') > 0 &&
            ! $this->is_report &&
            isset($this->filterDef['xFilter']) && // if it's a filter then apply the limit. #528
            ! isset($this->filterDef['betweenDates']) &&
            ! isset($this->filterDef['betweenTTDates']) &&
            ! isset($this->filterDef['relativedate']) &&
            ! isset($this->filterDef['dtSinceCreated']) &&
            ! isset($this->filterDef['dtSinceClosed']) &&
            ! isset($this->filterDef['relativedatetoday']) &&
            ! isset($this->filterDef['relativedateclosed']) &&
            ! isset($this->filterDef['relativedatelastpub']) &&
            ! isset($this->filterDef['relativedatelastcust']) &&
            ! isset($this->filterDef['lastupdate']) &&
            ! isset($this->filterDef['lastpubupdate']) &&
            ! isset($this->filterDef['lastcustupdate']) &&
            ! isset($this->filterDef['closedBeforeDate']) &&
            ! isset($this->filterDef['closedAfterDate']) &&
            ! isset($this->filterDef['beforeDate']) &&
            ! isset($this->filterDef['afterDate']);
    }

    /**
     * @param string $where
     * @param array $whitelist
     * @return array [$where, $queryBinds]
     */
    public function queryFromFilterDef($where='', $whitelist=[])
    {
        $queryBinds = [];
        foreach ($this->filterDef as $k => $v) {
            //Array is new style used in automation. K is field type
            if (is_array($v) && $k != 'displayColumns' && $k != 'reportingTags') {
                foreach ($v as $key => $condition) {
                    //If a sub group handle or else it's a normal condition
                    //No sub-conditions with whitelist
                    if (count($whitelist) == 0 && ($k == 'subconditions_and' || $k == 'subconditions_or')) {

                        $where .= '(';

                        $where_ar = array();
                        foreach ($this->filterDef[$condition] as $subk => $subcondgroup) {
                            //Loop over each one of the sub conditions of the same type to add a where for each one
                            foreach ($subcondgroup as $condk => $condvalue) {
                                //The sub group has the condition type (xcategory,etc) so use subk
                                list($where_part, $binds) = $this->wheresql($subk, $condvalue, true, $whitelist);
                                //Only append if we got something back. Might not in some cases where a special query is done in wheresql like with rep tags
                                if ( ! empty($where_part)) $where_ar[] = $where_part;
                                if (is_array($binds)) {
                                    $queryBinds = array_merge($queryBinds, $binds);
                                }
                            }
                        }

                        $where .= implode(($k == 'subconditions_and' ? ' AND ' : ' OR '), $where_ar);

                        $where .= ') ';

                        //Add and/or for the group
                        $where .= (! isset($this->filterDef['anyall']) || $this->filterDef['anyall'] == 'all' ? ' AND ' : ' OR ');

                    } else {
                        list($whereSql, $binds) = $this->wheresql($k, $condition, false, $whitelist);
                        $where .= $whereSql;
                        if (is_array($binds)) {
                            $queryBinds = array_merge($queryBinds, $binds);
                        }
                    }
                }
            } else {
                //This is a normal condition, not part of a sub-condition
                list($whereSql, $binds) = $this->wheresql($k, $v, false, $whitelist);
                $where .= $whereSql;
                if (is_array($binds)) {
                    $queryBinds = array_merge($queryBinds, $binds);
                }
            }
        }
        return [$where, $queryBinds];
    }
}
