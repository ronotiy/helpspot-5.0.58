<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}
/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.users.lib.php';
include cBASEPATH.'/helpspot/lib/class.requestupdate.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$pagetitle = lg_workspace_title;
$htmldirect = 0;
$tab = 'nav_workspace';
$subtab = '';
$sortby = isset($_GET['sortby']) ? (new HS\Http\Security)->parseAndCleanOrder($_GET['sortby']) : '';
$sortord = isset($_GET['sortord']) ? (new HS\Http\Security)->cleanOrderDirection($_GET['sortord']) : '';
$paginate = isset($_GET['paginate']) && is_numeric($_GET['paginate']) ? $_GET['paginate'] : 0;
$show = isset($_GET['show']) ? trim($_GET['show']) : $user['sWorkspaceDefault'];
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => $show]);
$GLOBALS['basepgurl'] = $basepgurl;
$filteroptions = '';
$rsslink = '';
$exportlink = '';
$noresults = '';

$efb = (session('error'))
    ?  errorBox(session('error'))
    : '';

// Set feedback text
$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

//Set cookie with current queue. Used to redirect after closing request
setcookie('last_queue', $show, 0, dirname($_SERVER['REQUEST_URI']));

/*****************************************
CHECK FOR NEW HELPSPOT VERSION
*****************************************/
// All user filters
$filters = apiGetAllFilters($user['xPerson'], 'all');
// Category list
$catsList = apiGetAllCategories(0);
// Get all users, include how many requests are assigned to them
$allStaff = apiGetAssignStaff();
// Get active status items
$activeStatus = apiGetActiveStatus();

