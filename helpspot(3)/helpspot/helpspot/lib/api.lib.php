<?php

// Comment
// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/******************************************
 * GET REQUEST DETAILS
 *****************************************
 * @param $reqid
 */
function apiGetRequest($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->GetRow('SELECT *, '.dbConcat(' ', 'HS_Request.sFirstName', 'HS_Request.sLastName').' AS fullname FROM HS_Request WHERE xRequest = ?', [$reqid]);
}

/******************************************
GET REQUEST CUSTOMER DETAILS
******************************************/
function apiGetRequestCustomer($reqid, $f, $l)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;
    static $cache = [];

    if (isset($cache[$reqid]) && ! empty($cache[$reqid])) {
        return $cache[$reqid];
    }

    $cache[$reqid] = $GLOBALS['DB']->GetRow('SELECT sFirstName,sLastName,sEmail FROM HS_Request WHERE xRequest = ?', [$reqid]);
    if ($cache[$reqid] == false) {
        return false;
    } else {
        return $cache[$reqid];
    }
}

/******************************************
 * GET USER
 * - caches since batch operations often call this many times
 *****************************************
 * @param $userid
 * @return bool|mixed
 */
function apiGetUser($userid)
{
    $userid = is_numeric($userid) ? $userid : 0;
    static $cache = [];

    if ($userid != 0) {
        if (isset($cache[$userid]) && ! empty($cache[$userid])) {
            return $cache[$userid];
        }

        $userid = isset($userid) && is_numeric($userid) ? $userid : '';

        return $cache[$userid] = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Person WHERE xPerson = ?', [$userid]);
    } else {
        return false;
    }
}

/**
 * @param $xPerson
 * @return bool
 */
function apiGetUserById($xPerson)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Person
        LEFT OUTER JOIN HS_Person_Status ON HS_Person.xPerson = HS_Person_Status.xPersonStatus
        LEFT JOIN HS_Permission_Groups ON HS_Person.fUserType = HS_Permission_Groups.xGroup
        WHERE xPerson = ?', [$xPerson]);
}

/**
 * @param $email
 * @return bool
 */
function apiGetUserByEmail($email)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Person
        LEFT OUTER JOIN HS_Person_Status ON HS_Person.xPerson = HS_Person_Status.xPersonStatus
        LEFT JOIN HS_Permission_Groups ON HS_Person.fUserType = HS_Permission_Groups.xGroup
        WHERE sEmail = ? and fDeleted = 0', [$email]);
}

/******************************************
GET USER BY AUTH DETAILS
******************************************/
function apiGetUserByAuth($username, $email)
{
    return $GLOBALS['DB']->GetRow('SELECT *
                                       FROM HS_Person
                                            LEFT OUTER JOIN HS_Person_Status ON HS_Person.xPerson = HS_Person_Status.xPersonStatus
                                            LEFT JOIN HS_Permission_Groups ON HS_Person.fUserType = HS_Permission_Groups.xGroup
                                       WHERE (sUsername = ? OR sEmail = ?)', [$username, $email]);
}

/******************************************
GET LOGGED IN USER
******************************************/
function apiGetLoggedInUser()
{
    global $user;

    //Primary purpose of this function is to make sure the below are set and empty if no logged in user is present, like when a auto rule is run
    if (empty($user)) {
        $user = [];
    }
    $user['sFname'] = ! isset($user['sFname']) ? '' : $user['sFname'];
    $user['sLname'] = ! isset($user['sLname']) ? '' : $user['sLname'];
    $user['sEmail'] = ! isset($user['sEmail']) ? '' : $user['sEmail'];
    $user['sPhone'] = ! isset($user['sPhone']) ? '' : $user['sPhone'];
    $user['fUserType'] = ! isset($user['fUserType']) ? 0 : $user['fUserType'];
    $user['xPerson'] = ! isset($user['xPerson']) ? 0 : $user['xPerson'];

    return $user;
}

/******************************************
 * GET ASSIGNED USER INFO FOR USE IN PLACEHOLDERS
 *****************************************
 * @param $userid
 * @return array|bool|mixed
 */
function apiGetUserPlaceholders($userid)
{
    $out = [];
    $out['sFname'] = '';
    $out['sLname'] = '';
    $out['sEmail'] = '';
    $out['sPhone'] = '';
    if ($userid == 0) {
        //return empty array of fields we use in placeholders
        return $out;
    } else {
        //if user found then return, else return empty array
        $auser = apiGetUser($userid);
        if ($auser) {
            return $auser;
        } else {
            return $out;
        }
    }
}

/******************************************
 * GET ALL PERMISSION GROUPS
 ****************************************
 */
function apiPermsGetAll()
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Permission_Groups');
}

/******************************************
 * GET PERMISSION BY ID
 *****************************************
 * @param $id
 */
function apiPermGetById($id)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Permission_Groups WHERE xGroup = ?', [$id]);
}

/******************************************
 * GET INITIAL REQUEST BODY
 *****************************************
 * @param $reqid
 */
function apiGetInitialRequest($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->GetRow('SELECT tNote,tEmailHeaders,xRequestHistory,fNoteIsHTML,fPublic FROM HS_Request_History WHERE HS_Request_History.fInitial = 1 AND xRequest = ?', [$reqid]);
}

/******************************************
GET LAST REQUEST NOTE
******************************************/
function apiGetLastPublicRequestNote($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->SelectLimit('SELECT tNote,tEmailHeaders,xRequestHistory,fNoteIsHTML FROM HS_Request_History WHERE HS_Request_History.fPublic = 1 AND HS_Request_History.xRequest = ? ORDER BY HS_Request_History.dtGMTChange DESC', 1, 0, [$reqid]);
}

