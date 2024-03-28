<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('request');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';
include cBASEPATH.'/helpspot/lib/class.feeds.php';

/*****************************************
FEED SECURITY
*****************************************/
//include protection code
if (! hs_setting('cHD_FEEDSENABLED')) {
    $feed = new RSS20;
    $item = new FeedItem();
    $item->description = '<h1>RSS feeds disabled</h1>';
    $feed->addItem($item);
    echo $feed->render();
    exit();
}

/*****************************************
VARIABLES
*****************************************/
//Make sure /admin doesn't output any headers
$pagebody = '';
$htmldirect = 1;
$tab = '';
$subtab = '';
$type = isset($_GET['type']) ? $_GET['type'] : exit();
$id = isset($_GET['id']) ? $_GET['id'] : '';

// All user filters
$filters = apiGetAllFilters($user['xPerson'], 'all');
    //add special filters
    $filters['reminders']['sFilterName'] = lg_reminders;
    $filters['subscriptions']['sFilterName'] = lg_subscriptions;
    $filters['inbox']['sFilterName'] = lg_inbox;
    $filters['myq']['sFilterName'] = lg_myq;
    $filters['spam']['sFilterName'] = lg_spam;

// Get all users, include how many requests are assigned to them
$allStaff = apiGetAllUsersComplete();
// Get all cats
$catlist = [];
$cats = apiGetAllCategoriesComplete();
if (hs_rscheck($cats)) {
    while ($cat = $cats->FetchRow()) {
        $catlist[$cat['xCategory']] = $cat['sCategory'];
    }
}
$catlist[0] = lg_inbox;