/*****************************************
ACTION
*****************************************/
if (isset($_POST['vmode']) && $_POST['vmode'] == 'batch') {
    //handle different actions
    if (isset($_POST['checktable']) && is_array($_POST['checktable'])) {

        //Handle batch responding
        if ($_POST['action'] == 'respond') {
            if (hs_setting('cHD_BATCHRESPOND')) {
                return redirect('admin?pg=request&batch[]=' . implode('&batch[]=', $_POST['checktable']));
            }
        }

        foreach ($_POST['checktable'] as $k=>$v) {

            //$GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

            if (is_numeric($_POST['action']) && is_numeric($_POST['optionField'])) {	//Set reqs to a category/user
                $fm = apiGetRequest($v);
                $fm['dtGMTOpened'] = date('U');
                //if a new category then set rep tags to empty else keep old tags
                if ($fm['xCategory'] != $_POST['action']) {
                    $fm['reportingTags'] = [];
                } else {
                    $fm['reportingTags'] = array_keys(apiGetRequestRepTags($v));
                }

                $fm['xCategory'] = $_POST['action'];
                $fm['xPersonAssignedTo'] = $_POST['optionField'];
                $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                $reqResult = $update->checkChanges();
            } elseif ($_POST['action'] == 'spam') {		//Mark as spam
                if (! perm('fCanManageSpam')) {
                    die();
                }

                $fm = apiGetRequest($v);
                $spamreqhis = apiGetInitialRequest($v);
                if (! hs_empty($spamreqhis['tEmailHeaders']) || $fm['fOpenedVia'] == 7) {
                    $fm['xStatus'] = hs_setting('cHD_STATUS_SPAM', 2);				//set to spam
                    $fm['xPersonAssignedTo'] = 0;	//no assignee
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();
                } else {
                    $efb = lg_workspace_notemail;
                }

                \Facades\HS\Cache\Manager::forgetFilter('spam');
            } elseif ($_POST['action'] == 'notspam') {		//Mark as not spam
                if (! perm('fCanManageSpam')) {
                    die();
                }

                $fm = apiGetRequest($v);
                $fm['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);				//set back to active
                $fm['xPersonAssignedTo'] = 0;	//no assignee
                $fm['dtGMTOpened'] = date('U');	//current dt
                $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                $reqResult = $update->checkChanges();
            } elseif ($_POST['action'] == 'deletespam') {	//Delete spam
                if (! perm('fCanManageSpam')) {
                    die();
                }

                //get request
                $spamreq = apiGetRequest($v);
                $spamreqhis = apiGetInitialRequest($v);

                //Delete form spam
                if ($spamreq['xStatus'] == hs_setting('cHD_STATUS_SPAM', 2) && $spamreq['fOpenedVia'] == 7) {
                    $text['body'] = stripFormBlurbs($spamreqhis['tNote']);
                    $text['headers'] = $spamreq['sFirstName'].' '.$spamreq['sLastName'].' '.$spamreq['sEmail'];

                    //train as spam
                    $filter = new UserScape_Bayesian_Classifier($text, 'request');
                    $filter->Train('-1');

                    //delete spam
                    apiDeleteRequest($v);
                    logMsg('DELETED from spam: '.$v);
                } elseif (! hs_empty($spamreqhis['tEmailHeaders'])) {
                    $headers = hs_unserialize($spamreqhis['tEmailHeaders']);
                    //train as spam
                    $text['subject'] = $spamreq['sTitle'];
                    $text['from'] = $spamreq['sFirstName'].' '.$spamreq['sLastName'];
                    $text['body'] = $spamreqhis['tNote'];

                    $spam_reply = hs_parse_email_header((isset($headers['reply-to']) ? $headers['reply-to'] : ''));	//get reply-to header
                    $spam_from = hs_parse_email_header((isset($headers['from']) ? $headers['from'] : ''));	//get from header
                    $text['headers'] = $spam_reply['mailbox'].' '.$spam_reply['host'].' ';
                    $text['headers'] .= $spam_from['mailbox'].' '.$spam_from['host'];

                    //train as spam
                    $filter = new UserScape_Bayesian_Classifier($text);
                    $filter->Train('-1');

                    //delete spam
                    apiDeleteRequest($v);
                    logMsg('DELETED from spam: '. $v);
                }
            } elseif ($_POST['action'] == 'close') {
                if (hs_setting('cHD_BATCHCLOSE')) {
                    $fm = apiGetRequest($v);
                    $fm['xStatus'] = $_POST['optionField'];
                    $fm['fOpen'] = 0;
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();
                }
            } elseif ($_POST['action'] == 'status') {
                $fm = apiGetRequest($v);
                // If it's being set back to active status then fOpen also needs to be set.
                if ($_POST['optionField'] == '1') {
                    $fm['fOpen'] = 1;
                }
                $fm['xStatus'] = $_POST['optionField'];

                // Ensure the xStatus actually exists. See #417
                // basically something is causing xStatus to be assigned to the request id, this should prevent that.
                $allstatus = apiGetAllStatus();
                $st = rsToArray($allstatus, 'xStatus');
                $hasStatus = false;
                foreach ($st as $item) {
                    if ($item['xStatus'] == $fm['xStatus']) {
                        $hasStatus = true;

                        break;
                    }
                }
                if (! $hasStatus) {
                    $fm['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);
                }

                $fm['dtGMTOpened'] = date('U');	//current dt
                $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                $reqResult = $update->checkChanges();
            } elseif ($_POST['action'] == 'merge' && is_numeric($_POST['optionField'])) {
                apiMergeRequests($v, $_POST['optionField']);
            } elseif ($_POST['action'] == 'inbox') {
                if (perm('fViewInbox')) {
                    $fm = apiGetRequest($v);
                    $fm['xPersonAssignedTo'] = 0;	//back to inbox
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();
                }
            } elseif ($_POST['action'] == 'trash') {
                if (perm('fCanManageTrash')) {
                    $fm = apiGetRequest($v);
                    $fm['fTrash'] = 1;
                    $fm['dtGMTTrashed'] = date('U');
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();

                    \Facades\HS\Cache\Manager::forgetFilter('trash');
                }
            } elseif ($_POST['action'] == 'nottrash') {
                if (perm('fCanManageTrash')) {
                    $fm = apiGetRequest($v);
                    $fm['fTrash'] = 0;
                    $fm['dtGMTTrashed'] = 0;
                    $fm['dtGMTOpened'] = date('U');	//current dt
                    $update = new requestUpdate($v, $fm, $user['xPerson'], __FILE__, __LINE__);
                    $reqResult = $update->checkChanges();
                }
            }

            //$GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
        }

        if( $efb ) {
            return redirect()->to($_POST['from'])
                ->with('error', $efb);
        } else {
            return redirect()->to($_POST['from']);
        }
    }
}

/*****************************************
PAGE ELEMENTS
*****************************************/
//Category list
$categoryOptionList = categorySelectOptions($catsList, false);

// Create form for batch changes
$footerform = '
	<input type="hidden" name="vmode" value="batch" />
	<input type="hidden" name="from" value="'.$basepgurl.'" />
	<input type="hidden" id="action" name="action" value="" />
	<input type="hidden" name="optionField" id="optionField" />';

$footerform .= '<div class="thin-disabled" id="batch_action_buttons" style="display:none;">';
if ($show == 'spam') {
    $footerform .= '<button class="btn" type="button" onclick="$(\'action\').value=\'deletespam\';doBatchSubmit();">'.lg_workspace_markdelspam.'</button>';
    $footerform .= '<button class="btn" type="button" onclick="$(\'action\').value=\'notspam\';doBatchSubmit();">'.lg_workspace_marknotspam.'</button>';
} elseif ($show == 'trash') {
    $footerform .= '<button class="btn" type="button" onclick="$(\'action\').value=\'nottrash\';doBatchSubmit();">'.lg_workspace_nottrash.'</button>';
} else {
    $footerform .= '<button class="btn" type="button" id="thin-button-reassign" onclick="">'.lg_workspace_moveto.'</button><div style="display:none;" id="button-reassign-option"><select id="button-category-select" onchange="$(\'action\').value=getSelectVal(this);personselect($(this).next(1));"><option value="">'.lg_workspace_selectcat.'</option>'.$categoryOptionList.'</select><br /><select onchange="$(\'optionField\').value=getSelectVal(this);doBatchSubmit();"></select></div>';
    if (hs_setting('cHD_BATCHCLOSE')) {
        $footerform .= '<button class="btn" type="button" id="thin-button-close" onclick="$(\'action\').value=\'close\';">'.lg_workspace_close.'</button><div style="display:none;" id="button-close-option"><select id="button-close-select" onchange="$(\'optionField\').value=getSelectVal(this);doBatchSubmit();"></select></div>';
    }
    if (hs_setting('cHD_BATCHRESPOND') && (perm('fCanBatchRespond'))) {
        $footerform .= '<button class="btn" type="button" id="thin-button-batch" onclick="$(\'action\').value=\'respond\';$(\'rsform_1\').submit();">'.lg_workspace_respond.'</button>';
    }
    if (perm('fCanMerge')) {
        $footerform .= '<button class="btn" type="button" id="thin-button-merge" onclick="$(\'action\').value=\'merge\';">'.lg_workspace_merge.'</button><div style="display:none;" id="button-merge-option"><select id="button-merge-select" onchange="$(\'optionField\').value=getSelectVal(this);doBatchSubmit();"></select></div>';
    }
    $footerform .= '<button class="btn" type="button" id="thin-button-status" onclick="$(\'action\').value=\'status\';">'.lg_workspace_changestatus.'</button><div style="display:none;" id="button-status-option"><select id="button-status-select" onchange="$(\'optionField\').value=getSelectVal(this);doBatchSubmit();"></select></div>';
    if (perm('fCanManageTrash')) {
        $footerform .= '<button class="btn" type="button" onclick="$(\'action\').value=\'trash\';doBatchSubmit();">'.lg_workspace_movetotrash.'</button>';
    }
    if (perm('fCanManageSpam')) {
        $footerform .= '<button class="btn" type="button" onclick="$(\'action\').value=\'spam\';doBatchSubmit();">'.lg_workspace_markspam.'</button>';
    }
}
$footerform .= '</div>';

/*****************************************
PAGE TEMPLATE COMPONENTS
*****************************************/
//ADD RSS FEED AND LINK
if (hs_setting('cHD_FEEDSENABLED')) {
    $rsslink = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => $show]);
    $headscript .= '<link rel="alternate" type="application/rss+xml" title="RSS" href="'.$rsslink.'">';	//RSS AutoDiscovery
}