/******************************************
 * GET REQUEST HISTORY DETAILS
 *****************************************
 * @param $reqid
 */
function apiGetRequestHistory($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    $sql = 'SELECT HS_Request_History.*, HS_Documents.xDocumentId, HS_Documents.sFilename, HS_Documents.sFileMimeType, HS_Documents.sCID
                FROM HS_Request_History LEFT OUTER JOIN HS_Documents ON HS_Documents.xRequestHistory = HS_Request_History.xRequestHistory
                WHERE xRequest = ?
                ORDER BY HS_Request_History.fPinned DESC, HS_Request_History.dtGMTChange DESC, HS_Request_History.xRequestHistory DESC';

    return $GLOBALS['DB']->Execute($sql, [$reqid]);
}

/******************************************
GET COUNT OF HISTORY ITEMS, LEAVING OUT ATTACHMENTS
******************************************/
function apiGetRequestHistoryCountWOAttachmets($reqid, $view)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    //Extra where clause for various views
    if ($view == 2) {
        $where = ' AND fPublic = 1';
    } elseif ($view == 4) {
        $where = ' AND '.dbStrLen('tNote').' > 0';
    }

    return $GLOBALS['DB']->GetOne('SELECT COUNT(*) AS thecount
                                     FROM HS_Request_History
                                     WHERE xRequest = ? '.$where, [$reqid]);
}

/******************************************
 * GET HISTORY EVENT - RETURNS SINGLE HISTORY ROW
 *****************************************
 * @param $reqhisid
 */
function apiGetHistoryEvent($reqhisid)
{
    $reqhisid = is_numeric($reqhisid) ? $reqhisid : 0;

    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Request_History WHERE xRequestHistory = ?', [$reqhisid]);
}

/******************************************
GET CATEGORY
******************************************/
function apiGetCategory($catid)
{
    if (! empty($catid)) {
        $catid = isset($catid) && is_numeric($catid) ? $catid : '';

        return $GLOBALS['DB']->GetRow('SELECT HS_Category.*,HS_Person.sFname,HS_Person.sLname
                                             FROM HS_Category LEFT OUTER JOIN HS_Person ON HS_Category.xPersonDefault = HS_Person.xPerson
                                             WHERE HS_Category.xCategory = ?', [$catid]);
    } else {
        return false;
    }
}

/******************************************
GET CATEGORY NAME
******************************************/
function apiGetCategoryName($catid)
{
    $name = $GLOBALS['DB']->GetOne('SELECT sCategory FROM HS_Category WHERE xCategory = ?', [$catid]);
    return ($name) ? $name : '';
}

/******************************************
 * GET ALL CATEGORIES
 *****************************************
 * @param $showdeleted
 * @param $sortby
 * @return mixed
 */
function apiGetAllCategories($showdeleted, $sortby = '')
{
    $cachekey = $showdeleted ? \HS\Cache\Manager::CACHE_CATEGORIES_DELETED_KEY : \HS\Cache\Manager::CACHE_CATEGORIES_ACTIVE_KEY;

    return \Illuminate\Support\Facades\Cache::remember($cachekey, \HS\Cache\Manager::CACHE_CATEGORIES_MINUTES, function () use ($showdeleted, $sortby) {
        $sortby = trim($sortby) != '' ? $sortby : 'sCategoryGroup ASC,sCategory ASC';
        $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);
        return $GLOBALS['DB']->Execute('SELECT HS_Category.*, '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS fullname
                                      FROM HS_Category LEFT OUTER JOIN HS_Person ON HS_Category.xPersonDefault = HS_Person.xPerson
                                      WHERE HS_Category.fDeleted = ?
                                      ORDER BY '.$sortby, [$showdeleted]);
    });
}

/******************************************
GET CATEGORY GROUPS
******************************************/
function apiGetCategoryGroups()
{
    return $GLOBALS['DB']->GetCol('SELECT DISTINCT sCategoryGroup FROM HS_Category WHERE fDeleted = 0');
}

/******************************************
 * GET ALL CATEGORIES COMPLETE
 *****************************************
 * @return mixed
 */
function apiGetAllCategoriesComplete()
{
    return \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_ALLCATEGORIES_KEY, \HS\Cache\Manager::CACHE_ALLCATEGORIES_MINUTES, function() {
        return $GLOBALS['DB']->Execute('SELECT HS_Category.*, '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' AS fullname
                                      FROM HS_Category LEFT OUTER JOIN HS_Person ON HS_Category.xPersonDefault = HS_Person.xPerson
                                      ORDER BY HS_Category.sCategory');
    });
}

/******************************************
GET ARRAY OF CATEGORIES CUSTOM FIELDS
******************************************/
function apiGetCategoryCustomFields($catid)
{
    $reqcat = apiGetCategory($catid);

    return hs_unserialize($reqcat['sCustomFieldList']);
}

/******************************************
GET ALL CATEGORIES A STAFFER BELONGS TO
******************************************/
function apiGetUserCats($xperson)
{
    $ok = [];

    $cats = apiGetAllCategories(0, '');
    $cats = rsToArray($cats, 'xCategory');
    foreach ($cats as $i=>$cat) {
        $cats[$i]['sPersonList'] = hs_unserialize($cat['sPersonList']);
        if (in_array($xperson, $cats[$i]['sPersonList'])) {
            $ok[] = $i;
        }
    }

    return $ok;
}

/**
 * @param array $user
 * @return string
 */
function apiGetUserAssignedCatsWhere(array $user)
{
    $cats = apiGetUserCats($user['xPerson']);
    if (empty($cats)) {
        return 'HS_Request.xCategory = 0';
    }
    return 'HS_Request.xCategory IN ('.implode(',', $cats).')';
}

/******************************************
GET ALL STAFFERS IN THE SAME CATEGORIES AS THE USER
******************************************/
function apiGetStaffInUserCats($xperson)
{
    $ok = [];

    $cats = apiGetAllCategories(0, '');
    $cats = rsToArray($cats, 'xCategory');
    foreach ($cats as $i=>$cat) {
        $cats[$i]['sPersonList'] = hs_unserialize($cat['sPersonList'], []);
        $cats[$i]['sPersonList'] = is_array($cats[$i]['sPersonList'])
            ? $cats[$i]['sPersonList']
            : [];

        if (in_array($xperson, $cats[$i]['sPersonList'])) {
            foreach ($cats[$i]['sPersonList'] as $k=>$id) {
                if ($id != $xperson && ! in_array($id, $ok)) {
                    $ok[] = $id;
                }
            }
        }
    }

    return $ok;
}

/******************************************
 * GET ALL REP TAGS FOR A CATEGORY
 *****************************************
 * @param $catid
 * @return array
 */
function apiGetReportingTags($catid)
{
    $out = [];
    $etrs = $GLOBALS['DB']->Execute('SELECT * FROM HS_Category_ReportingTags WHERE xCategory = ? ORDER BY iOrder ASC', [$catid]);
    if (hs_rscheck($etrs)) {
        while ($t = $etrs->FetchRow()) {
            $out[$t['xReportingTag']] = $t['sReportingTag'];
        }
    }

    return $out;
}

/******************************************
 * GET ALL USERS NOT DELETED
 *****************************************
 * @param int $active
 * @param string $sortby
 * @return mixed
 */
function apiGetAllUsers($active = 0, $sortby = '')
{
    $cachekey = $active ? \HS\Cache\Manager::CACHE_USERS_ACTIVE_KEY : \HS\Cache\Manager::CACHE_USERS_INACTIVE_KEY;

    return \Illuminate\Support\Facades\Cache::remember($cachekey, \HS\Cache\Manager::CACHE_USERS_MINUTES, function () use ($active, $sortby) {
        $sortby = trim($sortby) != '' ? $sortby.',' : '';
        $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);
        $users = $GLOBALS['DB']->Execute('SELECT *, '.dbConcat(' ', 'sFname', 'sLname').' AS fullname
									   FROM HS_Person, HS_Permission_Groups
									   WHERE HS_Person.fUserType = HS_Permission_Groups.xGroup AND fDeleted = ? ORDER BY '.$sortby.' sFname, sLname', [$active]);

        return $users;
    });
}

/******************************************
 * GET ALL USERS ANY STATUS
 *****************************************
 * @return mixed
 */
function apiGetAllUsersComplete()
{
    return \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_ALLUSERS_KEY, \HS\Cache\Manager::CACHE_ALLUSERS_MINUTES, function () {
        $temp = [];
        $users = $GLOBALS['DB']->Execute('SELECT *, '.dbConcat(' ', 'sFname', 'sLname').' AS fullname FROM HS_Person ORDER BY sFname, sLname');
        while ($r = $users->FetchRow()) {
            $temp[$r['xPerson']] = $r;
        }

        return $temp;
    });
}

