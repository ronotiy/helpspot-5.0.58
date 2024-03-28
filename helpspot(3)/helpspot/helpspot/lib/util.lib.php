<?php

// SECURITY: Don't allow direct calls

if (! defined('cBASEPATH')) {
    die();
}

use HS\Domain\Workspace\Document;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/*****************************************
dbclean - make sure no bad stuff goes
to database.
*****************************************/
function dbStringClean($thevar)
{
    if (is_numeric($thevar) && empty($thevar)) {
        $thevar = 0;
    } else {
        if (empty($thevar)) {
            $thevar = "''";
        } else {
            $thevar = qstr($thevar);
        }
    }

    return $thevar;
}

//Helper function to build prefix length indexes for My but not MS + PG
function indexLen($name, $len)
{
    if (config('database.default') == 'mysql') {
        return $name.'('.$len.')';
    } else {
        return $name;
    }
}

/*****************************************
IF MAGIC QUOTES GPC IS ENABLED THEN CLEAN IT
-gpc is not configurable at runtime. This cleans
-the nasty slashes out of the get,post,req vars
*****************************************/
function clean_data()
{
    //Prevent XSS in GET/REQUEST data. Also filtering request as we use that sometimes to preset form fields
    if (is_array($_GET) && ! empty($_GET)) {
        foreach ($_GET as $k=>$v) {
            if (is_array($_GET[$k])) {
                foreach ($_GET[$k] as $subk=>$subv) {
                    $_GET[$k][$subk] = filter_var($_GET[$k][$subk], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                }
            } else {
                $_GET[$k] = filter_var($_GET[$k], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            }
            $_REQUEST[$k] = filter_var($_REQUEST[$k], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
    }
}

/*****************************************
CLEAN A FILENAME WHICH WILL BE USED TO INCLUDE A FILE
*****************************************/
function clean_filename($name)
{
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    // Keep the file from traversing and keep it from finding files that start with period.
    if (utf8_strpos($name, '../') !== false or utf8_strpos($name, '.') === 0) {
        return false;
    }

    return $name;
}

/*****************************************
ESTABLISH DB CONNECTION
*****************************************/
function hsInitDB()
{
    $GLOBALS['DB'] = new \HS\Database\AdodbLaravelConnector();

    if (config('database.default') == 'sqlsrv') {
        //Set to match how old PHP MSSQL driver handled this. Eventually we may want to research this further to see if we can remove this.
        $GLOBALS['DB']->Execute('SET ANSI_WARNINGS OFF');
    }

    // todo: Do we need to set mysql sql_mode to be less strict?

    return true;
}

/*****************************************
GET & SET SETTINGS
*****************************************/
function hsInitSettings()
{
    $rs = \Illuminate\Support\Facades\Cache::remember(\HS\Cache\Manager::CACHE_SETTINGS_KEY, \HS\Cache\Manager::CACHE_SETTINGS_MINUTES, function () {
        return \DB::table('HS_Settings')->get();
    });

    foreach ($rs as $setting) {
        if (! defined($setting->sSetting)) {
            define($setting->sSetting, $setting->tValue);
        }
    }

    //If using TZ override then do so, else regular TZ vars
    if (function_exists('date_default_timezone_set') && ! empty(hs_setting('cHD_TIMEZONE_OVERRIDE'))) {
        @date_default_timezone_set(hs_setting('cHD_TIMEZONE_OVERRIDE'));
    } else {
        $tz = explode('.', hs_setting('cHD_TIMEZONE')); //Store tz with country so correct ones display. Only need first part for tz.
        putenv('TZ='.$tz[0]);
    }

    $GLOBALS['reqStatus'] = apiGetStatus();
    $GLOBALS['customFields'] = apiGetCustomFields();
}

/*****************************************
DELETE A SESSION
*****************************************/
function hs_delete_session($user)
{
    //$user will delete all their open sessions across browsers, etc.
    session_destroy();
}

/**
 * See if a string should be an IN BOOLEAN MODE search
 *
 * @var $search string
 * @return bool
 */
function isBooleanSearch($search)
{
    $words = explode(' ', $search);

    // The +, -, and spermie should only be present at the start of a word.
    $starting_operators = ['+', '-', '~'];
    foreach ($words as $word) {
        foreach ($starting_operators as $operator) {
            if (Illuminate\Support\Str::startsWith($word, $operator)) {
                return true;
            }
        }
    }

    // Check for any other special operators.
    $bool_operators = ['(', ')', '*', '"'];
    foreach ($bool_operators as $k=>$op) {
        if (utf8_strpos($search, $op) !== false) {
            return true;
        }
    }

    return false;
}

/******************************************
DB SPECIFIC FULL TEXT SEARCH
--Only returns code for full text matching
not entire query. Can only be used as part
of larger sql generator, like in filters.
******************************************/
function dbFullText($field, $query)
{
    $out = [];
    if (config('database.default') == 'mysql') {
        //Only use bool mode if trying a bool search
        $boolmode = isBooleanSearch($query) ? 'IN BOOLEAN MODE' : '';
        $out['sql'] = ' MATCH ('.$field.') AGAINST ( ? '.$boolmode.')';	//sql in the where
        $out['args'] = [$query];						//args
    } elseif (config('database.default') == 'sqlsrv') {
        $out['sql'] = ' CONTAINS('.$field.', ?)';
        $out['args'] = [str_replace(' AND ', ' & ', $query)];
    }

    return $out;
}

/******************************************
DB SPECIFIC LIKE - postgres is case sensitive and other aren't
******************************************/
function dbLike()
{
    return 'LIKE';
}

/******************************************
DB SPECIFIC STANDARD DEVIATION
******************************************/
function dbStd()
{
    return 'STD';
}

/******************************************
DB SPECIFIC NOT LIKE - postgres is case sensitive and other aren't
******************************************/
function dbNotLike()
{
    return 'NOT LIKE';
}

/******************************************
DB SPECIFIC CONCAT
******************************************/
function dbConcat()
{
    $s = '';
    $arr = func_get_args(); // first is spacer, then other args
    $space = $arr[0];
    array_shift($arr);

    if (config('database.default') == 'mysql') {
        $t = implode(',', $arr);
        $s = ' CONCAT_WS(\''.$space.'\','.$t.') ';
    } elseif (config('database.default') == 'sqlsrv') {
        $out = [];
        //Must cast for sql server
        foreach ($arr as $v) {
            $out[] = 'CAST( '.$v.' AS NVARCHAR(max))';
        }

        $s = implode(" + '".$space."' + ", $out);
    }

    return $s;
}

/******************************************
DB SPECIFIC SELECT LIMIT
******************************************/
function dbSelectLimit($sql, $from, $limit)
{
    if (config('database.default') == 'mysql') {
        $sql = $sql.' LIMIT '.$from.','.$limit.' ';
    } elseif (config('database.default') == 'sqlsrv') {
        $sql = preg_replace('/(^\s*select\s+(distinctrow|distinct)?)/i', '\\1 top '.$limit.' ', $sql);
    }

    return $sql;
}

/******************************************
DB SPECIFIC STRING LENGTH
******************************************/
function dbStrLen($col)
{
    if (config('database.default') == 'mysql') {
        $sql = ' LENGTH('.$col.') ';
    } elseif (config('database.default') == 'sqlsrv') {
        $sql = ' DATALENGTH('.$col.') ';
    }

    return $sql;
}

/******************************************
DB SPECIFIC INSERT ID
******************************************/
function dbLastInsertID($table, $field)
{
    return $GLOBALS['DB']->Insert_ID();
}

/********************************************
CURRENT DATABASE CAN PERFORM A BOOLEAN SEARCH
*********************************************/
function canBoolSearch()
{
    if (config('database.default') == 'mysql') {
        return false;	//for now don't allow bool searching. Doesn't return score, so can't sort
    } elseif (config('database.default') == 'sqlsrv') {
        return true;
    }
}

/******************************************
LOGOUT CALLBACK - Kills session
******************************************/
function logoutCallback($username)
{
    $user = apiGetUserByAuth($username, $username);
    hs_delete_session($user['xPerson']);
}

/******************************************
Make text safe for HTML form
******************************************/
function formClean($string)
{
    return hs_htmlspecialchars(trim(strip_tags($string)));
}

/**
 * Run hs_htmlspecialchars and trim but skip
 * the strip_tags for HTML allowed textareas.
 *
 * @param $string
 * @return string
 */
function formCleanHtml($string)
{
    return hs_htmlspecialchars(trim($string));
}

/******************************************
Return mime extension based on mime type
******************************************/
function hs_lookup_mime($type)
{
    switch ($type) {
        case 'image/bmp': return 'bmp';
        case 'image/gif': return 'gif';
        case 'image/jpeg': return 'jpg';
        case 'image/pjpeg': return 'jpg';
        case 'image/pict': return 'pic';
        case 'image/png': return 'png';
        case 'image/tiff': return 'tiff';
        case 'image/vnd.wap.wbmp': return 'wbmp';
        case 'image/x-icon': return 'ico';
        case 'video/mp4': return 'mp4';
        case 'video/mpeg': return 'mpg';
        case 'video/quicktime': return 'mov';
        case 'video/x-msvideo': return 'avi';
        case 'application/pdf': return 'pdf';
        case 'application/vnd.ms-powerpoint': return 'ppt';
        case 'application/vnd.ms-excel': return 'xls';
        case 'application/vnd.rn-realmedia': return 'rm';
        case 'application/x-shockwave-flash': return 'swf';
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': return 'docx';
        case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': return 'pptx';
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': return 'xlsx';
        case 'application/vnd.ms-excel.sheet.binary.macroenabled.12': return 'xlsb';
        case 'application/vnd.ms-excel.template.macroenabled.12': return 'xltm';
        case 'application/vnd.ms-excel.addin.macroenabled.12': return 'xlam';
        case 'application/vnd.ms-excel.sheet.macroenabled.12': return 'xlsm';
        case 'application/zip': return 'zip';
        case 'application/x-gzip': return 'gz';
        case 'application/x-tar': return 'tar';
        case 'audio/mpeg': return 'mp3';
        case 'audio/x-aiff': return 'aiff';
        case 'audio/x-pn-realaudio': return 'ra';
        case 'audio/x-wav': return 'wav';
        case 'audio/wav': return 'wav';
        case 'text/enriched': return 'txt';
        case 'text/plain': return 'txt';
        case 'text/html': return 'html';
        case 'message/rfc822': return 'eml'; //when class.imap.message hits one of these it saves it as a .eml
    }
}

/******************************************
Make text safe for HTML - used in array_walk
******************************************/
function CleanByRef(&$string)
{
    $string = hs_htmlspecialchars($string);
}

/******************************************
SET TO EMPTY ALL POST VARS EXCEPT ALLOWED
******************************************/
function cleanPostArray($allowed)
{
    foreach ($_POST as $k=>$v) {
        if (! in_array($k, $allowed)) {
            unset($_POST[$k]);
        }
    }
}

/******************************************
FIND MAX DEPTH OF MULTIDIMENSIONAL ARRAY
******************************************/
function find_max_array_depth($array, $path = [], $max = 0)
{
    if (is_array($array)) {
        $ct = count($path) + 1; //+1 for base level
        if ($ct > $max) {
            $max = $ct;
        }

        foreach ($array as $item=>$value) {
            if (is_array($value)) {
                $temp_path = $path;
                array_push($temp_path, $item);
                $max = find_max_array_depth($value, $temp_path, $max);
            }
        }
    }

    return $max;
}

/******************************************
REDIRECT AND CLOSE DB TO AVOID CGI ERRORS
******************************************/
function hs_redirect($location)
{
    hs_nocache_headers();

    header($location);          //Set redirect header
    exit();                     //Stop execution to avoid running any other code below this point
}

/******************************************
SET HEADERS TO PREVENT CACHING
******************************************/
function hs_nocache_headers()
{
    /* SET NO CACHE HEADERS TO AVOID REDIRECT CACHING IN IE */
    // 'Expires' in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // Always modified
    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

    // HTTP/1.1
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);

    // HTTP/1.0
    header('Pragma: no-cache');

    //Add P3P headers to avoid issues with IE
    header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
}

/******************************************
CACHE ITEM FOREVER
******************************************/
function hs_cache_forever()
{
    //Cache images
    header('Vary: Accept-Encoding');  // for proxies
    header('Expires: '.gmdate('D, d M Y H:i:s', time() + 315360000).' GMT');
    header('Cache-Control: max-age=315360000, public'); //we don't set private here as we're ok with proxies caching this as well since currently only used for static items like images
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', 1165793644));
}

/******************************************
USE THE RIGHT URL FOR STATIC CONTENT
Eventually could use this for cdn or cache server
******************************************/
function static_url()
{
    return defined('cHOST') ? cHOST : './';
    //Could set global var for this when hosting to push traffic to specific static server, remember ssl vs non-ssl
    //return (defined('cHD_USE_CDN') && cHD_USE_CDN == 1 && !defined('STATIC_DIRECT') ? 'http://cdn.helpspot.com' : cHOST);
}

/******************************************
HTML SPECIALCHARS W/CHARSETS
******************************************/
function hs_htmlspecialchars($string)
{
    return htmlspecialchars($string, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
}

/******************************************
HTML ENTITLES W/CHARSETS
******************************************/
function hs_htmlentities($string)
{
    // UTF8 doesn't require entities so just run through special chars
    return hs_htmlspecialchars($string);
}

/******************************************
HTML DECODE ENTITLES W/CHARSETS
******************************************/
function hs_html_entity_decode($string)
{
    return html_entity_decode($string, ENT_COMPAT, 'UTF-8');
}

/******************************************
STRIP TAGS FROM HTML
******************************************/
function hs_strip_tags($string, $force = false)
{
    if (hs_setting('cHD_STRIPHTML') == 1 or $force) {
        $string = strip_tags($string, hs_setting('cHD_HTMLALLOWED'));
        $string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');
    } else {
        //As far as I can see all places strip tags is used is also already escaped so nothing to do here

        //$string = hs_htmlspecialchars($string);
    }

    return $string;
}

/******************************************
 * CONVERT MARKDOWN TO HTML
 ******************************************
 * @param $string - The text to convert to markdown
 * @return string
 */
function hs_markdown($string)
{
    return (new Parsedown())->text($string);
}

/******************************************
 * CONVERT HTML TO MARKDOWN
 *****************************************
 * @param $string The html string to convert.
 * @param bool $width - Can't find this actually be used @deprecated
 * @return string
 */
function hs_html_2_markdown($string, $width = false)
{
    $markdown = new HTML_To_Markdown();
    $markdown->set_option('strip_tags', true);

    return with($markdown->convert($string), function($string) {
        # Capture <?php and <? style tags.
        # Will grab <?xml> tags as well (accepted trade off)
        $string = str_replace('<?', '<!--?', $string);
        return str_replace('?>', '?-->', $string);
    });
}

/******************************************
Turn a RS into an array key'd by provided field
******************************************/
function rsToArray(&$rs, $key, $cleanHTML = true)
{
    $out = [];
    if (is_object($rs) && $rs->RecordCount() > 0) {
        while ($row = $rs->FetchRow()) {
            //Clean all values in array
            if ($cleanHTML) {
                array_walk($row, 'CleanByRef');
            }

            $out[$row[$key]] = $row;
        }
    }

    return $out;
}

/******************************************
Return a column of a record set key'd by a specified field
******************************************/
function rsToColumn($rs, $key, $column, $function = false)
{
    $out = [];
    if (is_object($rs) && $rs->RecordCount() > 0) {
        while ($row = $rs->FetchRow()) {
            if ($function) {
                $out[$row[$key]] = call_user_func($function, $row[$column]);
            } else {
                $out[$row[$key]] = $row[$column];
            }
        }
    }

    return $out;
}

/******************************************
Set a value in an array if it doesn't exist
******************************************/
function hsArraySet(&$ar, $key)
{
    if (! isset($ar[$key])) {
        return $ar[$key] = '';
    } else {
        return $ar[$key];
    }
}

/******************************************
CLEAN AN ARRAY FOR HTML DISPLAY
******************************************/
function cleanArrayByRef(&$array)
{
    array_walk($array, 'CleanByRef');
}

/*****************************************
CHECK IF NUMERIC
*****************************************/
function hs_numeric(&$array, $name)
{
    return (isset($array[$name]) && is_numeric($array[$name])) ? true : false;
}

/*****************************************
CHECK IF EMPTY - built in one doesn't account for trims
*****************************************/
function hs_empty($value)
{
    if (is_string($value)) {
        return (trim($value) == '') ? true : false;
    } else {
        return empty($value); //if not string just default to built in
    }
}

/*****************************************
TEST IF TRULY EMPTY - Including empty WYSIWYG content
*****************************************/
function hs_isreallyempty($string)
{
    $string = strip_tags($string, '<img>');
    $string = preg_replace('/\s+/', '', $string);
    $string = preg_replace('~\x{00a0}~', '', $string);

    return empty($string);
}

/*****************************************
PHP < 5.1 returns -1 instead of false, this fixes that
*****************************************/
function hs_strtotime($d, $ts)
{
    $r = strtotime($d, $ts);

    if ($r != false && $r != -1) {
        return $r;
    } else {
        return false;
    }
}

/*****************************************
CHECK IF OBJECT AND NOT EMPTY RS
*****************************************/
function hs_rscheck(&$obj)
{
    if (is_object($obj) && $obj->RecordCount() > 0) {
        return true;
    } else {
        return false;
    }
}

/*****************************************
FORCE STRING TO CERTAIN LENGTH
*****************************************/
function hs_truncate($string, $length)
{
    return ! hs_empty($string) ? utf8_substr(trim($string), 0, $length) : '';
}

/*****************************************
CLEAN REQUEST HISTORY UP FOR API FUNCTIONS
*****************************************/
function hs_clean_req_history_for_API($reqid, &$req, $transform = false, $inlineImages = false)
{
    $allStaff = apiGetAllUsersComplete();
    $request = HS\Domain\Workspace\Request::with('history.documents')->find($reqid);

    $out = [];
    foreach ($request->history as $k => $history) {

        //Handle documents
        $historyDocs = [];
        if ($history->documents) {
            foreach ($history->documents as $document) {
                $doc = $document->toArray();
                $historyDocs[$doc['xDocumentId']]['sCID'] = $doc['sCID'];
                $historyDocs[$doc['xDocumentId']]['sFilename'] = $doc['sFilename'];
                $historyDocs[$doc['xDocumentId']]['xDocumentId'] = $doc['xDocumentId'];
                $historyDocs[$doc['xDocumentId']]['dtGMTChange'] = $doc['dtGMTChange'];
                $historyDocs[$doc['xDocumentId']]['sFileMimeType'] = $doc['sFileMimeType'];
            }
        }

        $row = $history->toArray();

        $id = $row['xRequestHistory'];
        $meta = hs_unserialize($row['tLog']);

        $out[$id] = $row;
        //Clean the note
        $out[$id]['tNote'] = replaceInlineImages($row['tNote'], $historyDocs, $row['xRequest'].$req['sRequestPassword'], $row['xRequestHistory'], $inlineImages);
        $out[$id]['tNote'] = formatNote($out[$id]['tNote'], $id, ($row['fNoteIsHTML'] ? 'is_html' : 'html'), false);

        if (! hs_empty($row['tLog']) && hs_empty($row['tNote'])) {
            $log_items = explode("\n", $row['tLog']);
            foreach ($log_items as $key=>$v) {
                $out[$id]['tNote'] .= '<div class="note-stream-item-logtext">'.hs_htmlspecialchars($v).'</div>';
            }

            // New event logs
            $logEvents = \HS\Domain\Workspace\Event::where('xRequestHistory', $row['xRequestHistory'])->orderBy('dtLogged', 'asc')->get();
            foreach ($logEvents as $logEvent) {
                $out[$id]['tNote'] .= '<div class="note-stream-item-logtext">'.hs_htmlspecialchars($logEvent->sDescription).'</div>';
            }
        }

        //Convert CC/BCC info into columns
        if ($row['fPublic'] && ! empty($row['tLog'])) {
            $out[$id]['cc'] = $meta['emailccgroup'];
            $out[$id]['bcc'] = $meta['emailbccgroup'];
            $out[$id]['to'] = $meta['emailtogroup'];
            $out[$id]['staff_notified'] = explode(',', $meta['ccstaff']);

            unset($out[$id]['tLog']);
        }

        //If external add a tag
        if ($row['fPublic'] == 0 && ! hs_empty($meta['emailtogroup'])) {
            $out[$id]['external'] = 1;
        } else {
            $out[$id]['external'] = 0;
        }

        //Add name
        if ($transform) {
            if ($row['xPerson'] > 0) {
                $out[$id]['xPerson'] = hs_htmlspecialchars($allStaff[$row['xPerson']]['sFname']).' '.hs_htmlspecialchars($allStaff[$row['xPerson']]['sLname']);
                $out[$id]['person_type'] = 'staff';
            } elseif ($row['xPerson'] == -1) {
                $out[$id]['xPerson'] = lg_systemnameportal;
                $out[$id]['person_type'] = 'system';
            } else {
                $out[$id]['xPerson'] = $req['sFirstName'].' '.$req['sLastName'];
                $out[$id]['person_type'] = 'customer';
            }
        }

        //Fix time field
        if ($transform) {
            $out[$id]['dtGMTChange'] = hs_showDate($out[$id]['dtGMTChange']);
        }

        //ADD FILE INFORMATION TO RETURNED ARRAY
        $out[$id]['files'] = [];
        if (count($historyDocs) && (hs_empty($row['tLog']) || ! hs_empty($row['tNote']))) {
            foreach ($historyDocs as $docid => $file) {
                $file['xDocumentId'] = $docid;
                $file['public_url'] = ($row['fPublic'] == 1 ? cHOST.'/index.php?pg=file&from=3&id='.$docid.'&reqid='.$row['xRequest'].$req['sRequestPassword'].'&reqhisid='.$id : '');
                $file['private_url'] = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $docid, 'showfullsize' => 1, 'download' => 1]);
                unset($file['dtGMTChange']);
                $out[$id]['files']['file'][] = $file;
            }
        }

        //Remove unneeded fields from row
        unset($out[$id]['sFilename']);
        unset($out[$id]['sFileMimeType']);
        unset($out[$id]['xDocumentId']);
        unset($out[$id]['iTimerSeconds']);
        unset($out[$id]['sRequestHistoryHash']);
    }

    return $out;
}

/******************************************
Make text safe for HTML form
******************************************/
function fixURL($string)
{
    $string = trim($string);

    if (substr($string, 0, 7) == 'http://') {
        return $string;
    } elseif (! empty($string)) {
        return 'http://'.$string;
    } else {
        return '';
    }
}

/******************************************
Replace quotes with other characters for JS
******************************************/
function replaceQuotes($string)
{
    return str_replace(["'", '"'], ['-', '*'], $string);
}

/*****************************************
Load an SVG inline
*****************************************/
function svg($file){
    return file_get_contents(base_path('public/static/img5/' . $file .'.svg'));
}

/*****************************************
writefile - write a file to disk
*****************************************/
function writeFile($path, $string, $type = 'w')
{
    if (is_string($path) and ($fp = fopen($path, $type))) {
        $result = fwrite($fp, $string);
        fclose($fp);

        return ($result === false)
            ? false
            : true;
    } else {
        return false;
    }
}

/*****************************************
CHANGE FILE/DIRECTORY PERMS
*****************************************/
function hs_chmod($path, $perms)
{
    //This only works on non-win systems
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        //umask is needed or else perms cannot be written on some systems correctly
        $old_umask = umask(0);
        chmod($path, $perms);
        umask($old_umask);
    }
}

/*****************************************
FIND TEMPORARY DIRECTORY
*****************************************/
if (! function_exists('sys_get_temp_dir')) {
    // Based on http://www.phpit.net/
    // article/creating-zip-tar-archives-dynamically-php/2/
    function sys_get_temp_dir()
    {
        // Try to get from environment variable
        if (! empty($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        } elseif (! empty($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        } elseif (! empty($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }

        // Detect by creating a temporary file
        else {
            // Try to use system's temporary directory
            // as random name shouldn't exist
            $temp_file = tempnam(md5(uniqid(rand(), true)), '');
            if ($temp_file) {
                $temp_dir = realpath(dirname($temp_file));
                unlink($temp_file);

                return $temp_dir;
            } else {
                return false;
            }
        }
    }
}

/*****************************************
ARRAY COMBINE FUNCTION FOR PHP < 5
*****************************************/
if (! function_exists('array_combine')) {
    function array_combine($a1, $a2)
    {
        $temp = [];
        $ct = count($a1);
        for ($i = 0; $i < $ct; $i++) {
            $temp[$a1[$i]] = $a2[$i];
        }

        if (! empty($temp)) {
            return $temp;
        } else {
            return false;
        }
    }
}

/********************************************
CHECK THAT A USERNAME IS A VALID ACTIVE USER
*********************************************/
function userIsValid($username)
{
    try {
        $rs = $GLOBALS['DB']->Execute('SELECT * FROM HS_Person WHERE sUsername = ? AND fDeleted = 0', [$username]);
        return (is_object($rs) && $rs->RecordCount() > 0);
    } catch(\Exception $e) {
        \Illuminate\Support\Facades\Log::error($e);
        return false;
    }
}

/********************************************
MAKE HTTP CALL - handle https as well using cURL
//options - ['type']=> http | http-post
*********************************************/
function hsHTTP($url, $options = [])
{
    //user cURL if possible. (required for https)
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, (isset($options['timeout']) ? $options['timeout'] : 10));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        //If http post specified in options
        if (isset($options['type']) && $options['type'] == 'http-post') {
            curl_setopt($curl, CURLOPT_POST, true);
            $post_data = parse_url($url); //use the GET query string to popuplate the POST field
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data['query']);
        }

        //If https just accept the cert, don't check it
        if (strpos($url, 'https') !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        // Add User Agent Header
        // Apache mod-security may require a user-agent string
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'User-Agent: HelpSpot HTTP Service',
        ]);

        $out = curl_exec($curl);
        curl_close($curl);
    } else { //default to regular get file
        $out = file_get_contents($url);
    }

    return $out;
}