if ($show != 'subscriptions' && $show != 'reminders') {
    $pageargs = (isset($_GET['paginate']) ? '&paginate='.$_GET['paginate'] : '');
    $exportlink .= str_replace('pg=workspace', 'pg=excel_filter', $basepgurl).'&type=excel'.$pageargs;
}

switch ($show) {
    case 'inbox':
        // Security: don't show inbox to guests or L2
        if (! perm('fViewInbox')) {
            die();
        }

        $ft = new hs_filter();
        // Override sort if needed
        if (! empty($sortby)) {
            $ft->overrideSort = $sortby.' '.$sortord;
        }
        $ft->paginate = $paginate;
        $ft->useSystemFilter('inbox');
        $ftrs = $ft->outputResultSet();
        foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];

            //Override widths if needed
            if (isset($ft->filterDef['displayColumnsWidths'][$v])) {
                $cols[$v]['width'] = $ft->filterDef['displayColumnsWidths'][$v];
            }
        }

        //Get full count
        $inboxCount = new hs_filter('', true);
        $inboxCount->useSystemFilter('inbox');
        $countTotal = $inboxCount->outputCountTotal();

        if($countTotal == 0){
            $noresults = getTip();
        }

        $datatable = recordSetTable($ftrs, $cols,
                            //options
                            ['sortby'=>$sortby,
                                    'sortord'=>$sortord,
                                    'filterid'=>$show,
                                    'paginate'=>$paginate,
                                    'width'=>'100%',
                                    'checkbox'=>'xRequest',
                                    'footer'=>$footerform,
                                    'rsslink'=>$rsslink,
                                    'exportlink'=>$exportlink,
                                    'showing'=>$show,
                                    'page'=>$page,
                                    'noresults'=> $noresults,
                                    'actionmenu' => true,
                                    'showcount'=>$countTotal,
                                    'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                    'title'=>$ft->filterDef['sFilterName'], ], $basepgurl);

        $filteroptions = '<b>'.lg_workspace_filterop.'</b>: <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace.customize', 'area' => 'inbox']).'">'.lg_workspace_customize.'</a> |';

        break;
    case 'myq':
        $ft = new hs_filter();
        // Override sort if needed
        if (! empty($sortby)) {
            $ft->overrideSort = $sortby.' '.$sortord;
        }
        $ft->paginate = $paginate;
        $ft->useSystemFilter('myq');
        $ftrs = $ft->outputResultSet();
        foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];

            //Override widths if needed
            if (isset($ft->filterDef['displayColumnsWidths'][$v])) {
                $cols[$v]['width'] = $ft->filterDef['displayColumnsWidths'][$v];
            }
        }

        //Get full count
        $myCount = new hs_filter('', true);
        $myCount->useSystemFilter('myq');
        $countTotal = $myCount->outputCountTotal();

        if($countTotal == 0){
            $noresults = getTip();
        }

        $datatable = recordSetTable($ftrs,$cols,
                            //options
                            ['sortby'=>$sortby,
                                    'sortord'=>$sortord,
                                    'filterid'=>$show,
                                    'paginate'=>$paginate,
                                    'width'=>'100%',
                                    'checkbox'=>'xRequest',
                                    'footer'=>$footerform,
                                    'rsslink'=>$rsslink,
                                    'exportlink'=>$exportlink,
                                    'showing'=>$show,
                                    'page'=>$page,
                                    'noresults'=>$noresults,
                                    'actionmenu' => true,
                                    'showcount'=>$countTotal,
                                    'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                    'title'=>$ft->filterDef['sFilterName'], ], $basepgurl);

        $filteroptions = '<b>'.lg_workspace_filterop.'</b>: <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace.customize', 'area' => 'myq']).'">'.lg_workspace_customize.'</a> |';

        break;
    case 'spam':
        if (! perm('fCanManageSpam')) {
            die();
        }

        $ft = new hs_filter();
        // Override sort if needed
        if (! empty($sortby)) {
            $ft->overrideSort = $sortby.' '.$sortord;
        }
        $ft->paginate = $paginate;
        $ft->useSystemFilter('spam');
        $ftrs = $ft->outputResultSet();
        foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];
        }

        //Get full count
        $spamCount = new hs_filter('', true);
        $spamCount->useSystemFilter('spam');
        $countTotal = $spamCount->cacheOutputCountTotal('spam', true);

        $datatable = recordSetTable($ftrs,$cols,
                            //options
                            ['sortby'=>$sortby,
                                    'sortord'=>$sortord,
                                    'paginate'=>$paginate,
                                    'width'=>'100%',
                                    'checkbox'=>'xRequest',
                                    'footer'=>$footerform,
                                    'rsslink'=>$rsslink,
                                    'exportlink'=>$exportlink,
                                    'actionmenu' => true,
                                    'showing'=>$show,
                                    'page'=>$page,
                                    'summary'=> (hs_setting('cHD_SPAMFILTER') == 1 ? lg_workspace_spammessage : lg_workspace_spammessageoff),
                                    'showcount'=>$countTotal,
                                    'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                    'title'=>$ft->filterDef['sFilterName'], ], $basepgurl);

        break;
    case 'trash':
        if (! perm('fCanManageTrash')) {
            die();
        }

        $ft = new hs_filter();
        // Override sort if needed
        if (! empty($sortby)) {
            $ft->overrideSort = $sortby.' '.$sortord;
        }
        $ft->paginate = $paginate;
        $ft->useSystemFilter('trash');
        $ftrs = $ft->outputResultSet();
        foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];
        }

        //Get full count
        $trashCount = new hs_filter('', true);
        $trashCount->useSystemFilter('trash');
        $countTotal = $trashCount->cacheOutputCountTotal('trash', true);

        $datatable = recordSetTable($ftrs,$cols,
                            //options
                            ['sortby'=>$sortby,
                                    'sortord'=>$sortord,
                                    'paginate'=>$paginate,
                                    'width'=>'100%',
                                    'checkbox'=>'xRequest',
                                    'footer'=>$footerform,
                                    'rsslink'=>$rsslink,
                                    'exportlink'=>$exportlink,
                                    'showing'=>$show,
                                    'page'=>$page,
                                    'actionmenu' => true,
                                    'summary'=> (hs_setting('cHD_DAYS_TO_LEAVE_TRASH') == 0 ? lg_workspace_trashmessagenever : sprintf(lg_workspace_trashmessage, hs_setting('cHD_DAYS_TO_LEAVE_TRASH').' '.(hs_setting('cHD_DAYS_TO_LEAVE_TRASH') == 1 ? lg_day : lg_days))),
                                    'showcount'=>$countTotal,
                                    'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                    'title'=>$ft->filterDef['sFilterName'], ], $basepgurl);

        break;
    case 'reminders':

        $sorting = getSorting($sortby, $sortord, ['xRequest', 'dtGMTReminder', 'tReminder']);
        $data = apiGetRemindersByPerson($user['xPerson'], $sorting);
        $count = $data ? $data->RecordCount() : 0;

        $datatable = recordSetTable($data,[$GLOBALS['filterCols']['view'],
                                     ['type'=>'string', 'label'=>lg_lookup_filter_remdate, 'sort'=>1, 'width'=>'200', 'fields'=>'dtGMTReminder', 'function'=>'hs_showDate'],
                                     ['type'=>'string', 'label'=>lg_lookup_filter_reminder, 'sort'=>1, 'width'=>'', 'fields'=>'tReminder'], ],
                                    //options
                                    ['sortby'=>$sortby,
                                           'sortord'=>$sortord,
                                           'paginate'=>false,
                                           'showing'=>$show,
                                           'page'=>$page,
                                           'showcount'=>$count,
                                           'actionmenu' => true,
                                           'title'=>lg_reminders,
                                            'rsslink'=>$rsslink,
                                            'exportlink'=>$exportlink,
                                           'width'=>'100%', ], $basepgurl);

        break;
    case 'subscriptions':
        if (perm('fCanViewOwnReqsOnly')) {
            die();
        } //can't subscribe in this case

        $data = apiGetSubscribersByPerson($user['xPerson'], ($sortby . ' ' . $sortord));
        $count = $data ? $data->RecordCount() : 0;

        $datatable = recordSetTable($data,[$GLOBALS['filterCols']['view'],
                                                $GLOBALS['filterCols']['fullname'],
                                                $GLOBALS['filterCols']['reqsummary'], $GLOBALS['filterCols']['age'], ],
                                    //options
                                    ['sortby'=>$sortby,
                                           'sortord'=>$sortord,
                                           'paginate'=>false,
                                           'showing'=>$show,
                                           'page'=>$page,
                                           'showcount'=>$count,
                                           'actionmenu' => true,
                                           'title'=>lg_subscriptions,
                                            'rsslink'=>$rsslink,
                                            'exportlink'=>$exportlink,
                                           'width'=>'100%', ], $basepgurl);

        break;
    default:
        if (is_numeric($show) && isset($filters[$show])) {

            //Set view first
            array_unshift($filters[$show]['displayColumns'], 'view');

            //Make take it show before view
            if ($loc = array_search('takeitfilter', $filters[$show]['displayColumns'])) {
                array_splice($filters[$show]['displayColumns'], $loc, 1);
                array_unshift($filters[$show]['displayColumns'], 'takeitfilter');
            }

            //Make flag col show first
            if ($loc = array_search('iLastReplyBy', $filters[$show]['displayColumns'])) {
                array_splice($filters[$show]['displayColumns'], $loc, 1);
                array_unshift($filters[$show]['displayColumns'], 'iLastReplyBy');
            }

            $ft = new hs_filter($filters[$show]);

            // Override sort if needed
            if (! empty($sortby)) {
                $ft->overrideSort = $sortby.' '.$sortord;
            }
            $ft->paginate = $paginate;
            $ftrs = $ft->outputResultSet();
            foreach ($ft->filterDef['displayColumns'] as $nk=>$v) {
                $cols[$v] = $GLOBALS['filterCols'][$v];

                //Override widths if needed
                if (isset($ft->filterDef['displayColumnsWidths'][$v])) {
                    $cols[$v]['width'] = $ft->filterDef['displayColumnsWidths'][$v];
                }
            }

            //Get counts
            $filCount = new hs_filter($filters[$show], true);
            $filCount->useCountCache = false; //always get a fresh total count here since we're viewing it
            $countTotal = $filCount->outputCountTotal();

            if($countTotal == 0){
                $noresults = getTip();
            }

            $datatable = recordSetTable($ftrs,$cols,
                            //options
                            ['sortby'=>$sortby,
                                    'sortord'=>$sortord,
                                    'filterid'=>$show,
                                    'filter_creator'=>$filters[$show]['xPerson'],
                                    'groupby'=>$ft->filterDef['groupBy'],
                                    'groupord'=>$ft->filterDef['groupByDir'],
                                    'paginate'=>$paginate,
                                    // Prevent showing load more on no limit queries
                                    'paginate_ct'=> ($filCount->is_no_limit == true ? $countTotal + 1 : hs_setting('cHD_MAXSEARCHRESULTS')),
                                    'width'=>'100%',
                                    'checkbox'=>'xRequest',
                                    'footer'=>$footerform,
                                    'rsslink'=>$rsslink,
                                    'exportlink'=>$exportlink,
                                    'actionmenu' => true,
                                    'showing'=>$show,
                                    'page'=>$page,
                                    'noresults'=>$noresults,
                                    //'showcount'=>$ftrs->RecordCount(),
                                    'showcount'=>$countTotal,
                                    'rowsonly'=>($_GET['rowsonly'] ? true : false),
                                    'title'=>$ft->filterDef['sFilterName'], ], $basepgurl);
        } else {
            $datatable = '';
        }

        break;
}

//If defined add count to title
if (isset($ft) && is_object($ft) && is_object($ft->resultSet)) {
    $pagetitle = ''.$countTotal.' : '.$pagetitle;
}

/*****************************************
JAVASCRIPT
*****************************************/
if (hs_setting('cHD_LIVEREFRESH_TIME') > 0 && ! in_array($show, ['trash', 'spam', 'subscriptions', 'reminders'])) {
    $headscript .= '
	<script type="text/javascript" language="JavaScript">

	window.formHasChanged = false;
	document.observe("dom:loaded", function(){
		watchFormChange();
	});

	function watchFormChange(){
		$jq("#rsform_1 input").live("change", function() {
			window.formHasChanged = true;
		});
	}

	function doLiveRefresh(){
		//Dont run if drop down selected or overlay up
		if(!window.formHasChanged && modalIsClosed()){
            hs_msg(lg_loading);

			//Do Live Refresh
			var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'ajax' => 'true', 'show' => $show, 'sortby' => $sortby, 'sortord' => $sortord, 'paginate' => $paginate]).'&rand=" + ajaxRandomString();

			var check = new Ajax.Request(
				url,
				{
					method: "post",
					onSuccess: insertLiveRefresh
				});
		}
	}
	hs_PeriodicalExecuter("doLiveRefresh",doLiveRefresh, '.hs_setting('cHD_LIVEREFRESH_TIME').');

	function insertLiveRefresh(){
		if(arguments[0].responseText && arguments[0].responseText.indexOf("<html") == -1){
			//Eval results
			eval("var response = " + arguments[0].responseText);

			//Update title
			document.title = response["counts"]["'.(is_numeric($show) ? 'filter'.$show : $show).'"] + " : '.hs_jshtmlentities(lg_workspace_title).' : '.hs_jshtmlentities(hs_setting('cHD_ORGNAME')).'";

			//Insert new data
			if(trim(response["html"]) != ""){
				$("ws_datatable").innerHTML = response["html"];
				response["html"].evalScripts();
				watchFormChange();
				setupButtons();
			}

			//Update counts
			$jq("#nav-inbox .count").html(response["counts"]["inbox"]);
			if(response["counts"]["inbox"] > 0){
				$jq("#nav-inbox .count").show();
			}else{
				$jq("#nav-inbox .count").hide();
			}
			$jq("#myq-count").html(response["counts"]["myq"]);
			$jq("#unread-count").html(response["counts"]["unread"]);
			';

    foreach ($filters as $fk=>$fv) {
        if ($fv['fShowCount'] == 1) {
            $headscript .= '
					if($jq("#filter-'.$fk.'-count").length){
						$jq("#filter-'.$fk.'-count").html(response["counts"]["filter'.$fk.'"]);
						if(response["counts"]["filter'.$fk.'"] > 0) $jq("#filter-'.$fk.'-count").show();
					}
					';
        }
    }

    $headscript .= '

            // Update any viewers
            showViewers();
		}
	}

	// update viewers every few seconds
	hs_PeriodicalExecuter("showViewers",showViewers, 10);

	//Observer prototips closing to disable live refresh
	document.observe("prototip:shown", function(event) {
		window.formHasChanged = true;
	});

	</script>';
}