/******************************************
GET XPERSON BY EMAIL
******************************************/
function userIdByEmail($email)
{
    $id = $GLOBALS['DB']->GetRow('SELECT xPerson FROM HS_Person WHERE sEmail = ? and fDeleted = 0', [$email]);
    return (empty($id)) ? 0 : $id['xPerson'];
}

/******************************************
 * GET SMS SYSTEMS LIST
 ****************************************
 */
function apiGetSMSSystems()
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_luSMS ORDER BY fTop DESC, sName');
}

/******************************************
 * GET SMS DETAILS
 *****************************************
 * @param $smsid
 */
function apiGetSMS($smsid)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_luSMS WHERE xSMSService = ?', [$smsid]);
}

/******************************************
GET STATUS NAME
******************************************/
function apiGetStatusName($statusid)
{
    $name = $GLOBALS['DB']->GetOne('SELECT sStatus FROM HS_luStatus WHERE xStatus = ?', [$statusid]);
    return ($name) ? $name : '';
}

/******************************************
 * GET STATUS LIST
 *****************************************
 * @return array
 */
function apiGetStatus()
{
    $st = \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_STATUS_KEY, \HS\Cache\Manager::CACHE_STATUS_MINUTES, function () {
        return $GLOBALS['DB']->Execute('SELECT * FROM HS_luStatus ORDER BY fOrder ASC, sStatus ASC');
    });

    $temp = [];
    while($r = $st->FetchRow()){
        $temp[$r['xStatus']] = $r['sStatus'];
    }

    //remove spam as an option if no perm for it
    if (! perm('fCanManageSpam')) {
        unset($temp[2]);
    }

    return $temp;
}

/******************************************
 * GET ACTIVE STATUS LIST
 *****************************************
 * @return array
 */
function apiGetActiveStatus()
{
    $st = \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_ACTIVESTATUS_KEY, \HS\Cache\Manager::CACHE_ACTIVESTATUS_MINUTES, function () {
        return $GLOBALS['DB']->Execute('SELECT * FROM HS_luStatus WHERE fDeleted=0 ORDER BY fOrder ASC, sStatus ASC');
    });

    $temp = [];
    while ($r = $st->FetchRow()) {
        $temp[$r['xStatus']] = $r['sStatus'];
    }

    //remove spam as an option if no perm for it
    if (! perm('fCanManageSpam')) {
        unset($temp[2]);
    }

    return $temp;
}

/******************************************
RETURN STATUS AS RECORD SET
******************************************/
function apiGetAllStatus()
{
    return \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_ALLSTATUS_KEY, \HS\Cache\Manager::CACHE_ALLSTATUS_MINUTES, function () {
        return $GLOBALS['DB']->Execute('SELECT * FROM HS_luStatus ORDER BY fOrder ASC, sStatus ASC');
    });
}