/********************************************
hsPost
*********************************************/
function hsPost($url, $data = [])
{
    ob_start();

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    curl_exec($curl);
    curl_close($curl);

    ob_end_clean();
}

/********************************************
GET BASIC AUTH INFO IN VARIOUS SCENERIOS
*********************************************/
function getBasicAuth()
{
    $user = false;
    $pass = false;

    //  Find Username, Please
    // ----------------------------------------------------------------

    if (isset($_SERVER['PHP_AUTH_USER'])) {
        $user = $_SERVER['PHP_AUTH_USER'];
    } elseif (isset($_ENV['REMOTE_USER'])) {
        $user = $_ENV['REMOTE_USER'];
    } elseif (@getenv('REMOTE_USER')) {
        $user = getenv('REMOTE_USER');
    } elseif (isset($_ENV['AUTH_USER'])) {
        $user = $_ENV['AUTH_USER'];
    } elseif (@getenv('AUTH_USER')) {
        $user = getenv('AUTH_USER');
    }

    //  Find Password, Please
    // ----------------------------------------------------------------

    if (isset($_SERVER['PHP_AUTH_PW'])) {
        $pass = $_SERVER['PHP_AUTH_PW'];
    } elseif (isset($_ENV['REMOTE_PASSWORD'])) {
        $pass = $_ENV['REMOTE_PASSWORD'];
    } elseif (@getenv('REMOTE_PASSWORD')) {
        $pass = getenv('REMOTE_PASSWORD');
    } elseif (isset($_ENV['AUTH_PASSWORD'])) {
        $pass = $_ENV['AUTH_PASSWORD'];
    } elseif (@getenv('AUTH_PASSWORD')) {
        $pass = getenv('AUTH_PASSWORD');
    }

    // Authentication for IIS
    // ----------------------------------------------------------------

    if (! isset($user) or ! isset($pass) or (empty($user) && empty($pass))) {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ') {
            list($user, $pass) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        } elseif (! empty($_ENV) && isset($_ENV['HTTP_AUTHORIZATION']) && substr($_ENV['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ') {
            list($user, $pass) = explode(':', base64_decode(substr($_ENV['HTTP_AUTHORIZATION'], 6)));
        } elseif (@getenv('HTTP_AUTHORIZATION') && substr(getenv('HTTP_AUTHORIZATION'), 0, 6) == 'Basic ') {
            list($user, $pass) = explode(':', base64_decode(substr(getenv('HTTP_AUTHORIZATION'), 6)));
        }
    }

    //  Authentication for FastCGI
    // ----------------------------------------------------------------

    if (! isset($user) or ! isset($pass) or (empty($user) && empty($pass))) {
        if (! empty($_ENV) && isset($_ENV['Authorization']) && substr($_ENV['Authorization'], 0, 6) == 'Basic ') {
            list($user, $pass) = explode(':', base64_decode(substr($_ENV['Authorization'], 6)));
        } elseif (@getenv('Authorization') && substr(getenv('Authorization'), 0, 6) == 'Basic ') {
            list($user, $pass) = explode(':', base64_decode(substr(getenv('Authorization'), 6)));
        }
    }

    return [$user, $pass];
}

/********************************************
CHECKBOX CHECK - IF VALUES MATCH THEN CHECK IT
*********************************************/
function checkboxCheck($orig, $new)
{
    if ($orig == $new) {
        return 'checked';
    } else {
        return '';
    }
}

/********************************************
MULTICHECKBOX CHECK - IF VALUES MATCH THEN CHECK IT
*********************************************/
function checkboxMuiltiboxCheck($new, $array)
{
    if (is_array($array)) {
        if (in_array($new, $array)) {
            return 'checked';
        } else {
            return '';
        }
    } else {
        return '';
    }
}

/********************************************
SELECTION CHECK - IF VARS MATCH THEN SELECTED
*********************************************/
function selectionCheck($orig, $new)
{
    // https://github.com/UserScape/HelpSpot/issues/562
    // I suspect this may be used in other cases, however
    // so checking if $orig is_numeric could be in order
    // switching to float because:
    // https://github.com/UserScape/HelpSpot/issues/573

    if (! is_array($orig)) {
        $orig = [$orig];
    }

    if (is_numeric($new)) {
        $new = (float) $new;
    }

    // Check any value of $orig. Allows the
    // checking of multiple possible values.
    foreach ($orig as $key => $match) {
        if (is_numeric($match)) {
            $orig[$key] = (float) $match;
        }

        // Return selected for
        // first available match
        if ($orig[$key] === $new) {
            return 'selected';
        }
    }

    return '';
}

/********************************************
MULTISELECTION CHECK - IF VARS IN ARRAY THEN SELECTED
*********************************************/
function selectionMultiboxCheck($new, $array)
{
    if (is_array($array)) {
        if (in_array($new, $array)) {
            return 'selected';
        } else {
            return '';
        }
    } else {
        return '';
    }
}

/********************************************
BOOLSHOW - RETURN STRING FOR IF 1 or 0
*********************************************/
function boolShow($value, $yes, $no)
{
    if (intval($value) === 1) {   //convert bools
        $out = $yes;
    } else {
        $out = $no;
    }

    return strval($out);
}

/********************************************
listFilesInDir - return array of files in dir
*********************************************/
function listFilesInDir($path)
{
    $out = [];
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            if (! is_dir($file) && $file[0] != '.') {
                $out[] = $file;
            }
        }
        closedir($handle);
    }
    sort($out);

    return $out;
}

