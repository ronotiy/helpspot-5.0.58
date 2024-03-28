<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load(['admin.settings', 'search', 'admin.tools.customfields', 'request', 'workspace', 'kb', 'conditional.ui', 'admin.users']);

//clean out any data in the buffer
ob_end_clean();

header('Content-Type: text/html; charset=utf-8');

//Make sure /admin doesn't output any headers
$pagebody = '';
$tab = '';
$subtab = '';
$out = '';

switch ($_GET['action']) {
    //Called from workspace.php
    case 'updateWorkspaceDefault':
        $GLOBALS['DB']->Execute('UPDATE HS_Person SET sWorkspaceDefault = ? WHERE xPerson = ?', [$_GET['show'], $user['xPerson']]);
        $out .= lg_ajax_workspacedefault;

        break;

    case 'notifylist':
        $allStaff = apiGetAllUsersComplete();

        if (isset($_GET['reqid']) && is_numeric($_GET['reqid'])) {
            $rssubs = apiGetActiveRequestSubscribers($_GET['reqid']);
            $subs = array_keys(rsToArray($rssubs, 'xPerson'));

            $req = apiGetRequest($_GET['reqid']);
            $au = $req['xPersonAssignedTo'];
        } else {
            $subs = [];
            $au = '';
        }

        //Logic to see if guests/l2 users are in limited mode
        if (perm('fLimitedToAssignedCats')) {
            $user_cats = apiGetUserCats($user['xPerson']);
        }

        $loopstaff = $allStaff;
        foreach ($loopstaff as $key=>$value) {
            if ($allStaff[$key]['fDeleted'] == 0) {
                $show = true;
                $rowclass = ($rcount % 2) ? 'tablerowon' : 'tablerowoff';

                //In limited mode don't show staff not in same cat as user
                if (perm('fLimitedToAssignedCats')) {
                    //See if this staff member should be visible to the current user viewing the page
                    $staffer_cats = apiGetUserCats($key);
                    if (is_array($staffer_cats) && ! empty($staffer_cats)) {
                        foreach ($staffer_cats as $id) {
                            if (! array_intersect($staffer_cats, $user_cats)) {
                                $show = false; //skip this staffer
                            }
                        }
                    } else {
                        //staffer not in any cats so don't show
                        $show = false;
                    }
                }

                if ($show) {
                    if ($key == $au) {
                        $box = '<span class="red">'.lg_ajax_au.'</span> - ';
                    } elseif (in_array($key, $subs)) {
                        $box = '<span class="red">'.lg_ajax_sub.'</span> - ';
                    } else {
                        $box = '<input type="checkbox" name="ccstaff[]" value="'.$key.'">';
                    }

                    $out .= '<div class="'.$rowclass.'">'.$box.' '.$allStaff[$key]['sFname'].' '.$allStaff[$key]['sLname'].'</div>';
                    $rcount++;
                }
            }
        }

        break;

    case 'toggleread':
        if ($_GET['mark'] == 'unread') {
            $GLOBALS['DB']->Execute('UPDATE HS_Request SET iLastReadCount = (iLastReadCount-1) WHERE xRequest = ? AND xPersonAssignedTo = ?', [$_GET['reqid'], $user['xPerson']]);
        } else {
            include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
            updateReadUnread($_GET['reqid']);
        }

        break;

    case 'addtime':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $timeID = apiAddTime($_POST);

        if ($timeID) {
            //Return new rows
            $rows = apiGetTimeForRequest($_POST['xRequest']);
            $table = ['html'	=>renderTimeTrackerTable($rows, $timeID),
                           'msg'	=>lg_ajax_timeadded,
                           'time'	=>parseSecondsToTime(apiGetTimeTotal($_POST['xRequest'])), ];

            header('Content-Type: application/json');
            echo json_encode($table);
        } else {
            //Return error message
            $error = ['error'=>lg_ajax_timeerr];
            header('Content-Type: application/json');
            echo json_encode($error);
        }

        break;

    case 'deletetime':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $delete = apiDeleteTime($_GET);

        if ($delete) {
            //Return new rows
            $rows = apiGetTimeForRequest($_GET['xRequest']);
            $table = ['html'	=>renderTimeTrackerTable($rows, $_GET['xTimeId']),
                           'msg'	=>lg_ajax_timedeleted,
                           'time'	=>parseSecondsToTime(apiGetTimeTotal($_GET['xRequest'])), ];

            header('Content-Type: application/json');
            echo json_encode($table);
        } else {
            //Return error message
            $error = ['error'=>errorBox(lg_ajax_timedelerr, '810')];
            header('Content-Type: application/json');
            echo json_encode($error);
        }

        break;

    case 'quote':
        include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $out = '';
        $events = [];
        $editor = ! empty($_GET['editor']) ? $_GET['editor'] : 'none';

        $people = apiGetAllUsersComplete();
        $request = apiGetRequest($_GET['reqid']);
        $mailbox = apiGetMailbox($request['xMailboxToSendFrom']);

        if (isset($_GET['allpublic'])) {
            $hist = apiGetRequestHistory($_GET['reqid']);
            $hist = rsToArray($hist, 'xRequestHistory', false);
            //Filter out any non-public notes
            foreach ($hist as $k=>$row) {
                if ($row['fPublic'] == 1) {
                    $events[] = $row;
                }
            }
        } else {
            $events[] = apiGetHistoryEvent($_GET['reqhisid']);
        }

        foreach ($events as $k=>$event) {
            $texthead = lg_ajax_origmsg."\n";
            $htmlhead = '<hr width="100%" />';
            $log = unserialize($event['tLog']);

            //Format a header if the note is an email
            if (! hs_empty($event['tEmailHeaders'])) {
                $headers = hs_unserialize($event['tEmailHeaders']);
                $from = hs_charset_emailheader($headers['from']);
                $to = hs_charset_emailheader($headers['to']);
                $date = hs_charset_emailheader($headers['date']);
                $subject = hs_charset_emailheader($headers['subject']);
            } elseif ($event['xPerson'] > 0) {
                $staffer = $people[$event['xPerson']]['sFname'];

                $from = '"'.$staffer.'" <'.$mailbox['sReplyEmail'].'>';
                $to = '<'.$log['customeremail'].'>';
                $date = date('r', $event['dtGMTChange']);
                $subject = $request['sTitle'];
            } elseif ($event['xPerson'] == 0) { // by customer, but not from email

                if (hs_empty($request['sFirstName']) && hs_empty($request['sLastName'])) {
                    $custname = lg_request_customer;
                } else {
                    $custname = $request['sFirstName'].' '.$request['sLastName'];
                }

                $from = '"'.$custname.'" <'.$request['sEmail'].'>';
                $to = '';
                $date = date('r', $event['dtGMTChange']);
                $subject = $request['sTitle'];
            } elseif ($event['xPerson'] == -1) {
                $label = hs_htmlspecialchars(lg_systemname);

                $from = $label;
                $to = '';
                $date = date('r', $event['dtGMTChange']);
                $subject = $request['sTitle'];
            } else {
                $texthead = '';
                $htmlhead = '';
            }

            // Forward an external request. See #490
            // https://github.com/UserScape/HelpSpot/issues/490
            if ($event['fPublic'] == 0 && ! hs_empty($log['emailtogroup'])) {
                $to = $log['emailtogroup'];
            }

            $texthead .= lg_request_from.': '.hs_htmlspecialchars($from)."\n";
            $texthead .= lg_request_sent.': '.hs_htmlspecialchars($date)."\n";
            $texthead .= lg_request_to.': '.hs_htmlspecialchars($to)."\n";
            if ($log['emailccgroup']) {
                $texthead .= lg_request_emailcc.': '.hs_htmlspecialchars($log['emailccgroup'])."\n";
            }
            $texthead .= lg_request_subject.': '.hs_htmlspecialchars($subject)."\n\n";

            $htmlhead .= '<b>'.lg_request_from.':</b> '.hs_htmlspecialchars($from).'<br>';
            $htmlhead .= '<b>'.lg_request_sent.':</b> '.hs_htmlspecialchars($date).'<br>';
            $htmlhead .= '<b>'.lg_request_to.':</b> '.hs_htmlspecialchars($to).'<br>';
            if ($log['emailccgroup']) {
                $htmlhead .= '<b>'.lg_request_emailcc.':</b> '.hs_htmlspecialchars($log['emailccgroup']).'<br>';
            }
            $htmlhead .= '<b>'.lg_request_subject.':</b> '.hs_htmlspecialchars($subject).'<br><br>';

            // if it's a private note hide all the headers so they aren't exposed
            // see https://github.com/UserScape/HelpSpot/issues/1228
            if ($event['fPublic'] == 0 && hs_empty($log['emailtogroup'])) {
                $htmlhead = '';
            }

            if ($editor == 'wysiwyg') {
                if ($event['fNoteIsHTML']) {
                    $out .= '<p><br></p><blockquote class="html_email_quote" style="border-left:3px solid #ccc;padding-left:4px;margin-left:0px;">'.$htmlhead.$event['tNote'].'</blockquote>';
                } else {
                    $out .= '<p><br></p><blockquote class="html_email_quote" style="border-left:3px solid #ccc;padding-left:4px;margin-left:0px;">'.$htmlhead.nl2br($event['tNote']).'</blockquote>';
                }
            } elseif ($editor == 'markdown' && $event['fNoteIsHTML']) {
                //This only handles if the editor is HTML and the quoted item is HTML, quoted item as text is handled below.
                //Adding the blockquote allows hs_html_2_markdown to put in the correct blockquote markdown syntax
                $out .= trim(hs_html_2_markdown('<blockquote>'.$htmlhead.$event['tNote'].'</blockquote>'))."\n";
            } else {
                //This is text so strip HTML
                $text = html_entity_decode(strip_tags($event['tNote']), ENT_COMPAT, 'UTF-8');
                $lines = explode("\n", $texthead.$text);
                foreach ($lines as $v) {
                    $out = $out.'> '.$v."\n";
                }
            }
        }

        break;

    case 'response':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $totalResponses = ($_GET['totalResponses']) ? (int) $_GET['totalResponses'] : 1;
        // In case javascript gives 'undefined'
        if (! is_numeric($totalResponses) || $totalResponses < 1) {
            $totalResponses = 1;
        }
        $response = apiGetRequestResponse($_GET['id']);

        // Get any attachments for the response.
        $options = json_decode($response['tResponseOptions']);
        if ($options->attachment) {
            foreach ($options->attachment as $id) {
                $response['documents'][] = json_encode(apiGetDocument($id));
            }
        }

        $text = $response['tResponse'];

        //If inserting into the wysiwyg then format as HTML
        if ($_GET['editor_type'] == 'wysiwyg') {
            // Escape pounds signs which are part of a placeholder so markdown doesn't convert to h2.
            // this has a look behind to ensure it isn't preceded with a character.
            $text = preg_replace('/^##(\w+)##/m', '\##$1##', $text);

            //Transform markdown
            $text = hs_markdown($text);
        }

        //Save response in stats
        apiResponseStatEvent($_GET['id'], $_GET['xRequest'], $user['xPerson']);

        $resp_array = ['text'=>$text, 'options'=>$response['tResponseOptions'], 'documents'=>$response['documents']];

        echo json_encode($resp_array);

        break;

    case 'conditionalui_mail':
    case 'conditionalui_auto':
    case 'conditionalui_trigger':
        include_once cBASEPATH.'/helpspot/lib/class.conditional.ui.php';
        include_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

        if ($_GET['action'] == 'conditionalui_mail') {
            $ui = new hs_conditional_ui_mail();
        } elseif ($_GET['action'] == 'conditionalui_trigger') {
            $ui = new hs_conditional_ui_trigger();
        } else {
            $ui = new hs_conditional_ui_auto();
        }

        switch ($_GET['do']) {
            case 'new_condition':
                $out .= $ui->newCondition((isset($_GET['subid']) ? $_GET['subid'] : false));

                break;
            case 'constraints':
                $out .= $ui->getConditionConstraints($_GET['type'], $_GET['rowid']);

                break;
            case 'new_action':
                $out .= $ui->newAction();

                break;
            case 'actiondetails':
                $value = $_GET['type'] == 'assignable_staff' ? ['xCategory'=>$_GET['xCategory'], 'selected'=>''] : $_GET['value'];
                $out .= $ui->getActionConstraints($_GET['type'], $_GET['rowid'], $value);

                break;
        }

        break;

    case 'mailrule_order':
        $order = 0;

        if (is_array($_GET['sortorder'])) {
            foreach ($_GET['sortorder'] as $k=>$v) {
                if (is_numeric($v)) {
                    $GLOBALS['DB']->Execute('UPDATE HS_Mail_Rules SET fOrder = ? WHERE xMailRule = ?', [$order, $v]);
                }
                $order++;
            }
        }

        break;

    case 'autorule_order':
        $order = 0;

        if (is_array($_GET['sortorder'])) {
            foreach ($_GET['sortorder'] as $k=>$v) {
                if (is_numeric($v)) {
                    $GLOBALS['DB']->Execute('UPDATE HS_Automation_Rules SET fOrder = ? WHERE xAutoRule = ?', [$order, $v]);
                }
                $order++;
            }
        }

        break;

    case 'status_order':
        $order = 1;

        if (is_array($_GET['sortorder'])) {
            foreach ($_GET['sortorder'] as $k=>$v) {
                if (is_numeric($v) && $v != hs_setting('cHD_STATUS_ACTIVE', 1) && $v != hs_setting('cHD_STATUS_SPAM', 2)) {
                    $GLOBALS['DB']->Execute('UPDATE HS_luStatus SET fOrder = ? WHERE xStatus = ?', [$order, $v]);
                }
                $order++;
            }
        }
        print_r($_GET['sortorder']);

        break;

    case 'cf_order':
        $order = 0;

        if (is_array($_GET['sortorder'])) {
            foreach ($_GET['sortorder'] as $k=>$v) {
                if (is_numeric($v)) {
                    $GLOBALS['DB']->Execute('UPDATE HS_CustomFields SET iOrder = ? WHERE xCustomField = ?', [$order, $v]);
                }
                $order++;
            }
            \Facades\HS\Cache\Manager::forget(\Facades\HS\Cache\Manager::key('CACHE_CUSTOMFIELD_KEY'));
        }

        break;

    case 'trigger_order':
        $order = 0;

        if (is_array($_GET['sortorder'])) {
            foreach ($_GET['sortorder'] as $k=>$v) {
                if (is_numeric($v)) {
                    $GLOBALS['DB']->Execute('UPDATE HS_Triggers SET fOrder = ? WHERE xTrigger = ?', [$order, $v]);
                }
                $order++;
            }
        }

        break;

    case 'kbui':
        include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        $books = apiGetAllBooks();
        $bklist .= '<select onchange="kbui_gettoc($jq(this).val());" class="kbui_select">';
        if (hs_rscheck($books)) {
            while ($row = $books->FetchRow()) {
                $bklist .= '<option value="'.$row['xBook'].'" '.selectionCheck($row['xBook'], $_GET['xBook']).'>'.hs_htmlspecialchars($row['sBookName']).' '.($row['fPrivate'] == 1 ? '('.lg_kbui_private.')' : '').'</option>';
            }
        }
        $bklist .= '</select>';

        $out .= '
		<table width="100%" height="100%" style="margin-bottom: 0px">
			<tr valign="top">
				<td class="kbui-nav">
					<div class="kbui-books">'.$bklist.'</div>
					<div id="kbui-toc"></div>
				</td>
				<td id="kbui-page" class="kbui-page">&nbsp;</td>
			</tr>
		</table>
		';

        break;

    case 'kbui-page':
        include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        $plist = '';
        $portals = apiGetAllPortals(0);
        if ($portals->RecordCount() > 0) {
            $plist .= '<select id="kbui_insert_link">';
            $plist .= '<option value="'.cHOST.'">'.lg_kbui_primaryportal.'</option>';
            while ($row = $portals->FetchRow()) {
                $plist .= '<option value="'.$row['sHost'].'" '.selectionCheck($row['xPortal'], $_GET['xPortal']).'>'.hs_htmlspecialchars($row['sPortalName']).'</option>';
            }
            $plist .= '</select>';
        } else {
            $plist .= '<input type="hidden" id="kbui_insert_link" value="'.cHOST.'" />';
        }

        $page = apiGetPage($_GET['xPage']);
        $chap = apiGetChapterByPage($_GET['xPage']);
        $book = apiGetBookByPage($_GET['xPage']);
        $out = '<div class="kbui-heading">';
        $out .= '<h1>'.$page['sPageName'].'</h1>';
        if ($book['fPrivate'] == 0) {
            $out .= '
				<div class="kbui-actions">
					'.$plist. '
					<a href="" class="btn inline-action" onclick="aKBL($jq(\'#kbui_insert_link\').val() + \'/index.php?pg=kb.page&id='.$_GET['xPage'].'\',\''.hs_jshtmlentities(htmlentities($page['sPageName'])).'\');return false;" style="margin-right:10px;margin-top:2px;">'.lg_kbui_insertlink. '</a>
					<a href="" class="btn inline-action" onclick="aKBL($jq(\'#kbui_insert_link\').val() + \'/index.php?pg=kb.chapter&id='.$chap['xChapter'].'\',\''.hs_jshtmlentities(htmlentities($chap['sChapterName'])).'\');return false;"  style="margin-right:10px;margin-top:2px;">'.lg_kbui_linkchapter. '</a>
					<a href="" class="btn inline-action" onclick="aKBL($jq(\'#kbui_insert_link\').val() + \'/index.php?pg=kb&id='.$book['xBook'].'\',\''.hs_jshtmlentities(htmlentities($book['sBookName'])).'\');return false;"  style="margin-top:2px;">'.lg_kbui_linkbook.'</a>
				</div>';
        }
        $out .= '</div>';
        $out .= '<div class="kbui-body">'.$page['tPage'].'</div>';

        break;

    case 'kbui-toc':
        include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        $open_folders = ($_COOKIE['sidebarOpenFolders'] ? explode(',', $_COOKIE['sidebarOpenFolders']) : '');

        //TOC list
        $tree = apiBuildChapPageTree($_GET['xBook'], false);
        $chaps = apiTocChaps($tree);
        if (is_array($chaps) && count($chaps) > 0) {
            $out .= '<ul class="sidebar">';
            foreach ($chaps as $chapid=>$c) {
                $id = 'kbui'.$chapid;
                $out .= '<li class="folder-li"><a href="" id="'.$id. '" class="folder book-pub"><span class="text">'.hs_htmlspecialchars($c['sChapterName']).'</span><span class="arrow '.(! hs_empty(in_array($id, $open_folders)) ? 'arrow-open' : '').'"></span></a></li>';

                $pages = apiTocPages($tree, $chapid);
                if (is_array($pages)) {
                    foreach ($pages as $pageid=>$p) {
                        $out .= '<li class="'.$id.'" style="'.(! in_array($id, $open_folders) ? 'display:none;' : '').'"><a href="" onClick="kbui_showpage('.$pageid. ');return false;" class="page-pub"><span class="text">'.hs_htmlspecialchars($p['sPageName']).'</span></a></li>';
                    }
                }
            }
            $out .= '</ul>';
        }

        break;

    case 'kbui-related':
        include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        $pageid = isset($_GET['page']) ? $_GET['page'] : '';
        $inpriv = isset($_GET['priv']) ? $_GET['priv'] : '';

        $pubbooks = apiGetBooks(0);
        if ($inpriv == 1) {
            $privbooks = apiGetBooks(1);
        }

        if (hs_rscheck($pubbooks)) {
            while ($b = $pubbooks->FetchRow()) {
                $books[$b['xBook']] = $b['sBookName'];
            }
        }
        if (isset($privbooks) && hs_rscheck($privbooks)) {
            while ($b = $privbooks->FetchRow()) {
                $books[$b['xBook']] = $b['sBookName'];
            }
        }

        $out = '<script type="text/javascript" language="JavaScript">

		function returnPages(){
			namestack = new Array();
			idstack = new Array();
			form = document.relatedpopup;
			for(var i = 0; i < form.pageids.length; i++){
				if(form.pageids[i].checked){
					tempname = "<li><b>" + document.getElementById(form.pageids[i].value).value + "</b></li>";
					namestack.push(tempname);
					idstack.push(form.pageids[i].value);
				}
			}
			//Sort in alpha order
			namestack.sort();

			parent.document.getElementById(\'relatedview\').innerHTML = "<ul style=\"list-style-type:none;margin-top:0px;\">" + namestack.join(\' \') + "</ul>";
			parent.document.getElementById(\'relatedpages\').value = idstack.join(\',\');
			closeAllModals();
		}

		function setPages(){
			pages = parent.document.getElementById(\'relatedpages\').value.split(\',\');
			pages.inArray = hs_inArray;	//add prototype
			form = document.relatedpopup;
			for(var i = 0; i < form.pageids.length; i++){
				tempval = form.pageids[i].value;
				if(pages.inArray(tempval)){
					form.pageids[i].checked = true;
				}
			}
		}

		</script>';

        $out .= '<form action="" name="relatedpopup">
            <button type="button" class="btn inline-action accent" onclick="returnPages();">'.lg_kb_savepages.'</button>';
        $out .= displayContentBoxTop(lg_kb_addeditrelated);

        $out .= '<ul class="kbtoc" style="padding-left:0px;">';

            foreach ($books as $k=>$name) {
                $out .= '<li style="margin-top: 0px;"><b>'.$name.'</b></li>';
                $tree = apiBuildChapPageTree($k, false);
                $chaps = apiTocChaps($tree);

                if (is_array($chaps) && count($chaps) > 0) {
                    $out .= '<ul class="kbtoc">';
                        foreach ($chaps as $chapid=>$c) {
                            $out .= '<li>'.$c['name'].'<ul class="kbtoc">';
                            $pages = apiTocPages($tree, $chapid);
                            if (is_array($pages)) {
                                foreach ($pages as $pid=>$p) {
                                    $dis = ($pageid != $pid) ? '' : ' DISABLED';
                                    $out .= '<li><input type="checkbox" name="pageids" value="'.$pid.'" '.$dis.'>
											  <input type="hidden" id="'.$pid.'" name="'.$pid.'" value="'.hs_htmlspecialchars($name).' / '.hs_htmlspecialchars($c['sChapterName']).' / '.hs_htmlspecialchars($p['sPageName']).'">
											  <a href="index.php?pg=kb.page&id='.$pid.'" class="'.$p['class'].'" target="_blank">'.$p['name'].'</a></li>';
                                }
                            }
                            $out .= '</ul><li>';
                        }
                    $out .= '</ul>';
                }
            }
        $out .= '</ul>';

        $out .= displayContentBoxBottom();

        $out .= '<button type="button" class="btn inline-action accent" onclick="returnPages();">'.lg_kb_savepages.'</button></form>';

        break;

    case 'auto_testcondition':
    //error_reporting(E_ALL);

        $rule = new hs_auto_rule();
        $rule->returnrs = true; //return result set instead of doing actions
        $rule->SetAutoRule($_POST);

        $result = $rule->ApplyRule();
        $count = is_object($result) ? $result->RecordCount() : 0;

        $onlyshow = isset($_POST['showall']) ? false : 10;

        $a = '';
        recordSetTable($a, []); //terrible hack so that groupid increments one and the table rules don't break.
        $datatable = recordSetTable($result,[$GLOBALS['filterCols']['view'],
                                                  $GLOBALS['filterCols']['fOpenedVia'],
                                                  //$GLOBALS['filterCols']['fOpen'], //breaks table for some reason
                                                  $GLOBALS['filterCols']['fullname'],
                                                  $GLOBALS['filterCols']['reqsummary'],
                                                  $GLOBALS['filterCols']['age'], ],
                                            //options
                                            ['width'=>'100%',
                                                    'onlyshow'=>$onlyshow,
                                                    'title'=>($rule->name).' ('.$count.')',
                                                    'popup'=>true, ]);

        $out .= $datatable;

        break;

    case 'run_filter':
        try {
            $rule = new hs_auto_rule();
            $rule->returnrs = true; //return result set instead of doing actions
            $rule->SetAutoRule($_POST);

            $ft = new hs_filter();
            $ft->filterDef = $rule->getFilterConditions();

            \Illuminate\Support\Facades\DB::enableQueryLog();
            $result = $ft->outputResultSet();
            $filterQuery = \Illuminate\Support\Facades\DB::getQueryLog();
            \Illuminate\Support\Facades\DB::disableQueryLog();

            $count = is_object($result) ? $result->RecordCount() : 0;
        } catch (Exception $e) {
            // Give them the error and bail out.
            $out = '<div class="sectionhead">Error</div><p class="rs-error">'.trim($e->getMessage(), " \t").'</p>';
            break;
        }

        $onlyshow = isset($_POST['showall']) ? false : 10;

        //Columns to show
        $cols = [$GLOBALS['filterCols']['view']];	//add view link by default

        foreach ($_POST['displayColumns'] as $k=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];

            //Override widths if needed
            if (isset($ft->filterDef['displayColumnsWidths'][$v])) {
                $cols[$v]['width'] = $ft->filterDef['displayColumnsWidths'][$v];
            }
        }

        $datatable = recordSetTable($result,$cols,
                                            //options
                                            ['width'=>'100%',
                                                    'onlyshow'=>$onlyshow,
                                                    'from_run_filter'=>true,
                                                    'groupby'=>$_POST['groupBy'],
                                                    'groupord'=>$_POST['groupByDir'],
                                                    'showcount'=>$count,
                                                    'noresults'=>lg_noresults,
                                                    'title'=> $rule->name.' '.($onlyshow ? sprintf(lg_ajax_showing, $onlyshow) : ''),
                                                    'rightfooter'=>'<div style="float:left;color:#ccc;margin:5px 20px 0 0;">('.$ft->run_time.'s)</div><a href="" class="btn inline-action" id="filter_sql_link" onclick="hs_overlay(\'filter_sql\');return false;">'.lg_ajax_filtersql.'</a>',
                                                    'popup'=>true, ]);

        //SQL text
        $resultQuery = '';
        $filterQueryCount = count($filterQuery);
        if ($filterQueryCount > 0) {
            $resultQuery = \HS\Database\QueryLogResult::fullSql($filterQuery[$filterQueryCount-1]);
        }

        $out .= '<div id="filter_sql" style="background-color:#fff;padding:10px;display:none">'.$resultQuery.'</div>';

        $out .= $datatable;

        break;

    case 'livelookup':
        include cBASEPATH.'/helpspot/lib/livelookup.php';

        $livelookup_sources = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));
        if (hs_setting('cHD_LIVELOOKUP') == 1 && (! is_array($livelookup_sources) || empty($livelookup_sources[0]['path']))) {
            $_POST['is_sample'] = 'sample';
            //$out = array('message'=>lg_request_livelookupnotsetup1.' '.lg_request_livelookupnotsetup2);
        }

        $out = apiLiveLookup($_POST);

        break;

    case 'historysearch':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $allStaff = apiGetAllUsersComplete();

        $search = apiRequestHistorySearch($_GET, false, __FILE__, __LINE__);

        //Set return box header
        if ($search->RecordCount() > 0) {
            $boxtitle = constant('lg_request_search'.$_GET['search_type']);
        } else {
            $boxtitle = lg_request_searchnomatch;
        }

        $code = '<a href="admin?pg=request.static&reqid=%s" onclick="showRequestDetails(%s); return false">%s</a>&nbsp;<a href="admin?pg=request&reqid=%s" target="_blank"><img src="'.static_url().'/static/img5/external.svg" style="margin-bottom:-2px;margin-left: 2px;height: 14px;" border="0" /></a>';

        $out .= '<div style="overflow:auto;width:100%;max-height:540px;padding:0px;margin:0px;borders:0px;">';

        $out .= recordSetTable($search,
            [['type' => 'link', 'label' => lg_request_reqid, 'sort' => 0, 'width' => '80',
                'code' => $code,
                'fields' => 'xRequest', 'linkfields' => ['xRequest', 'xRequest', 'xRequest', 'xRequest'], ],
                ['type' => 'lookup', 'label' => lg_historysearch_openclose, 'sort' => 0, 'fields' => 'fOpen', 'width' => '40', 'dataarray' => [lg_isclosed, lg_isopen]],
                ['type' => 'string', 'label' => lg_historysearch_date, 'sort' => 0, 'width' => '180', 'fields' => 'dtGMTOpened', 'function' => 'hs_showDate'],
                ['type' => 'string', 'label' => lg_historysearch_tnote, 'sort' => 0, 'fields' => 'tNote', 'hideflow' => true, 'function' => 'initRequestClean'], ],
            ['width' => '100%', ]);

        //Hack to replace line break at end of recordset table
        $out = str_replace('</table><br>', '</table>', $out);

        $out .= '</div>';

        break;

    case "sidebarsearch":
        require_once(cBASEPATH.'/helpspot/lib/api.requests.lib.php');

        $_GET['sSearch'] = $_GET['q'];

        $search = apiRequestHistorySearch($_GET,false,__FILE__,__LINE__);

        //Set return box header
        if($search->RecordCount() > 0){
            $boxtitle = constant("lg_request_search" . $_GET['search_type']);
        }

        $code = '<a href="admin?pg=request&reqid=%s">%s</a>&nbsp;<a href="admin.php?pg=request&reqid=%s" target="_blank"><img src="'.static_url().'/static/img5/external.svg" style="margin-bottom:-2px;margin-left: 2px;height: 14px;" border="0" /></a>';

        $noresults = '<div class="noresults filter" style="min-width:550px">'.lg_noresults_search.'</div>';

        $out .= recordSetTable($search,
            array(array('type'=>'link','label'=>lg_request_reqid,'sort'=>0,'width'=>'80',
                'code'=>$code,
                'fields'=>'xRequest','linkfields'=>array('xRequest','xRequest','xRequest','xRequest')),
                array('type'=>'lookup','label'=>lg_historysearch_openclose,'sort'=>0,'fields'=>'fOpen','width'=>'40','dataarray'=>array(lg_isclosed,lg_isopen)),

                # The below is the same value as `$GLOBALS['filterCols']['fullname']`, but not sortable
                array("type" => "string", "label" => lg_lookup_filter_custname, "sort" => false, "nowrap" => true, "fields" => "fullname", "width" => 110,),

                array('type'=>'string','label'=>lg_historysearch_date,'sort'=>0,'width'=>'180','fields'=>'dtGMTOpened','function'=>'hs_showDate'),
                array('type'=>'string','label'=>lg_historysearch_tnote,'sort'=>0,'fields'=>'tNote','hideflow'=>true,'function'=>'initRequestClean') ),
            array('no_table_borders' => true,
                'showcount'=>$search->RecordCount(),
                'title'=>$boxtitle,
                'width'=>'100%',
                'noresults'=>$noresults,
                'hideOverFlow' => true,
            ));

        break;

    case 'versioncheck':
        if (! defined('IS_BETA') && (hs_setting('cHD_NEWVERSIONCHECKED') < (time() - 432000)) && isAdmin()) {
            storeGlobalVar('cHD_NEWVERSIONCHECKED', strval(time()));
            $body = hsHTTP('https://store.helpspot.com/latest-release');
            $body = trim($body);
            if (version_compare($body, trim(hs_setting('cHD_VERSION'))) === 1 && ! empty($body) && strlen($body) < 8) {	//make sure if a server error is returned that's not stored
                storeGlobalVar('cHD_NEWVERSION', $body);
                $out .= displaySystemBox(sprintf(lg_ajax_newversion, $body));
            }
        }
        $out .= ' ';

        break;

    case 'jscalc':
        $out = '<table cellspacing="5" style="margin-bottom:0px;" align="center">
					<tr>
                        <td><label class="datalabel">'.hs_jshtmlentities(utf8_ucfirst(lg_days)).'</label></td>
						<td width="10">&nbsp;</td>
						<td><label class="datalabel">'.hs_jshtmlentities(utf8_ucfirst(lg_hours)). '</label></td>
                        <td width="10">&nbsp;</td>
                        <td></td>
					</tr>
					<tr>
                        <td><input type="text" value="" id="calc_days" size="5"></td>
						<td width="10">&nbsp;</td>
						<td><input type="text" value="" id="calc_hours" size="5"></td>
                        <td width="10">&nbsp;</td>
                        <td><button type="button" class="btn inline-action" onClick="do_min_calc();closeAllModals();return false;">'.hs_jshtmlentities(lg_ajax_at_insertmin).'</button></td>
					</tr>
				</table>';

        break;

    case 'remindershow':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $out = '<div style="width:400px;">
				<form action="'.$basepgurl.'" method="post" name="reminderpopupform" id="reminderpopupform">
				<input type="hidden" name="vmode" value="1">
				<input type="hidden" name="xRequest" value="'.$_GET['reqid'].'">';

        $out .= '<div class="field-wrap">
					<label class="datalabel" for="tReminder">'.lg_reminderpopup_reminder.'</label>
					<textarea id="tReminder" name="tReminder" cols="30" rows="4" style="width: 360px;" class=""></textarea>
				 </div>
				 <div class="field-wrap">
				 	<label class="datalabel">'.lg_reminderpopup_also.'</label>';

        $staffList = apiGetAllUsers();
        $staffList = rsToArray($staffList, 'xPerson', false);

        $out .= renderSelectMulti('reminder', $staffList, [$user['xPerson']], '', 'reminderto');
        $out .= '</div>
            <div class="field-wrap" style="width:300px;">
					<label class="datalabel" for="datetime">'.lg_reminderpopup_date.'</label>
				 	'.calinput('datetime', hs_strtotime('tomorrow', date('U')), true, true).'
				 </div>
        ';

        $out .= '<button type="button" name="submit" class="btn accent" onclick="if($(\'tReminder\').value == \'\' || $(\'datetime\').value == \'\'){hs_alert(\''.hs_jshtmlentities(lg_reminderpopup_remerror).'\');}else{submit_reminder();}return false;">'.lg_reminderpopup_submit.'</button>
				 </form>';

        $remindersrs = apiGetRemindersByReq($_GET['reqid'], $user['xPerson']);
        if (hs_rscheck($remindersrs) && $remindersrs->RecordCount() > 0) {
            $out .= displayContentBoxTop(lg_reminderpopup_reqreminders);
            while ($r = $remindersrs->FetchRow()) {
                $out .= '
				<div style="padding-bottom: 15px; margin-bottom:15px; border-bottom: 1px solid #eee">
					'.($r['xPersonCreator'] == $user['xPerson'] ? '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $_GET['reqid'], 'delreminder' => $r['xReminder']]).'" class="btn inline-action right">'.lg_request_deleterem.'</a>' : '').'
					<b>'.hs_showDate($r['dtGMTReminder']).'</b><br />
					'.nl2br($r['tReminder']).'
				</div>';
            }
            $out .= displayContentBoxBottom();
        }

        $out .= '<script type="text/javascript">
					//time to load form
					setTimeout(function(){$(\'tReminder\').focus();},1250);
				 </script>
				 </div>';
        break;

    case 'reminderset':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $_POST['xPersonCreator'] = $user['xPerson'];
        $_POST['dtGMTReminder'] = jsDateToTime(trim($_POST['datetime']), hs_setting('cHD_POPUPCALDATEFORMAT'));

        if (! empty($_POST['dtGMTReminder']) && $_POST['dtGMTReminder'] !== false) {
            apiAddReminder($_POST);
        }

        break;

    case 'emailheaders':
        $event = apiGetHistoryEvent($_GET['reqhisid']);

        if (! hs_empty($event['tEmailHeaders'])) {
            $headers = hs_unserialize($event['tEmailHeaders']);
            foreach ($headers as $key=>$value) {
                if ($key != 'rawheader') { //keep this as old notes will contain that element
                    if (is_array($value)) {
                        foreach ($value as $header_line) {
                            $out .= '<label class="datalabel">'.$key.'</label><p>'.hs_htmlspecialchars(hs_charset_emailheader($header_line)).'</p>';
                        }
                    } else {
                        $out .= '<label class="datalabel">'.$key.'</label><p>'.hs_htmlspecialchars(hs_charset_emailheader($value)).'</p>';
                    }
                }
            }
        }

        break;

    case 'emailsource':
        $event = apiGetHistoryEvent($_GET['reqhisid']);
        $event['tNote'] = hs_htmlspecialchars($event['tNote']);

        $out .= '<textarea name="emailsource" style="width:500px;height:500px;">'.$event['tNote'].'</textarea>';

        break;

    case 'request_history_init':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $fm = apiGetRequest($_GET['xRequest']);
        $allStaff = apiGetAssignStaff();
        $out = renderRequestHistory($_GET['xRequest'], $allStaff, $fm, false, true, $_GET['directlink']);

        break;

    case 'request_history_pin':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        return apiPinNote($_GET['xRequestHistory']);

        break;
    case 'request_history_showall':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $fm = apiGetRequest($_GET['xRequest']);
        $allStaff = apiGetAssignStaff();
        $out = renderRequestHistory($_GET['xRequest'], $allStaff, $fm, true, true, false, $_GET['fRequestHistoryView']);

		break;

	case "valid_wysiwyg_values":
		$out = lg_ajax_valid_wysiwyg_values;
		break;

	case "dragdrop": // dropzone
		include(cBASEPATH.'/helpspot/lib/api.requests.lib.php');
		if (Illuminate\Support\Facades\Request::hasFile('doc')) {
			$file = Illuminate\Support\Facades\Request::file('doc');
			if (!$file) continue;
			$mime = $file->getMimeType();
			$id = apiAddAttachment($file);
			// SRP violation you dirty dirty dirty developer. Document->isImage()
			$isImage = in_array($mime, $GLOBALS['imageMimeTypes']);
			header('Content-Type: application/json');
			echo json_encode([
				'id' => $id,
				'sFileName' => $file->getClientOriginalName(),
				'filePath' => cHOST.'/admin?pg=file&from=0&id='.$id,
				'isImage' => $isImage,
			]);
		}
		break;

	case "wysiwyg_upload": // tinymce
		$imageFolder = "images/";

		reset($_FILES);
		$temp = current($_FILES);
		$loc = $_GET['loc'];

		if (is_uploaded_file($temp['tmp_name'])) {
			// Verify extension
			if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png", "jpeg"))) {
				header("HTTP/1.0 500 Invalid extension.");
				die;
				return;
			}
			// Accept upload if there was no origin, or if it is an accepted origin
			if ($loc == 'kb') {
				$upload = app('HS\Attachments\ImageStore')->saveForKb($_GET['docid'], $temp);
			} else {
				$upload = app('HS\Attachments\ImageStore')->save($temp);
			}

			// Respond to the successful upload with JSON.
			// Use a location key to specify the path to the saved image resource.
			// { location : '/your/uploaded/image/file'}
			header('Content-Type: application/json');
			echo json_encode(['location' => $upload]);
		} else {
			// Notify Textbox.io editor that the upload failed
			header("HTTP/1.0 500 Server Error");
		}
		break;

	case "markdown_preview":
		//Hack to prevent placeholders from showing as headers in formatted text mode
		//$_POST['text'] = str_replace('##', '\#\#', $_POST['text']);
		$_POST['text'] = preg_replace('/##(.*)##/', '\#\#$1\#\#', $_POST['text']);

		$out = '<div style="padding:10px;">'.hs_markdown($_POST['text']).'</div>';
		break;

	case "markdown_syntax":
		$out .= '<table cellpadding="10" cellspacing="1" class="markdown_format_box" style="width:100%;padding:5px;">';
		$out .= '<tr><th>'.lg_request_mark_todo.'</th><th>'.lg_request_mark_type.'</th></tr>';
		$out .= '<tr><td><a href="http://www.google.com">Google</a></td><td>[Google](http://www.google.com)</td></tr>';
		$out .= '<tr><td><b>Bold phrase</b></td><td>**Bold phrase**</td></tr>';
		$out .= '<tr><td><i>Italic phrase<i></td><td>*Italic phrase*</td></tr>';
		$out .= '<tr><td><ul><li>Bulleted list</li><li>Bulleted list</li></ul></td><td>* Bulleted list<br>* Bulleted list</td></tr>';
		$out .= '<tr><td><ol><li>Numbered list</li><li>Numbered list</li></ol></td><td>1. Numbered list<br>2. Numbered list</td></tr>';
		$out .= '<tr><td><blockquote>Indented block</blockquote></td><td>> Indented block</td></tr>';
		$out .= '<tr><td>Line<br />break</td><td>Type 2 spaces and then a return</td></tr>';
		$out .= '<tr><td><hr></td><td>- - -</td></tr>';
		$out .= '<tr><td><h1>Large header</h1></td><td># Large header</td></tr>';
		$out .= '<tr><td><h2>Medium header</h2></td><td>## Medium header</td></tr>';
		$out .= '<tr><td><h3>Small header</h3></td><td>### Small header</td></tr>';
		$out .= '<tr><td align="center"><img src="'.static_url().'/static/img5/add-circle.svg" style="max-height:40px" /></td><td>![Title text](/path/to/img.jpg)</td></tr>';
		$out .= '</table>';
		$out .= '<div align="center" style="padding-top:4px;"><a href="http://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=149" target="_blank">'.lg_request_mark_full.'</a></div>';
		break;

	case "editreptag":
		//Change rep tag text
		$GLOBALS['DB']->Execute( 'UPDATE HS_Category_ReportingTags SET sReportingTag = ? WHERE xReportingTag = ?', array($_POST['value'],$_POST['xReportingTag']) );
		//Return string back for placement in page
		$out = $_POST['value'];
		break;

	case "editcustomdrop":
		//Get list items
		$listitems = hs_unserialize($GLOBALS['customFields'][$_GET['customfieldid']]['listItems']);
		//make change and reserialize
		$listitems[$_POST['index']] = $_POST['value'];
		$listitems = hs_serialize($listitems);
		//Save changes to DB
		$GLOBALS['DB']->Execute( 'UPDATE HS_CustomFields SET listItems=? WHERE xCustomField = ?', array($listitems,$_GET['customfieldid']) );
		//Return string back for placement in page
		$out = $_POST['value'];
		break;

	case "addfolder":
		$out = '
			<form onsubmit="return false">
                <div class="alert-title">'.lg_ajax_foldername.'</div>
			     <input type="text" name="new_folder" id="new_folder" size="20" style="width:100%; box-sizing: border-box;" onkeypress="return noenter(event);" value="'.hs_jshtmlentities($_GET['default']).'">
			</form>
		';

        break;

    case 'addCatGroup':
        $out = '
			<form onsubmit="return false">
			<label class="datalabel">'.hs_jshtmlentities(lg_ajax_groupname).'</label>
			<input type="text" name="new_group" id="new_group" size="20" style="width:180px;" onkeypress="return noenter(event);" value="">
			<button type="submit" name="button" class="btn inline-action" onClick="add_group_action($jq(\'#new_group\').val());">'.hs_jshtmlentities(lg_save).'</button>
			</form>
		';

        break;

    case 'mergeid':

        $out = '<form onsubmit="return false">
				<label class="datalabel">'.hs_jshtmlentities(lg_request_mergeid).'</label>
				<input type="text" name="merge_req_id" id="merge_req_id" size="8" value="">
				<button type="submit" name="button" class="btn inline-action" onClick="merge_request($jq(\'#merge_req_id\').val());">'.lg_request_merge.'</button>
				</form>';

        break;

    case 'mergeconfirm':
        $merge_into = apiGetRequest($_GET['req_into']);

        if ($merge_into && $_GET['req_into'] != $_GET['req_from']) {
            $out = '
			<div style="max-width: 400px; margin: 0 auto; padding: 10px 0 20px;">
				<div style="font-size:18px;text-align:center;">'.lg_request_mergeconftext1.' ('.$_GET['req_from'].')</div>
				<br>
				<div style="font-size:18px;text-align:center;"><b><u>'.lg_request_mergeconftext2.'</u></b></div>
				<br>
				<div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_mergeconfirmid.'</label></div>
                    <div class="control"><b>'.$merge_into['xRequest'].'</b></div>
                </div>
                <div class="hr"></div>
                <div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_custid.'</label></div>
                    <div class="control">'.$merge_into['sUserId'].'</div>
                </div>
                <div class="hr"></div>
                <div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_fname.'</label></div>
                    <div class="control">'.$merge_into['sFirstName'].'</div>
                </div>
                <div class="hr"></div>
                <div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_lname.'</label></div>
                    <div class="control">'.$merge_into['sLastName'].'</div>
                </div>
                <div class="hr"></div>
                <div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_email.'</label></div>
                    <div class="control">'.$merge_into['sEmail'].'</div>
                </div>
                <div class="hr"></div>
                <div class="fr">
                    <div class="label"><label class="datalabel">'.lg_request_mergeconfirmopen.'</label></div>
                    <div class="control">'.hs_showDate($merge_into['dtGMTOpened']).'</div>
                </div>
                <div class="footer-btns">
				    <button type="button" class="btn inline-action accent" onclick="merge_perform('.trim($_GET['req_from']).','.trim($_GET['req_into']).');return false;">'.lg_request_mergeconfbutton.'</button>
				</div>
			</div>
			';
        } else {
            $out = '<ul class="alert-error-list" style="width:500px;"><li>'.lg_request_mergebadid.'</li></ul>';
        }

        break;

    case 'do_merge':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $result = apiMergeRequests($_GET['req_from'], $_GET['req_into']);

        if (! $result) {
            header('HTTP/1.1 400 Bad Request');
        }

        break;

    case 'get_full_history':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $fm = apiGetRequest($_GET['reqid']);
        $allStaff = apiGetAssignStaff();

        $out .= renderRequestHistory($_GET['reqid'], $allStaff, $fm, true, false);

        //Make the note bodies show
        $out .= '
		<script type="text/javascript">
			$$(".note_body").each(function(o,index){
				o.style.width = "440px";
				o.style.display = "block";
			});

			//If we are in IE change table size to prevent horizontal scroll
			if(Prototype.Browser.IE){
				$$(".get_history_wrapper table:first").each(function(o){o.style.width = "96%"});
			}
		</script>';

        break;

    case 'build_drilldown':
        $out = '';
        $id = $_POST['xCustomField'];
        $drilldown_array = hs_unserialize($_POST['drilldown_array']);
        if (isset($_POST['new_value']) && ! hs_empty($_POST['new_value'])) {
            $p_new_value = utf8_trim(str_replace(['"', "'", "\n", '\\'], '', $_POST['new_value']));
        } //clean values
        if (isset($_POST['new_value_level']) && ! hs_empty($_POST['new_value_level'])) {
            $p_new_value_level = explode('#-#', $_POST['new_value_level']);
        }
        if (isset($_POST['remove_value'])) {
            $p_remove_value = $_POST['remove_value'];
        }

        //Add new value to array
        if (isset($p_new_value_level)) {
            //Loop over and save reference to next deeper array so that we can adjus the correct array
            $array = &$drilldown_array;
            for ($i = 0; $i < count($p_new_value_level); $i++) {
                $array = &$array[$p_new_value_level[$i]];
            }

            $array[$p_new_value] = false;
            ksort($array);
        } elseif (isset($p_new_value)) {
            $array = &$drilldown_array;
            $array[$p_new_value] = false;
            ksort($array);
        }

        if (isset($p_remove_value)) {
            $remove_path = explode('#-#', $p_remove_value);
            //Loop over and save reference to next deeper array so that we can adjus the correct array. -1 stops us before the final removal path key
            //since the element we want to delete is the final element of the path
            $array = &$drilldown_array;
            for ($i = 0; $i < count($remove_path) - 1; $i++) {
                $array = &$array[$remove_path[$i]];
            }

            unset($array[$remove_path[count($remove_path) - 1]]);
        }

        //$out .= RenderDrillDownList($id, $drilldown_array,array(),' ',true);
        $out .= '
			 <div style="background-color:#e6e6e6;padding:5px;margin-bottom:10px;padding:10px;border-radius:1px;">
                <div style="margin-bottom:5px;font-weight:bold;">'.lg_admin_cfields_drillview.'</div>
			 	<div>'.RenderDrillDownList($id, $drilldown_array, [], '<img src="' . static_url() . '/static/img5/angle-double-right-solid.svg" style="height: 20px;margin-left:3px;margin-right: 3px;" />').'</div>
			 </div>

			 <div>
			 	<div style="width:95%;margin-bottom:12px;"><b>'.lg_admin_cfields_drillmanage.'</b></div>
			 	<ul class="drilltree no-drop-line">'.RenderDrilDownEdit($drilldown_array).'</ul>
			 </div>

		';

        /*
        $out .= '<table width="380px"><tr valign="top">';
        $out .= '<td width="70%">
                 <div style="width:95%;border-bottom:1px solid #000;margin-bottom:5px;"><b>'.lg_admin_cfields_drillmanage.'</b></div>
                 <ul class="drilltree">'.RenderDrilDownEdit($drilldown_array).'</ul>
                 </td>';
        $out .= '<td>
                 <div style="border-bottom:1px solid #000;margin-bottom:5px;font-weight:bold;">'.lg_admin_cfields_drillview.'</div>
                 <div style="background-color:#e6e6e6;padding:5px;">
                 <div>'.RenderDrillDownList($id, $drilldown_array,array(),' </div><div style="padding-top:3px;"><img src="'.static_url().'/static/img/drilldown.gif" style="vertical-align: middle;" /> ').'</div>
                 </div>
                 </td>';
        $out .= '</tr></table>';
        */
        $out .= '<input type="hidden" name="drilldown_array" id="drilldown_array" value="'.utf8RawUrlEncode(hs_serialize($drilldown_array)).'" />';

        break;

    case 'batch_clear':
        session()->forget($_GET['batch_id']);

        break;

    case 'batch_respond':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/class.requestupdate.php';
        include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

        if (! is_numeric($_POST['reqid'])) {
            exit;
        }

        // Get the post data from the session.
        $post = session($_POST['batch_id']);
        $post['reqid'] = $_POST['reqid'];

        //Get original request
        $orig = apiGetRequest($_POST['reqid']);

        //Reopen request if it's closed
        if (intval($orig['fOpen']) === 0) {
            // Reopen request
            $orig['fOpen'] = 1;
            $orig['xStatus'] = hs_setting('cHD_STATUS_ACTIVE', 1);
            $orig['dtGMTOpened'] = date('U');
            //if the user isn't active then send to inbox
            $ustatus = apiGetUser($orig['xPersonAssignedTo']);
            if ($ustatus['fDeleted'] == 1) {
                $orig['xPersonAssignedTo'] = 0;
            }

            $update = new requestUpdate($_POST['reqid'], $orig, $user['xPerson'], __FILE__, __LINE__);
            $update->notify = false; //notify below instead
            $reqReopenResult = $update->checkChanges();
        }

        //filter out any empty fields, only update requests with new data. Keeps all custom fields from being reset, etc
        foreach ($post as $k=>$v) {
            if (hs_empty($v)) {
                unset($post[$k]);
            }
        }

        //merge in changes
        $post = array_merge($orig, $post);

        //override orig reqs dt to modify dt
        $post['dtGMTOpened'] = date('U');

        //set html notes
        $post['fNoteIsHTML'] = (hs_setting('cHD_HTMLEMAILS') ? 1 : 0);

        //Set CC/BCC
        $email_groups = getEmailGroups($orig);
        if (! empty($email_groups['last_cc'])) {
            if (empty($post['emailccgroup'])) {
                $post['emailccgroup'] = $email_groups['last_cc'];
            } else {
                $post['emailccgroup'] = $post['emailccgroup'].','.$email_groups['last_cc'];
            }
        }

        if (! empty($email_groups['last_bcc'])) {
            if (empty($post['emailbccgroup'])) {
                $post['emailbccgroup'] = $email_groups['last_bcc'];
            } else {
                $post['emailbccgroup'] = $post['emailbccgroup'].','.$email_groups['last_bcc'];
            }
        }

        //Set type as regular update or close update
        if ($post['batch_type'] == 'normal') {
            $post['sub_update'] = true;
        } else {
            $post['sub_updatenclose'] = true;
        }

        //Update request
        $result = apiProcessRequest($_POST['reqid'], $post, $_FILES, __FILE__, __LINE__);

        //Add batch note to history
        if (! isset($result['errorBoxText'])) {
            apiAddRequestHistory([
                'xRequest' => $_POST['reqid'],
                'xPerson' => $user['xPerson'],
                'dtGMTChange' => time() + 1,
                'tLog' => '',
                'tNote' => lg_lookup_22,
            ]);
        }

        //Handle results
        if (isset($result['errorBoxText'])) { //errors
            $out = 'error:general';
        } elseif (! empty($result['qstring'])) {	//This is an smtp error, notify batch system
            $out = 'error:smtp';
        } else { //everything went OK
            $out = 'ok';
        }

        break;

    case 'test_mailbox':

        $mailbox = new \HS\Mailbox();
        $mailbox->sType = trim($_GET['sType']);
        $mailbox->sUsername = trim($_GET['sUsername']);
        $mailbox->sPassword = encrypt(trim($_GET['sPassword']));
        $mailbox->sHostname = trim($_GET['sHostname']);
        $mailbox->sPort = trim($_GET['sPort']);
        $mailbox->sMailbox = trim($_GET['sMailbox']);
        $mailbox->sSecurity = trim($_GET['sSecurity']);
        $imap = new \HS\IncomingMail\Mailboxes\Imap($mailbox);

        try {
            $imap->connect();
            $out = displayFeedbackBox(lg_ajax_mailboxtestingsuccess, '100%');
        } catch (Exception $e) {
            $out = errorBox(lg_ajax_mailboxerror.': '.$e->getMessage(), '100%');
        }

        break;

    case 'test_outbound_email_ui':

        $out .= '
		<form name="test_email_form" id="test_email_form" onSubmit="return false;">
        <div style="padding:14px;min-width:600px;">
            <div class="fr">
                <div class="label"><label class="datalabel" for="test_email_to">'.lg_ajax_email_sendtestto.'</label></div>
                <div class="control"><input name="test_email_to" id="test_email_to" type="text" maxlength="255" value=""></div>
            </div>
            <div class="fr">
                <div class="label"><label class="datalabel">'.lg_ajax_email_method.'</label></div>
                <div class="control">'.($_GET['cHD_MAIL_OUTTYPE'] == 'smtp' ? lg_ajax_email_methodsmtp : lg_ajax_email_methodphp).'</div>
            </div>

            <div class="button-bar">
			 <button type="button" class="btn inline-action" onClick="sendTestEmail();">'.hs_jshtmlentities(lg_ajax_email_sendtest).'</button>
		    </div>
        </div>
        </form>
		';

        break;

    case 'test_outbound_email':
        $smtpSettings = [];
        foreach ($_GET as $k=>$v) {
            if (substr($k, 0, 9) == 'cHD_MAIL_') {
                $smtpSettings[$k] = trim($v);
            }
        }

        $message = (new \HS\Mail\Mailer\MessageBuilder(\HS\Mail\SendFrom::default()))
            ->to($_GET['test_email_to'])
            ->setSubject(lg_ajax_email_subject)
            ->setBodyHtml(lg_ajax_email_body)
            ->setBodyText(lg_ajax_email_body);

        $mailtestError = null;
        try {
            $mailer = \HS\Mail\HelpspotMailer::via( \HS\Mail\Mailer\SMTP::fromHelpSpotSettings($smtpSettings) )
                ->withAttachments( new \HS\Mail\Attachments() );

            $mailer->send( new \HS\Mail\HelpspotMessage($message) );
        } catch(\Exception $e) {
            $mailtestError = $e->getMessage();
        }

        $out = (is_null($mailtestError))
            ? displayFeedbackBox(lg_ajax_email_success, '500')
            : errorBox(lg_ajax_email_failed.': '.$mailtestError, '500');

        break;

    case 'ajax_field_lookup':
        include cBASEPATH.'/helpspot/lib/class.ajaxfield.php';

        if (function_exists('xml_parser_create')) {
            //Get XML
            $query = [];
            foreach ($_POST as $k=>$v) {
                if (! in_array($k, ['url', 'pg', 'action', 'rand'])) {
                    $query[] = $k.'='.urlencode($_POST[$k]);
                }
            }

            //Add info of person who's performing action
            $query[] = 'acting_person_xperson='.urlencode($user['xPerson']);
            $query[] = 'acting_person_fusertype='.urlencode($user['fUserType']);
            $query[] = 'acting_person_fname='.urlencode($user['sFname']);
            $query[] = 'acting_person_lname='.urlencode($user['sLname']);
            $query[] = 'acting_person_email='.urlencode($user['sEmail']);

            $xmlFile = hsHTTP($_POST['url'].'?'.implode('&', $query));

            //parse xml
            $xmlParser = xml_parser_create('ISO-8859-1');
            $llParser = new ajaxfield;

            xml_set_object($xmlParser, $llParser);
            xml_set_element_handler($xmlParser, 'start_element', 'end_element');
            xml_set_character_data_handler($xmlParser, 'data');
            xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);

            xml_parse($xmlParser, trim($xmlFile));

            //handle errors
            if (xml_get_error_code($xmlParser)) {
                errorLog(xml_error_string(xml_get_error_code($xmlParser)), 'AJAX Field', __FILE__, __LINE__);
                $feedbackArea = errorBox(lg_ajaxfield_xmlerror, '100%');
                $options = [];
            } else {
                $options = $llParser->getOptions();
            }
        } else {
            $out = '<p>'.lg_ajaxfield_noxml.'</p>';
        }

        //Free parser memory
        xml_parser_free($xmlParser);

        if (! empty($options)) {
            //Convert to internal HS encoding
            if (1 == 0) { //not currently used, need to use if next version has options for xml parser encoding to use
                foreach ($options as $key=>$value) {
                    foreach ($value as $k=>$v) {
                        $options[$key][$k] = $v;
                    }
                }
            }

            //Build select list
            $out .= '<select onchange="$(\''.$_POST['callingField'].'\').value=$F(this.id);$(\''.$_POST['callingField'].'_ajax_lookup\').innerHTML=\'\';$(\''.$_POST['callingField'].'_ajax_lookup\').hide();" id="\''.$_POST['callingField'].'_ajax_lookup_select\'">';
            $out .= '<option value=""></option>';
            foreach ($options as $key=>$value) {
                $out .= '<option value="'.hs_htmlspecialchars($value['value']).'">'.hs_htmlspecialchars($value['description']).'</option>';
            }
            $out .= '</select>';
        } else {
            $out = '<p>'.lg_ajaxfield_notfound.'</p>';
        }

        break;

    case 'push_request':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $result = doRequestPush($_POST['reqid'], $_POST['push_option'], $_POST['tComment']);

        if ($result['isobject']) {
            if (empty($result['errors'])) {
                //Get output for page
                $out = showPushesByReq($_POST['reqid']);
            } else {
                header('HTTP/1.1 400 Bad Request');
                $out = lg_ajax_push_error.' '.$result['errors'];
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
            $out = lg_ajax_push_notobject;
        }

        break;

    case 'push_details':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        //Include push file
        $push = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Request_Pushed WHERE xPushed = ?', [$_GET['xPushed']]);
        $customCodeFile = customCodePath('RequestPush-'.clean_filename($push['sPushedTo']).'.php');
        if (file_exists($customCodeFile)) {
            include $customCodeFile;

            //Init class
            $name = 'RequestPush_'.$push['sPushedTo'];
            if (class_exists($name)) {
                $rp = new $name;
            }

            //Get details
            $details_html = $rp->details($push['sReturnedID']);

            //Get user who did push
            if ($push['xPerson'] != -1) {
                $person = apiGetUser($push['xPerson']);
            } else {
                $person['sFname'] = lg_systemname;
            }

            $push_meta = '<div style="padding:0 10px;"><div class="field-wrap"><label class="datalabel">'.lg_ajax_push_metaby.'</label>'.$person['sFname'].' '.$person['sLname'].'</div></div>';
            $push_meta .= '<div style="padding:0 10px;"><div class="field-wrap"><label class="datalabel">'.lg_ajax_push_metato.'</label>'.$push['sPushedTo'].'</div></div>';
            $push_meta .= '<div style="padding:0 10px;"><div class="field-wrap"><label class="datalabel">'.lg_ajax_push_metacomment.'</label>'.nl2br($push['tComment']).'</div></div>';
            $push_meta .= '<div style="padding:0 10px;"><div class="field-wrap"><label class="datalabel">'.lg_ajax_push_metadate.'</label>'.hs_showDate($push['dtGMTPushed']).'</div></div>';
            $push_meta .= '<div style="padding:0 10px;"><div class="field-wrap"><label class="datalabel">'.lg_ajax_push_metaid.'</label>'.$push['sReturnedID'].'</div></div>';

            $out .= '<table style="margin-bottom:0px;min-width:800px;">';
            $out .= '<tr valign="top">
                        <td style="background-color: #eeeeee;padding:15px 20px;">
                            <div style="display:flex;justify-content:space-between;">
                                '.$push_meta.'
                            </div>
                        </td>
                    </tr>
                    <tr><td style="padding-top:20px;">'.$details_html.'</td></tr>';
            $out .= '</table>';
        } else {
            header('HTTP/1.1 400 Bad Request');
            $out = lg_ajax_push_nofile;
        }

        break;

    case 'logincheck':
        $out = 'OK';

        break;

    case 'logincheck_form':

        break;

    case 'save_draft_note':
            include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

            if ($ct = apiCreateDraft($_POST['xRequest'], $_POST['xPerson'], $_POST['tBody'])) {
                $out = $ct;
            } else {
                header('HTTP/1.1 400 Bad Request');
            }

        break;

    case 'get_draft_notes':
            include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

            if ($rs = apiGetDrafts($_GET['xRequest'], $_GET['xPerson'])) {
                $out = '<select id="draft_note_select" onchange="insert_draft_note();">';
                $out .= '<option value="">'.lg_ajax_select_draft.'</option>';
                while ($row = $rs->FetchRow()) {
                    $out .= '<option value="'.$row['xDraft'].'">'.hs_showDate($row['dtGMTSaved']).'</option>';
                }
                $out .= '</select>';
                $out = '<div style="padding:20px;">'.$out.'</div>';
            } else {
                header('HTTP/1.1 400 Bad Request');
            }

        break;

    case 'get_draft_note':
            include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

            if ($rs = apiGetDraft($_GET['xDraft'])) {
                $out = $rs['tNote'];
            } else {
                header('HTTP/1.1 400 Bad Request');
            }

        break;

    case 'rep_tags_for_cat':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $tags = [];
        $rs = apiGetReportingTags($_GET['xCategory']);

        foreach ($rs as $k=>$v) {
            $tags[] = [$k, $v];
        }

        header('Content-Type: application/json');
        echo json_encode($tags);

        break;

    case 'person_status_details':

        $person_status = new person_status();
        $person_status->update_status_details($_POST['xPersonStatus'], $_POST['sPage'], $_POST['fType'], $_POST['sDetails']);

        break;

    case 'person_status_workspace':

        $out .= '<div class="viewing-wrapper">'.renderRequestPagePersonStatus($_GET['reqid'], $user['xPerson']).'</div>';

        break;

    case 'person_status_requestpage':

        $out .= renderRequestPagePersonStatus($_GET['reqid'], $user['xPerson']);

        break;

    case 'submit_question':

        //Set data array
        $data = [];
        $data['sUserId'] = hs_setting('cHD_CUSTOMER_ID');
        $data['sFirstName'] = $user['sFname'];
        $data['sLastName'] = $user['sLname'];
        $data['sEmail'] = $user['sEmail'];
        $data['tNote'] = $_POST['tNote'];
        $data['xCategory'] = $_POST['xCategory'];

        //If OK to send tech details lets send a phpinfo dump
        if (isset($_POST['techdetails']) && $data['xCategory'] == 4) {
            $data['File1_sFilename'] = 'php_info_dump.html';
            $data['File1_sFileMimeType'] = 'text/html';

            ob_start();
            phpinfo();
            $data['File1_bFileBody'] = base64_encode(ob_get_contents());
            ob_end_clean();
        }

        $curl = curl_init('https://support.helpspot.com/api/index.php?method=request.create');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $curl_return = curl_exec($curl);
        curl_close($curl);

        //Detect error on UserScape end
        $inerror = strpos($curl_return, 'errors');

        if ($curl_return == false || $inerror) {
            $out = '
				<b class="red">'.lg_ajax_nh_error.':</b><br />'.lg_ajax_nh_errormsg.'
			';
        } else {
            //Find request ID
            //preg_match('|<xRequest>(.*)</xRequest>|',$curl_return,$match);
            //$reqid = $match[1];

            $out = '
				<b style="color:green;">'.lg_ajax_nh_success.'</b><br />
				'.lg_ajax_nh_success1.' <u>'.$user['sEmail'].'</u> '.lg_ajax_nh_success2.'
			';
        }

        break;

    case 'kb_afterorder_list':
        include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

        //Create after order list
        $tree = apiBuildChapPageTree($_GET['xBook'], true);
        $chaps = apiTocChaps($tree);

        $afterorderlist = '<option value="0">'.lg_kb_firstchap.'</option>';
        if (is_array($chaps)) {
            foreach ($chaps as $c) {
                if ($c['xChapter'] != $_GET['xChapter']) {
                    $afterorderlist .= '<option value="'.$c['xChapter'].'">'.lg_kb_after.' '.$c['sChapterName'].'</option>';
                }
            }
        }

        $out = $afterorderlist;

        break;

    case 'clear_stuck_emails':

        //Empty table
        $GLOBALS['DB']->Execute('DELETE FROM HS_Mailboxes_StuckEmails');

        //Reset counters
        $GLOBALS['DB']->Execute('UPDATE HS_Mailboxes SET iLastImportAttemptCt = 0');
        \Facades\HS\Cache\Manager::forget(\Facades\HS\Cache\Manager::key('CACHE_STUCKEMAILS_KEY'));

        break;

    case 'bizhours_create_holiday':
        $new_bizhours = hs_unserialize(hs_setting('cHD_BUSINESS_HOURS'));

        if (! isset($new_bizhours['holidays'])) {
            $new_bizhours['holidays'] = [];
        }

        $er_dateset = false;
        $error = '';

        //Create a holiday
        if ($_POST['bh_create'] == 1) {
            $date_ts = mktime(12, 0, 0, $_POST['bh_newholiday_iMonth'], $_POST['bh_newholiday_iDay'], $_POST['bh_newholiday_iYear']);
            $date = date('Y.m.d', $date_ts);

            //Check that date isn't already set
            if (! isset($new_bizhours['holidays'][$date])) {
                if (! $_POST['bh_newholiday_nohours']) {
                    $new_bizhours['holidays'][$date] = [
                        'start'	=>$_POST['bh_newholiday_start'],
                        'end'		=>$_POST['bh_newholiday_end'],
                    ];
                } else {
                    $new_bizhours['holidays'][$date] = false;
                }

                //Sort holidays
                krsort($new_bizhours['holidays']);

                //Save
                storeGlobalVar('cHD_BUSINESS_HOURS', hs_serialize($new_bizhours));
            } else {
                $er_dateset = true;
            }
        }

        if (count($new_bizhours['holidays']) > 0) {
            if ($er_dateset) {
                $error = '<br />'.errorBox(lg_admin_settings_bizhours_holidays_erdate, '100%');
            }

            $out = '<table width="100%">
					<tr>
						<td colspan="5"><hr />'.$error.'</td>
					</tr>
					<tr>
						<th width="220">'.lg_admin_settings_bizhours_date.'</th>
						<th width="100">'.lg_admin_settings_bizhours_nohours.'</th>
						<th>'.lg_admin_settings_bizhours_start.'</th>
						<th>'.lg_admin_settings_bizhours_end.'</th>
						<th width="16"></th>
					</tr>';

            foreach ($new_bizhours['holidays'] as $k=>$v) {
                $showdate = explode('.', $k);
                $out .= '
				<tr id="holiday_'.$k.'">
					<td><label class="datalabel">'.hs_showShortDate(mktime(12, 0, 0, $showdate[1], $showdate[2], $showdate[0])).'</label></td>
					<td align="center" width="100">'.($v === false ? lg_yes : lg_no).'</td>
					<td align="center">'.($v === false ? '-' : hs_ShowBizHoursFormat($v['start'])).'</td>
					<td align="center">'.($v === false ? '-' : hs_ShowBizHoursFormat($v['end'])).'</td>
					<td><a href="" onclick="bh_delete(\''.$k.'\');return false;"><img src="'.static_url().'/static/img5/remove.svg" class="hand svg28" border="0" /></a></td>
				</tr>';
            }

            $out .= '</table>';
            if ($date) {
                $out .= '
				<script type="text/javascript">
					new Effect.Highlight("holiday_\'.$date.\'",{duration:5.0, startcolor:\'#ffff99\', endcolor:\'#ffffff\'});
				</script >';
            }
        }

        break;

    case 'bizhours_delete_holiday':
        if (isset($_POST['bh_date'])) {
            $new_bizhours = hs_unserialize(hs_setting('cHD_BUSINESS_HOURS'));
            unset($new_bizhours['holidays'][$_POST['bh_date']]);

            //Save
            storeGlobalVar('cHD_BUSINESS_HOURS', hs_serialize($new_bizhours));
        }

        break;

    case 'search_customers':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        $search = apiRequestCustHisSearch($_GET);

        $out = recordSetTable($search,
                                [$GLOBALS['filterCols']['view'],
                                      $GLOBALS['filterCols']['fOpen'],
                                      $GLOBALS['filterCols']['dtGMTOpened'],
                                      $GLOBALS['filterCols']['sUserId'],
                                      $GLOBALS['filterCols']['fullname'],
                                      $GLOBALS['filterCols']['sEmail'],
                                      $GLOBALS['filterCols']['reqsummary'], ],
                                ['title'=>lg_search_result,
                                       'showcount'=>$search->RecordCount(),
                                       'from_run_filter'=>true,
                                       'width'=>'100%', ]);

        break;

    case 'search_data':
        if ($_GET['area'] == 'kb') {
            // KB
            $search = apiKbSearch($_GET, $advanced = true);

            $srs = new array2recordset;
            $srs->init($search);
        } else {
            // Else search requests
            require_once(cBASEPATH.'/helpspot/lib/api.requests.lib.php');
            $_GET['sSearch'] = $_GET['q'];
            $_GET['search_type'] = 8; // general search

            // Requests
            $search = apiRequestHistorySearch($_GET,false,__FILE__,__LINE__);

            $req = [];
            foreach($search->Records() as $key => $row) {
                $r = (array)$row;
                $req[] = [
                    'title' => lg_search_request.': '.$r['xRequest'].' * '.boolShow($r['fOpen'], lg_isopen, lg_isclosed).' * '.$r['sFirstName'].' '.$r['sLastName'],
                    'subject' => $r['sTitle'],
                    'link' => '?pg=request&reqid='.$r['xRequest'],
                    'desc' => utf8_substr(strip_tags(html_entity_decode(stripFormBlurbs($r['tNote']), ENT_COMPAT, 'UTF-8')), 0, 200),
                    'icon' => '',
                    'score' => round($r['score'] ?? 0, 2),
                    'date' => hs_showDate($r['dtGMTOpened']),
                ];
            }

            $srs = new array2recordset;
            $srs->init($req);
        }

        $code = '<b><a href="%s">%s</a></b><br>%s';
        $fields = ['link', 'title', 'desc'];

        // Requests only, not KB results, so we just check for $req
        if (isset($req[0]) && isset($req[0]['subject'])) {
            $code = '<b data-score="%s"><a href="%s">%s</a></b><br><span class="initsubject">%s - </span>%s';
            $fields = ['score', 'link', 'title', 'subject', 'desc'];
        }

        $nt = [];
        $nt[] = ['type'=>'link', 'label'=>'', 'sort'=>0, 'width'=>'', 'fields'=>'title',
            'code'=>$code, 'linkfields'=>$fields, ];
        //$nt[] 	= array('type'=>'string','label'=>'','sort'=>0,'width'=>'','fields'=>'desc','hideflow'=>true);
        $nt[] = ['type'=>'string', 'label'=>'', 'sort'=>0, 'width'=>'180', 'fields'=>'date'];

        $out .= recordSetTable($srs, $nt, [
            'title'=>lg_search_result,
            'showcount'=> $srs->RecordCount(),
            'from_run_filter'=>true,
            'width'=>'100%',
        ]);

        break;

    case 'search_adv':

        //Add columns
        $_GET['displayColumns'] = ['view', 'fOpen', 'dtGMTOpened', 'sUserId', 'fullname', 'sEmail', 'reqsummary'];

        $rule = new hs_auto_rule();
        $rule->returnrs = true; //return result set instead of doing actions
        $rule->SetAutoRule($_GET);

        $ft = new hs_filter();
        $ft->filterDef = $rule->getFilterConditions();

        $result = $ft->outputResultSet();
        $count = is_object($result) ? $result->RecordCount() : 0;

        //Columns to show
        foreach ($_GET['displayColumns'] as $k=>$v) {
            $cols[$v] = $GLOBALS['filterCols'][$v];
        }

        $out = recordSetTable($result,$cols,
                                //options
                                ['width'=>'100%',
                                        'from_run_filter'=>true,
                                        'title'=>lg_search_result,
                                        'showcount'=>$count, ]);

        break;

    case 'search_tags':
        $pages = apiTagSearchPages($_GET['sTag']);
        $nt = [];
        $nt[] = ['type'=>'link', 'label'=>'', 'sort'=>0, 'width'=>'', 'fields'=>'sPageName',
                            'code'=>'<a href="'.str_replace('%25s', '%s', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.page', 'page' => '%s'])).'">%s</a><br /><span class="search-meta">%s</span>', 'linkfields'=>['xPage', 'sPageName', 'sBookName'], ];
        $pagers = recordSetTable($pages, $nt,
                            ['title'=>lg_search_resultkb,
                                   'showcount'=>($pages ? $pages->RecordCount() : '0'),
                                   'from_run_filter'=>true,
                                   'width'=>'100%', ]);

        $out = '
			<div class="yui-g">'.$pagers.'</div>
		';

        break;

    case 'addressbook':

        $out = '
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr valign="top">
					<td>
						<div style="float:left;width:20px;padding:0px;margin-top:10px;border:none !important;">
						';

                        foreach (range('A', 'Z') as $i) {
                            $out .= '<a href="javascript:addressBookScroll(\''.$i.'\');" id="ab_letter_'.$i.'" class="ab-list-letter-link">'.$i.'</a>';
                        }

            $out .= '
						</div>
					</td>
					<td>
						<div style="width:300px;">
							<div id="ab_contact_header">'.lg_addressbook_contacts.'</div>
							<div id="ab_contact_list"></div>
						</div>
					</td>
					<td>

						<div class="tab_wrap" id="addressbook_tabs" style="width:375px; padding-left: 20px;">
							<ul class="tabs">
								<li class="noicon"><a href="#ab_livelookup_tab"><span>'.lg_addressbook_livelookup.'</span></a></li>
								<li class="noicon"><a href="#ab_addcontact_tab"><span>'.lg_addressbook_addcontact.'</span></a></li>
							</ul>

							<div name="ab_livelookup_tab" id="ab_livelookup_tab">
								<form action="" id="ab_livelookup_form" name="ab_livelookup_form" onsubmit="return false;">
								<div class="box_top_note">'.lg_addressbook_livelookup_ex.'</div><br />

								';

                                $livelookup_sources = hs_unserialize(hs_setting('cHD_LIVELOOKUP_SEARCHES'));
                                if (hs_setting('cHD_LIVELOOKUP') == 1 && is_array($livelookup_sources)) {
                                    $out .= '
									<p><label class="datalabel">'.lg_addressbook_llsource.'</label><select name="source_id" id="live_lookup_search_source" style="width:90%;">';
                                    foreach ($livelookup_sources as $key=>$value) {
                                        $out .= '<option value="'.$key.'">'.$value['name'].'</option>';
                                    }
                                    $out .= '
									</select></p>

									<p><label class="datalabel">'.lg_addressbook_fname.'</label>
										<input type="text" name="first_name" value="" tabindex="8888" style="width:90%;" />
									</p>

									<p><label class="datalabel">'.lg_addressbook_lname.'</label>
										<input type="text" name="last_name" value="" tabindex="8889" style="width:90%;" />
									</p>

									<button type="button" name="submit" class="btn inline-action" onclick="addressBookLiveLookup();">'.lg_addressbook_search.'</button>';
                                } else {
                                    $out .= '<div class="box_top_note">'.lg_addressbook_livelookup_na.'</div>';
                                }

                            $out .= '
							</form>
							</div>

							<div name="ab_addcontact_tab" id="ab_addcontact_tab">
                            <form action="" id="ab_addcontact_form" name="ab_addcontact_form" onsubmit="return false;">

                                <p><label class="datalabel req">'.lg_addressbook_fname.'</label>
									<input type="text" name="sFirstName" title="'.lg_validation_required.'" value="" class="required" style="width:90%;" required />
								</p>

								<p><label class="datalabel req">'.lg_addressbook_lname.'</label>
									<input type="text" name="sLastName" title="'.lg_validation_required. '" value="" class="required" style="width:90%;" required />
								</p>

								<p><label class="datalabel req">'.lg_addressbook_email.'</label>
									<input type="email" name="sEmail" title="'.lg_validation_email. '" value="" class="required validate-email" style="width:90%;" required />
								</p>

								<p><label class="datalabel">'.lg_addressbook_persontitle.'</label>
									<input type="text" name="sTitle" value="" style="width:90%;" />
								</p>

								<p><label class="datalabel">'.lg_addressbook_desc.'</label>
									<textarea name="sDescription" style="width:90%;height:40px;"></textarea>
								</p>

								<p><label class="datalabel">'.lg_addressbook_highlight.'</label>'.lg_addressbook_highlightex.'<br />
									<select name="fHighlight">
										<option value="0">'.lg_no.'</option>
										<option value="1">'.lg_yes.'</option>
									</select>
								</p>

								<button type="button" name="submit" class="btn inline-action" onclick="if(ab_valid.validate()){addressBookAddContact();}">'.lg_addressbook_add.'</button>
							</form>
							</div>
						</div>
					</td>
				</tr>
			</table>
		';

        break;

    case 'addressbook_internal':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        $ppl = apiGetABContacts();
        $out .= addressBookList($ppl);

        break;

    case 'addressbook_livelookup':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/livelookup.php';

        $ppl = apiLiveLookup($_POST, 'addressbook');
        $out = addressBookList($ppl, 'livelookup');

        break;

    case 'addressbook_addcontact':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

        apiCreateABContact($_POST);

        break;

    case 'addressbook_deletecontact':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        apiDeleteABContact($_POST['xContact']);

        $ppl = apiGetABContacts();
        $out .= addressBookList($ppl);

        break;

    case 'simplemenu_spam':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        $fm = apiGetRequest($_POST['reqid']);
        $spamreqhis = apiGetInitialRequest($_POST['reqid']);
        if (! hs_empty($spamreqhis['tEmailHeaders']) || $fm['fOpenedVia'] == 7) {
            $fm['xStatus'] = hs_setting('cHD_STATUS_SPAM', 2);				//set to spam
            $fm['xPersonAssignedTo'] = 0;	//no assignee
            $fm['dtGMTOpened'] = date('U');	//current dt
            $update = new requestUpdate($_POST['reqid'], $fm, $user['xPerson'], __FILE__, __LINE__);
            $reqResult = $update->checkChanges();

            \Facades\HS\Cache\Manager::forgetFilter('spam');
        } else {
            $out = lg_workspace_notemailqm;
        }

        break;

    case 'simplemenu_trash':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        if (perm('fCanManageTrash')) {
            $fm = apiGetRequest($_POST['reqid']);
            $fm['fTrash'] = 1;
            $fm['dtGMTTrashed'] = date('U');
            $fm['dtGMTOpened'] = date('U');	//current dt
            $update = new requestUpdate($_POST['reqid'], $fm, $user['xPerson'], __FILE__, __LINE__);
            $reqResult = $update->checkChanges();

            \Facades\HS\Cache\Manager::forgetFilter('trash');
        }

        break;

    case 'search_tips':
        $out = 'tips and things';

        break;

    case 'response_search_json':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        $results = apiGetAllRequestResponses(0, $user['xPerson'], $user['fUserType'], false, '');

        //Return list
        if (! empty($results)) {
            $resp_array = [];
            while ($row = $results->FetchRow()) {
                    array_push($resp_array,array('text'=>$row['sResponseTitle'], 'id'=>$row['xResponse']));
                }
        } else {
            //no matches
            return;
        }
        echo json_encode($resp_array);
        break;

    case 'response_search':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        $results = apiGetAllRequestResponses(0, $user['xPerson'], $user['fUserType'], false, '');

        $out .= '<ul>';

        if (empty($results)) {
            $out .= '<li>No Match</li></ul>';
            break;
        }

        while ($row = $results->FetchRow()) {
            if (stripos($row['sResponseTitle'], $_POST['search']) !== false) {
                //Set URL in hidden span so we can redirect direct from JS
                $title = formCleanHtml($row['sFolder']).' / '.formCleanHtml($row['sResponseTitle']).($row['fDeleted'] == 1 ? ' <b class="red"> - '.lg_inactive_text.'</b>' : '');
                $out .= '<li>'.$title.'<span style="display:none;">'.$row['xResponse'].'</span></li>';
            } elseif (stripos($row['tResponse'], $_POST['search']) !== false) {
                $title = formCleanHtml($row['sFolder']).' / '.formCleanHtml($row['sResponseTitle']).($row['fDeleted'] == 1 ? ' <b class="red"> - '.lg_inactive_text.'</b>' : '');
                $textSearchResult .= '<li>'.$title.'<span style="display:none;">'.$row['xResponse'].'</span></li>';
            }
        }

        if ($textSearchResult) {
            $out .= '<div style="font-weight:bold; padding:5px; font-size:85%; background-color:#f2f2f2; color:#555555;">More Results...</div>'.$textSearchResult;
        }

        $out .= '</ul>';

        break;

    case 'staff_search_json':

        //Get all staff
        $staff = apiGetAllUsersComplete();
        if (empty($staff)){
            return;
        }

        $staff_array = [];
        foreach ($staff as $r) {
            if ($r['xPerson'] == $user['xPerson'] || $r['fDeleted'] == 1) {
                continue;
            }
            array_push($staff_array,array('text'=>formCleanHtml($r['fullname']), 'id'=>$r['xPerson'].'|'.$r['fullname']));
        }

        echo json_encode($staff_array);
        break;

    case 'staff_search':
        include cBASEPATH.'/helpspot/lib/class.string.sort.php';

        //Get all staff
        $strings = [];
        $inactive = [];
        $staff = apiGetAllUsersComplete();
        foreach ($staff as $r) {
            $strings[$r['xPerson']] = $r['fullname'].($r['fDeleted'] == 1 ? ' <b class="red"> - '.lg_inactive_text.'</b>' : '');
            if ($r['fDeleted'] == 1) {
                $inactive[$r['xPerson']] = $r['xPerson'];
            }
        }

        $sorter = new string_sort($strings);
        $sorted = $sorter->dosort($_POST['search']);

        $results = [];
        foreach ($sorted as $k=>$v) {
            //Find key of original strings by searching for matching title
            if ($xperson = array_search($v, $strings)) {
                //Set result data from original lookup array $admin_search
                $results[$xperson] = $strings[$xperson];
            }
        }

        //Return list
        $out .= '<ul>';
        if (! empty($results)) {
            foreach ($results as $k=>$v) {
                //Set URL in hidden span so we can redirect direct from JS
                $out .= '<li>'.$results[$k].'<span style="display:none;">'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.users', 'resourceid' => $k, 'showdeleted' => (isset($inactive[$k]) ? 1 : 0)]).'</span></li>';
            }
        } else {
            $out .= '<li>'.lg_search_nomatch.'</li>';
        }
        $out .= '</ul>';

        break;

    case 'placeholder_tags_json':

        //Get all staff
        $tags = placeholderTags(false, false);
        if (empty($tags)) {
            return;
        }
        $tag_array = [];
        foreach ($tags as $k=>$v) {
            array_push($tag_array,array('text'=>$v, 'id'=>$k));
        };

        echo json_encode($tag_array);
        break;

    case 'set_admin_customizations':
        storeGlobalVar('cHD_ADMIN_CSS', $_POST['cHD_ADMIN_CSS']);
        storeGlobalVar('cHD_ADMIN_JS', $_POST['cHD_ADMIN_JS']);
        $out .= displayFeedbackBox(lg_saved_success);

        break;

    case 'set_theme':
        storeGlobalVar('cHD_THEME_PORTAL', $_POST['cHD_THEME_PORTAL']);
        $out .= displayFeedbackBox(lg_theme_changed);

        break;

    case 'edit_portal_template':

        $previous = [];

        if ($_GET['xPortal'] > 0) {
            $pid = $_GET['xPortal'];
            $portal = apiGetPortal($_GET['xPortal']);
            $path = $portal['sPortalPath'];
        } else {
            $pid = 0;
            $path = public_path();
        }

        //Get latest template
        if (strpos($_GET['template'], '/') !== false || strpos($_GET['template'], ' ') !== false) {
            exit;
        }

        if (file_exists($path.'/custom_templates/'.clean_filename($_GET['template']))) {
            $code = file_get_contents($path.'/custom_templates/'.$_GET['template']);

            //Find any previous versions
            if ($backups = listFilesInDir($path.'/custom_templates/backups')) {
                foreach ($backups as $k=>$file) {
                    $name = explode('_', $file);
                    if (strpos($_GET['template'], $name[0]) !== false) {
                        $previous[] = '<option value="'.$file.'">'.hs_showDate(str_replace('.php', '', $name[1])).'</option>';
                        //$previous[] = array('file'=>$file,'date'=>str_replace('.php','',$name[1]));
                    }
                }
            }
        } elseif (file_exists(cBASEPATH.'/helpspot/templates/'.clean_filename($_GET['template']))) {
            $code = file_get_contents(cBASEPATH.'/helpspot/templates/'.$_GET['template']);
        }

        $id = rand(11111111, 99999999);
        $out = '
			<div style="width: 600px;">
				<form action="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.templates', 'xPortal' => $pid]).'" method="POST">
					'.csrf_field().'
					<div class="popup-btn">
						'.(! empty($previous) || file_exists($path.'/custom_templates/'.clean_filename($_GET['template'])) ? '<select onchange="$(\'id_'.$id.'\').innerHTML=\'\';new Ajax.Updater(\'id_'.$id.'\',\'admin?pg=ajax_gateway&action=load_backup_template\',{method:\'get\',parameters: {xPortal:'.$pid.',template:$F(this)}});">
							<option value="">'.lg_admin_portaltemplates_viewprev.' '.hs_htmlentities($_GET['template']).'</option>
							'.implode('', $previous).'
							<option value="'.$_GET['template'].'">'.lg_admin_portaltemplates_originaltemplate.'</option>
						</select>' : ''). '
					</div>
                    <div class="field-wrap"><textarea id="id_'.$id.'" class="lined" wrap="off" name="template_code" style="width:100%;height:400px;">'.hs_htmlentities($code). '</textarea></div>
                    <input type="hidden" name="template_name" value="'.$_GET['template']. '" />
                    <div class="footer-btns">
                        <button type="submit" name="submit" class="btn accent">' . lg_admin_portaltemplates_savebutton . '</button>
                    </div>
				</form>
			</div>
		';

        break;

    case 'load_backup_template':

        if ($_GET['xPortal'] > 0) {
            $pid = $_GET['xPortal'];
            $portal = apiGetPortal($_GET['xPortal']);
            $path = $portal['sPortalPath'];
        } else {
            $pid = 0;
            $path = cBASEPATH;
        }

        if (strpos($_GET['template'], 'tpl.php') !== false) { //no timestamp so return original template
            $out = hs_htmlentities(file_get_contents($path.'/helpspot/templates/'.$_GET['template']));
        } elseif (file_exists($path.'/custom_templates/backups/'.clean_filename($_GET['template']))) {
            $out = hs_htmlentities(file_get_contents($path.'/custom_templates/backups/'.$_GET['template']));
        }

        break;

    case 'test_ldap':
        require_once cBASEPATH.'/helpspot/lib/adLDAP/adLDAP.php';

        $adldap = new adLDAP($_GET);

        if ($adldap->authenticate($_GET['username'], $_GET['password'])) {
            $out = displayFeedbackBox(lg_ajax_ldapsuccess, '100%');
        } else {
            //adldap will oddly return success when auth fails under last error
            $error = $adldap->get_last_error() == 'Success' ? '' : ': '.$adldap->get_last_error();

            $out = errorBox(lg_ajax_ldaperror.$error, '100%');
        }

        break;

    case 'req_subscribe':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        apiSubscribeToRequest($_GET['xRequest'], $user['xPerson']);

        break;

    case 'req_unsubscribe':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        apiUnSubscribeToRequest($_GET['xRequest'], $user['xPerson']);

        break;

    case 'req_noturgent':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        if( ! apiCurrentUserCanAccessRequest($_GET['xRequest'])) exit();
        $request = apiGetRequest($_GET['xRequest']);

        $request['fUrgent'] = 0;

        $update = new requestUpdate($_GET['xRequest'], $request, $user['xPerson'], __FILE__, __LINE__);
        $update->checkChanges();

        break;

    case 'req_isurgent':
        include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        include cBASEPATH.'/helpspot/lib/class.requestupdate.php';

        if( ! apiCurrentUserCanAccessRequest($_GET['xRequest'])) exit();
        $request = apiGetRequest($_GET['xRequest']);

        $request['fUrgent'] = 1;

        $update = new requestUpdate($_GET['xRequest'], $request, $user['xPerson'], __FILE__, __LINE__);
        $update->checkChanges();
        break;

    case "stream":
        include(cBASEPATH.'/helpspot/lib/api.requests.lib.php');

        $_POST['limit'] = isset($_POST['limit']) ? (int) $_POST['limit'] : 20;
        $xrh = (int) $_POST['limit'] > 0 ? $_POST['last_xRequestHistory'] : $_POST['oldest_xRequestHistory'];

        $history = apiGetFilterStream($_POST['reqids'],$_POST['sFilterView'],$xrh,$_POST['limit']);
        $people = apiGetAllUsersComplete();

        if(hs_rscheck($history)){
            while($row = $history->FetchRow()){
                $request = apiGetRequestCustomer($row['xRequest'],__FILE__,__LINE__);

                $out .= noteItem($row,$people,$request,array(
                    'stream'=>true,
                    'reqid_link'=>true,
                    'clickable_text'=>true
                ));
            }

            if($history->RecordCount() == abs($_POST['limit'])){
                $out .= '
                <div class="note-stream-load-more" id="note-stream-load-more">
                    <div class="note-stream-load-more-top "></div>
                    <div class="clearfix">
                        <a href="" class="btn centerbtn" onclick="$jq(\'#note-stream-load-more\').addClass(\'note-stream-load-more-nobg\').html(ajaxLoading());loadMore();return false;">'.lg_stream_loadmore.'</a>
                    </div>
                    <div class="note-stream-load-more-bottom"></div>
                </div>';
            }
        }

        break;

    case 'search_add_tag':
        //Find tag name
        $tag = apiGetTagById($_GET['xTag']);
        $out .= renderTag($_GET['xTag'], $tag, 'sTag[]', true);

        break;

    case 'tag_autocomplete':

        $tags = apiTagAutocompleteSearch($_POST['search']);

        $out .= '<ul>';
        if ($tags) {
            foreach ($tags as $k=>$t) {
                $out .= '<li>'.$t.'</li>';
            }
        }
        $out .= '</ul>';

        break;

    case 'report_data':
        // Prevent timeouts on Matrix and other long running reports
        set_time_limit(0);

        include cBASEPATH.'/helpspot/lib/class.reports.php';

        if (! function_exists('mb_convert_encoding')) {
            header('Content-Type: application/json;');
        } else {
            header('Content-Type: application/json; charset=utf-8');
        }

        $GLOBALS['lang']->load(['reports', 'todayboard']);

        $report = new reports($_REQUEST);
        $method = $_GET['show'];

        if (method_exists($report, $method)) {
            // Json header already sent above
            echo json_encode($report->$method());
        }

        break;

    case 'report_save':
        $GLOBALS['lang']->load(['reports']);
        $rs = $GLOBALS['DB']->Execute('INSERT INTO HS_Saved_Reports (xPerson,sReport,sPage,sShow,tData)
								 		VALUES (?,?,?,?,?)', [$user['xPerson'], $_POST['sReport'], $_POST['sPage'], $_POST['sShow'], hs_serialize($_POST)]);

        if ($rs) {
            $out = lg_reports_reportsaved;
        }

        break;

    case 'report_resave':
        $xreport = $_POST['xReport'];
        unset($_POST['xReport']);
        $GLOBALS['lang']->load(['reports']);
        $rs = $GLOBALS['DB']->Execute('UPDATE HS_Saved_Reports SET tData = ? WHERE xReport = ?',
                                        [hs_serialize($_POST), $xreport]);

        if ($rs) {
            $out = lg_reports_resavedone;
        }

        break;

    case 'report_delete':
        $rs = $GLOBALS['DB']->Execute('DELETE FROM HS_Saved_Reports WHERE xReport = ?', [$_POST['xReport']]);

        break;

    case 'update_reporting_tag':
        $tag = str_replace('tag_', '', $_POST['xReportingTag']);
        $value = $_POST['new_value'];

        if (! empty($value)) {
            $rs = $GLOBALS['DB']->Execute('UPDATE HS_Category_ReportingTags
										   SET sReportingTag = ?
										   WHERE xReportingTag = ?',
                                            [$value, $tag]);

            if ($rs) {
                $out = true;
            }
        }

        break;

    case 'get_request_viewers':

        if (app('cache')->has('filter-viewers')) {
            $out = app('cache')->get('filter-viewers');
        } else {
            $viewers = new person_status();
            $out = $viewers->get_all_viewing();

            // you can only set times less than 1 minute via decimals
            app('cache')->add('filter-viewers', $out, 0.15);
        }

        header('Content-Type: application/json');
        $out = json_encode($out);

        break;
}

function searchKb($query, $searcher, $renderer)
{
    $results = $searcher->knowledgeBooks($query);
    $viewPresenter = new HS\View\Search\FulltextKnowledgeBookPresenter($results, $renderer);

    return $viewPresenter->render();
}

function searchCustomers($query, $searcher, $renderer, $searchHistory = false)
{
    global $user;

    // protect search results when in limited access mode
    $categoriesAllowed = [];
    if (perm('fLimitedToAssignedCats')) {
        $categoriesAllowed = apiGetUserCats($user['xPerson']);
    }

    // If user can only see own requests
    $xPerson = null;
    if (perm('fCanViewOwnReqsOnly')) {
        $xPerson = $user['xPerson'];
    }

    if ($searchHistory) {
        $results = $searcher->requests($query, $categoriesAllowed, $xPerson);
    } else {
        $results = $searcher->customers($query, $categoriesAllowed, $xPerson);
    }

    $viewPresenter = new HS\View\Search\SidebarRequestPresenter($results, $renderer);

    return $viewPresenter->render();
}

function searchHistory($query, $searcher, $renderer)
{
    global $user;

    // protect search results when in limited access mode
    $categoriesAllowed = [];
    if (perm('fLimitedToAssignedCats')) {
        $categoriesAllowed = apiGetUserCats($user['xPerson']);
    }

    // If user can only see own requests
    $xPerson = null;
    if (perm('fCanViewOwnReqsOnly')) {
        $xPerson = $user['xPerson'];
    }

    $results = $searcher->customers($query, $categoriesAllowed, $xPerson);

    $viewPresenter = new HS\View\Search\HistorySearchRequestPresenter($results, $renderer);

    return $viewPresenter->render();
}

function searchRequests($query, $searcher, $renderer)
{
    global $user;

    // protect search results when in limited access mode
    $categoriesAllowed = [];
    if (perm('fLimitedToAssignedCats')) {
        $categoriesAllowed = apiGetUserCats($user['xPerson']);
    }

    // If user can only see own requests
    $xPerson = null;
    if (perm('fCanViewOwnReqsOnly')) {
        $xPerson = $user['xPerson'];
    }

    $results = $searcher->requests($query, $categoriesAllowed, $xPerson);

    $viewPresenter = new HS\View\Search\FulltextRequestPresenter($results, $renderer);

    return $viewPresenter->render();
}

//Output the results
echo $out;