/******************************************
 * GET ALL STATUS TYPES
 *****************************************
 * @param $showdeleted
 * @param $sortby
 */
function apiGetStatusByDel($showdeleted, $sortby)
{
    $sortby = trim($sortby) != '' ? $sortby : 'sStatus ASC';
    $sortby = (new HS\Http\Security)->parseAndCleanOrder($sortby);

    return $GLOBALS['DB']->Execute('SELECT *
                                      FROM HS_luStatus
                                      WHERE HS_luStatus.fDeleted = ? AND xStatus <> '.hs_setting('cHD_STATUS_ACTIVE', 1).' AND xStatus <> '.hs_setting('cHD_STATUS_SPAM', 2).'
                                      ORDER BY '.$sortby, [$showdeleted]);
}

/******************************************
RETURN RS OF ACTIVE REQUEST SUBSCRIBERS
******************************************/
function apiGetActiveRequestSubscribers($reqid)
{
    $reqid = is_numeric($reqid) ? $reqid : 0;

    return $GLOBALS['DB']->Execute('SELECT HS_Subscriptions.xPerson
                                           FROM HS_Subscriptions,HS_Person
                                           WHERE HS_Subscriptions.xRequest = ? AND HS_Subscriptions.xPerson = HS_Person.xPerson
                                                 AND HS_Person.fDeleted = 0', [$reqid]);
}

/******************************************
RETURN THE CUSTOM FIELD ARRAY
******************************************/
function apiGetCustomFields(){

    $res = $GLOBALS['DB']->Execute( 'SELECT * FROM HS_CustomFields ORDER BY iOrder ASC, fieldName ASC' );

    if($res && is_object($res)){
        $out = array();

        while($r = $res->FetchRow()){
            $out[$r['xCustomField']] = $r;
            $out[$r['xCustomField']]['fieldID'] = $r['xCustomField'];
        }

        return $out;
    }else{
        errorLog($GLOBALS['DB']->ErrorMsg(),'Database');
        // If this query fails it can cause automation rules to return random results because it removes the custom fields
        // from the WHERE. So we just want to exit to prevent any damage.
        exit();
    }

}

/******************************************
 * DELETE RESOURCE - sets fDeleted flag
 *****************************************
 * @param $table
 * @param $key
 * @param $resourceid
 * @param $action
 * @return false
 */
function apiDeleteResource($table, $key, $resourceid, $action)
{
    if ($action == 'delete') {
        $a = 1;
    }
    if ($action == 'undelete') {
        $a = 0;
    }

    if (! empty($table) && ! empty($key)) {
        // $table & $key are not cleaned beacuse the clean function will put in quotes and there shouldn't be any
        return $GLOBALS['DB']->Execute('UPDATE '.$table.' SET fDeleted=? WHERE '.$key.' = ?', [$a, $resourceid]);
    } else {
        return false;
    }
}

/******************************************
 * DO A SEARCH ON KNOWLEDGE BOOKS
 *****************************************
 * @param $args
 * @param $advanced
 * @return array
 */
function apiKbSearch($args, $advanced)
{
    global $user;

    // protect search results when in limited access mode
    $check_category = '';
    if (perm('fLimitedToAssignedCats')) {
        $check_category = ' AND '. apiGetUserAssignedCatsWhere($user);
    }

    $kb = [];
    $kbsql = '';
    $bindv = [];

    $args['q'] = hs_html_entity_decode($args['q']);

    //Build SQL for each type
    if (config('database.default') == 'mysql') {
        $boolmode = ($advanced) ? 'IN BOOLEAN MODE' : '';

        $kbsql = 'SELECT HS_KB_Pages.xPage,HS_KB_Pages.tPage,HS_KB_Books.xBook,sPageName,sBookName,
					MATCH (sPageName, tPage) AGAINST ( ? '.$boolmode.') AS score
				FROM HS_KB_Pages,HS_KB_Chapters,HS_KB_Books
				WHERE HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook
					AND MATCH (sPageName, tPage) AGAINST ( ? '.$boolmode.') %s
				ORDER BY score DESC';

        $bindv = [$args['q'], $args['q']];
    } elseif (config('database.default') == 'sqlsrv') {
        $out = [];

        if (utf8_strpos(utf8_trim($args['q']), ' OR ') === false && utf8_strpos(utf8_trim($args['q']), "'") !== 0 && utf8_strpos(utf8_trim($args['q']), '"') !== 0) { // ALLOW BINARY SEARCHING
            $q = explode(' ', $args['q']);
            foreach ($q as $word) {
                $out[] = utf8_trim($word);
            }
            $args['q'] = hs_html_entity_decode(implode(' & ', $out));
        }

        $kbsql = 'SELECT HS_KB_Pages.xPage,HS_KB_Pages.tPage,HS_KB_Books.xBook,sPageName,sBookName,
							FTS.Rank as score
					FROM HS_KB_Chapters,HS_KB_Books,HS_KB_Pages
						JOIN CONTAINSTABLE (HS_KB_Pages, (sPageName, tPage), ?, '.hs_setting('cHD_MAXSEARCHRESULTS').') FTS ON HS_KB_Pages.xPage = FTS.[KEY]
					WHERE HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND HS_KB_Chapters.xBook = HS_KB_Books.xBook  %s
					ORDER BY score DESC';

        $bindv = [$args['q']];
    }

    //Change id var name for GET
    if (IN_PORTAL) {
        $kbvar = 'id';
    } else {
        $kbvar = 'page';
    }

    //Set max values to return
    $maxres = $args['area'] == 'all' ? round((hs_setting('cHD_MAXSEARCHRESULTS') / 3), 0) : hs_setting('cHD_MAXSEARCHRESULTS');

    //Search for KB
    if ($args['area'] == 'kb' || $args['area'] == 'all') {
        $ispriv = IN_PORTAL ? ' AND HS_KB_Pages.fHidden=0 AND HS_KB_Chapters.fHidden=0 AND HS_KB_Books.fPrivate = 0' : '';

        $kbrs = $GLOBALS['DB']->SelectLimit(sprintf($kbsql, $ispriv), $maxres, 0, $bindv);

        $i = 0;
        while ($f = $kbrs->FetchRow()) {
            $kb[$i]['title'] = $f['sBookName'].' ~ '.$f['sPageName'];
            $kb[$i]['link'] = '?pg=kb.page&'.$kbvar.'='.$f['xPage'];
            $kb[$i]['desc'] = utf8_substr(strip_tags(html_entity_decode($f['tPage'], ENT_COMPAT, 'UTF-8')), 0, 120);
            $kb[$i]['icon'] = '';
            $kb[$i]['score'] = round($f['score'], 2);
            $kb[$i]['date'] = '';

            //Used in API
            $kb[$i]['sBookName'] = $f['sBookName'];
            $kb[$i]['sPageName'] = $f['sPageName'];
            $kb[$i]['xPage'] = $f['xPage'];
            $kb[$i]['xBook'] = $f['xBook']; //used by multi-portal
            $i++;
        }
    }

    return $kb;
    //Combine results and sort
}

function sortDataSearch(&$a, $k)
{
    //$a; # the multi dimensional array
    $arr_elmnt_nmbr = $k; // the element we want to base the sort on

    for ($i = 0; $i < count($a) - 1; $i++) {
        for ($j = 0; $j < count($a) - 1 - $i; $j++) {
            if ($a[$j + 1][$arr_elmnt_nmbr] < $a[$j][$arr_elmnt_nmbr]) {
                $tmp = $a[$j];
                $a[$j] = $a[$j + 1];
                $a[$j + 1] = $tmp;
            }
        }
    }
}

/******************************************
 * GET FIRST EVER REQUEST
 ****************************************
 */
function apiFirstRequestDate()
{
    return $GLOBALS['DB']->GetOne('SELECT MIN(dtGMTOpened) FROM HS_Request ');
}

/******************************************
 * GET MAIL RULES
 *****************************************
 * @param $showdeleted
 */
function apiGetMailRules($showdeleted)
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Mail_Rules WHERE fDeleted = ? ORDER BY fOrder ASC, sRuleName', [$showdeleted]);
}

/******************************************
 * GET AUTOMATION RULES
 *****************************************
 * @param $showdeleted
 */
function apiGetAutoRules($showdeleted)
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Automation_Rules WHERE fDeleted = ? ORDER BY fOrder ASC, sRuleName', [$showdeleted]);
}