/********************************************
listDirectories - return folders in a path
*********************************************/
function listFolders($path)
{
    $out = [];
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            if (is_dir($path.'/'.$file) && $file[0] != '.') {
                $out[] = $file;
            }
        }
        closedir($handle);
    }

    return $out;
}

/********************************************
RETURN URL OF CURRENT PAGE WITH QUERY STRING
*********************************************/
function utilPageUrl()
{
    $name = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $name = str_replace('//', '/', $name);

    return $name.'?'.$_SERVER['QUERY_STRING'];
}

/********************************************
LICENSE HELPERS
*********************************************/
function licenseAtUserLimit()
{
    //See if we're unlimited
    if ($GLOBALS['license']['Users'] == 'unlimited') {
        return false;
    }

    $activestaff = apiGetAllUsers();
    if ($GLOBALS['license']['Users'] > $activestaff->RecordCount()) {
        return false;
    } else {
        return true;
    }
}

function licenseOverUserLimit()
{
    //See if we're unlimited
    if ($GLOBALS['license']['Users'] == 'unlimited') {
        return false;
    }

    $activestaff = apiGetAllUsers();
    if ($GLOBALS['license']['Users'] < $activestaff->RecordCount()) {
        return true;
    } else {
        return false;
    }
}

function subscription()
{
    return app('HS\License\Subscription');
}