/*****************************************
BUILD FEED
*****************************************/
if ($type == 'RSS20') {
    $ft = new hs_filter();
    $feed = new RSS20;

    switch ($id) {
    case 'reminders':
            $ftrs = apiGetRemindersByPerson($user['xPerson'], '');
            if (hs_rscheck($ftrs)) {
                while ($row = $ftrs->FetchRow()) {
                    $item = new FeedItem();
                    $item->title = hs_showDate($row['dtGMTReminder']);
                    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]).'#reminders';
                    $item->guid = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]).'#reminders';

                    $item->description = renderFeedHeader($subrow, $allStaff, $catlist);
                    $item->description .= '<br><b>'.lg_feed_createdby.' '.$allStaff[$row['xPersonCreator']]['fullname'].'</b><br>';
                    $item->description .= nl2br(hs_htmlspecialchars($row['tReminder']));

                    $feed->addItem($item);
                }
            }

        break;
    case 'subscriptions':
            if (perm('fCanViewOwnReqsOnly')) {
                die();
            } //can't subscribe in this case

            //similar to the "normal" filtered views below but we have to lookup request info
            $ftrs = apiGetSubscribersByPerson($user['xPerson'], '');
            if (hs_rscheck($ftrs)) {
                while ($subrow = $ftrs->FetchRow()) {
                    $row = apiGetRequest($subrow['xRequest']);
                    $initnote = apiGetInitialRequest($row['xRequest']);
                    $item = new FeedItem();
                    $item->title = $row['fUrgent'] ? lg_isurgent.' | ' : '';
                    $item->title .= $row['xRequest'].' | '.$row['fullname'].' | '.hs_htmlspecialchars(utf8_substr(strip_tags(stripFormBlurbs($initnote['tNote'])), 0, 75));
                    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]);
                    $item->guid = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]);
                    $item->category = lg_feed_category.$catlist[$row['xCategory']];
                    $item->date = $row['dtGMTOpened'];

                    $item->description = '<tr><td>'.renderFeedHeader($subrow, $allStaff, $catlist).'</td></tr>';

                    $item->description .= '<tr><td>&nbsp;</td></tr>';

                    $item->description .= '<tr><td><b style="color:blue">'.lg_feed_reqhistory.'</b></td></tr>';

                    $item->description .= '<tr><td>'.renderRequestHistoryFeed($row['xRequest'], $allStaff, $row).'</td></tr>';

                    $item->description = '<table width="80%">'.$item->description.'</table>';

                    $feed->addItem($item);
                }
            }

        break;
    default:
        if (is_numeric($id) && isset($filters[$id])) {
            //Replace filter instance created above
            $ft = new hs_filter($filters[$id]);
        } elseif ($id == 'inbox') {
            if (! perm('fViewInbox')) {
                die();
            }

            $ft->useSystemFilter('inbox');
        } elseif ($id == 'myq') {
            $ft->useSystemFilter('myq');
        } elseif ($id == 'spam') {
            if (! perm('fCanManageSpam')) {
                die();
            }

            $ft->useSystemFilter('spam');
        } else {
            exit();
        }

        $ftrs = $ft->outputResultSet();

        if (hs_rscheck($ftrs)) {
            while ($row = $ftrs->FetchRow()) {
                $initnote = apiGetInitialRequest($row['xRequest']);
                $item = new FeedItem();
                $item->title = $row['fUrgent'] ? lg_isurgent.' * ' : '';
                $item->title .= $row['xRequest'].' * '.hs_htmlspecialchars($row['fullname']).' * '.hs_htmlspecialchars(utf8_substr(strip_tags(stripFormBlurbs($initnote['tNote'])), 0, 75));
                $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]);
                $item->guid = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $row['xRequest']]);
                $item->category = lg_feed_category.$catlist[$row['xCategory']];
                $item->date = $row['dtGMTOpened'];

                $item->description = '<tr><td>'.renderFeedHeader($row, $allStaff, $catlist).'</td></tr>';

                $item->description .= '<tr><td>&nbsp;</td></tr>';

                $item->description .= '<tr><td><b style="color:blue">'.lg_feed_reqhistory.'</b></td></tr>';
                $item->description .= '<tr><td>'.renderRequestHistoryFeed($row['xRequest'], $allStaff, $row, $filters[$id]['fCustomerFriendlyRSS']).'</td></tr>';

                $item->description = '<table width="80%">'.$item->description.'</table>';

                $feed->addItem($item);
            }
        }

        break;
    }

    //Rest of global channel items for feed
    $feed->title = $filters[$id]['sFilterName'];
    $feed->pagename = $filters[$id]['sFilterName'].'.xml';
    $feed->description = '';
    $feed->copyright = hs_setting('cHD_FEEDCOPYRIGHT');
    $feed->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => $id]);
} elseif ($type == 'OPML') {
    $feed = new OPML;

    $feed->title = lg_feed_helpdeskfilters;
    $feed->pagename = lg_feed_helpdeskfilters.'.opml';

    $filters = apiGetAllFilters($user['xPerson'], 'all');
    $folders = apiCreateFolderList($filters);

    //list out defaults
    $item = new FeedItem();
    $item->title = lg_inbox;
    $item->description = '';
    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => 'inbox']);
    $feed->addItem($item);

    $item = new FeedItem();
    $item->title = lg_myq;
    $item->description = '';
    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => 'myq']);
    $feed->addItem($item);

    $item = new FeedItem();
    $item->title = lg_reminders;
    $item->description = '';
    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => 'reminders']);
    $feed->addItem($item);

    $item = new FeedItem();
    $item->title = lg_subscriptions;
    $item->description = '';
    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => 'subscriptions']);
    $feed->addItem($item);

    $item = new FeedItem();
    $item->title = lg_spam;
    $item->description = '';
    $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => 'spam']);
    $feed->addItem($item);

    //set all filters/folders
    foreach ($folders as $f) {
        if (! empty($f)) {
            $item = new FeedItem();
            $item->title = $f;
            $item->description = [];
            foreach ($filters as $xkey=>$filter) {
                if ($f == $filter['sFilterFolder']) {
                    $fobj = new FeedItem();
                    $fobj->title = $filter['sFilterName'];
                    $fobj->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => $xkey]);
                    $item->description[] = $fobj;
                }
            }
            $feed->addItem($item);
        }
    }

    //output filters which aren't in folders
    foreach ($filters as $xkey=>$filter) {
        if ($filter['sFilterFolder'] == '') {
            $item = new FeedItem();
            $item->title = $filter['sFilterName'];
            $item->description = '';
            $item->link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'feed_filter', 'type' => 'RSS20', 'id' => $xkey]);
            $feed->addItem($item);
        }
    }
} else {
    exit();
}

$pagebody = $feed->render();