/**
 * Get the total number of times an auto rule has ran.
 *
 * @param $xRequest
 * @param $xAutoRule
 * @return int
 */
function autoRuleTotalRuns($xRequest, $xAutoRule)
{
    $res = $GLOBALS['DB']->GetOne('SELECT iRunCount FROM HS_Automation_Runs WHERE xRequest = ? AND xAutomationId = ?', [$xRequest, $xAutoRule]);

    if (!$res) {
        // We don't have anything in the table yet, so insert it.
        $GLOBALS['DB']->Execute('INSERT INTO HS_Automation_Runs (iRunCount, xRequest, xAutomationId) VALUES (0, ?, ?)', [$xRequest, $xAutoRule]);
        return 0;
    }

    return $res;
}

/**
 * Increment the number of times the auto rule has ran
 * @param $xRequest
 * @param $xAutoRule
 * @return void
 */
function incrementRuleRuns($xRequest, $xAutoRule)
{
    return $GLOBALS['DB']->Execute('UPDATE HS_Automation_Runs SET iRunCount = iRunCount + 1 WHERE xRequest = ? AND xAutomationId = ?', [$xRequest, $xAutoRule]);
}

/******************************************
 * GET TRIGGERS
 *****************************************
 * @param $showdeleted
 */
function apiGetTriggers($showdeleted)
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Triggers WHERE fDeleted = ? ORDER BY fOrder ASC, sTriggerName', [$showdeleted]);
}

/******************************************
GET TRIGGERS BY TYPE
******************************************/
function apiGetTriggersByType($type)
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Triggers WHERE fDeleted = 0 AND fType = ? ORDER BY fOrder ASC, sTriggerName', [$type]);
}

/******************************************
RUN TRIGGERS
******************************************/
function apiRunTriggers($xrequest, $request, $old_request, $note_content, $note_type, $person, $type, $f, $l)
{
    $triggers = apiGetTriggersByType($type);
    if (hs_rscheck($triggers)) {
        //If triggers aren't loaded, load them
        if (! class_exists('hs_trigger')) {
            include_once cBASEPATH.'/helpspot/lib/class.triggers.php';
        }

        while ($r = $triggers->FetchRow()) {
            $trigger = hs_unserialize($r['tTriggerDef']);

            //Added needed meta info
            $request['acting_person'] = $person;
            $request['note_type'] = $note_type;
            $request['note_content'] = $note_content;
            $request['xRequest'] = $xrequest;

            if ($trigger instanceof hs_trigger) {
                $request = $trigger->compareRequestsAndTrigger($request, $old_request);
            } else {
                errorLog('Invalid Trigger: '.$r['sTriggerName'], 'Trigger', $f, $l);
            }
        }
    }
}