/********************************************
Set a Cookie
*********************************************/
function set_cookie($name = '', $value = '', $expire = '', $path = '', $domain = '')
{

    //Handle arrays of cookies being passed in
    if (is_array($name)) {
        foreach (['value', 'expire', 'domain', 'path', 'prefix', 'name'] as $item) {
            if (isset($name[$item])) {
                $$item = $name[$item];
            }
        }
    }

    //Set path
    if ($path == '') {
        $path = dirname($_SERVER['REQUEST_URI']);
    }

    //Set expiration
    if (! is_numeric($expire)) {
        $expire = time() - 86500;
    } else {
        if ($expire > 0) {
            $expire = time() + $expire;
        } else {
            $expire = 0;
        }
    }

    setcookie($name, $value, $expire, $path, $domain, 0);
}

/********************************************
Get a Cookies value
*********************************************/
function get_cookie($name = '')
{
    return $_COOKIE[$name];
}

/********************************************
Delete a Cookie
*********************************************/
function delete_cookie($name = '')
{
    set_cookie($name, '', -86500);
}

/********************************************
TRY TO FIGURE OUT FIRST NAME FROM LAST
*********************************************/
function parseName($name)
{
    $temp = trim($name);
    //filter stuff that we don't use
    $temp = preg_replace('/ ?\(.*?\)/', '', $temp);

    $out = ['fname'=>'', 'lname'=>''];
    if (strstr(trim($temp), ',')) {        //corporate format of lastname, firstname
        $t = explode(',', $temp);
        $out['lname'] = $t[0];
        unset($t[0]);
        $out['fname'] = implode(' ', $t);
    } elseif (strstr(trim($temp), '.')) {       //first.last format
        $t = explode('.', $temp);
        $out['fname'] = $t[0];
        unset($t[0]);
        $out['lname'] = implode(' ', $t);
    } elseif (! strstr(trim($temp), ' ')) {  //If name doesn't have a space then return as only first
        $out['fname'] = $temp;
    } else {                              //normal format of fname followed by lname with space
        $t = explode(' ', $temp);
        $out['fname'] = $t[0];
        unset($t[0]);
        $out['lname'] = implode(' ', $t);
    }

    $out['fname'] = trim($out['fname']);
    $out['lname'] = trim($out['lname']);

    return $out;
}

