<?php

// SECURITY: Don't allow direct calls
use HS\Domain\Workspace\Request;

if (! defined('cBASEPATH')) {
    die();
}

class api_public extends api
{
    //API access type
    public $api = 'public';

    //Valid methods, ones which can be publicly called.
    public $valid_methods = ['customer.getRequests',
                               'request.create',
                               'request.update',
                               'request.getCategories',
                               'request.getCustomFields',
                               'request.get',
                               'kb.list',
                               'kb.get',
                               'kb.getBookTOC',
                               'kb.getPage',
                               'kb.search',
                               'kb.voteHelpful',
                               'kb.voteNotHelpful',
                               'util.getFieldLabels', ];

    //Array of columns to exclude from result set for each public method call
    public $col_exclude = [];

    public $disabled = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        //Setup exclude columns, set to empty array by default
        foreach ($this->valid_methods as $k=>$v) {
            $this->col_exclude[$v] = [];
        }
        $ex_array = hs_unserialize(hs_setting('cHD_WSPUBLIC_EXCLUDECOLUMNS'));
        foreach ($ex_array as $k=>$v) {
            $this->col_exclude[$k] = $v;
        }

        //If API type disabled then abort
        if (! hs_setting('cHD_WSPUBLIC')) {
            $this->disabled = true;
            header('HTTP/1.1 '.$this->error_header);
            if ($this->output_type == 'json') {
                header('Content-Type: text/javascript;');
                echo '{"reply":"Public API not enabled"}';
            } elseif ($this->output_type == 'php') {
                header('Content-Type: text/html; charset=UTF-8');
                echo serialize('Public API not enabled');
            } else {
                header('Content-type: text/xml');
                echo '<?xml version="1.0" encoding="UTF-8"?><reply>Public API not enabled</reply>';
            }
        }
    }

    /**
     * Return all the requests for this email account.
     * @GET sEmail (REQUIRED)
     * @GET sPassword (REQUIRED)
     */
    public function customer_getRequests()
    {
        $this->root_tag = 'requests';

        $email = $this->_GET('sEmail');
        $password = $this->_GET('sPassword');

        if (! $this->in_error()) {
            $xLogin = false;
            //Validate customer
            if (hs_setting('cHD_PORTAL_LOGIN_AUTHTYPE') == 'blackbox') {
                //Include the black box file
                if (file_exists(customCodePath('BlackBoxPortal.php'))) {
                    require_once customCodePath('BlackBoxPortal.php');

                    if (function_exists('BlackBoxPortal')) {
                        $bbcheck = BlackBoxPortal($email, $password);
                        if ($bbcheck) {
                            //Set the email address
                            $email = trim($bbcheck);

                            //Get the login ID
                            $xLogin = $GLOBALS['DB']->GetOne('SELECT xLogin FROM HS_Portal_Login WHERE sEmail = ?', [trim($bbcheck)]);

                            //If no login ID then let's build them an account since we know from BB that they are a valid user
                            if (! $xLogin) {
                                apiPortalAddLoginIfNew($email);
                                $xLogin = $GLOBALS['DB']->GetOne('SELECT xLogin FROM HS_Portal_Login WHERE sEmail = ?', [trim($bbcheck)]);
                            }
                        }
                    }
                }
            } else { //HS internal login
                //Set the email address
                $email = trim($email);
                //Get the login ID
                $portal_user = $GLOBALS['DB']->GetRow('SELECT xLogin,sPasswordHash FROM HS_Portal_Login WHERE sEmail = ?', [$email]);
                $hasher = new PasswordHash(4, false);
                if ($hasher->CheckPassword(trim($password), $portal_user['sPasswordHash'])) {
                    $xLogin = $portal_user['xLogin'];
                } else {
                    $xLogin = false;
                }
            }

            if ($xLogin) {
                $data = apiPortalRequestHistoryForEmail($email, hs_setting('cHD_PORTAL_LOGIN_SEARCHONTYPE'));

                foreach ($data as $k=>$v) {
                    $this->result['request'][$k] = $v;
                    $this->result['request'][$k]['tNote'] = strip_tags($this->result['request'][$k]['tNote']); //Strip any HTML
                    $this->result['request'][$k] = $this->_stripColsFromArray($this->result['request'][$k], array_merge($this->col_exclude['customer.getRequests'], ['iLastReadCount', 'sRequestHash', 'xMailboxToSendFrom']));
                }
            } else {
                $this->_error(104);
            }
        }
    }

    /**
     * Create a request.
     * @POST tNote (REQUIRED)
     * @POST xCategory
     * @POST sFirstName
     * @POST sLastName
     * @POST sUserId
     * @POST sEmail
     * @POST sPhone
     * @POST fUrgent
     * @POST FileX_sFilename
     * @POST FileX_sFileMimeType
     * @POST FileX_bFileBody
     * @POST CustomX
     */
    public function request_create()
    {
        $this->root_tag = 'request';
        include_once cBASEPATH.'/helpspot/lib/class.multiportal.php';
        include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        $time = time();
        $customFields = apiGetCustomFields();

        //Setup array of request data
        $data = ['sub_create'	=> 'yes', //Tell api call that this is a create request
                       'fOpenedVia'	=> $this->_POST('fOpenedVia', 6),
                       'fPublic'	=> 1,
                       'dtGMTOpened'=> $time,
                       'tBody'		=> $this->_POST('tNote'),
                       'xCategory'	=> $this->_POST('xCategory', 0),
                       'xPortal'	=> $this->_POST('xPortal', 0),
                       'sFirstName'	=> $this->_POST('sFirstName', ''),
                       'sLastName'	=> $this->_POST('sLastName', ''),
                       'sUserId'	=> $this->_POST('sUserId', ''),
                       'sEmail'		=> $this->_POST('sEmail', ''),
                       'sPhone'		=> $this->_POST('sPhone', ''),
                       'fUrgent'	=> $this->_POST('fUrgent', 0), ];

        //Add custom fields
        if (is_array($customFields)) {
            foreach ($customFields as $k=>$v) {
                if ($v['isPublic']) {
                    $data['Custom'.$v['xCustomField']] = $this->_POST('Custom'.$v['xCustomField'], '');
                }
            }
        }

        //Make sure API created requests for a custom portal get the correct send from
        if ($data['xPortal'] > 0) {
            $portal = new hs_multiportal($data['xPortal']);
            $mb = apiGetMailbox($portal->xMailboxToSendFrom);
            $data['xMailboxToSendFrom'] = $mb['xMailbox']; //make sure we send from this mailbox when a staffer updates
        }

        if (! $this->in_error()) {
            $files='';
            //Process Request
            $request = apiProcessRequest('', $data, $files, __FILE__, __LINE__);

            if ($request && ! isset($request['errorBoxText'])) {
                //Add documents if any sent
                for ($c = 1; $c < 10; $c++) {
                    $file = 'File'.$c;
                    if (! empty($_POST[$file.'_sFilename']) && ! empty($_POST[$file.'_sFileMimeType']) && ! empty($_POST[$file.'_bFileBody'])) {
                        $data = ['sFilename'		=> $this->_POST($file.'_sFilename', ''),
                                       'sFileMimeType'	=> $this->_POST($file.'_sFileMimeType', ''),
                                       'blobFile'		=> $this->_POST($file.'_bFileBody', ''), ];

                        //Add document
                        $msgFiles = [
                            ['name' =>$data['sFilename'],
                            'mimetype' =>$data['sFileMimeType'],
                            'body' =>base64_decode($data['blobFile']), ]
                        ];

                        apiAddDocument($request['reqid'], $msgFiles, $request['xRequestHistory'], __FILE__, __LINE__);
                    }
                }
            }
        }

        if ($request && ! isset($request['errorBoxText'])) {
            $this->result = ['xRequest'=>$request['reqid'], 'accesskey'=>$request['reqid'].$request['sRequestPassword']];
        } elseif (isset($request['errorBoxText'])) {
            $this->_error(207, $request['errorBoxText']);
        } else {
            $this->_error(206);
        }
    }

    /**
     * Update a request.
     * @POST accesskey (REQUIRED)
     * @POST tNote (REQUIRED)
     */
    public function request_update()
    {
        $this->root_tag = 'request';
        $time = time();
        $customFields = apiGetCustomFields();

        //Parse the access key that's been passed in
        $pkey = parseAccessKey($this->_POST('accesskey'));

        //Get the request information
        if (is_numeric($pkey['xRequest'])) {
            $request = apiGetRequest($pkey['xRequest']);
        }

        //Check if this is a merged request
        if ($request == false && $merged_id = apiCheckIfMerged($pkey['xRequest'])) {
            $this->_error(208, '', ['xRequest' => $merged_id]);

            return;
        }

        //Check that the access key is valid
        if (! $this->in_error() && $request && $request['sRequestPassword'] == $pkey['sRequestPassword']) {

            if (Request::reachedHistoryLimit($pkey['xRequest'])) {
                return $this->_error(106);
            }

            //Setup array of request data
            $data = ['tNote' => $this->_POST('tNote')];

            if (! $this->in_error()) {
                //If request was already closed then we should reopen.
                if (intval($request['fOpen']) === 0) {
                    // Reopen request
                    $request['fOpen'] = 1;
                    $request['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);
                    $request['dtGMTOpened'] = date('U');	//current dt
                    //if the user isn't active then send to inbox
                    $ustatus = apiGetUser($request['xPersonAssignedTo']);
                    if ($ustatus['fDeleted'] == 1) {
                        $request['xPersonAssignedTo'] = 0;
                    }

                    $update = new requestUpdate($pkey['xRequest'], $request, 0, __FILE__, __LINE__);
                    $update->notify = false; //notify below instead
                    $update->skipTrigger = true; // Call triggers below
                    $reqResult = $update->checkChanges();
                }

                //If request was in trash we should remove it
                if (intval($request['fTrash']) === 1) {
                    // Reopen request
                    $request['fTrash'] = 0;
                    $request['dtGMTTrashed'] = 0;
                    $request['dtGMTOpened'] = date('U');	//current dt

                    $update = new requestUpdate($pkey['xRequest'], $request, 0, __FILE__, __LINE__);
                    $update->notify = false; //notify below instead
                    $update->skipTrigger = true; // Call triggers below
                    $reqResult = $update->checkChanges();
                }

                //Add note
                $reqHis = apiAddRequestHistory([
                    'xPerson' => 0,
                    'xRequest' => $pkey['xRequest'],
                    'dtGMTChange' => $time,
                    'fPublic' => 1,
                    'tNote' =>$data['tNote'],
                ]);

                app('events')->flush('request.history.create');

                // Run any triggers
                apiRunTriggers($request['xRequest'], $request, $request, $data['tNote'], 1, 0, 2, __FILE__, __LINE__);

                //Add documents if any
                if ($reqHis) {
                    //Add documents if any sent
                    for ($c = 1; $c < 10; $c++) {
                        $file = 'File'.$c;
                        if (! empty($_POST[$file.'_sFilename']) && ! empty($_POST[$file.'_sFileMimeType']) && ! empty($_POST[$file.'_bFileBody'])) {
                            $data = ['sFilename'		=> $this->_POST($file.'_sFilename', ''),
                                           'sFileMimeType'	=> $this->_POST($file.'_sFileMimeType', ''),
                                           'blobFile'		=> $this->_POST($file.'_bFileBody', ''), ];

                            //Add document
                            $msgFiles = [
                                ['name' =>$data['sFilename'],
                                'mimetype' =>$data['sFileMimeType'],
                                'body' =>base64_decode($data['blobFile']), ]
                            ];

                            apiAddDocument($request['xRequest'], $msgFiles, $reqHis, __FILE__, __LINE__);
                        }
                    }
                }

                //Send notification from here instead of from within addreqhis so that we can send log and body in one email
                if (isset($reqHis) || isset($reqResult['xRequestHistory'])) {
                    //Array if both set else just the ID
                    if (isset($reqHis) && isset($reqResult['xRequestHistory'])) {
                        $ids = [$reqHis, $reqResult['xRequestHistory']];	//first is body, second is log
                    } else {
                        $ids = isset($reqHis) ? $reqHis : $reqResult['xRequestHistory'];
                    }

                    $notifier = new hs_notify($pkey['xRequest'], $ids, 0, __FILE__, __LINE__);
                    $notifier->SetRequestType('existing');
                    $notifier->Notify();
                }
            }
        }

        if (isset($reqHis)) {
            $this->result = ['xRequest'=>$pkey['xRequest']];
        } else {
            $this->_error(210);
        }
    }

    /**
     * Get a list of the public categories.
     */
    public function request_getCategories()
    {
        $this->root_tag = 'categories';

        $result = apiGetPublicCategories();
        $custom_fields = apiGetCustomFields(); //Get custom fields for public check below

        $out = rsToArray($result, 'xCategory', false);

        foreach ($out as $k=>$v) {
            //Custom fields
            if (! empty($v['sCustomFieldList'])) {
                $checked_fields = [];
                $fields = hs_unserialize($v['sCustomFieldList']);

                foreach ($fields as $k=>$fid) {
                    if (isset($custom_fields[$fid]) && $custom_fields[$fid]['isPublic']) {
                        $checked_fields[] = $fid;
                    }
                }

                $out[$v['xCategory']]['sCustomFieldList'] = ['xCustomField'=>$checked_fields];
            } else {
                $out[$v['xCategory']]['sCustomFieldList'] = null;
            }

            $out[$v['xCategory']] = $this->_stripColsFromArray($out[$v['xCategory']], array_merge($this->col_exclude['request.getCategories'], ['sPersonList', 'fAutoAssignTo', 'xPersonDefault', 'fAllowPublicSubmit', 'fDeleted']));
        }

        $this->result = ['category'=>$out];
    }

    /**
     * Get a list of the public custom fields.
     */
    public function request_getCustomFields()
    {
        $this->root_tag = 'customfields';

        $out = [];
        $fields = apiGetCustomFields();
        if (is_array($fields)) {
            foreach ($fields as $v) {
                if ($v['isPublic']) {
                    $out[$v['fieldID']] = $v;
                    $out[$v['fieldID']]['fieldName'] = hs_htmlspecialchars($v['fieldName']);

                    if (! hs_empty($out[$v['fieldID']]['listItems']) && $out[$v['fieldID']]['fieldType'] != 'drilldown') {
                        $list = hs_unserialize($v['listItems']);
                        if (! empty($list)) {
                            $out[$v['fieldID']]['listItems'] = ['item'=>$list];
                        } else {
                            $out[$v['fieldID']]['listItems'] = '';
                        }
                    } else {
                        $out[$v['fieldID']]['listItems'] = '';
                    }

                    $out[$v['fieldID']] = $this->_stripColsFromArray($out[$v['fieldID']], array_merge($this->col_exclude['request.getCustomFields'], ['sAjaxUrl', 'fieldID', 'isPublic']));
                }
            }
        }

        $this->result = ['field'=>$out];
    }

    /**
     * Get a request.
     * @GET accesskey (required)
     */
    public function request_get()
    {
        $this->root_tag = 'request';

        //Parse the access key that's been passed in
        $pkey = parseAccessKey($this->_GET('accesskey'));

        //Get the request information
        if (is_numeric($pkey['xRequest'])) {
            $request = apiGetRequest($pkey['xRequest']);
        }

        //Check if this is a merged request
        if ($request == false && $merged_id = apiCheckIfMerged($pkey['xRequest'])) {
            $this->_error(208, '', ['xRequest' => $merged_id]);

            return;
        }

        //Check that the access key is valid
        if ($request && $request['sRequestPassword'] == $pkey['sRequestPassword']) {
            $allStaff = apiGetAllUsersComplete();
            $allstatus = apiGetAllStatus();
            $category = apiGetCategory($request['xCategory']);

            $st = rsToArray($allstatus, 'xStatus');

            $assigneduser = apiGetUser($request['xPersonAssignedTo']);

            $request['sStatus'] = $st[$request['xStatus']]['sStatus'];
            $request['sCategory'] = isset($category['sCategory']) ? $category['sCategory'] : '';
            $request['sAssignedToFirstName'] = $assigneduser['sFname'];
            $request['sAssignedToLastName'] = $assigneduser['sLname'];

            $reqHistRes = apiGetRequestHistory($pkey['xRequest']);

            //Loop over history and find all docs to return
            $historyDocs = [];
            while ($row = $reqHistRes->FetchRow()) {
                if ($row['xDocumentId'] != 0) {
                    $historyDocs[$row['xDocumentId']]['dtGMTChange'] = $row['dtGMTChange'];
                    $historyDocs[$row['xDocumentId']]['sFileMimeType'] = $row['sFileMimeType'];
                    $historyDocs[$row['xDocumentId']]['sFilename'] = $row['sFilename'];
                    $historyDocs[$row['xDocumentId']]['sCID'] = $row['sCID'];
                }
            }
            $reqHistRes->Move(0);

            while ($row = $reqHistRes->FetchRow()) {
                if ($row['xDocumentId'] == 0) {
                    $row['files'] = [];

                    $row['tNote'] = replaceInlineImages($row['tNote'], $historyDocs, $this->_GET('accesskey'), $row['xRequestHistory']);
                    $row['tNote'] = formatNote($row['tNote'], $row['xRequestHistory'], ($row['fNoteIsHTML'] ? 'is_html' : 'html'), false);

                    if ($row['fPublic'] == 1) {
                        //Add name
                        if ($row['xPerson'] > 0) {
                            $row['firstname'] = hs_htmlspecialchars($allStaff[$row['xPerson']]['sFname']);
                            $row['lastname'] = hs_htmlspecialchars($allStaff[$row['xPerson']]['sLname']);
                        } elseif ($row['xPerson'] == -1) {
                            $row['firstname'] = lg_systemnameportal;
                            $row['lastname'] = '';
                        } else {
                            $row['firstname'] = $request['sFirstName'];
                            $row['lastname'] = $request['sLastName'];
                        }

                        //ADD FILE INFORMATION TO RETURNED ARRAY
                        $file_exclude = [];
                        foreach ($this->col_exclude['request.get'] as $k=>$v) {
                            if (strpos($v, 'request_history.file.') !== false) {
                                $file_exclude[] = str_replace('request_history.file.', '', $v);
                            }
                        }
                        if (count($historyDocs)) {
                            foreach ($historyDocs as $docid=>$file) {
                                if ($file['dtGMTChange'] == $row['dtGMTChange']) {
                                    $file['xDocumentId'] = $docid;
                                    $file['url'] = cHOST.'/index.php?pg=file&from=3&id='.$docid.'&reqid='.$row['xRequest'].$request['sRequestPassword'].'&reqhisid='.$row['xRequestHistory'];
                                    $row['files']['file'][] = $this->_stripColsFromArray($file, array_merge($file_exclude, ['dtGMTChange']));
                                }
                            }
                        }

                        //Remove unneeded fields from row
                        unset($row['sFilename']);
                        unset($row['sFileMimeType']);
                        unset($row['xDocumentId']);

                        //Set row data
                        $rh_exclude = [];
                        foreach ($this->col_exclude['request.get'] as $k=>$v) {
                            if (strpos($v, 'request_history.') !== false) {
                                $rh_exclude[] = str_replace('request_history.', '', $v);
                            }
                        }
                        $request['request_history']['item'][] = $this->_stripColsFromArray($row, array_merge($rh_exclude, ['iTimerSeconds', 'tLog', 'tEmailHeaders', 'fPublic']));
                    }
                }
            }

            $this->result = $this->_stripColsFromArray($request, array_merge($this->col_exclude['request.get'], ['iLastReadCount', 'sRequestHash']));
        } else {
            $this->_error(102);
        }
    }

    /**
     * List all public KB's.
     */
    public function kb_list()
    {
        $this->root_tag = 'books';

        $books = apiGetBooks(0);
        $this->result = $this->_rsToOutputArray($books, 'book', array_merge($this->col_exclude['kb.list'], ['tEditors', 'fPrivate']));
    }

    /**
     * Get a book.
     * @GET xBook (REQUIRED)
     */
    public function kb_get()
    {
        $this->root_tag = 'book';

        $book = apiGetBook($this->_GET('xBook'));
        if ($book['fPrivate'] == 0) {
            $this->result = $this->_stripColsFromArray($book, array_merge($this->col_exclude['kb.get'], ['tEditors', 'fPrivate']));
        }
    }

    /**
     * Get a full TOC for a book.
     * @GET xBook (REQUIRED)
     * @GET fWithPageHTML //returns the full page HTML
     */
    public function kb_getBookTOC()
    {
        $this->root_tag = 'toc';

        $this->result = [];
        //Build tree and find book
        $tree = apiBuildChapPageTree($this->_GET('xBook'), false);
        $book = apiGetBook($this->_GET('xBook'));

        //Chaps
        $chaps = apiTocChaps($tree);
        //Build chapters and pages array
        if ($book['fPrivate'] == 0 && is_array($chaps) && count($chaps) > 0) {
            foreach ($chaps as $chapid=>$c) {
                $c = $this->_stripColsFromArray($c, array_merge($this->col_exclude['kb.getBookTOC'], ['type', 'class', 'fHidden', 'fPrivate']));

                $pages = apiTocPages($tree, $chapid);

                foreach ($pages as $k=>$page) {
                    if (! $this->_GET('fWithPageHTML', 0)) {
                        $exclude = ['tPage'];
                    }
                    $c['pages']['page'][$page['xPage']] = $this->_stripColsFromArray($page, array_merge($this->col_exclude['kb.getPage'], ['type', 'class', 'fHidden', 'fPrivate'], $exclude));

                    //Add in related pages
                    $related = apiGetRelatedPages($page['xPage']);
                    if (hs_rscheck($related)) {
                        $related = rsToArray($related, 'xRelatedPage', false);
                        foreach ($related as $rp) {
                            unset($rp['xPage']);
                            $c['pages']['page'][$page['xPage']]['relatedpages']['relatedpage'][] = $rp;
                        }
                    }

                    //Add in tags
                    $tags = apiGetTags($page['xPage']);
                    if ($tags) {
                        foreach ($tags as $tag) {
                            $c['pages']['page'][$page['xPage']]['tags']['tag'][] = $tag;
                        }
                    }
                }

                //Save chapter
                $this->result['chapter'][] = $c;
            }
        }
    }

    /**
     * Get a page.
     * @GET xPage (REQUIRED)
     */
    public function kb_getPage()
    {
        $this->root_tag = 'page';

        $page = apiGetPage($this->_GET('xPage'));
        $chapter = apiGetChapter($page['xChapter']);
        $tree = apiBuildChapPageTree($chapter['xBook'], false);

        $page = apiPageFromTree($tree, $this->_GET('xPage'));

        //Add in related pages
        $related = apiGetRelatedPages($this->_GET('xPage'));
        if (hs_rscheck($related)) {
            $related = rsToArray($related, 'xRelatedPage', false);
            foreach ($related as $rp) {
                unset($rp['xPage']);
                $page['relatedpages']['relatedpage'][] = $rp;
            }
        }

        //Add in tags
        $tags = apiGetTags($this->_GET('xPage'));
        if ($tags) {
            foreach ($tags as $tag) {
                $page['tags']['tag'][] = $tag;
            }
        }

        //Add in docs
        $docs = apiGetPageDocs($this->_GET('xPage'));

        if (hs_rscheck($docs)) {
            $docs = rsToArray($docs, 'xDocumentId', false);
            foreach ($docs as $rp) {
                $page['files']['file'][] = 'index.php?pg=file&from=2&id='.$rp['xDocumentId'];
            }
        }

        if ($page['fPrivate'] == 0 && $page['fHidden'] == 0) {
            $this->result = $this->_stripColsFromArray($page, array_merge($this->col_exclude['kb.getPage'], ['type', 'class', 'fHidden', 'fPrivate']));
        }
    }

    /**
     * Search the KBs.
     * @GET q (REQUIRED)
     */
    public function kb_search()
    {
        $this->root_tag = 'pages';

        $this->result = $this->_kb_search();
    }

    public function _kb_search()
    {
        $args['area'] = 'kb';
        $args['q'] = $this->_GET('q');

        //Search
        $result = apiKbSearch($args, $advanced = false);

        //Format multidimensional array as RS
        $srs = new array2recordset;
        $srs->init($result);

        return $this->_rsToOutputArray($srs, 'page', array_merge($this->col_exclude['kb.search'], ['icon', 'date', 'title', 'score']));
    }

    /**
     * Vote a page as helpful.
     * @GET xPage (REQUIRED)
     */
    public function kb_voteHelpful()
    {
        $this->root_tag = 'page';

        $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iHelpful = iHelpful+1 WHERE xPage = ?', [$this->_GET('xPage')]);
        $count = $GLOBALS['DB']->GetOne('SELECT iHelpful FROM HS_KB_Pages WHERE xPage = ?', [$this->_GET('xPage')]);

        $this->result = ['iHelpful'=>$count];
    }

    /**
     * Vote a page as not helpful.
     * @GET xPage (REQUIRED)
     */
    public function kb_voteNotHelpful()
    {
        $this->root_tag = 'page';

        $GLOBALS['DB']->Execute('UPDATE HS_KB_Pages SET iNotHelpful = iNotHelpful+1 WHERE xPage = ?', [$this->_GET('xPage')]);
        $count = $GLOBALS['DB']->GetOne('SELECT iNotHelpful FROM HS_KB_Pages WHERE xPage = ?', [$this->_GET('xPage')]);

        $this->result = ['iNotHelpful'=>$count];
    }

    /**
     * Return labels for columns.
     */
    public function util_getFieldLabels()
    {
        $this->root_tag = 'labels';

        $this->result = [];
        foreach ($GLOBALS['filterCols'] as $k=>$v) {
            if (! in_array($k, ['view', 'isunread', 'takeitfilter', 'takeit', 'livelookup'])) {
                $this->result[$k] = (! empty($v['label']) ? $v['label'] : $v['label2']);
            }
        }
    }
}