/******************************************
DO SPAM CHECKS FOR PORTAL REQUEST PAGE
-Works oddly in that it passes results back into global $_POST array for use by addpost functions
******************************************/
function apiPortalReqSPAMCheck()
{
    $text = [];
    if (hs_setting('cHD_PORTAL_FORMFORMAT') == 1) {
        $text['body'] = $_POST['did'];
        $text['body'] .= "\n".$_POST['expected'];
        $text['body'] .= "\n".$_POST['actual'];
        if (! empty($_POST['additional'])) {
            $text['body'] .= "\n".$_POST['additional'];
        }
    } elseif (hs_setting('cHD_PORTAL_FORMFORMAT') == 0) {
        $text['body'] = $_POST['simple'];
    }

    $text['headers'] = $_POST['fullname'].' '.$_POST['sEmail'].' '.hs_clientIP();

    $spamcheck = new UserScape_Bayesian_Classifier($text, 'request');
    $spamcat = $spamcheck->Check();

    if (apiPortalPostAltSPAMCheck($text['body']) || $spamcat == '-1') {
        //If using auto delete then if probability above defined amount ignore the message, simply redirect
        if (hs_setting('cHD_PORTAL_SPAM_AUTODELETE') && round($spamcheck->probability * 100) >= hs_setting('cHD_PORTAL_SPAM_AUTODELETE')) {
            hs_redirect('Location: '.cHOST.'/index.php?pg=moderated&ad=true');
        }

        //Actual spam not trained until deleted in spam queue
        $_POST['xStatus'] = hs_setting('cHD_STATUS_SPAM', 2);
        $_POST['xPersonAssignedTo'] = 0;	//no assignee
    }
}