/********************************************
SIMPLE EMAIL VALIDATION
*********************************************/
function validateEmail($email)
{

    //This is PHP5 only, php4 we just return true and allow it through.
    if (version_compare(PHP_VERSION, '5', '>=')) {
        $validator = new EmailAddressValidator;
        if (! $validator->check_email_address($email)) {
            return false;
        }
    }

    return true;
}

/********************************************
 * STORE GLOBAL VAR IN DATABASE
 ********************************************
 * @param $name
 * @param string $var
 * @return bool
 */
function storeGlobalVar($name, $var = '')
{
    try {
        $GLOBALS['DB']->Execute('UPDATE HS_Settings SET tValue = ? WHERE sSetting = ?', [$var, $name]);
        \Facades\HS\Cache\Manager::forget(\HS\Cache\Manager::CACHE_SETTINGS_KEY);
        return true;
    } catch(\Exception $e) {
        \Illuminate\Support\Facades\Log::error($e);
        return false;
    }
}

/********************************************
CREATES A LOCAL UNIX TIMESTAMP
*********************************************/
function hs_localUnixTime($timestamp)
{
    //return $timestamp + cHD_TIMEOFFSET+(date('I')*3600);
    return $timestamp;
}

/********************************************
TAKE SECONDS AND MAKE HRS/MINUTES/DAYS
*********************************************/
function hsTimeFromSeconds($seconds)
{
    if ($seconds != 0) {
        return time_since(0, round($seconds, 0));
    } else {
        return '-';
    }
}

/********************************************
CREATE A RFC 822 DATE FOR RSS FEEDS
*********************************************/
function RFCDate($timestamp)
{
    $result = gmdate('D, j M Y H:i:s', $timestamp).' GMT';

    return $result;
}

/********************************************
QUICK FILE CHECK
*********************************************/
function hs_file_perm_ok($file)
{
    //Only check Linux currently
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $perm = substr(decoct(fileperms(clean_filename($file))), 3);
        //Simple check for 777 perms on HS files.
        if ($perm == '777') {
            return false;
        } else {
            return true;
        }
    }

    return true;
}

/********************************************
ENCRYPT STRINGS
DEPRECIATED: This is now only used for updating HS4->HS5
             for updating HS_Mailbox password encryption
*********************************************/
function hs_crypt($msg)
{
    $key = SSKEY;
    $out = trim($msg);

    $rc4 = new Crypt_RC4;
    $rc4->setKey($key);     //uses constant sskey
    $rc4->crypt($out);

    return base64_encode($out);
}

/********************************************
DECRYPT STRINGS
DEPRECIATED: This is now only used for updating HS4->HS5
             for updating HS_Mailbox password encryption
*********************************************/
function hs_decrypt($msg)
{
    $key = SSKEY;
    $out = base64_decode(trim($msg));

    $rc4 = new Crypt_RC4;
    $rc4->setKey($key);     //uses constant sskey
    $rc4->decrypt($out);

    return $out;
}

/********************************************
RETURN IP OF CLIENT CURRENTLY CALLING SCRIPT
*********************************************/
function hs_clientIP()
{
    $ip = 'unknown';
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) { //if being sent through a proxy try and find true IP
        $t = explode(',', getenv('HTTP_X_FORWARDED_FOR'));
        $c = count($t) - 1;
        $ip = $t[$c];
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    }

    return $ip;
}

/********************************************
SERIALIZE DATA INTO A STORABLE STRING
*********************************************/
function hs_serialize($var)
{
    return serialize($var);
}

/********************************************
UNSERIALIZE DATA TO PHP VARIABLE
*********************************************/
function hs_unserialize($var, $default = [])
{
    $var = trim($var);
    if (is_string($var) && ! empty($var)) {
        return unserialize($var);
    } else {
        return $default;
    }
}

/********************************************
PROPERLY ESCAPE STRINGS FOR USE IN JS
*********************************************/
function hs_jshtmlentities($string)
{
    $string = str_replace("'", "\'", $string);
    $string = str_replace('"', '\"', $string);
    $string = str_replace('\\\\"', '\\\\\"', $string);
    //return htmlentities($string);
    return $string;
}

/********************************************
RETURN VALUE OF IMAGE UPLOAD ERROR FROM http://us2.php.net/manual/en/features.file-upload.errors.php
*********************************************/
function hs_imageerror($er)
{
    switch ($er) {
        case 0:
        return 'There is no error, the file uploaded with success.';

            break;
        case 1:
        return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';

            break;
        case 2:
        return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';

            break;
        case 3:
        return 'The uploaded file was only partially uploaded.';

            break;
        case 4:
        return 'No file was uploaded. No file selected.';

            break;
        case 5:
        return '';

            break;
        case 6:
        return 'Missing a temporary folder.';

            break;
    }
}

/********************************************
DETERMINE IF STAR ON FRONT OF STRING OR BACK
*********************************************/
function wildCardLoc($string)
{
    if (! empty($string)) {
        /*
                $size = strlen($string)-1;
                $out = false;

                if($string{0} == '*'){
                    $out = 'front';
                }
                if($string{$size} == '*'){
                    $out = 'back';
                }
                if($string{0} == '*' && $string{$size} == '*'){
                    $out = 'both';
                }

                return $out;
        */
        $size = strlen($string) - 1;
        $out = false;

        if (strrpos($string, '*') !== false) {
            $out = str_replace('*', '%', $string);
        } else {
            //not a wildcard
            $out = false;
        }

        if (strrpos($string, '*') !== false) {
            $out = str_replace('*', '%', $string);
        } else {
            //not a wildcard
            $out = false;
        }

        return $out;
    } else {
        return false;
    }
}

/********************************************
SET ARRAY POINTER TO A SPECIFIC KEY
*********************************************/
function array_set_current(&$array, $key)
{
    reset($array);
    while (current($array) !== false) {
        if (key($array) == $key) {
            break;
        }
        next($array);
    }
}

/********************************************
Encode a string in UTF8 and then url encode it
*********************************************/
function utf8RawUrlEncode($string)
{
    return rawurlencode($string);
}

/********************************************
Properly decode URL of UTF8 and regular chars
*********************************************/
function utf8RawUrlDecode($source)
{
    $decodedStr = '';
    $pos = 0;
    $len = strlen($source);
    while ($pos < $len) {
        $charAt = substr($source, $pos, 1);
        if ($charAt == '%') {
            $pos++;
            $charAt = substr($source, $pos, 1);
            if ($charAt == 'u') {
                // we got a unicode character
                $pos++;
                $unicodeHexVal = substr($source, $pos, 4);
                $unicode = hexdec($unicodeHexVal);
                $entity = '&#'.$unicode.';';
                $decodedStr .= utf8_encode($entity);
                $pos += 4;
            } else {
                // we have an escaped ascii character
                $hexVal = substr($source, $pos, 2);
                $decodedStr .= chr(hexdec($hexVal));
                $pos += 2;
            }
        } else {
            $decodedStr .= $charAt;
            $pos++;
        }
    }

    return $decodedStr;
}