$headscript .= '
<script type="text/javascript" language="JavaScript">
	document.observe("dom:loaded", function(){
		if("'.$_GET['filter-created'].'" != ""){
			//Need delay as onclick event for nav item is setup after this
			setTimeout(function(){
				if(!$("#nav-'.$_GET['filter-created'].'").is(":visible")) $("#"+$("#nav-'.$_GET['filter-created'].'").parent().attr("class")).click();
				$("#nav-'.$_GET['filter-created'].'").parent().effect("highlight", {}, 3000);
			}, 200);
		}
	});

	function checkform(groupId){
		return true;
	}

	//status type array
	statuslist = [';
    $i = 0; $slen = count($activeStatus);
    foreach ($activeStatus as $k=>$v) {
        if ($k != 2) {
            $headscript .= '["'.hs_jshtmlentities($k).'","'.hs_jshtmlentities($v).'"]';
            if ($i != $slen) {
                $headscript .= ',';
            }
        }
        $i++;
    }

    $headscript .= ']

	$jq().ready(function(){
		if ($jq("#button-status-select").length > 0) {
			setupButtons();
			showViewers();
		}
	});

	//When we swith to JQ tab system move to live()
	function setupButtons(){
		//Button: status
		var j=1;
		var len = statuslist.length;
		document.getElementById("button-status-select").options[0]= new Option("'.hs_jshtmlentities(lg_workspace_selstatus).'","");
		for(i=0; i < len; i++) {
			if(statuslist[i]){
				newOptText = statuslist[i][1];
				newOptValue = statuslist[i][0];
				document.getElementById("button-status-select").options[j]= new Option(newOptText,newOptValue);
				j++;
			}
		}

		new Tip("thin-button-status", $("button-status-option").innerHTML, {
				title: "",
				className: "hstinytipfat",
				border: 0,
				radius: 0,
				showOn: "click",
				closeButton: true,
                stem: "bottomMiddle",
				hideOn: "click",
				width: "auto",
				hook: { target: "topMiddle", tip: "bottomMiddle" }
			});

		//Button: Close
		if($jq("#thin-button-close").length){
			var j=1;
			var len = statuslist.length;
			document.getElementById("button-close-select").options[0]= new Option("'.hs_jshtmlentities(lg_workspace_closestatus).'","");
			for(i=0; i < len; i++) {
				if(statuslist[i] && statuslist[i][0] != 1){ //Dont allow active as valid closing status
					newOptText = statuslist[i][1];
					newOptValue = statuslist[i][0];
					document.getElementById("button-close-select").options[j]= new Option(newOptText,newOptValue);
					j++;
				}
			}

			new Tip("thin-button-close", $("button-close-option").innerHTML, {
					title: "",
					className: "hstinytipfat",
					border: 0,
					radius: 0,
					showOn: "click",
					closeButton: true,
                    stem: "bottomMiddle",
					hideOn: "click",
					width: "auto",
					hook: { target: "topMiddle", tip: "bottomMiddle" }
				});
		}

		//Button: Merge
		Event.observe("thin-button-merge","click", function(){
			var j=1;

			// Use jQuery to empty out all the options
			$jq("#button-merge-select").empty();

			// Build the options
			document.getElementById("button-merge-select").options[0]= new Option("'.hs_jshtmlentities(lg_workspace_selmerge). '","");
			$$("input[name^=checktable]:checked").each(function(elem){
				var id = $(elem).value;
				var request = id + " - " + $jq.trim($jq("tr.row-"+ id +" td.js-request").text()).substring(0, 25) + "...";
				document.getElementById("button-merge-select").options[j]= new Option(request, id);
				j++;
			});

			$jq("input[name^=checktable]").on("click", function(e){
				if (typeof $("thin-button-merge").prototip !== "undefined") {
					// Hide the tip so we can unbind this click event.
					$("thin-button-merge").prototip.hide();
				}
				return $jq("#thin-button-merge").trigger("click");
			});

			new Tip("thin-button-merge", $("button-merge-option").innerHTML, {
					title: "",
					className: "hstinytipfat",
					border: 0,
					radius: 0,
					showOn: "click",
					closeButton: true,
                    stem: "bottomMiddle",
					hideOn: "click",
					width: "auto",
					hook: { target: "topMiddle", tip: "bottomMiddle" }
				});

			$("thin-button-merge").prototip.show();

			// Listen to the tip hide to unbind any events.
			$("thin-button-merge").observe("prototip:hidden", function(event) {
				$jq("input[name^=checktable]").off("click");
			});
		});

		//Button: Reassign
		new Tip("thin-button-reassign", $("button-reassign-option").innerHTML, {
                title: "",
				className: "hstinytipfat",
				border: 0,
				radius: 0,
				showOn: "click",
				closeButton: true,
				hideOn: "click",
				stem: "bottomMiddle",
				width: "300px",
				hook: { target: "topMiddle", tip: "bottomMiddle" }
			});
	}

	function doBatchSubmit(){
		Tips.hideAll();
		hs_msg("'.hs_jshtmlentities(lg_workspace_batchchange).'", true);
		$(\'rsform_1\').submit();
	}

	//Directly access element as Tip makes copy so cant use ID
	function personselect(elem){
		var people = new Array();

		';
        if (hs_rscheck($catsList)) {
            $catsList->Move(0);
            while ($c = $catsList->FetchRow()) {
                $i = 0;
                $out = '';
                $jspeople = hs_unserialize($c['sPersonList']);
                if (is_array($jspeople)) {
                    $jsarrsize = count($jspeople);
                    $inbox_default = $c['xPersonDefault'] == 0 ? ' ('.lg_default.')' : '';
                    if (perm('fViewInbox')) {
                        $out .= '["0", "'.utf8_strtoupper(lg_inbox).$inbox_default.'"],';
                    } // add the inbox as an option
                    foreach ($jspeople as $nk=>$p) {
                        $i++;
                        //if the user hasn't been deleted since the user list for the cat was created
                        if (isset($allStaff[$p]) && $allStaff[$p]['fDeleted'] == 0) {
                            //user name/assignments
                            $out = $out.'["'.hs_jshtmlentities($p).'","'.hs_jshtmlentities($allStaff[$p]['namereq']);
                            //if user is default for cat then show
                            if ($c['xPersonDefault'] == $p) {
                                $out = $out.' ('.lg_default.') ';
                            }
                            //if user is out then show
                            if ($allStaff[$p]['xPersonOutOfOffice'] != 0) {
                                $out = $out.' ('.lg_out.') ';
                            }
                            $out = $out.'"]';
                            if ($jsarrsize != $i) {
                                $out = $out.',';
                            }
                        }
                    }
                    $headscript .= 'people['.$c['xCategory'].'] = [ '.$out.' ];';
                }
            }
        }

    $headscript .= '
		// new cat id
		var newcat 	  = $F("action");
		var j=1;
		if(newcat){
            var catlen = people[newcat].length;
            var personList = people[newcat].entries();
                    var categoryStaffID = [];
                    for (person of personList) {
                       categoryStaffID.push(person[0]);
                      }
                    if(categoryStaffID.indexOf("'.$user['xPerson'].'") == -1 && '.(perm('fCanTransferRequests') ? '1' : '0') .' == 1 && '.(perm('fLimitedToAssignedCats') ? '1' : '0'). '==1) {
                        hs_alert("'.lg_request_transfer_warning.'");
                    }
			elem.options[0]= new Option("'.hs_jshtmlentities(lg_workspace_assignto).'","");
			for(i=0; i < catlen; i++) {
				if(people[newcat][i]){
					newOptText = people[newcat][i][1];
					newOptValue=people[newcat][i][0];
					newOptText.indexOf = hs_indexOf;	//prototype
					elem.options[j]= new Option(newOptText,newOptValue);
					j++;
				}
			}
		}
	}

	//AJAX: Toggle Read/Unread
	function toggleRead(elem,reqid){
		if($(elem).hasClassName("table-icons-read") || $(elem).hasClassName("table-icons-replied")){
			var mark = "unread";
			var msg = "'.hs_jshtmlentities(lg_workspace_markunread_msg).'";
			$(elem).removeClassName("table-icons-read");
			$(elem).removeClassName("table-icons-replied");
			$(elem).addClassName("table-icons-unread");
			$("unread-count").update((parseInt($("unread-count").innerHTML) + 1)); //up the count by one
		}else{
			var mark = "read";
			var msg = "'.hs_jshtmlentities(lg_workspace_markread_msg).'";
			$(elem).removeClassName("table-icons-unread");
			$(elem).addClassName("table-icons-read");
			$("unread-count").update((parseInt($("unread-count").innerHTML) - 1)); //decrease the count by one
		}

		new Ajax.Request("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'toggleread']).'&reqid=" + reqid + "&mark=" + mark + "&rand=" + ajaxRandomString());

		hs_msg(msg);
	}

	//AJAX: Set the default workspace page
	function setDefaultWorkspace(){
		$jq.get("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'updateWorkspaceDefault', 'show' => $show]).'&rand=" + ajaxRandomString(),
			function(data){
				hs_msg(data);
			});

		Tips.hideAll();
	}

	//Set the active filter view
	function set_filter_view(id){
		$$(".table-top-menu ul li").each(function(elem){
			if(elem.hasClassName("active")) elem.removeClassName("active");
		});

		var active = $("table-top-menu-"+id).up("li");
		active.addClassName("active");
	}

	//Triage
	function init_triage(){
		set_filter_view("triage");

		//Scroll to top
		scroll(0,0);

		//Find first ID
		var first = $("rsgroup_1").down("[id^=tr-]");

		//Highlight first
		first.addClassName("streamViewTriageHighlight");

		//Open view
		showOverflow(first.id.replace("tr-",""));

		//Set total count
		$("streamViewTriage-count-total").update("'.(is_object($ft->resultSet) ? $ft->resultSet->RecordCount() : '').'");
	}

	function triage_next(){
		//Find first
		var first = $("rsgroup_1").down("[id^=tr-]");

		//remove it
		if(first) first.remove();

		//Find again
		next = $("rsgroup_1").down("[id^=tr-]");

		//Next
		if(next){
			//Increase count
			$("streamViewTriage-count-cur").update((parseInt($("streamViewTriage-count-cur").innerHTML) + 1));

			//Highlight first
			next.addClassName("streamViewTriageHighlight");

			showOverflow(next.id.replace("tr-",""));
		}else{
			goPage(window.location.href);
		}
	}

	//Retry emails that are stuck
	function retryStuckEmails(){
		var url = "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'clear_stuck_emails']).'&rand=" + ajaxRandomString();

		var check = new Ajax.Request(
			url,
			{
				method: \'post\',
				onSuccess: function(){
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace']).'");
				}
			});
	}

	//AJAX: Check the latest version
	function checkVersion(){
		var url = "'.action('Admin\AdminBaseController@adminFileCalled').'";
		var pars = "pg=ajax_gateway&action=versioncheck&rand=" + ajaxRandomString();

		var updateWs = new Ajax.Updater(
						{success: \'flash_feedback\'},
						url,
						{method: \'get\', parameters: pars, onFailure: ajaxError,evalScripts: true});
	}
	';

    //Keyboard shortcuts
    if ($user['fKeyboardShortcuts']) {
        $headscript .= '
		//Function keys
		Event.observe(document, \'keydown\', scFKeys, false);	//Event to watch for keypresses

		function scFKeys(thekey) {
			if(shortcutsON && modalIsClosed()){
				//Function keys
				if(thekey.keyCode == 67){							//c, compose new request
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request']).'");
				}else if(thekey.keyCode == 82){						//r, request id search box
					Field.focus("sidebar-q");
					Event.stop(thekey);
				}else if(thekey.keyCode == 49){						//1, Default Workspace
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace']).'");
				}else if(thekey.keyCode == 50){						//2, Inbox
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'inbox']).'");
				}else if(thekey.keyCode == 51){						//3, My Queue
					goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'myq']).'");
				'; //Tricky - closing bracket added below

        //Render the rest of the keys
        if (! empty($filters)) {
            foreach ($filters as $fk=>$f) {
                if (! hs_empty($f['sShortcut'])) {
                    //Store in 2 different vars so that global keys are in the if/else statement first and override personal key settings
                    $varname = $f['fGlobal'] ? 'globalkeys' : 'personkeys';
                    $pg = $f['sFilterView'] != 'grid' ? 'workspace.stream' : 'workspace';
                    $$varname .= '
						}else if(thekey.keyCode == '.$f['sShortcut'].'){
							goPage("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => $pg, 'show' => $fk]).'");'; //bracket closed below
                }
            }
        }

        $headscript .= $globalkeys;
        $headscript .= $personkeys;

        $headscript .= '
				} //Close elseif block
			} //Close shortcut check
		}
		';
    }

    if ($show != 'reminders' && $show != 'subscriptions' && $user['fKeyboardShortcuts']) {
        $headscript .= '
		document.observe("dom:loaded", function(){
			// Keyboard shortcuts
			pagePosCookie = getCookie("'.$show. 'shortcutposition");

			if(pagePosCookie){
				currentRequest = pagePosCookie;
			}else{
				currentRequest = $("rsgroup_1").down("[id^=tr-]");
				currentRequest = currentRequest.id.replace("tr-","");
			}

            //Setup initial location
			Element.addClassName("td-" + currentRequest,"tablerowpointer");
		});

		Event.observe(document, \'keydown\', scAction, false);	//Event to watch for keypresses

		function scAction(thekey) {

			if(shortcutsON){
				if(thekey.keyCode == 75 || thekey.keyCode == 38){		//k and up arrow
					scMove("backward");
				}else if(thekey.keyCode == 74 || thekey.keyCode == 40){	//j and down arrow
					scMove("forward");
				}else if(thekey.keyCode == 79 || thekey.keyCode == 86 || thekey.keyCode == 39){	//o and v and right arrow
					scGoTo();
				}else if(thekey.keyCode == 88){							//x
					scCheck();
				}
			}

		}

		function scMove(direction){
			oldreq = currentRequest;

			//Find next pointer in proper direction
            var cur = $("tr-"+currentRequest);
            if (! cur) {
                var jqTr = $jq("tr.item-row").first();
                cur = $(jqTr.attr("id"));
            }

			if(direction == "forward"){
				var next = cur.next("[id^=tr-]");
			}else{
				var next = cur.previous("[id^=tr-]");
			}

			if(next){

				//Assign new id
				currentRequest = next.id.replace("tr-","");

				//Remove class from current pointer
				Element.removeClassName("td-" + oldreq,"tablerowpointer");

				//Show new pointer
				Element.addClassName("td-" + currentRequest,"tablerowpointer");

				//Set cookie to remember position
				setCookie("'.$show.'shortcutposition",currentRequest);

			}
		}

		function scGoTo(){
			if($("takeitfilter-"+currentRequest)){
				goPage($("takeitfilter-"+currentRequest).href);
			}else if($("takeit-"+currentRequest)){
				goPage($("takeit-"+currentRequest).href);
			}else{
				goPage($("link-"+currentRequest).href);
			}
		}

		function scCheck(){
			$(currentRequest + "_checkbox").click();
		}
		';
    }

    $headscript .= '</script>';

//Email errors
if (isset($_GET['emailerror'])) {
    $headscript .= '
		<script type="text/javascript" language="JavaScript">
		$jq().ready(function(){
			hs_alert("'.hs_jshtmlentities($_GET['emailerror']).'");
		});
		</script>';
}

//On load check version -432000
if ((hs_setting('cHD_NEWVERSIONCHECKED') < (time() - 432000)) && isAdmin() && ! defined('IS_BETA')) {
    $onload .= 'checkVersion();';
}

/*****************************************
PAGE OUTPUTS
*****************************************/
//Handle ajax or regular request
if (isset($_GET['ajax'])) {

    //Set a header with the correct charset
    header('Content-Type: text/html; charset=UTF-8');

    $htmldirect = true;

    $out = [];
    $out['html'] = $datatable;

    //Get counts for each filter

    //Inbox
    $inboxCount = new hs_filter('', true);
    $inboxCount->useSystemFilter('inbox');
    $out['counts']['inbox'] = $inboxCount->outputCountTotal();

    //Myq
    $myCount = new hs_filter('', true);
    $myCount->useSystemFilter('myq');
    $out['counts']['myq'] = $myCount->outputCountTotal();

    $out['counts']['unread'] = 0;
    $myUnreadCount = new hs_filter('', true);
    $myUnreadCount->useSystemFilter('myq_unread');
    $out['counts']['unread'] = $myUnreadCount->outputCountTotal();

    //Custom filters
    $filters = apiGetAllFilters($user['xPerson'], 'all');
    foreach ($filters as $k=>$v) {
        if ($v['fShowCount'] == 1 || $k == $show) {
            $filCount = new hs_filter($v, true);

            //Allow cache for all filters except filter we're viewing
            if ($k != $show) {
                $filCount->useCountCache = true;
            }

            $out['counts']['filter'.$k] = $filCount->outputCountTotal();
        }
    }

    $pagebody = json_encode($out);
} else {
    $pagebody .= '<span id="flash_feedback">'.$efb.$fb.'</span>'; //feedback if any

    //Display stuck email notice
    $stuck_emails = \Illuminate\Support\Facades\Cache::remember(\Facades\HS\Cache\Manager::key('CACHE_STUCKEMAILS_KEY'), \Facades\HS\Cache\Manager::key('CACHE_STUCKEMAILS_MINUTES'), function () {
        $stuck_rs = $GLOBALS['DB']->Execute('SELECT HS_Mailboxes_StuckEmails.*, HS_Mailboxes.sUsername, HS_Mailboxes.sHostname FROM HS_Mailboxes_StuckEmails,HS_Mailboxes WHERE HS_Mailboxes_StuckEmails.xMailbox = HS_Mailboxes.xMailbox');

        return $stuck_rs;
    });

    if (hs_rscheck($stuck_emails)) {
        $stuck_html = lg_workspace_stuckmsg.' <a href="" onclick="$(\'stuckdetails\').toggle();return false;">'.lg_workspace_stuckviewdetails.'</a>

				<table width="100%" id="stuckdetails" cellpadding="0" cellspacing="0" align="center" style="display:none;color:#000;font-weight:normal;">
							<tr>
								<td colspan="3"><p>'.lg_workspace_stuckmsg3.'</p><p>'.lg_workspace_stuckmsg2.'</p><div style="border-bottom:1px solid #ccc;text-align:center;font-weight:bold;padding-bottom:3px;">'.lg_workspace_stucklist.'</div><br></td>
							</tr>
							<tr>
								<td style="font-weight:bold;color:#333;">'.lg_workspace_stuckbox.'</td>
								<td style="font-weight:bold;color:#333;">'.lg_workspace_stuckfrom.'</td>
								<td style="font-weight:bold;color:#333;">'.lg_workspace_stuckdate.'</td>
							</tr>';
        while ($row = $stuck_emails->FetchRow()) {
            $stuck_html .= '
							<tr>
								<td>'.$row['sUsername'].' - '.$row['sHostname'].'</td>
								<td>'.$row['sEmailFrom'].'</td>
								<td>'.$row['sEmailDate'].'</td>
							</tr>';
        }
        $stuck_html .= '<tr><td colspan="3" style="padding-top:10px;"><button class="btn" onclick="retryStuckEmails();">'.lg_workspace_stuckretry.'</button></td></tr></table>';

        $pagebody .= displaySystemBox($stuck_html);
    }

    //Data table container
    $pagebody .= '<div id="ws_datatable" class="card" style="padding:20px;">'.$datatable.'</div>';

    /*
    $viewers = '';
    switch ($show) {
            case 'inbox': $viewers = lg_workspace_viewable_permstaff;

break;
            case 'myq':
            case 'subscriptions':
            case 'reminders':
                $viewers = lg_workspace_viewable_onlyme;

            break;
        }
    if (is_numeric($show) && isset($filters[$show])) {
        switch ($filters[$show]['fType']) {
                case 1: $viewers = lg_workspace_viewable_everyone;

break;
                case 2: $viewers = lg_workspace_viewable_onlyme;

break;
                case 3:
                    $viewers = $GLOBALS['DB']->GetOne('SELECT sGroup FROM HS_Permission_Groups WHERE xGroup = ?', [$filters[$show]['fPermissionGroup']]).' ('.lg_workspace_viewable_group.')';

                    break;
                case 4:
                    $fpeople = $GLOBALS['DB']->Execute('SELECT sFname,sLname
														FROM HS_Person LEFT OUTER JOIN HS_Filter_People ON HS_Filter_People.xPerson = HS_Person.xPerson
														WHERE HS_Person.fDeleted = 0
														AND HS_Filter_People.xFilter = ?', [$show]);
                    $viewers = [];
                    while ($r = $fpeople->FetchRow()) {
                        $viewers[] = $r['sFname'].' '.$r['sLname'];
                    }
                    $viewers = implode(', ', $viewers);

                    break;
            }
    }
    $pagebody .= '<div class="filter-viewable"><strong>'.lg_workspace_viewable.':</strong> '.$viewers.'</div>';
    */
}