/******************************************
DO NON-BAYESIAN SPAM CHECKS
******************************************/
function apiPortalPostAltSPAMCheck($text)
{

    //Check X links
    if (preg_match_all('/http:\/\/?[^ ]+/i', nl2br($text), $matches) >= hs_setting('cHD_PORTAL_SPAM_LINK_CT')) {
        return true;
    }

    //Check timestamp
    if (hs_setting('cHD_PORTAL_SPAM_FORMVALID_ENABLED')) { //Only perform check if enabled
        if (isset($_POST['hs_fv_timestamp']) && isset($_POST['hs_fv_ip']) && isset($_POST['hs_fv_hash'])
            && ! empty($_POST['hs_fv_timestamp']) && ! empty($_POST['hs_fv_ip']) && ! empty($_POST['hs_fv_hash'])) {
            //This checks that timestamp and IP were not altered
            if ($_POST['hs_fv_hash'] == md5($_POST['hs_fv_ip'].$_POST['hs_fv_timestamp'].'R5 4239a aASf fasd')) {
                //Check time to see if still good
                if ($_POST['hs_fv_timestamp'] < (time() - hs_setting('cHD_PORTAL_SPAM_FORMVALID'))) {
                    return true; //form is too old
                }
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    return false;
}

/******************************************
GENERATE A RANDOM SET OF CHARACTERS FOR A PASSWORD
******************************************/
function randomPasswordString($length = 6, $strong = false)
{
    $len = $length;
    $s = '';
    $i = 0;
    $ascii_ranges = [0=>['45', '57'], 1=>['63', '93'], 2=>['97', '122'], 3 =>['65', '90']];
    do {
        if ($strong) {
            $range = $ascii_ranges[rand(0, 2)];
            $s .= chr(mt_rand($range[0], $range[1]));
        } else {
            $range = $ascii_ranges[rand(2, 3)];
            $s .= chr(mt_rand($range[0], $range[1]));
        }
        $i++;
    } while ($i < $len);

    return $s;
}

/******************************************
ADD PORTAL LOGIN FOR EMAIL IF NON EXISTS
******************************************/
function apiPortalAddLoginIfNew($email, $password = false)
{
    //Ignore empty or incorrect emails
    if (! hs_empty($email)) {
        //Check to see if login already exists, if not add it
        //$login_check = $GLOBALS['DB']->GetOne( 'SELECT xLogin FROM HS_Portal_Login WHERE sEmail = ?', array(utf8_strtolower(utf8_trim($email))) );

        $login_check = \DB::table('HS_Portal_Login')->select('xLogin')->where('sEmail', utf8_strtolower(utf8_trim($email)))->first();

        if (! $login_check) {
            //Set a password if one not provided
            if (! $password || $password == '') {
                $hasher = new PasswordHash(4, false);
                $password = randomPasswordString(8, true);
            }

            \DB::table('HS_Portal_Login')->insert([
                'sEmail' => utf8_strtolower(utf8_trim($email)),
                'sPasswordHash' => $hasher->HashPassword($password),
            ]);

            return $password;
        } else {
            return ''; //We no longer return a password for existing users as we don't know it
        }
    } else {
        return ''; //Return empty string for empty email sent in
    }
}

/******************************************
CHANGE A PORTAL LOGIN PASSWORD
******************************************/
function apiPortalPasswordUpdate($email, $password)
{
    $hasher = new PasswordHash(4, false);

    return $GLOBALS['DB']->Execute('UPDATE HS_Portal_Login SET sPasswordHash = ? WHERE sEmail = ?', [$hasher->HashPassword($password), $email]);
}

/******************************************
 * GET AN ACTIVE PORTAL
 *****************************************
 * @param $xportal
 */
function apiGetPortal($xportal)
{
    return $GLOBALS['DB']->GetRow('SELECT * FROM HS_Multi_Portal WHERE xPortal = ?', [$xportal]);
}

/******************************************
 * GET ALL PORTALS
 *****************************************
 * @param $active
 * @return mixed
 */
function apiGetAllPortals($active)
{
    $cachekey = $active ? \HS\Cache\Manager::CACHE_ALLPORTALS_ACTIVE_KEY : \HS\Cache\Manager::CACHE_ALLPORTALS_DELETED_KEY;

    return \Illuminate\Support\Facades\Cache::remember($cachekey, \HS\Cache\Manager::CACHE_ALLPORTALS_MINUTES, function () use ($active) {
        return $GLOBALS['DB']->Execute('SELECT * FROM HS_Multi_Portal WHERE fDeleted = ? ORDER BY sPortalName', [$active]);
    });
}

/******************************************
 * GET ALL PORTALS ANY STATUS
 ****************************************
 */
function apiGetAllPortalsComplete()
{
    return $GLOBALS['DB']->Execute('SELECT * FROM HS_Multi_Portal ORDER BY sPortalName');
}

/******************************************
RETURN A PORTAL NAME
******************************************/
function apiGetPortalName($id, $openedvia)
{
    static $portals = [];

    if ($openedvia != 7 && $openedvia != 6) {
        return '';
    } //if not opened via the portal or web server then just exit

    //If opened via WS then make sure it was passed in a portal or else exit
    if ($openedvia == 6 && $id < 1) {
        return '';
    }

    if (empty($portals)) {
        $query = apiGetAllPortalsComplete();
        $portals = rsToArray($query, 'xPortal', false);
    }

    return isset($portals[$id]) ? $portals[$id]['sPortalName'] : lg_primaryportal;
}

/******************************************
APPEND PREFIX TO SUBJECT
******************************************/
function apiSetSubjectPrefix($prefix, $title)
{
    if (! hs_empty($prefix)) {
        return $prefix.': '.$title;
    } else {
        return $title;
    }
}

/******************************************
ADD A KNOWLEDGE TAG
******************************************/
function apiAddTags($tags = [], $xpage = 0, $xtopicid = 0)
{
    $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

    //Clear tags from this page/topic
    if ($xpage > 0) {
        $GLOBALS['DB']->Execute('DELETE FROM HS_Tags_Map WHERE xPage = ?', [$xpage]);
    }
    if ($xtopicid > 0) {
        $GLOBALS['DB']->Execute('DELETE FROM HS_Tags_Map WHERE xTopicId = ?', [$xtopicid]);
    }

    //Loop over all tags, add if new and then map to page/topic
    if (! empty($tags)) {
        foreach ($tags as $k=>$tag) {
            //Search
            $xtag = $GLOBALS['DB']->GetOne('SELECT xTag FROM HS_Tags WHERE sTag = ?', [$tag]);

            //If not found add it
            if (! $xtag) {
                $new = $GLOBALS['DB']->Execute('INSERT INTO HS_Tags(sTag) VALUES (?)', [$tag]);
                $xtag = dbLastInsertID('HS_Tags', 'xTag');
            }

            //Insert new relation
            $GLOBALS['DB']->Execute('INSERT INTO HS_Tags_Map(xTag,xPage,xTopicId) VALUES (?,?,?)', [$xtag, $xpage, $xtopicid]);
        }
    }

    $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
}

/******************************************
GET KNOWLEDGE TAGS FOR A PAGE OR TOPIC
******************************************/
function apiGetTags($xpage = 0, $xtopicid = 0)
{
    if ($xpage > 0) {
        return $GLOBALS['DB']->GetCol('SELECT sTag FROM HS_Tags, HS_Tags_Map WHERE HS_Tags.xTag = HS_Tags_Map.xTag AND HS_Tags_Map.xPage = ?', [$xpage]);
    } else {
        return $GLOBALS['DB']->GetCol('SELECT sTag FROM HS_Tags, HS_Tags_Map WHERE HS_Tags.xTag = HS_Tags_Map.xTag AND HS_Tags_Map.xTopicId = ?', [$xtopicid]);
    }
}

/******************************************
GET ALL TAGS
******************************************/
function apiGetAllTags()
{
    return $GLOBALS['DB']->Execute('SELECT xTag,sTag FROM HS_Tags ORDER BY sTag ASC');
}

/******************************************
GET ALL TAGS W/POPULARITY
******************************************/
function apiGetTagsWPopularity($search = false)
{
    $rs = [];

    if (IN_PORTAL) {
        $mpkb = '';
        if (isset($GLOBALS['hs_multiportal'])) {
            if (! empty($GLOBALS['hs_multiportal']->kbs)) {
                $mpkb = ' AND HS_KB_Books.xBook IN ('.implode(',', $GLOBALS['hs_multiportal']->kbs).')';
            } else {
                $mpkb = ' AND 1=0 ';
            }
        }

        //Limit to certain words
        if ($search) {
            $words_clean = [];
            foreach ($search as $k=>$w) {
                $words_clean[] = qstr(utf8_trim($w));
            }
            $wordsearch = ' AND HS_Tags.sTag IN ('.implode(',', $words_clean).')';
        } else {
            $wordsearch = '';
        }

        $tags = $GLOBALS['DB']->Execute('SELECT HS_Tags.sTag, HS_Tags_Map.xTag, COUNT(HS_Tags_Map.xTag) AS ct
										FROM HS_Tags, HS_Tags_Map, HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
										WHERE HS_Tags.xTag = HS_Tags_Map.xTag AND
												HS_Tags_Map.xPage = HS_KB_Pages.xPage AND
												HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
												HS_KB_Chapters.xBook = HS_KB_Books.xBook AND
												HS_KB_Books.fPrivate = 0 '.$mpkb.' '.$wordsearch.'
										GROUP BY HS_Tags_Map.xTag, HS_Tags.sTag
										ORDER BY sTag ASC');
    } else {
        $tags = $GLOBALS['DB']->Execute('SELECT HS_Tags.sTag, HS_Tags_Map.xTag, COUNT(HS_Tags_Map.xTag) AS ct
										FROM HS_Tags, HS_Tags_Map
										WHERE HS_Tags.xTag = HS_Tags_Map.xTag
										GROUP BY HS_Tags_Map.xTag, HS_Tags.sTag
										ORDER BY sTag ASC');
    }

    if (hs_rscheck($tags)) {
        $i = 0;
        while ($r = $tags->FetchRow()) {
            if ($i == 0) {
                $max = $r['ct'];
                $min = $r['ct'];
            }

            if ($max < $r['ct']) {
                $max = $r['ct'];
            }
            if ($min > $r['ct']) {
                $min = $r['ct'];
            }

            $i++;
        }

        $tags->Move(0);

        $rs = rsToArray($tags, 'xTag');

        foreach ($rs as $k=>$v) {
            if ($max - $min == 0) { // if it's zero then set to 100 to prevent division by zero error.
                $rs[$k]['font-size'] = 100;
            } else {
                $rs[$k]['font-size'] = 100 + (($max - ($max - ($v['ct'] - $min))) * (197 - 100) / ($max - $min));
            }
        }
    }

    return $rs;
}

/******************************************
GET AUTOCOMPLETE TAG SEARCH
******************************************/
function apiTagAutocompleteSearch($search)
{
    if (! hs_empty($search)) {
        return $GLOBALS['DB']->GetCol('SELECT sTag FROM HS_Tags WHERE sTag '.dbLike().' ? ORDER BY sTag ASC', [$search.'%']);
    }
}

/******************************************
GET TAG BY ID
******************************************/
function apiGetTagById($id)
{
    return $GLOBALS['DB']->GetOne('SELECT sTag FROM HS_Tags WHERE xTag = ?', [$id]);
}

/******************************************
SEARCH FOR TAGS IN KB
******************************************/
function apiTagSearchPages($tags)
{
    if (! function_exists('apiGetBookByPage')) {
        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
    }

    $bindv = [];
    $string = [];
    foreach ($tags as $k=>$tag) {
        $string[] = ' HS_Tags.sTag = ? ';
        $bindv[] = $tag;
    }

    $tag_search = (! empty($string) ? '('.implode(' OR ', $string).')' : '1=0');

    $tags = $GLOBALS['DB']->GetArray('SELECT DISTINCT HS_KB_Pages.xPage, HS_KB_Pages.sPageName, COUNT(*) as ct
								    FROM HS_KB_Pages,HS_Tags_Map,HS_Tags
								    WHERE '.$tag_search.' AND
								    		HS_Tags.xTag = HS_Tags_Map.xTag AND
								    		HS_Tags_Map.xPage = HS_KB_Pages.xPage
								    GROUP BY HS_KB_Pages.xPage, HS_KB_Pages.sPageName
								    HAVING COUNT(*) = '.count($tags), $bindv);

    foreach ($tags as $k=>$tag) {
        $book = apiGetBookByPage($tag['xPage']);
        $tags[$k]['sBookName'] = $book['sBookName'];
    }

    $tagrs = new array2recordset;
    $tagrs->init($tags);

    return $tagrs;
}

/******************************************
SEARCH FOR TAGS IN PUBLIC KB
******************************************/
function apiTagSearchPortalPages($xtag)
{
    if (! function_exists('apiGetBookByPage')) {
        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
    }

    $mpkb = '';
    if (isset($GLOBALS['hs_multiportal'])) {
        if (! empty($GLOBALS['hs_multiportal']->kbs)) {
            $mpkb = ' AND HS_KB_Books.xBook IN ('.implode(',', $GLOBALS['hs_multiportal']->kbs).')';
        } else {
            $mpkb = ' AND 1=0 ';
        }
    }

    $tags = $GLOBALS['DB']->GetArray('SELECT HS_KB_Pages.xPage, HS_KB_Pages.sPageName
								    FROM HS_Tags_Map, HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
								    WHERE HS_Tags_Map.xTag = ? AND
								    	  HS_Tags_Map.xPage = HS_KB_Pages.xPage AND
										  HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
										  HS_KB_Chapters.xBook = HS_KB_Books.xBook AND
										  HS_KB_Books.fPrivate = 0 AND
										  HS_KB_Pages.fHidden = 0 '.$mpkb.'
								    ORDER BY HS_KB_Pages.sPageName ASC', [$xtag]);

    foreach ($tags as $k=>$tag) {
        $book = apiGetBookByPage($tag['xPage']);
        $tags[$k]['sBookName'] = $book['sBookName'];
    }

    return $tags;
}

/******************************************
DELETE KNOWLEDGE TAGS
******************************************/
function apiDeleteTags($xpage = 0, $xtopicid = 0)
{
    if ($xpage > 0) {
        \DB::statement('DELETE FROM HS_Tags_Map WHERE xPage = ?', [$xpage]);
    } else {
        \DB::statement('DELETE FROM HS_Tags_Map WHERE xTopicId = ?', [$xtopicid]);
    }

    //Clean out any orphans
    \DB::statement('DELETE FROM HS_Tags WHERE xTag NOT IN (SELECT xTag FROM HS_Tags_Map)');
}

/******************************************
ASSIGNMENT CHAIN
******************************************/
function logAssignmentChange($reqid, $assignto, $msg = '', $prev_person = 0, $changedby = 0)
{
    $GLOBALS['DB']->Execute('INSERT INTO HS_Assignment_Chain(xRequest,xPerson,xPreviousPerson,xChangedByPerson,dtChange,sLogItem) VALUES (?,?,?,?,?,?)',
                            [$reqid,
                                  $assignto,
                                  $prev_person,
                                  $changedby,
                                  time(),
                                  utf8_trim($msg), ]);
}