/********************************************
Parse emails from email header - will only
return the first email! Not safe for CC lists.
*********************************************/
function hs_parse_email_header($emails)
{
    $out = ['personal'=>'', 'host'=>'', 'mailbox'=>''];

    if (function_exists('imap_rfc822_parse_adrlist')) {
        //Handle emails in old 'at' format. ex: Leppnen, Janne  (SANITEC) <janne.leppanen@sanitec.com>
        //The issue is with the personal part not in quotes and having a comma. This old format isn't handled right by imap_rfc822_parse_adrlist
        if (utf8_strpos($emails, ',') && utf8_strpos($emails, '<') && utf8_strpos($emails, '"') === false) {
            $emails = '"'.str_replace('<', '"<', $emails); //Wrap personal part in quotes
        }

        $emails = stripslashes($emails);

        $tmp = imap_rfc822_parse_adrlist($emails, null);

        if (is_array($tmp)) {
            $out['personal'] = (isset($tmp[0]->personal)) ? hs_charset_emailheader($tmp[0]->personal) : '';
            $out['host'] = hs_charset_emailheader($tmp[0]->host);
            $out['mailbox'] = hs_charset_emailheader($tmp[0]->mailbox);
        }
    }

    return $out;
}

/********************************************
email header charset helper
*********************************************/
function hs_charset_emailheader($header)
{
    $out = '';
    if (function_exists('imap_mime_header_decode')) {
        $decode = imap_mime_header_decode($header);

        if (count($decode) >= 1) {
            foreach ($decode as $part) {
                if ($part->charset == 'default' || $part->charset == '') {	//leave in_charset empty(which it is when calling for email header) unless there's a charset in the header
                    $out .= hs_check_charset_and_convert($part->text, 'UTF-8');
                } else {
                    $charset = (new HS\Charset\Converter())->convert($part->charset);
                    $out .= hs_charset_convert($charset, 'UTF-8', $part->text);
                }
            }

            return $out;
        } else {
            return $header;
        }
    } else {
        //IMAP is not installed
        return $header;
    }
}

/********************************************
charset helper, convert between charsets/encodines
*********************************************/
function hs_charset_convert($in_charset, $out_charset, $string)
{
    return app('charset.encoder')->encode($string, $out_charset, $in_charset);
}

/********************************************
try and determine the charset and convert as needed
*********************************************/
function hs_check_charset_and_convert($string, $out_charset)
{
    $detector = app('charset.detector');
    $encoder = app('charset.encoder');

    /*
     * These test if '=== true', as if mbstring isn't installed
     * a NullDetector is returned, which returns NULL for
     * the test, instead of a boolean
     *
     * A NullDetector means the string will be directly returned
     **/

    if ($detector->isEncoded('UTF-8', $string) === true) {
        return $encoder->encode($string, $out_charset, 'UTF-8');
    }

    if ($detector->isEncoded('iso-8859-1', $string) === true) {
        return $encoder->encode($string, $out_charset, 'iso-8859-1');
    }

    if ($detector->isEncoded('us-ascii', $string) === true) {
        return $encoder->encode($string, $out_charset, 'us-ascii');
    }

    return $string;
}

/**
 * wordwrap for utf8 encoded strings.
 *
 * @param string $str
 * @param int $len
 * @param string $what
 * @return string
 * @author Milian Wolff <mail@milianw.de>
 */
function utf8_wordwrap($str, $width, $break, $cut = false)
{
    if (! $cut) {
        $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.$width.',}\b#U';
    } else {
        $regexp = '#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.$width.'}#';
    }
    if (function_exists('mb_strlen')) {
        $str_len = mb_strlen($str, 'UTF-8');
    } else {
        $str_len = preg_match_all('/[\x00-\x7F\xC0-\xFD]/', $str, $var_empty);
    }
    $while_what = ceil($str_len / $width);
    $i = 1;
    $return = '';
    while ($i < $while_what) {
        preg_match($regexp, $str, $matches);
        $string = $matches[0];
        $return .= $string.$break;
        $str = substr($str, strlen($string));
        $i++;
    }

    return $return.$str;
}

/********************************************
parse a comma sep list of emails to remove any
formatting and only return actual emails
*********************************************/
function hs_parse_email_list($string)
{
    $out = [];
    //Clean out quoted names which sometimes contain comma's which mess things up
    $string = preg_replace('/"(.+?)"/', '', $string);
    $emails = explode(',', $string);

    if (count($emails) > 0) {
        foreach ($emails as $e) {
            //If formatted as Jamie Landsman <jlandsman@gmail.com> then pull out email
            if (utf8_strpos($e, '<') !== false) {
                $start = utf8_strpos($e, '<');
                $end = utf8_strpos($e, '>');
                $out[] = utf8_substr($e, $start + 1, ($end - $start - 1));
            } else {
                $out[] = $e;
            }
        }

        return implode(', ', $out);
    } else {
        return $string;
    }
}

/********************************************
USER SIGNATURE - show mobile if in mobile UI
*********************************************/
function hs_user_signature($force_text = false)
{
    global $user;
    global $page;
    $out = '';

    if (hs_setting('cHD_HTMLEMAILS') && ! $force_text) {
        if (! hs_empty($user['tSignature_HTML'])) {
            $out = hs_markdown($user['tSignature_HTML']);
        }
    } else {
        if (! hs_empty($user['tSignature'])) {
            $out = "\n".$user['tSignature'];
        }
    }

    return $out;
}

/********************************************
Turn time tracker time into seconds
*********************************************/
function parseTimeToSeconds($time)
{
    $seconds = 0;

    //handle the 2 different formats
    if (strpos($time, '.') !== false) {
        $seconds = $time * 60 * 60;
    } else {
        $parts = explode(':', $time);
        //handle full time vs only minutes
        if (isset($parts[0]) && isset($parts[1]) && isset($parts[2])) {
            $seconds = ($parts[0] * 60 * 60) + ($parts[1] * 60) + $parts[2];
        } elseif (isset($parts[0]) && isset($parts[1])) {
            $seconds = ($parts[0] * 60 * 60) + ($parts[1] * 60);
        } else {
            $seconds = $time * 60;
        }
    }

    return is_numeric($seconds) ? $seconds : 0;
}

/********************************************
Turn seconds into hh:mm time
*********************************************/
function parseSecondsToTime($seconds)
{
    if (is_numeric($seconds)) {
        $minutes = $seconds / 60;
        $hours = intval($minutes / 60);
        $time = $hours.':'.str_pad(round($minutes - ($hours * 60), 0), 2, '0', STR_PAD_LEFT);
    } else {
        $time = '-';
    }

    return $time;
}

/********************************************
Turn seconds into hh:mm time with labels
*********************************************/
function parseSecondsToTimeWlabel($seconds)
{
    $time = '';

    if (is_numeric($seconds)) {
        $minutes = $seconds / 60;
        $hours = intval($minutes / 60);
        $time = $hours.' '.lg_hours.', '.str_pad(round($minutes - ($hours * 60), 0), 2, '0', STR_PAD_LEFT).' '.lg_minute;
    } else {
        $time = '-';
    }

    return $time;
}

/********************************************
TIMER
*********************************************/
class hsTimer
{
    public $starttime;

    public $endtime;

    public function __construct()
    {
        $this->starttime = microtime(true);

        return true;
    }

    public function stop_timer()
    {
        $this->endtime = microtime(true);

        return true;
    }

    public function show_timer()
    {
        return number_format($this->endtime - $this->starttime, 3);
    }

    public function stop_n_show()
    {
        $this->stop_timer();

        return $this->show_timer();
    }
}

/********************************************
STATS - AVERAGE
*********************************************/
function stats_average($array)
{
    $sum = array_sum($array);
    $count = count($array);
    $avg = ($count ? $sum / $count : 0);

    return $avg;
}

/********************************************
STATS - STANDARD DEVIATION
*********************************************/
function stats_std($array)
{
    $avg = stats_average($array);

    foreach ($array as $value) {
        $variance[] = pow($value - $avg, 2);
    }

    return sqrt(stats_average($variance));
}

/********************************************
STATS - MEDIAN
*********************************************/
function stats_median($array)
{
    sort($array);
    $count = count($array);
    $middleval = floor(($count - 1) / 2); // find the middle value, or the lowest middle value
    if ($count % 2) { // odd number, middle is the median
        $median = $array[$middleval];
    } else { // even number, calculate avg of 2 medians
        $low = $array[$middleval];
        $high = $array[$middleval + 1];
        $median = (($low + $high) / 2);
    }

    return $median;
}

/********************************************
FUNCTION WRAPPER TO GET BIZ HOURS
*********************************************/
function getBizHours($start, $end)
{
    //We only want to init this once
    if (! isset($GLOBALS['bizhours'])) {
        $GLOBALS['bizhours'] = new business_hours;
    }

    return parseSecondsToTimeWlabel(($end ? $GLOBALS['bizhours']->getBizTime($start, $end) : ''));
}

/********************************************
IMAGE RESIZE - based on:
Copyright 2008 Maxim Chernyak
http://github.com/maxim/smart_resize_image
*********************************************/
function smart_resize_image(&$file,
                              $width = 0,
                              $height = 0,
                              $proportional = false,
                              $output = 'browser',
                              $delete_original = true,
                              $use_linux_commands = false)
{
    $tmpdir = is_writable(ini_get('upload_tmp_dir')) ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
    $filepath = $tmpdir.'/'.md5(uniqid(rand(), true));
    writeFile($filepath, $file);
    $file = $filepath;

    if ($height <= 0 && $width <= 0) {
        return false;
    }

    // Setting defaults and meta
    $info = getimagesize($file);
    $image = '';
    $final_width = 0;
    $final_height = 0;
    list($width_old, $height_old) = $info;

    // Only resize if larger than max width, so set to current size
    if ($width > $info[0]) {
        $width = $info[0];
    }

    // Calculating proportionality
    if ($proportional) {
        if ($width == 0) {
            $factor = $height / $height_old;
        } elseif ($height == 0) {
            $factor = $width / $width_old;
        } else {
            $factor = min($width / $width_old, $height / $height_old);
        }

        $final_width = round($width_old * $factor);
        $final_height = round($height_old * $factor);
    } else {
        $final_width = ($width <= 0) ? $width_old : $width;
        $final_height = ($height <= 0) ? $height_old : $height;
    }

    // Loading image to memory according to type
    switch ($info[2]) {
      case IMAGETYPE_GIF:   $image = imagecreatefromgif($file);

break;
      case IMAGETYPE_JPEG:  $image = imagecreatefromjpeg($file);

break;
      case IMAGETYPE_PNG:   $image = imagecreatefrompng($file);

break;
      default: return false;
    }

    // This is the resizing/resampling/transparency-preserving magic
    $image_resized = imagecreatetruecolor($final_width, $final_height);
    if (($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG)) {
        $transparency = imagecolortransparent($image);

        if ($transparency >= 0) {
            $transparent_color = imagecolorsforindex($image, $trnprt_indx);
            $transparency = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
            imagefill($image_resized, 0, 0, $transparency);
            imagecolortransparent($image_resized, $transparency);
        } elseif ($info[2] == IMAGETYPE_PNG) {
            imagealphablending($image_resized, false);
            $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
            imagefill($image_resized, 0, 0, $color);
            imagesavealpha($image_resized, true);
        }
    }
    imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);

    // Taking care of original, if needed
    if ($delete_original) {
        if ($use_linux_commands) {
            exec('rm '.$file);
        } else {
            @unlink($file);
        }
    }

    // Preparing a method of providing result
    switch (strtolower($output)) {
      case 'browser':
        $mime = image_type_to_mime_type($info[2]);
        header("Content-type: $mime");
        $output = null;
        getImageBinary($info[2], $image_resized, $output);

      break;
      case 'file':
        $output = $file;
        getImageBinary($info[2], $image_resized, $output);

      break;
      case 'return':
        return getImageBinary($info[2], $image_resized, null, false);

      break;
      default:
        return false;

      break;
    }
}

function getImageBinary($imageType, $resource, $fileOutput, $toBrowserOrFile = true)
{
    if (! $toBrowserOrFile) {
        ob_start();
    }

    switch ($imageType) {
        case IMAGETYPE_GIF:   imagegif($resource, $fileOutput);

break;
        case IMAGETYPE_JPEG:  imagejpeg($resource, $fileOutput);

break;
        case IMAGETYPE_PNG:   imagepng($resource, $fileOutput);

break;
    }

    if (! $toBrowserOrFile) {
        $imageString = ob_get_contents();
        ob_end_clean();

        return $imageString;
    }

    return true;
}

/********************************************
EMAIL ADDRESS VERIFICATION - PHP5 ONLY
*********************************************/
/*

    EmailAddressValidator Class
    http://code.google.com/p/php-email-address-validation/

    Released under New BSD license
    http://www.opensource.org/licenses/bsd-license.php

    Sample Code
    ----------------
    $validator = new EmailAddressValidator;
    if ($validator->check_email_address('test@example.org')) {
        // Email address is technically valid
    }

*/

class EmailAddressValidator
{
    /**
     * Check email address validity.
     * @param   strEmailAddress     Email address to be checked
     * @return  bool if email is valid, false if not
     */
    public function check_email_address($strEmailAddress)
    {
        // Control characters are not allowed
        if (preg_match('/[\x00-\x1F\x7F-\xFF]/', $strEmailAddress)) {
            return false;
        }

        // Check email length - min 3 (a@a), max 256
        if (! $this->check_text_length($strEmailAddress, 3, 256)) {
            return false;
        }

        // Split it into sections using last instance of "@"
        $intAtSymbol = strrpos($strEmailAddress, '@');
        if ($intAtSymbol === false) {
            // No "@" symbol in email.
            return false;
        }
        $arrEmailAddress[0] = substr($strEmailAddress, 0, $intAtSymbol);
        $arrEmailAddress[1] = substr($strEmailAddress, $intAtSymbol + 1);

        // Count the "@" symbols. Only one is allowed, except where
        // contained in quote marks in the local part. Quickest way to
        // check this is to remove anything in quotes. We also remove
        // characters escaped with backslash, and the backslash
        // character.
        $arrTempAddress[0] = preg_replace('/\./', '', $arrEmailAddress[0]);
        $arrTempAddress[0] = preg_replace('/"[^"]+"/', '', $arrTempAddress[0]);
        $arrTempAddress[1] = $arrEmailAddress[1];
        $strTempAddress = $arrTempAddress[0].$arrTempAddress[1];
        // Then check - should be no "@" symbols.
        if (strrpos($strTempAddress, '@') !== false) {
            // "@" symbol found
            return false;
        }

        // Check local portion
        if (! $this->check_local_portion($arrEmailAddress[0])) {
            return false;
        }

        // Check domain portion
        if (! $this->check_domain_portion($arrEmailAddress[1])) {
            return false;
        }

        // If we're still here, all checks above passed. Email is valid.
        return true;
    }

    /**
     * Checks email section before "@" symbol for validity.
     * @param   string strLocalPortion     Text to be checked
     * @return  true if local portion is valid, false if not
     */
    public function check_local_portion($strLocalPortion)
    {
        // Local portion can only be from 1 to 64 characters, inclusive.
        // Please note that servers are encouraged to accept longer local
        // parts than 64 characters.
        if (! $this->check_text_length($strLocalPortion, 1, 64)) {
            return false;
        }
        // Local portion must be:
        // 1) a dot-atom (strings separated by periods)
        // 2) a quoted string
        // 3) an obsolete format string (combination of the above)
        $arrLocalPortion = explode('.', $strLocalPortion);
        for ($i = 0, $max = count($arrLocalPortion); $i < $max; $i++) {
            if (! preg_match('.^('
                            .'([A-Za-z0-9!#$%&\'*+/=?^_`{|}~-]'
                            .'[A-Za-z0-9!#$%&\'*+/=?^_`{|}~-]{0,63})'
                            .'|'
                            .'("[^\\\"]{0,62}")'
                            .')$.', $arrLocalPortion[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks email section after "@" symbol for validity.
     * @param   strDomainPortion     Text to be checked
     * @return  bool true if domain portion is valid, false if not
     */
    public function check_domain_portion($strDomainPortion)
    {
        // Total domain can only be from 1 to 255 characters, inclusive
        if (! $this->check_text_length($strDomainPortion, 1, 255)) {
            return false;
        }
        // Check if domain is IP, possibly enclosed in square brackets.
        if (preg_match('/^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])'
           .'(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}$/', $strDomainPortion) ||
            preg_match('/^\[(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])'
           .'(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}\]$/', $strDomainPortion)) {
            return true;
        } else {
            $arrDomainPortion = explode('.', $strDomainPortion);
            if (count($arrDomainPortion) < 2) {
                return false; // Not enough parts to domain
            }
            for ($i = 0, $max = count($arrDomainPortion); $i < $max; $i++) {
                // Each portion must be between 1 and 63 characters, inclusive
                if (! $this->check_text_length($arrDomainPortion[$i], 1, 63)) {
                    return false;
                }
                if (! preg_match('/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|'
                   .'([A-Za-z0-9]+))$/', $arrDomainPortion[$i])) {
                    return false;
                }
                if ($i == $max - 1) { // TLD cannot be only numbers
                    if (strlen(preg_replace('/[0-9]/', '', $arrDomainPortion[$i])) <= 0) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check given text length is between defined bounds.
     * @param   string strText     Text to be checked
     * @param   int intMinimum  Minimum acceptable length
     * @param   int intMaximum  Maximum acceptable length
     * @return  bool true if string is within bounds (inclusive), false if not
     */
    public function check_text_length($strText, $intMinimum, $intMaximum)
    {
        // Minimum and maximum are both inclusive
        $intTextLength = strlen($strText);
        if (($intTextLength < $intMinimum) || ($intTextLength > $intMaximum)) {
            return false;
        } else {
            return true;
        }
    }
}

/******************************************
PERM FUNCTIONS: See if any staff are in a group
******************************************/
function staffInPermGp($gp)
{
    return $GLOBALS['DB']->GetOne('SELECT COUNT(*) AS ct FROM HS_Person WHERE fUserType = ?', [$gp]);
}

/******************************************
PERM FUNCTIONS: See if user meets this perm
******************************************/
function perm($perm)
{
    global $user;

    if (! $user) {
        return false;
    }

    //Return true if user has perm
    return intval($user[$perm]) === 1;
}

/********************************************
PERM FUNCTIONS: shortcut for admin check
*********************************************/
function isAdmin()
{
    if ($GLOBALS['user']['fUserType'] == 1) {
        return true;
    } else {
        return false;
    }
}

/********************************************
FIND IMAGE CID'S WITHIN MESSAGE CONTENT
 *********************************************/
function getInlineCIDs($content)
{
    $inlines = [];
    preg_match_all('/\bsrc\s*=\s*"\s*cid:([^"\r\n]*)"/i', $content, $inlines);

    return $inlines;
}

/********************************************
REPLACE CID'S IN NOTES WITH IMG TAGS
*********************************************/
function replaceInlineImages($note, $historyDocs = [], $accesskey = false, $histid = false, $inlineImages = false)
{
    //find references to inline attachments and insert proper attachment (try an accommodate spaces if there are any)
    $inlines = getInlineCIDs($note);

    if (! empty($inlines[1])) {
        foreach ($inlines[1] as $k=>$img) {
            if (! empty($img) && ! empty($historyDocs)) {
                //Find right attachment and make link
                foreach ($historyDocs as $key=>$v) {
                    if ($v['sCID'] == $img && in_array($v['sFileMimeType'], $GLOBALS['imageMimeTypes'])) {
                        $inline_url = '';
                        if (! $inlineImages) {
                            if (! $accesskey) {
                                $inline_url = 'admin?pg=file&from=0&id='.$key;
                            } else {
                                $inline_url = 'index.php?pg=file&from=3&id='.$key.'&reqid='.$accesskey.'&reqhisid='.$histid;
                            }
                        } else {
                            try {
                                $document = Document::fromAdminRequest($key);
                                $body = $document->asBase64();
                                $inline_url = 'data:'.$v['sFileMimeType'].';base64,'.$body;
                            } catch (ModelNotFoundException $e) {
                                //
                            }
                        }
                        $note = str_replace('<img', '<img title="'.hs_htmlspecialchars($v['sFilename']).'" class="note-stream-item-inline-img"', $note);
                        $note = str_replace('cid:'.$img, $inline_url, $note);

                        break;
                    }
                }

                //In case we can't display it then hide it
                $note = str_replace('cid:'.$img.'"', '" style="display:none;"', $note);
            }
        }
    }

    return $note;
}

function getSorting($sortBy, $order, $cols = [])
{
    $order = getSortOrder($order);
    if (in_array($sortBy, $cols)) {
        return $sortBy.' '.$order;
    } else {
        return '';
    }
}
/**
 * Ensure sort order is always asc/desc.
 *
 * @param $order
 * @return string
 */
function getSortOrder($order)
{
    return in_array(strtolower($order), ['asc', 'desc']) ? $order : '';
}

/**
 * Determine if this account is hosted.
 *
 * @return bool
 */
function isHosted()
{
    if (config('helpspot.hosted', false)) {
        return true;
    }

    return false;
}

function wysiwgyImageRegex()
{
    return '/(.*)<img src="admin\?pg=file&amp;from=0&amp;id=(\d*)" \/>(.*)/';
}

function getWysiwgyImageUploadIds($string)
{
    preg_match_all(wysiwgyImageRegex(), $string, $matches);
    return $matches[2];
}

function wysiwygImageReplace($text)
{
    return preg_replace(wysiwgyImageRegex(), '$1<img src="cid:$2">$3', $text);
}

function inlineImageReplacer($text)
{
    return preg_replace('/<img data-src="(.+)" src="(.+)" \/>/', '$1', $text);
}

/**
 * Check if using a helpspot cloud email
 * Useful for hiding hosted-helpspot settings.
 * @param $email
 * @return bool
 */
function isHelpspotEmail($email)
{
    return \Illuminate\Support\Str::endsWith($email, ['helpspot.com', 'helpspot.email']);
}

/**
 * Log message (info) using Laravel's logging mechanism.
 * @param $msg
 * @return mixed
 */
function logMsg($msg)
{
    global $user;

    if (isset($user['sEmail'])) {
        $msg .= ' by '.$user['sEmail'];
    }

    return app('log')->info($msg);
}

/**
 * Get SMTP security transports available
 * on customer's system.
 * @return array
 */
function smtpSecurityProtocols()
{
    $smtpTransports = stream_get_transports();
    $listedTransports = [];

    foreach ($smtpTransports as $transport) {
        // Add any "ssl*" that isn't just "ssl"
        if (strrpos($transport, 'ssl') === 0 && $transport !== 'ssl') {
            $listedTransports[] = $transport;
        }

        // Add any "tls*" that isn't just "tls"
        if (strrpos($transport, 'tls') === 0 && $transport !== 'tls') {
            $listedTransports[] = $transport;
        }
    }

    return $listedTransports;
}

function getHelpSpotRssArticleLink()
{

    // We're going to grab the latest blog posts and use them to link from the footer
    // and the login screen. These are the latest posts from all helpspot.com blogs combined.
    if (! app('cache')->has('latest_helpspot_link')) {
        try {
            $feed = hsHTTP('https://www.helpspot.com/all-blogs-feed');
            $xml = simplexml_load_string($feed, 'SimpleXMLElement', LIBXML_NOCDATA);

            $link = [
                'link' => (string) $xml->channel->item->link,
                'title' => (string) $xml->channel->item->title,
            ];

            // Check for new blog posts every hour.
            app('cache')->put('latest_helpspot_link', $link, 60);

            return $link;
        } catch (Exception $e) {
            // For now we don't care about parse errors. Just ignore.
            return [
                'link' => 'https://www.helpspot.com/delightenment',
                'title' => 'Delightenment Articles',
            ];
        }
    } else {
        return app('cache')->get('latest_helpspot_link');
    }
}

function createBlogLinkMetaQueryString()
{
    global $user;

    return base64_encode(json_encode(['email'=>(isset($user['sEmail']) ? $user['sEmail'] : ''), 'customer_id'=>hs_setting('cHD_CUSTOMER_ID')]));
}

/**
 * Lang helper
 */
function hs_lang($langString, $default)
{
    if (defined($langString)) {
        return constant($langString);
    }

    return $default;
}

/**
 * See if the user is active.
 * @param array $user
 * @return bool
 */
function userIsActive(array $user)
{
    return $user['fDeleted'] == 0;
}

/**
 * See if the user is deleted.
 * @param array $user
 * @return bool
 */
function userIsDeleted(array $user)
{
    return ! userIsActive($user);
}

/**
 * Check that the $loginto var is a true internal link
 * Basically it should have admin.php at the beginning of the
 * string. The following are two examples of valid admin redirects:
 * `admin.php?pg=`
 * `/admin.php?pg=`.
 *
 * @param $path
 * @return bool
 */
function isInternalRedirect($path)
{
    $adminPosInString = strpos($path, 'admin');

    return $adminPosInString !== false && $adminPosInString < 2;
}

/**
 * Get the path of the custom_code directory
 * @param null $file
 * @return string
 */
function customCodePath($file=null)
{
    $path = ($file)
        ? 'custom_code/' . $file
        : 'custom_code';

    if(file_exists(public_path('custom_code'))) {
        return public_path($path);
    }

    if(file_exists(cBASEPATH . '/custom_code')) {
        return cBASEPATH . '/' . $path;
    }

    return public_path($path);
}

function inDarkMode(){
    return auth()->user()->fDarkMode ? true : false;
}
