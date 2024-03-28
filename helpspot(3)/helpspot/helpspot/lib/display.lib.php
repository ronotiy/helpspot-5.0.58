<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

function hs_mouseover($id, $onid)
{
    if ($id != $onid) {
        return 'onMouseOver="headerRollover(\''.$id.'\')" onMouseOut="headerRollout(\''.$id.'\')"';
    } else {
        return '';
    }
}

function hs_headerlinknon($id, $onid)
{
    if ($id != $onid) {
        return '';
    } else {
        return ' headerlinkon ';
    }
}

function hs_buttonon($id, $onid)
{
    if ($id != $onid) {
        return '';
    } else {
        return ' headerbigbuttonon ';
    }
}

function hs_subnavon($id, $onid)
{
    if ($id != $onid) {
        return '';
    } else {
        return ' subnavitemon ';
    }
}

//JS & CSS includes based on if we're in production or not
function jsCssIncludes($ver = false)
{
    //set version, in case we're in an upgrade so we can use latest files.
    if (! $ver) {
        $ver = hs_setting('cHD_VERSION');
    }

    $head = '
		<!--PROTOTIP PATHS-->
		<script type="text/javascript">
			var Tips = {
			  options: {
				paths: {                                // paths can be relative to this file or an absolute url
				  images:     "'.static_url().'/static/js/prototip/images/prototip/",
				  javascript: "'.static_url().'/static/js/prototip/js/prototip/"
				},
				zIndex: 6000                            // raise if required
			  }
			};
		</script>
	';

    $head .= '
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,600,700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="'.static_url().mix('static/css/helpspot.css').'" media="screen">
		<link rel="stylesheet" href="'.static_url().mix('static/css/helpspot-print.css').'" type="text/css" media="print" />
		<script type="text/javascript" src="'.static_url().mix('static/js/helpspot.js').'"></script>
	';

    return $head;
}

function displayHeader($title = '', $style = 'default', $tab = 'nav_workspace', $headscript = '', $onload = '', $subtab = 0)
{
    global $user,$auth,$page;

    //Check the MyQueue to see if we show the empty icon or not
    $myCount = new hs_filter('', true);
    $myCount->useSystemFilter('myq');

    $userNotifications = [];
    if( auth()->user() ) {
        $userNotifications = auth()->user()->unreadNotifications->mapWithKeys(function($notification) {
            return [$notification->id => $notification->toArray()];
        })->toArray();
    }

    $avatar = new HS\Avatar\Avatar();

    $css = hs_setting('cHD_ADMIN_CSS');
    $js = hs_setting('cHD_ADMIN_JS');

    $out = '
	<!DOCTYPE html>
	<html lang="en" class="'.(inDarkMode() ? 'dark' : '').'">
	<head>
		<title>'.$title.' | HelpSpot</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="apple-touch-icon" sizes="180x180" href="'.url('apple-touch-icon.png').'">
		<link rel="icon" type="image/png" sizes="32x32" href="'.url('favicon-32x32.png').'">
		<link rel="icon" type="image/png" sizes="16x16" href="'.url('favicon-16x16.png').'">
        <link rel="shortcut icon" href="'.url('favicon.ico').'">
		<link rel="manifest" href="'.url('site.webmanifest').'">
		<script type="text/javascript">
            window.HS = {
		        HS_CSRF_TOKEN: "'.csrf_token().'",

		        user: {
		            notifications: '.json_encode($userNotifications).'
		        }
		    };
			HS_CSRF_TOKEN = "'.csrf_token().'";

            //Global JS text
            lg_js_error = "'.hs_jshtmlentities(lg_js_error).'";
            lg_js_notification = "'.hs_jshtmlentities(lg_js_notification).'";
            lg_js_confirmation = "'.hs_jshtmlentities(lg_js_confirmation).'";
            lg_sessionexpired = "'.hs_jshtmlentities(lg_sessionexpired).'";
            lg_loading = "'.hs_jshtmlentities(lg_loading).'";
            lg_prev = "'.hs_jshtmlentities(lg_prev).'";
            lg_next = "'.hs_jshtmlentities(lg_next).'";
            lg_streamview_end = "'.lg_streamview_end.'";
            cHD_IDLE_TIMEOUT = '.hs_setting('cHD_IDLE_TIMEOUT').';
            cHD_REFRESH_SECONDS = '.hs_setting('cHD_REFRESH_SECONDS').';
            button_save = "'.hs_jshtmlentities(lg_save).'";
            button_ok = "'.hs_jshtmlentities(lg_button_ok).'";
            button_close = "'.hs_jshtmlentities(lg_button_close).'";
            button_cancel = "'.hs_jshtmlentities(lg_button_cancel).'";

            //Global path
            chost = "'.cHOST.'";
            static_path = "'.static_url().'/static";
        </script>
		'.jsCssIncludes().'
		<script type="text/javascript">
			$jq(document).ready(function(){
				'.$onload.'
			});

			//Watch for esc keypress and hide popup if found
			Event.observe(document, \'keydown\', function(thekey){if(thekey.keyCode == 27){Tips.hideAll();}}, false);

			document.observe("dom:loaded", function(){
				//ajax loading indicator and error/session handler
				// Ajax.Responders.register(ajaxHandlers); //see handler in general.js

				//Session check
				hs_PeriodicalExecuter("sessioncheck",function(){
					new Ajax.Request("'.action('Admin\AdminBaseController@sessionCheck').'", {
                        onComplete:  function(res){
                            updateCsrfTokens(res.responseJSON.csrf)
                        }
                    });
				}, 120);
			});

			//Check for low res screens
			$jq(document).ready(function(){
				if($jq(window).width() < 1024){
					$jq("body").addClass("r1024");
				}
			});
            '.$js.'
		</script>
		<style>'.$css.'</style>
		'.$headscript.'
	</head>
	<body class="'.(inDarkMode() ? 'dark' : '').'">
	'.(defined('IS_BETA') ? '<div class="beta"></div>' : '').'
	<div id="hs_msg" style="display:none;"><div id="hs_msg_inner"></div></div>

    <div class="main-layout '.( ($tab =='nav_workspace' || $tab == 'nav_search' || $tab == 'nav_responses') && $_COOKIE['sidebarState'] == 'closed' ? 'sidebar-closed' : '').'">
        <header>
        	<div class="modules">';
                $out .= '<a href="https://www.helpspot.com" target="_blank" class="hslogo"><img src="' . static_url() . '/static/img5/helpspot-avatar-logo'.(inDarkMode() ? '-black' : '').'.svg" style="height:30px;width:30px;margin-right:18px;margin-bottom: -2px;" /></a>';
    		    $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace']).'"><span '.($tab == 'nav_workspace' ? ' class="active"' : '').'>'.lg_queue.'</span></a>';
    		    $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb']).'"><span '.($tab == 'nav_kb' ? ' class="active"' : '').'>'.lg_kb.'</span></a>';

    		    if (perm('fModuleReports')) {
    		        $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'todayboard']).'"><span '.($tab == 'nav_reports' ? ' class="active"' : '').'>'.lg_reports.'</span></a>';
    		    }

                $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses']).'"><span '.($tab == 'nav_responses' ? ' class="active"' : '').'>'.lg_responses.'</span></a>';

                if (isAdmin()) {
                    $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin']).'"><span '.($tab == 'nav_admin' ? ' class="active"' : '').'>'.lg_adminhome.'</span></a>';
                }

                if (perm('fCanAdvancedSearch')) {
                    $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'search']).'"><span><img src="'.static_url().'/static/img5/search.svg" style="height:20px;" title="'.lg_search_tab.'" /></span></a>';
                }

                if ($host = HS\MultiPortal::getHostIfOnlyOne()) {
                    $out .= '<a href="'.$host.'" id="nav-portal-link" target="_portal" style="" title="'.lg_portalnav.'"><span><img src="'.static_url().'/static/img5/browser-solid.svg" style="height:20px;" /></span></a>';
                } else {
                    $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'portals']).'" id="nav-portal-link" style="" title="'.lg_portalnav.'"><span><img src="'.static_url().'/static/img5/browser-solid.svg" style="height:20px;" /></span></a>';
                }

        	$out .= '
        	</div>
            <div class="rightside">
            	<div id="name-menu">
            		<div>'.$avatar->xPerson($user['xPerson'])->size(36)->html().'</div>
            		<div style="padding-left:12px;padding-right:20px;">
            			<span class="name">'.$user['sFname'].'</span><br />
            			<span class="company">'.hs_setting('cHD_ORGNAME').'</span>

    					<div id="name_menu_list" style="display:none;">
    						<div style="display:flex;">
    							<div style="flex:1">
    								<ul class="tooltip-menu">
                                        <li><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.users', 'pref' => 1]).'"><span class="tooltip-menu-maintext">'.lg_prefs.'</span></a></li>
                                        <li class="tooltip-menu-divider"><div></div></li>
                                        <li><a href="'.route('darkmode').'" onclick="event.preventDefault(); document.getElementById(\'darkmode-form\').submit();"><span class="tooltip-menu-maintext">'.hs_htmlspecialchars((inDarkMode() ? lg_darkmode_off : lg_darkmode_on)).'</span></a></li>
                                        <li class="tooltip-menu-divider"><div></div></li>
                                        <li><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'reminders']).'"><span class="tooltip-menu-maintext">'.hs_htmlspecialchars(lg_reminders).'</span></a></li>
                                        <li><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'subscriptions']).'"><span class="tooltip-menu-maintext">'.hs_htmlspecialchars(lg_subscriptions).'</span></a></li>
                                        <li class="tooltip-menu-divider"><div></div></li>
    									<li><a href="https://support.helpspot.com" target="_blank"><span class="tooltip-menu-maintext">'.lg_help.'</span></a></li>
    									<li><a href="http://discuss.helpspot.com" target="_blank"><span class="tooltip-menu-maintext">'.lg_forums.'</span></a></li>
    									<li><a href="https://www.helpspot.com/training" target="_blank"><span class="tooltip-menu-maintext">'.lg_academy.'</span></a></li>
    									<li class="tooltip-menu-divider"><div></div></li>
                                        <li><a href="'.route('logout').'" onclick="event.preventDefault(); document.getElementById(\'logout-form\').submit();"><span class="tooltip-menu-maintext">'.lg_logout.'</span></a></li>
    								</ul>
    								<form id="darkmode-form" action="'.route('darkmode').'" method="POST" style="display: none;">
    									'.csrf_field().'
    								</form>
    								<form id="logout-form" action="'.route('logout').'" method="POST" style="display: none;">
    									'.csrf_field().'
    								</form>
    							</div>
    						</div>
    					</div>
    					<script type="text/javascript">
    						new Tip("name-menu", $("name_menu_list"),{
    								title: "",
    								border: 0,
    								radius: 0,
                                    className: "hstinytipfat autoclose",
    								showOn: "click",
                                    stem: false,
    								hideOn: false,
    								hideAfter: 3,
    								width: "auto",
    								hook: { target: "bottomRight", tip: "topRight" }
    							});

    						$("name-menu").observe("prototip:shown", function(){
    							this.addClassName("name-menu-active");
    						});

    						$("name-menu").observe("prototip:hidden", function(){
    							this.removeClassName("name-menu-active");
    						});
    					</script>
            		</div>
        		</div>
        	</div>
    	</header>

		<main class="'.$style.'">';

    if (subscription()->onGracePeriod()) {
        $out .= displaySystemBox('
            <div style="display:flex;align-items:center;justify-content: space-between;width: 100%;">
                <div>Your subscription has expired and will be shut off in '.subscription()->endsIn().' days.</div>
                <a class="btn inline-action accent" href="https://store.helpspot.com">Renew Today</a>
            </div>');
    }

    if( auth()->user() ) {
        $unreadNotifications = auth()->user()->unreadNotifications;
        if($unreadNotifications->count()) {
            $notifications = '<div id="hs_notification_window" style="width:100%">';
            foreach($unreadNotifications as $notification) {
                if( $notification->type == \HS\Notifications\EmailSendError::class ) {
                    $notifications .= '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                                <div>' . lg_not_email_send_error . ' <a href="'.$notification->data['route'].'" style="text-decoration:underline; padding: 0; color: inherit; background-color: transparent; border-radius: 0;">'.$notification->data['request'].'</a></div>
                                <div><a class="action" href="#" data-notification="'.$notification->id.'" onclick="dismissNotification(event)">'.lg_not_dismiss.'</a></div>
                            </div>';
                }
            }
            $notifications .= ($unreadNotifications->count() > 1) ? '<div style="text-align:right; margin-bottom: 10px; padding-top:18px; border-top: 1px solid #cab93d;"><a class="action" href="#" onclick="dismissAllNotifications(event)">Dismiss All</a></div>' : '';
            $notifications .= '</div>';
            $out .= displaySystemBox($notifications);
        }
    }

    return $out;
}

/*****************************************
DISPLAY FOOTER
*****************************************/
function displayFooter($tab = 'nav_workspace')
{
    global $user;
    global $page;
    $out = '';

    $workspace_request_sidebar = ($tab =='nav_workspace' || $tab == 'nav_search' || $tab == 'nav_responses');

    $out .= '
			</main>
		    <nav id="navigation">
                <div class="navigation-inner">
            ';
    //Open folder list which controls open/closed state of folders
    $open_folders = ($_COOKIE['sidebarOpenFolders'] ? explode(',', $_COOKIE['sidebarOpenFolders']) : []);

    if ($workspace_request_sidebar) {
        $show = isset($_GET['show']) ? trim($_GET['show']) : $user['sWorkspaceDefault'];
        if ($page == 'custompg') {
            $show = 'custompg_'.$_GET['file'].'.php';
        }
        if ($page == 'request') {
            $show = $_COOKIE['last_queue'];
        }
        if ($page == 'filter.requests') {
            $show = $_GET['filterid'];
        }

        $topfilters = apiGetAllFilters($user['xPerson'], 'top');
        $filters = apiGetAllFilters($user['xPerson'], 'bottom');
        $folders = apiCreateFolderList($filters);

        if (perm('fViewInbox')) {
            $inboxCount = new hs_filter('', true);
            $inboxCount->useSystemFilter('inbox');
            $inboxCount = $inboxCount->outputCountTotal();
        }

        $myCount = new hs_filter('', true);
        $myCount->useSystemFilter('myq');
        $myCountTotal = $myCount->outputCountTotal();

        //Find unreads
        $myUnreadCount = new hs_filter('', true);
        $myUnreadCount->useSystemFilter('myq_unread');

        if (perm('fCanManageSpam')) {
            $spamCount = new hs_filter('', true);
            $spamCount->useSystemFilter('spam');
            $spamCount = $spamCount->cacheOutputCountTotal('spam', ($show == 'spam' ? true : false));
        }

        if (perm('fCanManageTrash')) {
            $trashCount = new hs_filter('', true);
            $trashCount->useSystemFilter('trash');
            $trashCount = $trashCount->cacheOutputCountTotal('trash', ($show == 'trash' ? true : false));
        }

        if($workspace_request_sidebar){
            $out .= '
                <div class="sidebar-top-button">
                    <div style="cursor:pointer;height:34px;width:28px;border-radius: 3px;padding-right: 5px;display:flex;align-items:center;justify-content:center;"
                            onclick="toggleSidebarState();">
                        <img src="'.static_url().'/static/img5/navigate-back.svg" style="height:24px;" title="" class="sidebar-state-btn-close" />
                        <img src="'.static_url().'/static/img5/navigate-forward.svg" style="height:24px;" title="" class="sidebar-state-btn-open" />
                    </div>
                    <a title="'.lg_newrequest.'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request']).'" class="btn full accent create" style="flex:1;margin-bottom:16px;">
                        <img src="'.static_url().'/static/img5/add-white.svg" style="" class="" />
                        <span>'.lg_newrequest.'</span>
                    </a>
                </div>
                ';
        }elseif($tab == 'nav_kb'){
            $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.order']).'" class="btn full" style="margin-bottom:8px;">'.hs_htmlspecialchars(lg_kb_bookorder).'</a>';
        }

        $out .= '<ul id="" class="sidebar">';

        foreach ($topfilters as $k=>$v) {
            $count = '';
            if ($v['fShowCount'] == 1) {
                $filCount = new hs_filter($v, true);

                //Always use cache as filter we're viewing has been update in workspace.php call
                $filCount->useCountCache = true;

                //Get the count
                $filCountTotal = $filCount->outputCountTotal();

                $count = '<span class="count" id="filter-'.$v['xFilter'].'-count" '.($filCountTotal <= 0 ? 'style="display:none;"' : '').'>'.$filCountTotal.'</span>';
            }
            $out .= '<li class="folder- '.($show == $v['xFilter'] ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => ($v['sFilterView'] != 'grid' ? 'workspace.stream' : 'workspace'), 'show' => $v['xFilter']]).'" id="nav-'.$v['xFilter'].'" class="filter-top"><span class="text">'.hs_htmlspecialchars($v['sFilterName']).'</span> '.$count.'</a></li>';
        }

        $out .= '
						'.(perm('fViewInbox') ? '<li class="'.($show == 'inbox' ? 'active' : '').'"><a title="'.hs_htmlspecialchars(lg_helpdesknav).'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'inbox']).'" id="nav-inbox" class="inbox"><span class="text">'.hs_htmlspecialchars(lg_helpdesknav).'</span><span class="count" '.($inboxCount <= 0 ? 'style="display:none;"' : '').'>'.$inboxCount.'</span></a></li>' : '').'
						<li class="'.($show == 'myq' ? 'active' : '').'"><a title="'.hs_htmlspecialchars(lg_myq).'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'myq']).'" id="nav-myq" class="myq"><span class="text">'.hs_htmlspecialchars(lg_myq).'</span><span class="count"><span id="unread-count">'.$myUnreadCount->outputCountTotal().'</span> / <span id="myq-count">'.$myCountTotal.'</span></span></a></li>
						'.(perm('fCanManageSpam') ? '<li class="'.($show == 'spam' ? 'active' : '').'"><a title="'.hs_htmlspecialchars(lg_spam).'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'spam']).'" id="nav-spam" class="spam"><span class="text">'.hs_htmlspecialchars(lg_spam).'</span> '.($spamCount > 0 ? '<span class="count">'.$spamCount.'</span>' : '').'</a></li>' : '').'
						'.(perm('fCanManageTrash') ? '<li class="'.($show == 'trash' ? 'active' : '').'"><a title="'.hs_htmlspecialchars(lg_trash).'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace', 'show' => 'trash']).'" id="nav-trash" class="trash"><span class="text">'.hs_htmlspecialchars(lg_trash).'</span> '.($trashCount > 0 ? '<span class="count">'.$trashCount.'</span>' : '').'</a></li>' : '').'
					</ul>';

        $out .= '<form id="search_form" method="post" action="/" onsubmit="sidebarSearchAction(\'request\');return false;">
                    <div id="search-box" class="">
                            <input type="text" id="sidebar-q" placeholder="'.hs_htmlspecialchars(lg_search).'" autocomplete="off" onFocus="hs_shortcutsOff();" onBlur="hs_shortcutsOn();" />
                            <input type="hidden" name="sidebar-search-type" id="sidebar-search-type" value="request" />
                    </div>
                </form>';

        $out .= '
				<ul id="" class="sidebar sidebar-filters" style="margin-top: 20px;">';

        //Order filters for proper output
        $foldernames = [];
        $filternames = [];
        foreach ($filters as $k=>$v) {
            $foldernames[] = $v['sFilterFolder'];
            $filternames[] = $v['sFilterName'];
        }

        $sort = SORT_ASC; //Fix for crazy zend guard
        array_multisort($foldernames, $sort, $filternames, $sort, $filters);

        //Output filters
        $last_folder = '';
        $current_folder = '';
        foreach ($filters as $k=>$v) {
            //Handle folders
            $current_folder = $v['sFilterFolder'];
            $folderid = 'folder-'.(! hs_empty($v['sFilterFolder']) ? md5($v['sFilterFolder']) : '');

            if ($current_folder != $last_folder && ! hs_empty($current_folder)) {
                $out .= '<li class="folder-li"><a href="" id="'.$folderid.'" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array($folderid, $open_folders)) ? 'arrow-open' : '').'"></span> '.hs_htmlspecialchars($current_folder).'</span></a></li>';
            }

            $count = '';
            if ($v['fShowCount'] == 1) {
                $filCount = new hs_filter($v, true);

                //Allow cache for all filters except filter we're viewing
                if ($v['xFilter'] != $show) {
                    $filCount->useCountCache = true;
                }

                //Get the count
                $filCountTotal = $filCount->outputCountTotal();

                $count = '<span class="count" id="filter-'.$v['xFilter'].'-count" '.($filCountTotal <= 0 ? 'style="display:none;"' : '').'>'.$filCountTotal.'</span>';
            }

            $out .= '<li class="'.$folderid.' '.($show == $v['xFilter'] ? 'active' : '').'" style="'.(! hs_empty($v['sFilterFolder'] && ! in_array($folderid, $open_folders)) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => ($v['sFilterView'] != 'grid' ? 'workspace.stream' : 'workspace'), 'show' => $v['xFilter']]).'" id="nav-'.$v['xFilter'].'" class="filter"><span class="text">'.hs_htmlspecialchars($v['sFilterName']).'</span> '.$count.'</a></li>';

            $last_folder = $current_folder;
        }

        $out .= '</ul>';

        //Show custom pages
        $files = listFilesInDir(base_path('helpspot/custom_pages/'));

        if ((! empty($files) && count($files) > 1)) {
            $out .= '<ul id="" class="sidebar sidebar-custompg">';
            $out .= '<li class="folder-li"><a href="" id="custompg-folder" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('custompg-folder', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_custom_pages).'</span></a></li>';
            foreach ($files as $k=>$v) {
                if ($v != 'example.php') {
                    $name = str_replace(['_', '.php'], [' ', ''], $v);
                    $name = utf8_ucwords($name);
                    $out .= '<li class="custompg-folder '.($show == 'custompg_'.$v ? 'active' : '').'" style="'.(! in_array('custompg-folder', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'custompg', 'file' => str_replace('.php', '', $v)]).'" class="custompg"><span class="text">'.hs_htmlspecialchars($name).'</span></a></li>';
                }
            }
            $out.-'</ul>';
        }
    } elseif ($tab == 'nav_kb') {
        $chappages = '';

        $out .='
            <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.manage']).'" class="btn full accent create" style="flex:1;margin-bottom:8px;">
                <span>'.lg_kb_addbook.'</span>
            </a>';

        $out .= '<ul id="" class="sidebar">';

        if ($_GET['pg'] == 'kb.page') {
            //We show pages in current chapter if on a page for each navigation
            include_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
            $page = apiGetPage($_GET['page']);
            $chapter = apiGetChapter($page['xChapter']);
            $pages = apiGetChapPages($page['xChapter'], true);

            if (hs_rscheck($pages)) {
                while ($p = $pages->FetchRow()) {
                    $out .= '<li class="'.($_GET['page'] == $p['xPage'] ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.page', 'page' => $p['xPage']]). '" id="" class="page-pub"><span class="text '.($p['fHidden'] ? 'kbhidden' : '').'">'.hs_htmlspecialchars($p['sPageName']).'</span></a></li>';
                }
            }

        }else{

            $pubrs = apiGetBooks(0);
            if (hs_rscheck($pubrs)) {
                while ($row = $pubrs->FetchRow()) {
                    $out .= '<li class="'.($_GET['book'] == $row['xBook'] ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.book', 'book' => $row['xBook']]).'" class="book-pub"><span class="text">'.hs_htmlspecialchars($row['sBookName']).'</span></a></li>';
                }
            }

            $privrs = apiGetBooks(1);
            if (hs_rscheck($privrs) && perm('fModuleKbPriv')) {
                while ($row = $privrs->FetchRow()) {
                    $out .= '<li class="'.($_GET['book'] == $row['xBook'] ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.book', 'book' => $row['xBook']]).'" class="book-priv"><span class="text">'.hs_htmlspecialchars($row['sBookName']).'</span></a></li>';
                }
            }

        }

        $out .= '</ul>';

    } elseif($tab == 'nav_reports') {
        $reports = new \HS\Domain\Reports\SavedReports();
        $folders = $reports->sidebarFolders($user);
        $folder = '';
        $myreps = '';
        foreach($folders as $row) {
            if ($row->sFolder != $folder) {
                $folderId = md5($row->sFolder);
                $name = ($row->sFolder == '') ? lg_report_saved_reports : $row->sFolder;
                $myreps .= '<li class="folder-li"><a href="" id="folder-'.$folderId.'" class="folder"><span class="text"><span class="arrow '.(!hs_empty(in_array("folder-".$folderId,$open_folders)) ? 'arrow-open' :'').'"></span>'.hs_htmlspecialchars($name).'</span></a></li>';
                $folder = $row->sFolder;
            }
            $myreps .= '<li class="folder-'.$folderId.' '.($_GET['xReport'] == $row->xReport ? 'active' : '').'" style="'.(!in_array('folder-'.$folderId,$open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => $row->sPage, 'show' => $row->sShow, 'xReport' => $row->xReport]).'" class="reports"><span class="text">'.hs_htmlspecialchars($row->sReport).'</span></a></li>';
        }

        $show = isset($_GET['show']) ? trim($_GET['show']) : 'todayboard';
        $out .= '

						<ul id="admin-navigation" class="sidebar sidebar-reports">
							<li class="'.(! isset($_GET['xReport']) && $show == 'todayboard' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'todayboard']).'" class=""><span class="text">'.hs_htmlspecialchars(lg_todayboard).'</span></a></li>
							<li class="'.(! isset($_GET['xReport']) && $show == 'report_over_time' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_over_time']).'" class=""><span class="text">'.hs_htmlspecialchars(lg_reports_reqs_over_time).'</span></a></li>
							<li class="'.($page == 'reports.matrix' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.matrix']).'" class=""><span class="text">'.hs_htmlspecialchars(lg_reports_matrix).'</span></a></li>
							<li class="'.(! isset($_GET['xReport']) && $show == 'report_productivity' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.dual', 'show' => 'report_productivity_replyspeed', 'show2' => 'report_productivity_resolution']).'" class=""><span class="text">'.hs_htmlspecialchars(lg_reports_productivity).'</span></a></li>

							<li class="folder-li"><a href="" id="folder-report-analysis" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-report-analysis', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_reports_analysis).'</span></a></li>
								<li class="folder-report-analysis '.(! isset($_GET['xReport']) && $show == 'report_first_response' ? 'active' : '').'" style="'.(! in_array('folder-report-analysis', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_first_response']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_speed_to_first).'</span></a></li>
								<li class="folder-report-analysis '.(! isset($_GET['xReport']) && $show == 'report_first_assignment' ? 'active' : '').'" style="'.(! in_array('folder-report-analysis', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_first_assignment']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_speed_to_first_assignment).'</span></a></li>
								<li class="folder-report-analysis '.(! isset($_GET['xReport']) && $show == 'report_replies_by_count' ? 'active' : '').'" style="'.(! in_array('folder-report-analysis', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_replies_by_count']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_replies_to_close).'</span></a></li>
								<li class="folder-report-analysis '.(! isset($_GET['xReport']) && $show == 'report_interactions' ? 'active' : '').'" style="'.(! in_array('folder-report-analysis', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_interactions']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_interactions).'</span></a></li>
								<li class="folder-report-analysis '.(! isset($_GET['xReport']) && $show == 'report_resolution_speed' ? 'active' : '').'" style="'.(! in_array('folder-report-analysis', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_resolution_speed']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_resolution_speed).'</span></a></li>

							<li class="folder-li"><a href="" id="folder-portal" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-portal', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_reports_portal).'</span></a></li>
								<li class="folder-portal '.(! isset($_GET['xReport']) && $show == 'report_searches_no_results' ? 'active' : '').'" style="'.(! in_array('folder-portal', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_searches_no_results']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_report_searches_no_results).'</span></a></li>
								<li class="folder-portal '.(! isset($_GET['xReport']) && $show == 'report_searches' ? 'active' : '').'" style="'.(! in_array('folder-portal', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_searches']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_report_searches_agg).'</span></a></li>
								<li class="folder-portal '.(! isset($_GET['xReport']) && $show == 'report_kb_helpful' ? 'active' : '').'" style="'.(! in_array('folder-portal', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_kb_helpful']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_kb_helpful).'</span></a></li>

							<li class="folder-li"><a href="" id="folder-staff" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-staff', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_reports_staff).'</span></a></li>
								<li class="folder-staff '.(! isset($_GET['xReport']) && $show == 'report_responses' ? 'active' : '').'" style="'.(! in_array('folder-staff', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_responses']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_responses).'</span></a></li>

							<li class="folder-li"><a href="" id="folder-customers" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-customers', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_reports_customers).'</span></a></li>
								<li class="folder-customers '.(! isset($_GET['xReport']) && $show == 'report_customer_activity' ? 'active' : '').'" style="'.(! in_array('folder-customers', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_customer_activity']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_customer_activity).'</span></a></li>

							<li class="folder-li"><a href="" id="folder-timetracker" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-timetracker', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_reports_timetracker).'</span></a></li>
								<li class="folder-timetracker '.(! isset($_GET['xReport']) && $show == 'report_tt_over_time' ? 'active' : '').'" style="'.(! in_array('folder-timetracker', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports', 'show' => 'report_tt_over_time']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_tt_over_time).'</span></a></li>
								<li class="folder-timetracker '.(! isset($_GET['xReport']) && $show == 'report_time_events' ? 'active' : '').'" style="'.(! in_array('folder-timetracker', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'reports.list', 'show' => 'report_time_events']).'" class="reports"><span class="text">'.hs_htmlspecialchars(lg_reports_tt_events).'</span></a></li>

						</ul>

						<ul id="admin-navigation" class="sidebar">
							'.$myreps.'
						</ul>
					';
    } elseif ($tab == 'nav_admin') {
        $out .= '
						<ul id="admin-navigation" class="sidebar">
							<li class="'.($page == 'admin' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin']).'" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_overview_nav).'</span></a></li>
							<li class="'.($page == 'admin.users' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.users']).'" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_users_nav).'</span></a></li>
							<li class="'.($page == 'admin.mailboxes' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.mailboxes']).'" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_mailboxes_nav).'</span></a></li>
							<li class="'.($page == 'admin.hdcategories' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.hdcategories']).'" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_categories_nav).'</span></a></li>
							<li class="'.($page == 'admin.tools.customfields' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.customfields']).'" id="" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_reqfields).'</span></a></li>
							<li class="'.($page == 'admin.integrations' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.integrations']).'" id="" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_integrations).'</span></a></li>
							<li class="'.($page == 'admin.tools.customer' ? 'active' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.customer']).'" id="" class="admin-customertools"><span class="text">'.hs_htmlspecialchars(lg_admin_customer_tools).'</span></a></li>
							<li class=""><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.settings']).'" class="admin-top"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_nav).'</span></a></li>

							'.($page == 'admin.settings' ? '
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_system).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_system).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_system).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_emailintegration).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_emailintegration).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_emailintegration).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_htmlemailsbox).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_htmlemailsbox).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_htmlemailsbox).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_bizhours).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_bizhours).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_bizhours).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_datetime).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_datetime).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_datetime).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_timetracking).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_timetracking).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_timetracking).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_livelookup).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_livelookup).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_livelookup).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_ws).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_ws).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_ws).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_auth).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_auth).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_auth).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_kb).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_kb).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_kb).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_portal).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_portal).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_portal).'</span></a></li>
								<li class="admin-link" id="settings-nav-'.md5(lg_admin_settings_workers).'" style="'.($page != 'admin.settings' ? 'display:none;' : '').'"><a href="" onclick="return showSetting(\''.md5(lg_admin_settings_workers).'\');" class="settings"><span class="text">'.hs_htmlspecialchars(lg_admin_settings_workers).'</span></a></li>
							' : '').'

							<li><div class="hr"></div></li>

							<li class="folder-li"><a href="" id="folder-admin-data" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-admin-data', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_admin_data_nav).'</span></a></li>
								<li class="admin-link folder-admin-data '.($page == 'admin.status' ? 'active' : '').'" style="'.(! in_array('folder-admin-data', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.status']).'" class="admin-status"><span class="text">'.hs_htmlspecialchars(lg_admin_status_nav).'</span></a></li>
								<li class="admin-link folder-admin-data '.($page == 'admin.groups' ? 'active' : '').'" style="'.(! in_array('folder-admin-data', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.groups']).'" class="admin-permgroup"><span class="text">'.hs_htmlspecialchars(lg_admin_groups_nav).'</span></a></li>
							<li class="folder-li"><a href="" id="folder-admin-customize" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-admin-customize', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_admin_customize_nav).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.customize' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.customize']).'" class="admin-customize"><span class="text">'.hs_htmlspecialchars(lg_admin_customize_admin_nav).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.themes' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.themes']).'" class="admin-themes"><span class="text">'.hs_htmlspecialchars(lg_admin_themes_nav).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.widgets' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.widgets']).'" class="admin-widgets"><span class="text">'.hs_htmlspecialchars(lg_admin_widgets_nav).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.tools.portals' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.portals']).'" id="" class="admin-secondaryportal"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_portals).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.tools.email' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.email']).'" id="" class="admin-emailtemplates"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_email).'</span></a></li>
								<li class="admin-link folder-admin-customize '.($page == 'admin.tools.templates' ? 'active' : '').'" style="'.(! in_array('folder-admin-customize', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.templates']).'" id="" class="admin-portaltemplates"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_templates).'</span></a></li>
							<li class="folder-li"><a href="" id="folder-admin-tools" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-admin-tools', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_admin_tools_nav).'</span></a></li>
								<li class="admin-link folder-admin-tools '.($page == 'admin.triggers' ? 'active' : '').'" style="'.(! in_array('folder-admin-tools', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.triggers']).'" id="" class="admin-triggers"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_triggers).'</span></a></li>
								<li class="admin-link folder-admin-tools '.($page == 'admin.automation' ? 'active' : '').'" style="'.(! in_array('folder-admin-tools', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.automation']).'" id="" class="admin-autorules"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_auto).'</span></a></li>
								<li class="admin-link folder-admin-tools '.($page == 'admin.rules' ? 'active' : '').'" style="'.(! in_array('folder-admin-tools', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.rules']).'" id="" class="admin-mailrules"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_mailrules).'</span></a></li>
							<li class="folder-li"><a href="" id="folder-admin-system" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-admin-system', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_admin_system_nav).'</span></a></li>
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.filtermgmt' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.filtermgmt']).'" id="" class="admin-filtermgmt"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_filtermgmt).'</span></a></li>
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.jobsmgmt' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.jobsmgmt']).'" id="" class="admin-jobsmgmt"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_jobsmgmt).'</span></a></li>
								<!--
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.responsemgmt' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="/admin?pg=admin.tools.responsemgmt" id="" class="admin-responsemgmt"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_responsemgmt).'</span></a></li>
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.archive' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="/admin?pg=admin.tools.archive" id="" class="admin-archive"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_archive).'</span></a></li>
								-->
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.errorlog' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.errorlog']).'" id="" class="admin-errorlog"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_errorlog).'</span></a></li>
								<li class="admin-link folder-admin-system '.($page == 'admin.tools.sysinfo' ? 'active' : '').'" style="'.(! in_array('folder-admin-system', $open_folders) ? 'display:none;' : '').'"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.sysinfo']).'" id="" class="admin-sysinfo"><span class="text">'.hs_htmlspecialchars(lg_admin_tools_sysinfo).'</span></a></li>
							<!--
							<li class="folder-li"><a href="" id="folder-admin-integration" class="folder"><span class="text"><span class="arrow '.(! hs_empty(in_array('folder-admin-integration', $open_folders)) ? 'arrow-open' : '').'"></span>'.hs_htmlspecialchars(lg_admin_integration_nav).'</span></a></li>
								<li class="admin-link folder-admin-integration '.($page == 'admin.integration.survey' ? 'active' : '').'" style="'.(! in_array('folder-admin-integration', $open_folders) ? 'display:none;' : '').'"><a href="/admin?pg=admin.integration.survey" id="" class="admin-survey"><span class="text">'.hs_htmlspecialchars(lg_admin_integration_surveytools).'</span></a></li>
							-->
						</ul>
					';
    }

    $licenseinfo = '';

    if(isset($GLOBALS['license']['trial'])){
        $licenseinfo = '<span class="red">'.lg_trialexpires.'</span>: '.hs_showShortDate($GLOBALS['license']['trial']).' <a href="'.createStoreLink().'" class="btn secondary tiny" target="_blank" style="margin-left:10px;">'.lg_purchase.'</a>';
    }else{
        if(subscription()->isFree()){
            $licenseinfo .= lg_freestart.'<a href="https://store.helpspot.com/" class="btn secondary tiny" target="_blank" style="margin-left:10px;">'.lg_upgrade.'</a>';
        }
    }

    $out .= '
				<!-- folder system -->
				<script type="text/javascript">
					document.observe("dom:loaded", function(){
						folderUI("navigation");
					});

					'.($page == 'admin.settings' ? '
					//Load first setting page
					$jq(document).ready(function() {
						showSetting("'.(isset($_GET['admin_settings_page']) && ! empty($_GET['admin_settings_page']) ? $_GET['admin_settings_page'] : md5(lg_admin_settings_system)).'");
					});
					' : '').'
				</script>
            </div> <!-- end of .navigation-inner -->
		</nav>
        <div class="nav-action">';

            if(($tab == 'nav_workspace' || $tab == 'nav_search') ){
                $out .= '<div style="display: flex;flex-wrap: wrap;">';

                if(! perm('fCanViewOwnReqsOnly')){
                    $out .= '<a title="'.lg_filter_requests_nav.'" href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests']).'" class="btn full" style="flex:1">
                                <img src="'.static_url().'/static/img5/filter-solid.svg" style="" class="" />
                                <span>'.lg_filter_requests_nav.'</span>
                            </a>';
                }

                $out .= '</div>';

            }elseif($tab == 'nav_kb'){
                $out .= '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.order']).'" class="btn full">'.hs_htmlspecialchars(lg_kb_bookorder).'</a>';
            }

        $out .= '</div>
		<footer style="justify-content:flex-end">
            '.$licenseinfo.'
		</footer>
	</div>';

    $out .= '
		<div class="autocomplete autocomplete-small" id="search-box-small-autocomplete" style="display:none"></div>
		<div class="popup-holder"></div>
	';

    if (hs_setting('cHD_CUSTCONNECT_ACTIVE')) {
        // Formerly intercom.io
        // Add any customer metrics snippets here
    }

    $out .= '</body></html>';

    return $out;
}

/*****************************************
DISPLAY SIMPLE HEADER
*****************************************/
function displaySimpleHeader($title = '', $style = 'blank', $tab = 1, $headscript = '', $onload = '')
{
    global $user,$auth;

    //Current version. If we're in the installer show proper version
    if (defined('instHSVersion')) {
        $ver = instHSVersion;
    } else {
        $ver = hs_setting('cHD_VERSION');
    }

    //Setup defaults for when this is used in installation
    $orgname = hs_setting('cHD_ORGNAME', '');

    $out = '
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>'.$title.' : '.$orgname.'</title>
		 <link rel="apple-touch-icon" sizes="180x180" href="'.url('apple-touch-icon.png').'">
		<link rel="icon" type="image/png" sizes="32x32" href="'.url('favicon-32x32.png').'">
		<link rel="icon" type="image/png" sizes="16x16" href="'.url('favicon-16x16.png').'">
        <link rel="shortcut icon" href="'.url('favicon.ico').'">
		<link rel="manifest" href="'.url('site.webmanifest').'">
		'.jsCssIncludes($ver).'
		'.$headscript.'
		<script type="text/javascript">
		    window.HS = {
		        HS_CSRF_TOKEN: "'.csrf_token().'"
		    };
			HS_CSRF_TOKEN = "'.csrf_token().'";
			window.onload=function(){'.$onload.'}
		</script>
	</head>
	<body>
		<div id="hs_msg" style="display:none;"><div id="hs_msg_inner"></div></div>
	';
    //do simple wrap table here
    return $out;
}

/*****************************************
DISPLAY SIMPLE FOOTER
*****************************************/
function displaySimpleFooter()
{
    $out = '';
    //end of layout table
    $out .= '</body></html>';

    return $out;
}

/*****************************************
SEARCH BOX
*****************************************/
function displaySmallSearchBox($type = 'staff', $title = '', $params = [], $wrapcss = '', $showinactive = true)
{
    $boxid = $type.'-search-box-small-q';
    $wrapid = $type.'-search-box-small';
    $out = '';

    //Setup params if any
    if (! empty($params)) {
        $parlist = [];
        foreach ($params as $k=>$v) {
            $parlist[] = $k.": '".$v."'";
        }
        $params = '{'.implode(',', $parlist).'}';
    }

    if ($type == 'staff') {
        $autocomplete = '
			new Ajax.Autocompleter("'.$boxid.'","search-box-small-autocomplete", "'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'staff_search']).'", {
				paramName:"search"
				, parameters:'.$params.'
				, minChars: 1
				, frequency:0.1
				, updateElement: function(sel){
					if (typeof $(sel).down("span") != "undefined") {
						goPage($(sel).down("span").innerHTML.replace(/&amp;/g,"&"));
						$("'.$boxid.'").value="";
					}
				}
			});
		';
    } elseif ($type == 'responses') {
        $ajaxParams = ['pg' => 'ajax_gateway', 'action' => 'response_search'];
        if (! $showinactive) {
            $ajaxParams['showinactive'] = 0;
        }

        $actionCompleteAjaxUrl = action('Admin\AdminBaseController@adminFileCalled', $ajaxParams);
        $autocomplete = '
			new Ajax.Autocompleter("'.$boxid.'","search-box-small-autocomplete", "'.$actionCompleteAjaxUrl.'", {
				paramName:"search"
				, parameters:'.$params.'
				, minChars: 1
				, frequency:0.3
				, updateElement: function(sel){
						getResponse($(sel).down("span").innerHTML);$("'.$boxid.'").value="";
				}
			});
		';
    }

    $out .= '
	<div id="'.$wrapid.'" class="search-box-small '.$wrapcss.'">
			<input type="text" id="'.$boxid.'" class="search-box-small-q no-submit" value="'.hs_htmlspecialchars($title).'" autocomplete="off" tabindex="-1" onFocus="hs_shortcutsOff();" onBlur="hs_shortcutsOn();" />
	</div>
	<script type="text/javascript">
		$jq(document).ready(function() {
			$jq(".no-submit").keypress(function(e){
				if ( e.which == 13 ) { e.preventDefault(); }
			});
			$jq("#'.$boxid.'").focus(function() {
				if($jq(this).val() == "'.hs_jshtmlentities($title).'") $jq(this).val("");
			});
			$jq("#'.$boxid.'").blur(function() {
				if($jq(this).val() == ""){
					$jq(this).val("'.hs_jshtmlentities($title).'");
				}
			});
		});
		document.observe("dom:loaded", function(){
			'.$autocomplete.'
		});
	</script>';

    return $out;
}

/*****************************************
NOTE BOX NAVIGATION HEADER
*****************************************/
function milonic_head()
{
    $out = '';
    if (defined('STATIC_DIRECT')) {
        $out .= '<script type="text/javascript" src="static/js/milonic/milonic_src.js"></script>';
        $out .= '<script type="text/javascript" src="static/js/milonic/mmenudom.js"></script>';
    //$out .= '<script type="text/javascript" src="static/js/milonic/mm_menueditapi.js"></script>';
    } else {
        $out .= '<script type="text/javascript" src="'.static_url().mix('static/js/helpspot.milonic.js').'"></script>';
    }

    $out .= '
	<script type="text/javascript" language="JavaScript">
	fixMozillaZIndex=true; //Fixes Z-Index problem  with Mozilla browsers but causes odd scrolling problem, toggle to see if it helps
	_menuCloseDelay=500;
	_menuOpenDelay=150;
	_subOffsetTop=2;
	_subOffsetLeft=-2;
	closeAllOnClick=true;

	with(submenuStyle=new mm_style()){
	bordercolor="#c7c7c7";
	borderstyle="solid";
	borderwidth=1;
	openstyle="rtl";
	fontfamily="Inter,-apple-system, BlinkMacSystemFont, Roboto, Ubuntu, Arial, sans-serif";
	fontstyle="normal";
	headerbgcolor="#c7c7c7";
	headercolor="#3a2d23";
	offbgcolor="#c7c7c7";
	offcolor="#3a2d23";
	onbgcolor="#cccccc";
	oncolor="#208AAE";
	padding=10;
	pagebgcolor="#82B6D7";
	pagecolor="black";
	separatorcolor="#c7c7c7";
	separatorsize=1;
	subimage="'.static_url().'/static/img5/navigate-forward.svg";
	subimagepadding=0;
	subimageposition="right";
	subimagecss="milonicsubimage";
	}';

    $out .= renderMenuKB();
    $out .= renderMenuResponses();

    $out .= '

	drawMenus() // Draw the sub menus first.
	</script>';

    return $out;
}

/********************************************
HEAD SETUP FOR WYSIWYG
*********************************************/
function wysiwyg_load($id, $loc, $docid){ //ids of textareas
    $setup = '';
    $out = '';
    $toolbar = 'bold italic link forecolor backcolor bullist numlist table removeformat pastetext emoticons code';
	if ($docid and $loc == 'request') {
		$toolbar = 'save '.$toolbar;
    }
    if ($loc == 'request') {
        $setup = 'simpleStorage.set("newRequest", get_note_body("tBody"));';
    }

    if ($loc == 'kb') {
        $toolbar = 'formatselect aligncenter alignjustify alignleft alignnone alignright ' . $toolbar;
    }

	$out .= '
		<script src="./static/js/tinymce/tinymce.min.js"></script>
        <script type="text/javascript">
                tinymce.init({
				selector: "#'.$id.'",
				plugins: "code autoresize lists table paste save emoticons link autolink'.($loc == 'request' ? ' hsresponseautocomplete hsstaffautocomplete hstagautocomplete ': '').'",
                max_height: 500, // this option sets the maximum height the editor can automatically expand to
                menubar: false,
				toolbar: "'.$toolbar.'",
				images_upload_url: "'.cHOST.'/admin?pg=ajax_gateway&action=wysiwyg_upload&loc='.$loc.'&docid='.$docid. '",
				images_upload_base_path: "",
                images_upload_credentials: true,
                images_reuse_filename: true,
                // file_picker_types: "image",
                // block_unsupported_drop: false,
				paste_data_images: true,
				save_onsavecallback: function () { doNoteDraftSave(); },
                content_style: "body {font-family: Inter,-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Noto Sans\', Ubuntu, \'Droid Sans\', Arial, \'Helvetica Neue\', sans-serif; !important;margin:8px;} img{max-width:560px;} '.(inDarkMode() ? 'body, .mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before { color:rgba(255,255,255,0.70); } body a{ color: #4299e1; }' : '').'",
                content_css_cors: true,
				branding: false,
                statusbar: false,
                contextmenu: false,
                browser_spellcheck: true,
                setup: function(editor) {
                    editor.on("keyup", function(e) {
                        '. $setup .'
                    });
                }
            });
            </script>';


	return $out;
}

/********************************************
IF NO WYSIWYG THEN SHOW MARKDOWN INFO
*********************************************/
function markdown_setup($textarea)
{
    $out = '
		<script type="text/javascript" language="JavaScript">
		function load_markdown(id){
			editor_type = "markdown"; //change editor type to make sure other JS scripts know this is markdown
			new Insertion.Before(id, \'<input type="hidden" name="note_is_markdown" value="1"><div id="\'+id+\'_preview" style="border:0px;display:none;"></div>\');
			new Insertion.Before(\''.$textarea.'\', \'<input type="hidden" name="note_is_markdown" value="1"><div class="button-bar"><a href="#" id="'.$textarea.'_markdown_syntax_popup" onclick="return false;" class="btn inline-action">'.hs_jshtmlentities(lg_no_wysiwyg_textarea_formatting).'</a> <a href="#"  class="btn inline-action" onclick="new Ajax.Updater(\\\'\'+id+\'_preview\\\', \\\'admin?pg=ajax_gateway&action=markdown_preview&rand=\\\' + ajaxRandomString(), {parameters: \\\'text=\\\'+eq($F(\\\'\'+id+\'\\\')), onComplete: function(){ hs_overlay(\\\'\'+id+\'_preview\\\', {title:\\\''.hs_jshtmlentities(lg_preview).'\\\'}); } });return false;">'.hs_jshtmlentities(lg_preview).'</a></div>\');
		}

		$jq(document).ready(function() {
			if($jq("#request_note_box").length != 0){
				var tBodyWidth = $jq("#request_note_box").innerWidth() - $jq("#request_note_box_box_body").css("padding-left").replace("px","") - $jq("#request_note_box_box_body").css("padding-right").replace("px","") - $jq("#tBody").css("padding-left").replace("px","") - $jq("#tBody").css("padding-right").replace("px","");
				$jq("#tBody").css("width",tBodyWidth+"px");
			}

			if($jq("#'.$textarea.'").length != 0){
				load_markdown(\''.$textarea.'\');
				$jq("#'.$textarea.'_markdown_syntax_popup").click(function(){hs_overlay({href:\'admin?pg=ajax_gateway&action=markdown_syntax\',title:\''.hs_jshtmlentities(lg_markdown_format_label).'\'});});
				new ResizeableTextarea($("'.$textarea.'"));
			}
		});
		</script>';

    return $out;
}

function showFormatedTextOptions($id)
{
    $out = '
		<input type="hidden" name="note_is_markdown" value="1">
		<div id="'.$id.'_preview" style="display:none;"></div>

		<div class="button-bar" style="overflow:hidden;display:flex;">
			<a href="#" id="'.$id.'_markdown_syntax_popup" onclick="return false;" class="btn inline-action">'.hs_jshtmlentities(lg_no_wysiwyg_textarea_formatting).'</a>
            <a href="#"  class="btn inline-action" onclick="new Ajax.Updater(\''.$id.'_preview\', \'admin?pg=ajax_gateway&action=markdown_preview&rand=\' + ajaxRandomString(), {parameters: \'text=\'+eq($F(\''.$id.'\')), onComplete: function(){ hs_overlay(\''.$id.'_preview\', {title:\''.hs_jshtmlentities(lg_preview).'\'}); } });return false;">'.hs_jshtmlentities(lg_preview).'</a>
		</div>

		<script type="text/javascript" language="JavaScript">
		$jq(document).ready(function() {
			$jq("#'.$id.'_markdown_syntax_popup").click(function(){hs_overlay({href:\'admin?pg=ajax_gateway&action=markdown_syntax\',title:\''.hs_jshtmlentities(lg_markdown_format_label).'\'});});
		});
		</script>';

    return $out;
}

/********************************************
LIST ELEMENTS FOR RESPONSES MENU
*********************************************/
function renderMenuResponses()
{
    global $user;
    $out = '';
    $mainmenu = '';
    $responses = apiGetAllRequestResponses(0, $user['xPerson'], $user['fUserType'], false, '');
    $totalResponses = ($_GET['batch']) ? count($_GET['batch']) : 1;

    //Add response option
    $mainmenu .= 'aI("text=`<a href='.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.responses']).' class=\\\'btn inline-action\\\'>'.hs_jshtmlentities(lg_jsmenu_manageresponses).'</a>`;type=form;onbgcolor=;onborder=;align=center");';

    // Show shortcut message
    $mainmenu .= 'aI("text=`<div style=\\\'color:#f8f9fa;\\\'>' . lg_response_shortcut . '</div>`;type=disabled;");';

    //Setup recently used and most used
    if (hs_rscheck($responses)) {
        $rs = apiGetMostUsedResponses($user['xPerson']);
        if (hs_rscheck($rs)) {
            //Add entry in main menu
            $mainmenu .= 'aI("text='.hs_jshtmlentities(lg_jsmenu_respmostused).';url=javascript:void(0);showmenu='.hs_jshtmlentities(lg_jsmenu_respmostused).';");';
            //Open new submenu
            $out .= '
				with(milonic=new menuname("'.hs_jshtmlentities(lg_jsmenu_respmostused).'")){
				   style=submenuStyle;
				   openstyle="ltr";
				   overflow="scroll";';

            while ($row = $rs->FetchRow()) {
                $out .= 'aI("text='.hs_jshtmlentities($row['sResponseTitle']).';url=javascript:getResponse('.$row['xResponse'].', '.$totalResponses.');");';
            }

            $out .= '}';
        }
    }

    if (hs_rscheck($responses) && $responses->RecordCount() != 0) {
        $rm = [];
        while ($row = $responses->FetchRow()) {
            //Clean up so we know exactly how many folders we have
            $folders = explode('/', $row['sFolder'], 3);
            foreach ($folders as $k=>$folder) {
                $folders[$k] = trim($folder);
            }
            $folder_name = implode('#-#', $folders);
            $rm[$folder_name][] = 'aI("text='.hs_jshtmlentities($row['sResponseTitle']).';url=javascript:getResponse('.$row['xResponse'].', '.$totalResponses.');");';
        }

        //Loop over results and build any folders which are not already built.
        foreach ($rm as $k=>$v) {
            $folders = explode('#-#', $k);

            if (isset($folders[2])) {
                $name2 = $folders[0].'#-#'.$folders[1];
                $name3 = $folders[0].'#-#'.$folders[1].'#-#'.$folders[2];

                $string = 'aI("text='.hs_jshtmlentities($folders[2]).';showmenu='.md5($name3).';");';

                if (! isset($rm[$name2])) {
                    $rm[$name2] = [];
                }
                if (array_search($string, $rm[$name2]) === false) {
                    array_unshift($rm[$name2], $string);
                }
            }

            if (isset($folders[1])) {
                $name1 = $folders[0];
                $name2 = $folders[0].'#-#'.$folders[1];

                $string = 'aI("text='.hs_jshtmlentities($folders[1]).';showmenu='.md5($name2).';");';

                if (! isset($rm[$name1])) {
                    $rm[$name1] = [];
                }
                if (array_search($string, $rm[$name1]) === false) {
                    array_unshift($rm[$name1], $string);
                }
            }

            // In case where we don't have a first level folder add it
            if (isset($folders[0])) {
                if (! isset($rm[$folders[0]])) {
                    $rm[$folders[0]] = [];
                }
            }
        }

        ksort($rm);

        //Build each menu
        foreach ($rm as $k=>$ai_array) {
            $out .= '
				with(milonic=new menuname("'.md5($k).'")){
				   style=submenuStyle;
				   openstyle="ltr";
				   overflow="scroll";
				   '.implode('', $ai_array).'
				}';
        }

        //Add main menu links
        foreach ($rm as $k=>$v) {
            if (! utf8_strpos($k, '#-#')) {
                $mainmenu .= 'aI("text='.hs_jshtmlentities($k).';showmenu='.md5($k).';url=javascript:void(0);");';
            }
        }
    }

    //If no responses then show message
    if (! hs_rscheck($responses)) {
        $mainmenu .= 'aI("text='.lg_jsmenu_appresponseempty.'");';
    }

    //Setup main menu after sub menus
    $out = $out.'with(milonic=new menuname("response_mil_menu")){style=submenuStyle;openstyle="ltr";overflow="scroll";'.$mainmenu.'}';

    return $out;
}

/********************************************
LIST ELEMENTS FOR KB MENU
*********************************************/
function renderMenuKB()
{
    $pub_books = apiGetAllBooks();

    $out = '<!-- kb sub menu -->
			with(milonic=new menuname("kb_mil_menu")){
				style=submenuStyle;
				openstyle="rtl";
				overflow="scroll";';

    if (hs_rscheck($pub_books)) {
        while ($book = $pub_books->FetchRow()) {
            $out .= 'aI("text='.hs_jshtmlentities($book['sBookName']).($book['fPrivate'] == 1 ? ' ('.lg_request_kbprivate.')' : '').';url=javascript:kbui('.$book['xBook'].');");';
        }
    } else {
        $out .= 'aI("text='.lg_jsmenu_nokb.'");';
    }

    $out .= "}\n\n";

    return $out;
}

function renderOptionMenuButton($id){
    return '<div id="'.$id.'" class="option-menu" title="'.lg_options.'"><img src="'.static_url().'/static/img5/ellipsis-h-solid.svg" style="height:24px;" title="" /></div>';
}

/********************************************
RENDER JS FOR DRILL DOWN LIST
*********************************************/
function RenderDrillDownList($field_id, $elements, $selected = [], $sep = ' ', $hidden_name = '', $tindex = '')
{
    $level_ct = find_max_array_depth($elements);
    //Build level names
    $levels = [];
    for ($i = 1; $i <= $level_ct; $i++) {
        array_push($levels, 'Custom'.$field_id.'_'.$i);
    }

    //Build js object name
    $jsname = 'drill_list_Custom'.$field_id;

    //Build hidden field name
    $hidden_name = empty($hidden_name) ? 'Custom'.$field_id : $hidden_name;

    $out = jsForDrillDownList($levels, $jsname, $elements);

    //Set what the page should show when loaded
    if (! empty($selected)) {
        $out .= '<script type="text/javascript">'."\n";
        foreach ($selected as $key=>$value) {
            $out .= $jsname.'.forField("'.$key.'").setValues("'.$value.'");';
        }
        $out .= '</script>';
    }

    //".('drill_list_Custom'.$unid).".forField(custom+'_'+i).setValues($(custom+'_'+i).getValue()); this line sets select objects default value for page load. We do this so when  initDynamicOptionLists(); is called when a new field is added via ajax the existing fields in the form don't get reset
    $oc_function = 'var f=function(){var depth='.$level_ct.";var custom='Custom".$field_id."';var stack=new Array;for(i=1;i<=depth;i++){if($(custom+'_'+i).getValue() != '' && $(custom+'_'+i).getValue() != null){".('drill_list_Custom'.$field_id).".forField(custom+'_'+i).setValues($(custom+'_'+i).getValue());stack.push($(custom+'_'+i).getValue());}} $('".$hidden_name."').value=stack.join('#-#'); }; f();";

    //Setup onchange handler
    $onchange = 'onchange="'.$jsname.'.change(this); '.$oc_function.'"';

    $i = true;
    $selects = [];
    $temp = '';
    foreach ($levels as $k=>$v) {
        $temp = '<div style="display:flex;align-items:center;">';

        if ($i == false) $temp .= $sep;

        $temp .= '<select tabindex="'.$tindex.'" name="'.$v.'" id="'.$v.'" '.$onchange.' style="flex:1;">';
        if ($i == true) {
            reset($selected);
            $selected_in_firstlist = '';
            if (! empty($selected)) {
                $selected_in_firstlist = current($selected);
            }

            //On the first select list out the first level, the script will list the others via JS
            $temp .= '<option value=""></option>';
            foreach ($elements as $key=>$value) {
                /*
                //top level element with sub elements, else is only used when top level element has no sub elements
                if(is_array($value)){
                    $out .= '<option value="'.$key.'" '.($selected_in_firstlist == $key ? 'selected' : '').'>'.$key.'</option>';
                }else{
                    $out .= '<option value="'.$key.'" '.($selected_in_firstlist == $key ? 'selected' : '').'>'.$key.'</option>';
                }
                */
                $temp .= '<option value="'.$key.'" '.($selected_in_firstlist == $key ? 'selected' : '').'>'.$key.'</option>';
            }
            $i = false;
        } else {
            $temp .= '<script type="text/javascript">'.$jsname.'.printOptions("'.$v.'")</script>';
        }
        $temp .= '</select></div>';

        $selects[] = $temp;
    }

    //Hidden field which holds level count, makes js easier
    $out .= '<input type="hidden" name="Custom'.$field_id.'_ct" id="Custom'.$field_id.'_ct" value="'.count($levels).'" />';

    $out .= implode('', $selects);

    //Hidden field which holds concatenated result of all fields
    $out .= '<input name="'.$hidden_name.'" id="'.$hidden_name.'" type="hidden" value="" />';

    //JS To setup lists and initially set hidden field that holds actual value
    $out .= '<script type="text/javascript">
			 initDynamicOptionLists();
			 $("Custom'.$field_id.'"+"_'.$level_ct.'").onchange();
			 </script>';

    return $out;
}

/********************************************
RENDER JS FOR DRILL DOWN LIST
*********************************************/
function jsForDrillDownList($levels, $jsname, $elements)
{
    $level_ct = count($levels);
    $out = '<script type="text/javascript">'."\n";

    $out .= $jsname.' = new DynamicOptionList("'.implode('","', $levels).'");'."\n";
    $out .= $jsname.'.selectFirstOption = true;';

    $out .= jsForDrillDownElement($jsname, $elements);

    $out .= '</script>';

    return $out;
}

/********************************************
RENDER JS FOR EACH DRILL DOWN, RECURSIVE
*********************************************/
function jsForDrillDownElement($jsname, $element, $parent = [], $output = '')
{
    $parent_string = '';
    $array_values = array_keys($element);
    array_unshift($array_values, ''); //Makes first selection option an empty element

    if (is_array($element)) {
        //Create path for parent level
        foreach ($parent as $p) {
            $parent_string .= '.forValue("'.hs_jshtmlentities($p).'")';
        }

        //if(count($array_values) == count($element)) $parent = array();

        if (! empty($array_values)) {
            $output = $jsname.$parent_string.'.addOptions("'.implode('","', $array_values).'");'."\n";
        }

        foreach ($element as $item=>$value) {
            if (is_array($value)) {
                $temp_parent = $parent;
                array_push($temp_parent, $item);
                $output .= jsForDrillDownElement($jsname, $value, $temp_parent, $output);
            }
        }
    }

    if (! empty($output)) {
        return $output;
    } else {
        return false;
    }
}

/********************************************
RENDER THE EDIT TREE FOR DRILL DOWNS
*********************************************/
function RenderDrilDownEdit($drill_array, $parent = [])
{
    $id = md5(implode('#-#', $parent));

    foreach ($drill_array as $item=>$value) {
        if (is_array($value)) {
            $temp_parent = $parent;
            array_push($temp_parent, $item);
            $output .= '<li><span class="hand" onclick="remove_from_group(\''.implode('#-#', array_merge($parent, [$item])).'\');return false;"><img src="'.static_url().'/static/img5/times-solid-red.svg" style="vertical-align: middle;" width="16" height="16" /></span> <img src="'.static_url().'/static/img5/arrow-alt-right-solid-blue.svg" style="vertical-align: middle;margin-right: 4px;" width="16" height="16" /> '.$item.' <ul class="drilltree">'.RenderDrilDownEdit($value, $temp_parent).'</ul>';
        } else {
            $thisid = md5(implode('#-#', array_merge($parent, [$item])));
            $output .= '<li><span class="hand" onclick="remove_from_group(\''.implode('#-#', array_merge($parent, [$item])).'\');return false;"><img src="'.static_url().'/static/img5/times-solid-red.svg" style="vertical-align: middle;" width="16" height="16" /></span> <span class="hand" id="'.$thisid.'" onclick="add_sub_element(this.id, \'group_'.$id.'\',\''.implode('#-#', array_merge($parent, [$item])).'\');return false;"><img src="'.static_url().'/static/img5/arrow-alt-right-solid-blue.svg" style="vertical-align: middle;margin-right: 4px;" width="16" height="16" /></span> '.$item.' <span id="'.$thisid.'_sublist"></span>';
        }
    }

    //$output .= '<br>';
    $output .= '<div id="group_'.$id.'_wrapper">';
    $output .= '<div class="hand" style="padding-top:2px;" onclick="add_to_group(\'group_'.$id.'\', \''.hs_jshtmlentities(implode('#-#', $parent)).'\');return false;" id="group_'.$id.'"><img src="'.static_url().'/static/img5/plus-solid-green.svg" width="16" height="16" /></div>';
    $output .= '<span id="group_'.$id.'_form"></span>';
    $output .= '</div>';
    $output .= '</li>';

    if (! empty($output)) {
        return $output;
    } else {
        return false;
    }
}

/*****************************************
DISPLAY CONTENT BOX
*****************************************/
function displayContentBoxTop($primary = '', $secondary = '', $tophtml = '', $width = '100%', $class = '', $classbody = '', $toponly = false, $hide = false, $forceID = false, $topMenu = false)
{
    $id = ($forceID ? $forceID : 'box_id_'.md5($primary));

    $out = '
	<div class="box '.($toponly ? 'box-top-noborder' : '').' '.$class.'" width="'.$width.'" id="'.$id.'" '.($hide ? 'style="display:none;"' : '').'>';
    if ($topMenu) {
        $tmct = count($topMenu);
        $out .= '<ul class="box-top-menu">';
        foreach ($topMenu as $k=>$item) {
            if (isset($item['html'])) {
                $out .= '<li class="box-top-menu-li">'.$item['html'].'</li>';
            } else {
                $out .= '<li class="box-top-menu-li '.($tmct == 1 ? 'box-top-menu-li-border' : '').'" id="'.$item['id'].'_hook"><a href="" onclick="'.$item['click'].';return false;" id="'.$item['id'].'">'.$item['name'].'<img src="'.static_url().'/static/img5/space.gif" id="" class="box-top-menu-arrow"></a></li>';
            }
        }
        $out .= '</ul>';
    }

    if ($primary) {
        $out .= '<div class="box_title">'.$primary.'</div>';
    }

    if ($secondary || $tophtml) {
        $out .= '<div class="box_top">';
        if ($secondary) {
            $out .= '<div class="box_top_note">'.$secondary.'</div>';
        }
        if ($tophtml) {
            $out .= '<div class="'.($secondary ? 'box_top_html' : '').'">'.$tophtml.'</div>';
        }
        $out .= '</div>';
    }

    $out .= '<div class="box_body '.($toponly ? 'box-hide' : '').' '.$classbody.'" id="'.$id.'_box_body">';

    return $out;
}

function displayContentBoxBottom($html = false)
{
    $out = '</div>';

    if ($html) {
        $out .= '<div class="box_footer">'.$html.'</div>';
    }

    $out .= '</div>';

    return $out;
}

/*****************************************
CONDITIONAL BOX
*****************************************/
function conditionalBoxTop($primary = '', $secondary = '', $tophtml = '')
{
    $out = '
	<div class="box" width="100%" id="box_id_'.md5($primary).'">
		<div class="box_title">'.$primary.'</div>';
    if ($secondary || $tophtml) {
        $out .= '<div class="box_top">';
        if ($secondary) {
            $out .= '<div class="box_top_note">'.$secondary.'</div>';
        }
        if ($tophtml) {
            $out .= '<div class="'.($secondary ? 'box_top_html' : '').'">'.$tophtml.'</div>';
        }
        $out .= '</div>';
    }

    $out .= '<div class="box_body box_body_solid">';

    return $out;
}

function conditionalBoxBottom($html = false)
{
    $out = '</div>';

    if ($html) {
        $out .= '<div class="box_footer">'.$html.'</div>';
    }

    $out .= '</div>';

    return $out;
}

/*****************************************
RENDER REQUEST TEXT/HTML FEED/MAIL HEADER
*****************************************/
function renderRequestTextHeader($reqrow, &$allStaff, &$catlist, $type = 'text')
{
    $out = '';

    $assign = $reqrow['xPersonAssignedTo'] ? $allStaff[$reqrow['xPersonAssignedTo']]['sFname'].' '.$allStaff[$reqrow['xPersonAssignedTo']]['sLname'] : lg_noassign;

    $goto = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqrow['xRequest']]);

    if ($type == 'text') {
        $out .= ($reqrow['fUrgent']) ? '**'.lg_isurgent."**\n\n" : '';

        $out .= lg_feed_reqinfo;

        $out .= ' ('.lg_feed_viewlink.': '.$goto.")\n";

        $out .= lg_feed_assign.': '.$assign."\n";
        $out .= lg_feed_status.': '.$GLOBALS['reqStatus'][$reqrow['xStatus']]."\n";
        $out .= lg_feed_category.': '.$catlist[$reqrow['xCategory']]."\n\n";

        $out .= lg_feed_customer."\n";
        $out .= lg_feed_cname.': '.$reqrow['fullname']."\n";
        $out .= lg_feed_cid.': '.$reqrow['sUserId']."\n";
        $out .= lg_feed_cemail.': '.$reqrow['sEmail']."\n";
        $out .= lg_feed_cphone.': '.$reqrow['sPhone'];
    } else {
        $out = '
				<table cellspacing="0" cellpadding="3">
					<tr>
						<td colspan="2"><b><u>'.lg_feed_reqinfo.'</u></b></td>
					</tr>
					<tr>
						<td>'.lg_feed_viewlink.':</td>
						<td><a href="'.$goto.'">'.$reqrow['xRequest'].'</a></td>
					</tr>
					<tr>
						<td>'.lg_feed_assign.':</td>
						<td>'.$assign.'</td>
					</tr>
					<tr>
						<td>'.lg_feed_status.':</td>
						<td>'.$GLOBALS['reqStatus'][$reqrow['xStatus']].'</td>
					</tr>
					<tr>
						<td>'.lg_feed_category.':</td>
						<td>'.$catlist[$reqrow['xCategory']].'</td>
					</tr>
					<tr>
						<td colspan="2"><b><u>'.lg_feed_customer.'</b></u></td>
					</tr>
					<tr>
						<td>'.lg_feed_cname.':</td>
						<td>'.$reqrow['fullname'].'</td>
					</tr>
					<tr>
						<td>'.lg_feed_cid.':</td>
						<td>'.$reqrow['sUserId'].'</td>
					</tr>
					<tr>
						<td>'.lg_feed_cemail.':</td>
						<td>'.$reqrow['sEmail'].'</td>
					</tr>
					<tr>
						<td>'.lg_feed_cphone.':</td>
						<td>'.$reqrow['sPhone'].'</td>
					</tr>
				</table>
			';
    }

    return $out;
}

/*****************************************
RENDER FEED HTML HEADER
*****************************************/
function renderFeedHeader($reqrow, &$allStaff, &$catlist)
{
    $out = '<table width="500" border="0">';

    $out .= ($reqrow['fUrgent']) ? '<tr><td colspan="4" align="center">**'.lg_isurgent.'**</td></tr>' : '';

    $out .= '<tr><td colspan="2" width="50%"><b style="color:blue">'.lg_feed_customer.'</b></td><td colspan="2" width="50%"><b style="color:blue">'.lg_feed_reqinfo.'</b></td></tr>';

    $assign = $reqrow['xPersonAssignedTo'] ? $allStaff[$reqrow['xPersonAssignedTo']]['sFname'].' '.$allStaff[$reqrow['xPersonAssignedTo']]['sLname'] : lg_noassign;

    $out .= '<tr><td>'.lg_feed_cname.':</td><td>'.$reqrow['fullname'].'</td><td>'.lg_feed_assign.':</td><td>'.$assign.'</td></tr>';
    $out .= '<tr><td>'.lg_feed_cid.':</td><td>'.$reqrow['sUserId'].'</td><td>'.lg_feed_status.':</td><td>'.$GLOBALS['reqStatus'][$reqrow['xStatus']].'</td></tr>';
    $out .= '<tr><td>'.lg_feed_cemail.':</td><td>'.$reqrow['sEmail'].'</td><td>'.lg_feed_category.':</td><td>'.$catlist[$reqrow['xCategory']].'</td></tr>';
    $out .= '<tr><td>'.lg_feed_cphone.':</td><td>'.$reqrow['sPhone'].'</td><td>&nbsp;</td><td>&nbsp;</td></tr>';

    $out .= '</table>';

    return $out;
}

/*****************************************
RENDER HTML VERSION OF REQUEST HISTORY
*****************************************/
function renderRequestHistory($reqid, &$allStaff, &$fm, $showall = false, $showmenu = true, $directlink = false, $request_history_view = false, $from_streamview=false)
{
    global $user;

    $reqid = is_numeric($reqid) ? $reqid : 0;
    $out = '';
    $request_history_view = ($request_history_view ? $request_history_view : $user['fRequestHistoryView']);
    $files_only = ($request_history_view == 3 ? true : false);

    // Get request history
    $ticket = \HS\Domain\Workspace\Request::with('history.documents')->find($reqid);
    $totalNotes = $ticket->history->count();

    if ($ticket) {

        if ($directlink and $directlink > 0) { // Show only the single note
            $notes = $ticket->history()
                ->where('xRequestHistory', $directlink)
                ->get();
        } else if ($showall) {
            $notes = $ticket->history;
        }else if ($user['fRequestHistoryView'] == 4 ){
            //Limit to only records with a tNote if the staff member has notes only display in their preferences
            $limit = $user['iRequestHistoryLimit'];
            $notes = $ticket->history()
                ->where('tNote','!=', '')
                ->limit($user['iRequestHistoryLimit'])
                ->get();
        }
        else {
            // set a limit over what we need to keep it from loading a huge number but enough so that if they are using notes only            $notes = $ticket->history()
            $notes = $ticket->history()
                ->limit($user['iRequestHistoryLimit'])
                ->get();
        }
        //Loop through once and find documents so they can be added to each entry
        $historyDocs = [];
        $attachment_times = [];

        $rcount = 0;
        foreach ($notes as $history) {
            $rcount++;
            $row = $history->toArray();
            $row['body'] = '';
            $row['attachments'] = '';
            $options = [];

            if($from_streamview){
            	$options['hidepin'] = true;
            }

            //View logic - 1 = full, 2 = public only, 3 = files only, 4 = just notes
            if (isset($fm['fOpen']) && $fm['fOpen'] == 1) {
                if ($request_history_view == 2 && $row['fPublic'] != 1) {
                    //Skip row
                    continue;
                } elseif ($request_history_view == 3) {
                    //We're only showing files so skip any rows without files
                    if (! $history->documents->count()) {
                        continue;
                    }
                } elseif ($request_history_view == 4 && hs_empty($row['tNote'])) {
                    //Skip row
                    continue;
                }
            }

            //Merged request note
            if ($row['fMergedFromRequest']) {
                $options['flag'] .= '<div class="note-stream-item-flag">'.lg_request_merged.': '.$row['fMergedFromRequest'].'</div>';
            }

            //Footer links - only show if open
            if ($fm['fOpen'] == 1 && $showmenu) {
                $row['menu'] = '';

                //Quote
                if (! hs_empty($row['tNote'])) {
                    $row['menu'] .= '<li class="tooltip-menu-divider"><a href="#" onClick="hs_quote('.$row['xRequestHistory'].',\'tBody\');return false;"><span class="tooltip-menu-maintext">'.lg_request_quote.'</span></a></li>';
                }

                //Forward
                if (! hs_empty($row['tNote'])) {
                    $row['menu'] .= '<li class="tooltip-menu-divider"><a href="#" onClick="hs_forward('.$row['xRequestHistory'].',\'tBody\');return false;"><span class="tooltip-menu-maintext">'.lg_request_forward.'</span></a></li>';
                }

                //Public
                if ($row['fPublic'] == 1) {
                    $row['menu'] .= '<li class="tooltip-menu-divider"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'reqhisid' => $row['xRequestHistory'], 'unpublic' => 1]).'"
                                        onClick="return hs_confirm(\''.lg_request_publiccheck.'\',this.href);" title="'.lg_request_public.'"><span class="tooltip-menu-maintext">'.lg_request_public.'</span></a></li>';
                }

                //Private
                if ($row['fPublic'] == 0 && ! hs_empty($row['tNote'])) {
                    $row['menu'] .= '<li class="tooltip-menu-divider"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $reqid, 'reqhisid' => $row['xRequestHistory'], 'makepublic' => 1]).'"
                                        onClick="return hs_confirm(\''.lg_request_makepubliccheck.'\',this.href);" title="'.lg_request_makepublic.'"><span class="tooltip-menu-maintext">'.lg_request_makepublic.'</span></a></li>';
                }

                //Convert to request
                if ($row['xPerson'] >= 0) {
                    $row['menu'] .= '<li class="tooltip-menu-divider"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'convertToRequest' => 1, 'reqid' => $fm['xRequest'], 'xRequestHistory' => $row['xRequestHistory']]).'" onClick="return hs_confirm(\''.hs_jshtmlentities(lg_request_converttorequestconfirm).'\',this.href);"><span class="tooltip-menu-maintext">'.lg_request_converttorequest.'</span></a></li>';
                }

                //Permalink
                $row['menu'] .= '<li class="tooltip-menu-divider"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $fm['xRequest'], 'xRequestHistory' => $row['xRequestHistory']]).'"><span class="tooltip-menu-maintext">'.lg_request_directlink.': #'.$row['xRequestHistory'].'</span></a></li>';
            }
            if ($history->documents) {
                foreach ($history->documents as $document) {
                    $doc = $document->toArray();
                    $historyDocs[$doc['xDocumentId']]['xDocumentId'] = $doc['xDocumentId'];
                    $historyDocs[$doc['xDocumentId']]['dtGMTChange'] = $doc['dtGMTChange'];
                    $historyDocs[$doc['xDocumentId']]['sFileMimeType'] = $doc['sFileMimeType'];
                    $historyDocs[$doc['xDocumentId']]['sFilename'] = $doc['sFilename'];
                    $historyDocs[$doc['xDocumentId']]['sCID'] = $doc['sCID'];
                    $attachment_times[] = $doc['dtGMTChange'];
                }
            }

            if (is_array($historyDocs) && (hs_empty($row['tLog']) || ! hs_empty($row['tNote']))) {
                foreach ($historyDocs as $k=>$v) {
                        $object = '';
                        if (in_array($v['sFileMimeType'], $GLOBALS['audioMimeTypes']) && hs_setting('cHD_EMBED_MEDIA') == 1) {
                            $object = '<div><audio src="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k]).'" controls="controls"></div>';
                        }

                        // Generate a download link used in various places with the attachments.
                        $download_link = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k, 'showfullsize' => 1, 'download' => 1]);

                        $row['attachments'] .= '<div class="note-stream-item-attachment-detail">
                                                    <div class="note-stream-item-attachment-icon" onclick="window.location = \''.$download_link.'\';">
                                                        '.hs_showMime($v['sFilename']).'
                                                    </div>
                                                    <strong onclick="window.location = \''.$download_link.'\';">'.hs_htmlspecialchars($v['sFilename']).'</strong>
                                                    '.(in_array($v['sFileMimeType'], $GLOBALS['imageMimeTypes']) && $user['fHideImages'] == 0 && strpos($row['tNote'], $v['sCID']) === false ? '
                                                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k, 'showfullsize' => 1]).'" target="_blank">
                                                        <img src="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k]).'" alt="'.$v['sFilename'].'" align="top" border="0">
                                                    </a><br>' : '').'
                                                    '.(! empty($object) ? $object : '').'
                                                    <a href="'.$download_link.'" class="action_link" id="download_link_'.$k.'">'.lg_download.'</a>
                                                    '.(in_array($v['sFileMimeType'], $GLOBALS['imageMimeTypes']) ? '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k, 'showfullsize' => 1]).'" target="_blank" class="action_link">'.lg_request_viewfull.'</a>' : '').'
                                                    '.($v['sFileMimeType'] === 'application/pdf' ? '<a href="admin?pg=file&from=0&id='.$k.'&showfullsize=1" target="_blank" class="action_link">'.lg_request_view.'</a>' : '').'
                                                    <span id="reattach-link-'.$k.'"><a href="" class="reattach-link action_link" onclick="addAnotherReAttach('.$k.',\''.hs_htmlspecialchars(addslashes($v['sFilename'])).'\');return false;">'.lg_reattach.'</a></span>
                                                </div>';
                }
            }

            //Normal update
            if (! hs_empty($row['tNote']) && ! $files_only) {
                $row['tNote'] = replaceInlineImages($row['tNote'], $historyDocs);
                $row['body'] .= formatNote($row['tNote'], $row['xRequestHistory'], ($row['fNoteIsHTML'] ? 'is_html' : 'html'));
            // event log
            } elseif (! hs_empty($row['tLog']) && ! $files_only) {
                // Log titles OR legacy tLog lines
                $log_items = explode("\n", $row['tLog']);
                foreach ($log_items as $k=>$v) {
                    $row['body'] .= '<div class="note-stream-item-logtext">'.hs_htmlspecialchars($v).'</div>';
                }

                // New event logs
                $logEvents = \HS\Domain\Workspace\Event::where('xRequestHistory', $row['xRequestHistory'])->orderBy('dtLogged', 'asc')->get();
                foreach ($logEvents as $logEvent) {
                    $row['body'] .= '<div class="note-stream-item-logtext">'.hs_htmlspecialchars($logEvent->sDescription).'</div>';
                }
            }

            $out .= noteItem($row, $allStaff, $fm, $options);

            unset($historyDocs);

            //If limit reached add the show all row, move record set to the last line so orig request is shown
            if (($directlink && $directlink == $row['xRequestHistory']) || ($rcount >= $user['iRequestHistoryLimit'] && ! $showall)) {
                $showalltext = $directlink ? sprintf(lg_request_showingxdirect, $totalNotes) : sprintf(lg_request_showingx, $user['iRequestHistoryLimit'], $totalNotes);
                $out .= '
                <div class="note-stream-load-more" id="note-stream-load-more">
                    <div class="note-stream-load-more-text">'.$showalltext.'</div>
                    <a href="" class="btn accent" onclick="$jq(\'#note-stream-load-more\').html(ajaxLoading());'.hsAJAXinline('function(){$(\'request_history_body\').innerHTML=arguments[0].responseText;arguments[0].responseText.evalScripts();}', 'request_history_showall', 'xRequest='.$reqid).'return false;">'.lg_request_showall.'</a>
                </div>
                ';
                break;
            }
        }
    }

    //No history items
    if (empty($out)) {
        $out = '<div class="table-no-results">'.lg_nohistory.'</div>';
    }

    //Hover Intent
    $out .= '<script type="text/javascript">showNoteItemMenu();</script>';

    return $out;
}

/*****************************************
NOTE ITEM BOX
*****************************************/
function noteItem(&$item, &$people, &$request, $options = [])
{
    static $rcount = 0;
    $log = '';
    $out = '';
    $msgheader = '';
    $haslog = false;

    //Unserialize log
    if (! hs_empty($item['tLog'])) {
        $log = hs_unserialize($item['tLog']);
        $haslog = true;
    }

    if (! hs_empty($item['tEmailHeaders'])) {
        $fromemail = true;
        $headers = hs_unserialize($item['tEmailHeaders']);
        //Odd format here to provide backward compatibility with old email parser
        $from = hs_parse_email_header((isset($headers['fromaddress']) ? $headers['fromaddress'] : $headers['from']));
        $replyto = hs_parse_email_header((isset($headers['reply_toaddress']) ? $headers['reply_toaddress'] : $headers['reply-to']));
        $to = isset($headers['toaddress']) ? $headers['toaddress'] : $headers['to'];
        $cc = isset($headers['ccaddress']) ? $headers['ccaddress'] : $headers['cc'];

        //Email headers and source
        if (isset($item['menu'])) {
            $item['menu'] .= '<li class="tooltip-menu-divider"><a href="" onclick="showHistoryEmailAndHeaders('.$item['xRequestHistory'].',\'emailheaders\',\''.hs_jshtmlentities(lg_request_showheaders).'\');return false;"><span class="tooltip-menu-maintext">'.lg_request_showheaders.'</span></a></li>';
            $item['menu'] .= '<li class="tooltip-menu-divider"><a href="" onclick="showHistoryEmailAndHeaders('.$item['xRequestHistory'].',\'emailsource\',\''.hs_jshtmlentities(lg_request_showsource).'\');return false;"><span class="tooltip-menu-maintext">'.lg_request_showsource.'</span></a></li>';
        }
    } else {
        $fromemail = false;
    }

    //Note type
    if ($item['fPublic'] == 1) {
        $label = 'public';
    } elseif ($item['fPublic'] == 0 && isset($log['emailtogroup']) && ! hs_empty($log['emailtogroup'])) {
        $label = 'external';
    } elseif ($item['fPublic'] == 0) {
        $label = 'private';
    }

    //Set person label
    if ($fromemail) {
        if (! empty($from['personal'])) {
            $name = parseName($from['personal']);
            $toplabel = $name['fname'].' '.$name['lname'];
        } else {
            $toplabel = $from['mailbox'].'@'.$from['host'];
        }
    } elseif ($item['xPerson'] > 0) {
        $toplabel = hs_htmlspecialchars($people[$item['xPerson']]['sFname']).' '.hs_htmlspecialchars($people[$item['xPerson']]['sLname']);
    } elseif ($item['xPerson'] == 0) {
        if (hs_empty($request['sFirstName']) && hs_empty($request['sLastName'])) {
            $toplabel = lg_request_customer;
        } else {
            $toplabel = hs_htmlspecialchars($request['sFirstName']).' '.hs_htmlspecialchars($request['sLastName']);
        }
    } elseif ($item['xPerson'] == -1) {
        $toplabel = hs_htmlspecialchars(lg_systemname);
    }

    //($tofrom == lg_label_customer ? $tofrom : $tofrom.' ('.lg_label_customer.')');

    //$tofrom = explode(',',$log['emailtogroup']);
    //$tofrom = $tofrom[0];
    //hs_htmlspecialchars($people[$item['xPerson']]['sFname']).' '.substr($people[$item['xPerson']]['sLname'],0,1).' '.(isset($to) ? '<span class="note-stream-item-to">'.lg_to.'</span> '.$to : '');

    //Set emails header info
    if ($fromemail) {
        $msgheader .= '<tr><td class="label">'.lg_request_from.':</td><td>'.(! empty($from['personal']) ? hs_htmlspecialchars($from['personal'].' - ') : '').hs_htmlspecialchars($from['mailbox'].'@'.$from['host']).'</td></tr>';
        $msgheader .= '<tr><td class="label">'.lg_request_to.':</td><td>'.hs_htmlspecialchars(hs_charset_emailheader(hs_parse_email_list($to))).'</td></tr>';
        if (! empty($cc)) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailcc.':</td><td>'.hs_htmlspecialchars(hs_charset_emailheader(hs_parse_email_list($cc))).'</td></tr>';
        }
        if (! empty($replyto['host'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailreply.':</td><td>'.(! empty($replyto['personal']) ? hs_htmlspecialchars($replyto['personal'].' - ') : '').hs_htmlspecialchars($replyto['mailbox'].'@'.$replyto['host']).'</td></tr>';
        }
        $msgheader .= '<tr><td class="label">'.lg_request_subject.':</td><td>'.hs_htmlspecialchars(hs_charset_emailheader($headers['subject'])).'</td></tr>';
    } elseif ($item['fPublic'] and $item['xPerson'] == 0 && hs_empty($item['tEmailHeaders']) && $haslog) {
        $msgheader .= '<tr><td>'.lg_request_viaportal.'</td></tr>';
    } elseif ($haslog) {
        //email subject that was sent
        if (! hs_empty($log['sTitle'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_subject.':</td><td>'.hs_htmlspecialchars($log['sTitle']).'</td></tr>';
        }
        //Notified internal users
        $ccpeople = explode(',', $log['ccstaff']);
        if (is_array($ccpeople) && ! hs_empty($ccpeople) && ! empty($ccpeople[0])) {	//Need !empty($ccpeople[0]), not sure why!
            $l = 1;
            $pplcount = count($ccpeople);
            $msgheader .= '<tr><td class="label">'.lg_request_cc.':</td><td>';
            foreach ($ccpeople as $ccid) {
                $msgheader .= hs_htmlspecialchars($people[$ccid]['sFname'].' '.$people[$ccid]['sLname']);
                $msgheader .= ($l < $pplcount) ? ', ' : '';
                $l++;
            }
            $msgheader .= '</td></tr>';
        }
        //to line, used in normal public update
        if (! hs_empty($log['customeremail'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailto.':</td><td>'.hs_htmlspecialchars($log['customeremail']).'</td></tr>';
        }
        //to line, used in external notes
        if (! hs_empty($log['emailtogroup'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailto.':</td><td>'.hs_htmlspecialchars(str_replace(',', ', ', $log['emailtogroup'])).'</td></tr>';
        }
        //customer cc's - used in public and external notes
        if (! hs_empty($log['emailccgroup'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailcc.':</td><td>'.hs_htmlspecialchars(str_replace(',', ', ', $log['emailccgroup'])).'</td></tr>';
        }
        //customer bcc's - used in public and external notes
        if (! hs_empty($log['emailbccgroup'])) {
            $msgheader .= '<tr><td class="label">'.lg_request_emailbcc.':</td><td>'.hs_htmlspecialchars($log['emailbccgroup']).'</td></tr>';
        }
    }

    //Row photo
    $avatar = new HS\Avatar\Avatar();
    $photo = $avatar->name($toplabel)->xPerson($item['xPerson'])->html();

    //Body
    if(isset($options['stream'])){
        $body = formatStreamNote($item['tNote'],($item['fNoteIsHTML'] ? 'is_html' : 'html'));
    }else{
        $body = wordwrap($item['body'], 70, "\n");
    }

    $pin = false;
    if ($item['tNote'] != '') {
        $pinLabel = ($item['fPinned']) ? lg_request_pinned : '';
        if ($request['fOpen']) {
            $pin = '<a class="note-stream-item-pin '.($item['fPinned'] ? 'pinned' : '').'" href="javascript:void(0);" data-id="'.$item['xRequestHistory'].'">'.svg('pin').$pinLabel.'</a>';
        }
    }

    $out .= '
	<div class="note-stream-item card smshadow '.($rcount & 1 ? 'note-stream-item-odd' : '').' note-stream-item-'.$label.'" id="xRequestHistory-'.$item['xRequestHistory'].'" style="position:relative;">
		<div class="note-stream-item-sidebar">
			<div class="note-stream-item-icon user-icon-wrap">
				'.$photo.'
			</div>
		</div>
		<div class="note-stream-item-body">
			'.(isset($options['flag']) && ! empty($options['flag']) ? $options['flag'] : '').'

            <div class="note-stream-item-header">
                <div style="display:flex;align-items:center;">
                    '.(isset($options['reqid_link']) ? '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => $item['xRequest']]).'" class="reqid-link" style="font-weight:normal;display:inline;margin-right:10px;font-weight:700;" id="link-'.$item['xRequest'].'">'.$item['xRequest'].'</a>' : '').'
                    <div class="note-stream-item-name">
                        '.$toplabel.'
                    </div>
                </div>
                <div class="note-stream-item-right" style="margin-right:80px;">
                    '.(isset($item['menu']) ? '<div style="display: flex;align-items: center;margin-right:10px;"><img src="'.static_url().'/static/img5/ellipsis-h-solid.svg" class="note-stream-item-menubtn" id="xRequestHistory-'.$item['xRequestHistory'].'-menubtn" alt="" style="height: 18px;cursor: pointer;" title="'.lg_menu.'"></div>' : '').'
                    '.(isset($item['menu']) ? '<div class="note-stream-item-menu" id="xRequestHistory-'.$item['xRequestHistory'].'-menubtn-content" style="display:none;"><ul class="tooltip-menu">'.$item['menu'].'</ul></div>' : '').'
                    '.(isset($options['hidepin']) ? '' : $pin).'
                    <div class="note-stream-item-date">'.hs_showDate($item['dtGMTChange']).'</div>
                </div>
            </div>

            <div class="note-label label-'.$label.'" style="position:absolute;top:12px;right:0;">'.constant('lg_label_'.$label).'</div>

            <div class="note-stream-item-text">
                '.(! empty($msgheader) ? '<table class="note-stream-header-wrap">'.$msgheader.'</table>' : '').'
                <div class="note-stream-item-body-'.$label.'">'.$body.'</div>
            </div>

            '.(! empty($item['attachments']) ? '<div class="note-stream-item-attachments">'.$item['attachments'].'</div>' : '').'
        </div>
	</div>';

    $rcount++;

    return $out;
}

/*****************************************
HISTORY FOR USE IN DATA FEEDS
*****************************************/
function renderRequestHistoryFeed($reqid, &$allStaff, &$fm, $customerfriendly = false)
{
    global $page;
    global $user;
    $reqid = is_numeric($reqid) ? $reqid : 0;
    $out = '';
    $pad = 75;

    // Get request history
    $request = \HS\Domain\Workspace\Request::with('history.documents')->find($reqid);

    if ($request) {
        foreach ($request->history as $history) {

            //Loop through once and find documents so they can be added to each entry
            $historyDocs = [];
            if ($history->documents) {
                foreach ($history->documents as $document) {
                    $doc = $document->toArray();
                    $historyDocs[$doc['xDocumentId']]['xDocumentId'] = $doc['xDocumentId'];
                    $historyDocs[$doc['xDocumentId']]['dtGMTChange'] = $doc['dtGMTChange'];
                    $historyDocs[$doc['xDocumentId']]['sFileMimeType'] = $doc['sFileMimeType'];
                    $historyDocs[$doc['xDocumentId']]['sFilename'] = $doc['sFilename'];
                    $historyDocs[$doc['xDocumentId']]['sCID'] = $doc['sCID'];
                    $attachment_times[] = $doc['dtGMTChange'];
                }
            }

            $row = $history->toArray();

            //If only customer friendly feeds allowed then skip row if it's not public
            if ($customerfriendly && $row['fPublic'] == 0) {
                continue;
            }

            $msgheader = '';
            $histime = '';
            $ccline = '';
            $cust_toline = '';
            $cust_ccline = '';
            $cust_bccline = '';
            $body = '';
            $headerlink = '';
            $timer = '';
            $email_subject_line = '';
            $class = 'historylabel';

            if (! hs_empty($row['tEmailHeaders'])) {
                $fromemail = true;
                $headers = hs_unserialize($row['tEmailHeaders']);
                //Odd format here to provide backward compatibility with old email parser
                $from = hs_parse_email_header((isset($headers['fromaddress']) ? $headers['fromaddress'] : $headers['from']));
                //$to   		= hs_parse_email_header( (isset($headers['toaddress']) ? $headers['toaddress'] : $headers['to']) );
                $to = isset($headers['toaddress']) ? $headers['toaddress'] : $headers['to'];
                $cc = isset($headers['ccaddress']) ? $headers['ccaddress'] : $headers['cc'];
            } else {
                $fromemail = false;
            }

            //Merged request note
            $merged_line = $row['fMergedFromRequest'] ? '<div style="background-color:#fcc5b5;text-align:center;color:#f96668;font-weight:bold;padding-bottom:1px;">'.lg_request_merged.'</div>' : '';

            //timer
            if ($row['iTimerSeconds'] != 0) {
                $row['iTimerSeconds'] = $row['iTimerSeconds'] < 60 ? 60 : $row['iTimerSeconds']; // if less than a minute show a minute
                $timer = '('.time_since(0, $row['iTimerSeconds']).')';
            }

            // secondary top info
            if ($fromemail) {
                $msgheader .= '<tr><td><b>'.lg_request_from.':  '.hs_htmlspecialchars($from['personal'].' - '.$from['mailbox'].'@'.$from['host']).'</b></td></tr>';
                $msgheader .= '<tr><td>'.lg_request_to.'      : '.hs_htmlspecialchars(hs_charset_emailheader(hs_parse_email_list($to))).'</td></tr>';
                $msgheader .= ! empty($cc) ? '<tr><td>'.lg_request_cc.'      : '.hs_htmlspecialchars(hs_charset_emailheader(hs_parse_email_list($cc))).'</td></tr>' : '';
                $msgheader .= '<tr><td>'.lg_request_subject.' : '.hs_htmlspecialchars(hs_charset_emailheader($headers['subject'])).'</td></tr>';
            } elseif ($row['xPerson'] > 0) {
                $msgheader .= '<tr><td><b>'.$allStaff[$row['xPerson']]['sFname'].' '.$allStaff[$row['xPerson']]['sLname'].' '.$timer.'</td></tr>';
            } elseif ($row['xPerson'] == 0) {
                if (hs_empty($fm['sFirstName']) && hs_empty($fm['sLastName'])) {
                    $custname = lg_request_customer;
                } else {
                    $custname = $fm['sFirstName'].' '.$fm['sLastName'];
                }
                $msgheader .= '<tr><td><b>'.hs_htmlspecialchars($custname).'</b></td></tr>';
            } elseif ($row['xPerson'] == -1) {
                $msgheader .= '<tr class="'.$class.'"><td>'.hs_htmlspecialchars(lg_systemname).'</td></tr>';
            }

            //Public
            if ($row['fPublic'] == 1) {
                $public = '('.lg_request_publicrss.') ';
            } else {
                $public = '';
            }

            //Time
            if ($fm['dtGMTOpened'] == $row['dtGMTChange']) {
                $initialdate = hs_showDate($fm['dtGMTOpened']);
                $histime .= sprintf(lg_creation, $initialdate);
                $histime = $public.$histime;
            } else {
                $histime .= '<span>'.$public.hs_showDate($row['dtGMTChange']).'</span>';
            }

            //Normal update
            if (! hs_empty($row['tNote'])) {
                $body = formatNote($row['tNote'], $row['xRequestHistory'], ($row['fNoteIsHTML'] ? 'is_html' : 'html'), $fromemail);
                if (! hs_empty($row['tLog'])) {
                    $l = 1;
                    $log = hs_unserialize($row['tLog']);

                    //email subject that was sent
                    if (! hs_empty($log['sTitle'])) {
                        $email_subject_line = '<tr><td colspan="2" style="color:#cc9999;font-size:9px;">'.lg_request_emailsubject.': '.$log['sTitle'].'</td></tr>';
                    }

                    //Notified internal users
                    $ccpeople = explode(',',$log['ccstaff']);
                    if (is_array($ccpeople) && ! hs_empty($ccpeople) && ! empty($ccpeople[0])) {	//Need !empty($ccpeople[0]), not sure why!
                        $ccline .= '<tr><td colspan="2" class="cclist">'.lg_request_cc.': ';
                        foreach ($ccpeople as $ccid) {
                            $ccline .= $allStaff[$ccid]['sFname'].' '.$allStaff[$ccid]['sLname'];
                            $ccline .= ($l < count($ccpeople)) ? ', ' : '';
                            $l++;
                        }
                        $ccline .= '</td></tr>';
                    }

                    //to line, for normal public notes
                    if (! hs_empty($log['customeremail'])) {
                        $cust_toline = '<tr><td colspan="2" style="color:#cc9999;font-size:9px;">'.lg_request_emailto.': '.$log['customeremail'].'</td></tr>';
                    }

                    //to line, used in external notes
                    if (! hs_empty($log['emailtogroup'])) {
                        $cust_toline = '<tr><td colspan="2" style="color:#cc9999;font-size:9px;">'.lg_request_emailto.': '.str_replace(',', ', ', $log['emailtogroup']).'</td></tr>';
                    }

                    //customer cc's - used in public and external notes
                    if (! hs_empty($log['emailccgroup'])) {
                        $cust_ccline = '<tr><td colspan="2" style="color:#cc9999;font-size:9px;">'.lg_request_emailcc.': '.str_replace(',', ', ', $log['emailccgroup']).'</td></tr>';
                    }

                    //customer bcc's - used in public and external notes
                    if (! hs_empty($log['emailbccgroup'])) {
                        $cust_bccline = '<tr><td colspan="2" style="color:#cc9999;font-size:9px;">'.lg_request_emailbcc.': '.$log['emailbccgroup'].'</td></tr>';
                    }
                }
                // event log
            } elseif (! hs_empty($row['tLog'])) {
                $body = nl2br($row['tLog']);
            }

            if (is_array($historyDocs) && (hs_empty($row['tLog']) || ! hs_empty($row['tNote']))) {
                $body .= '<br>';
                foreach ($historyDocs as $k=>$v) {
                    if (in_array($v['sFileMimeType'], $GLOBALS['imageMimeTypes']) && $user['fHideImages'] == 0) {
                        $body .= '<br><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k, 'showfullsize' => 1]).'" target="_blank"><img src="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k]).'" alt="'.$v['sFilename'].'" align="top" border="0"></a>';
                    } else {
                        $body .= '<table border="0"><tr valign="middle"><td>'.hs_showMime($v['sFilename']).'</td><td><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file', 'from' => 0, 'id' => $k, 'showfullsize' => 1]).'">'.$v['sFilename'].'</a></td></tr></table>';
                    }
                }
            }

            //OUTPUT
            if (! empty($msgheader)) {
                $out .= $msgheader;
            }

            if (! empty($body)) {
                $out .= $merged_line.' '.$email_subject_line.' '.$cust_toline.' '.$cust_ccline.' '.$cust_bccline.' '.$ccline;
                $out .= '<tr><td>'.$body.'</td></tr>';
            }

            if (! empty($histime)) {
                $out .= '<tr><td align="right">'.$histime.'</td></tr>';
            }

            $out .= '<tr><td align="center"><hr width="70%"></td></tr>';

        }
    }

    return '<table width="100%">'.$out.'</table>';
}

/*****************************************
A TOGGLE FIELDSET
*****************************************/
function toggleField($body, $label, $id, $width = '90%', $bgcolor = '#fff', $bgbodycolor = '#EEEEEE', $default_open = false)
{
    $out = '
		<div id="'.$id.'">
			<div onclick="Effect.toggle(\''.$id.'_body\',\'blind\');$(this).toggleClassName(\'toggle-arrow-down\');" id="'.$id.'_toggle_arrow" class="datalabel hand togglefield-label toggle-arrow" style="width:'.$width.';">
				<span class="togglefield-label-inner" style="background-color:'.$bgcolor.';">'.$label.'</span>
			</div>
			<div style="display:none;width:'.$width.';" class="togglefield-body" id="'.$id.'_body">
				<div class="togglefield-body-inner" style="background-color:'.$bgbodycolor.';">'.$body.'</div>
			</div>
		</div>
	';

    if ($default_open) {
        $out .= '
			<script type="text/javascript">
				Event.observe(window, "load", function(){
					Effect.toggle(\''.$id.'_body\',\'blind\');$("'.$id.'_toggle_arrow").toggleClassName(\'toggle-arrow-down\');
				});
			</script>
		';
    }

    return $out;
}

/*****************************************
A MULTICOLUMN CHECKBOX GROUP
*****************************************/
function colCheckboxGroup($data, array $selected_array, $field, $emptymsg = '')
{
    $out = '';

    $data = (array)$data; // if null, set to []

    //Find out how long the array is
    $len = count($data);

    $collen = floor($len / 2);

    // Make sure $selected_array is actually an array.
    if (hs_empty($selected_array) or ! is_array($selected_array)) {
        $selected_array = [];
    }

    if ($len > 0) {
        $i = 0;
        $out = '<table style="" width="90%"><tr valign="top"><td width="33%">';

        foreach ($data as $val=>$name) {

            //Only create columns if we have more than 10 items
            if ($len > 10 && $i == $collen) {
                $out .= '</td><td width="33%">';
                $i = 1;
            } else {
                $i++;
            }

            $out .= '<input type="checkbox" name="'.$field.'[]" value="'.$val.'" '.(in_array($val, $selected_array) ? 'checked="checked"' : '').' /> '.$name.'<br />';
        }

        $out .= '</td></tr></table>';
    } else {
        $out .= '<p style="font-weight:bold;padding-left:15px;">'.$emptymsg.'</p>';
    }

    return $out;
}

/*****************************************
RETURN A FORMATED DATE/TIME STRING
*****************************************/
function hs_showDate($timestamp)
{
    if (is_numeric($timestamp)) {
        return strftime_win32(hs_setting('cHD_DATEFORMAT'), $timestamp);
    } else {
        return $timestamp;
    }
}

/*****************************************
RETURN A FORMATED SHORT DATE/TIME STRING
*****************************************/
function hs_showShortDate($timestamp)
{
    if (is_numeric($timestamp) and $timestamp > 0) {
        return strftime_win32(hs_setting('cHD_SHORTDATEFORMAT'), $timestamp);
    } else {
        return $timestamp;
    }
}

/*****************************************
CUSTOM FORMAT DATE/TIME STRING
*****************************************/
function hs_showCustomDate($timestamp, $format)
{
    if (is_numeric($timestamp)) {
        return strftime_win32($format, $timestamp);
    } else {
        return $timestamp;
    }
}

/**
 * @param $score
 * @return string
 */
function hs_showNpsScore($score)
{
    if (! is_numeric($score)) {
        return '-';
    }

    require_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';

    $responseType = apiGetResponseType($score, 'nps');
    $detractor = defined('lg_conditional_at_thermostat_detractor') ? lg_conditional_at_thermostat_detractor : 'detractor';
    $passive = defined('lg_conditional_at_thermostat_passive') ? lg_conditional_at_thermostat_passive : 'passive';
    $promoter = defined('lg_conditional_at_thermostat_promoter') ? lg_conditional_at_thermostat_promoter : 'promoter';

    $bgcolor = 'transparent';
    $color = '#ffffff';
    switch ($responseType) {
        case $promoter:
            $bgcolor = '#4bc800';

            break;
        case $passive:
            $bgcolor = '#ffe533';
            $color = '#676767';

            break;
        case $detractor:
            $bgcolor = '#e35557';

            break;
    }

    return '<span class="color-label" style="color: '.$color.'; background-color:'.$bgcolor.';">'.utf8_ucfirst($responseType).'</span> '.$score;
}

/**
 * @param $score
 * @return string
 */
function hs_showCsatScore($score)
{
    if (! is_numeric($score)) {
        return '-';
    }

    require_once cBASEPATH.'/helpspot/lib/api.thermostat.lib.php';

    $responseType = apiGetResponseType($score, 'csat');
    $detractor = defined('lg_conditional_at_thermostat_detractor') ? lg_conditional_at_thermostat_detractor : 'detractor';
    $promoter = defined('lg_conditional_at_thermostat_promoter') ? lg_conditional_at_thermostat_promoter : 'promoter';

    $bgcolor = 'transparent';
    $color = '#ffffff';
    switch ($responseType) {
        case $promoter:
            $bgcolor = '#4bc800';

            break;
        case $detractor:
            $bgcolor = '#e35557';

            break;
    }

    return '<span class="color-label" style="color: '.$color.'; background-color:'.$bgcolor.';">'.utf8_ucfirst($responseType).'</span> '.$score;
}

/*****************************************
WORK AROUND FOR WIN32 LACK OF FORMATS
*****************************************/
function strftime_win32($format, $ts = null)
{
    if (! $ts) {
        $ts = time();
    }

    $mapping = [
       '%C' => sprintf('%02d', date('Y', $ts) / 100),
       '%D' => '%m/%d/%y',
       '%e' => sprintf("%' 2d", date('j', $ts)),
       '%h' => '%b',
       '%n' => "\n",
       '%r' => date('h:i:s', $ts).' %p',
       '%R' => date('H:i', $ts),
       '%t' => "\t",
       '%T' => '%H:%M:%S',
       '%u' => ($w = date('w', $ts)) ? $w : 7,
   ];
    $format = str_replace(
       array_keys($mapping),
       array_values($mapping),
       $format
   );

    return utf8_encode(strftime($format, $ts));
}

/*****************************************
DISPLAY MONTH DROP DOWN
*****************************************/
function hs_ShowMonth($selected = false)
{
    $out = '';
    $selected = $selected ? $selected : date('n');

    for ($i = 1; $i <= 12; $i++) {
        $sel = selectionCheck($i, $selected);
        $out .= '<option value="'.$i.'" '.$sel.'>'.$i.'</option>';
    }

    return $out;
}

/*****************************************
DISPLAY DAY DROP DOWN
*****************************************/
function hs_ShowDay($selected = false)
{
    $out = '';
    $selected = $selected ? $selected : date('j');

    for ($i = 1; $i <= 31; $i++) {
        $sel = selectionCheck($i, $selected);
        $out .= '<option value="'.$i.'" '.$sel.'>'.$i.'</option>';
    }

    return $out;
}

/*****************************************
DISPLAY YEAR DROP DOWN
*****************************************/
function hs_ShowYear($selected = false, $years_back = 1)
{
    $out = '';
    $years_back = ($years_back < 1 ? 1 : $years_back); //must go back at least 1 year
    $selected = $selected ? $selected : date('Y');

    for ($i = date('Y', mktime(0, 0, 0, 1, 1, date('Y') - $years_back)); $i <= date('Y', mktime(0, 0, 0, 1, 1, date('Y') + 1)); $i++) {
        $sel = selectionCheck($i, $selected);
        $out .= '<option value="'.$i.'" '.$sel.'>'.$i.'</option>';
    }

    return $out;
}

/*****************************************
DISPLAY MONTH DROP DOWN THAT SETS DATE FIELDS GIVEN
*****************************************/
function hs_ShowMonthQuickDrop($from, $to, $name = 'quickmonth')
{
    $out = '';
    $current = 0;
    $start = apiFirstRequestDate();
    $end = time();

    $out = '<option value="" selected>'.lg_quicktimeselect.'</option>';

    //Today
    $v = mktime(0, 0, 0).'|'.hs_showCustomDate(mktime(0, 0, 0), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|'.mktime(23, 59, 59).'|'.hs_showCustomDate(mktime(23, 59, 59), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|date_hour';
    $out .= '<option value="'.$v.'">'.lg_quicktimeselect_today.'</option>';

    //Yesterday
    $v = mktime(0, 0, 0, date('n'), date('j') - 1).'|'.hs_showCustomDate(mktime(0, 0, 0, date('n'), date('j') - 1), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|'.mktime(23, 59, 59, date('n'), date('j') - 1).'|'.hs_showCustomDate(mktime(23, 59, 59, date('n'), date('j') - 1), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|date_hour';
    $out .= '<option value="'.$v.'">'.lg_quicktimeselect_yesterday.'</option>';

    //All time
    $v = $start.'|'.hs_showCustomDate($start, hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|'.$end.'|'.hs_showCustomDate($end, hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|date_year';
    $out .= '<option value="'.$v.'">'.lg_quicktimeselectalltime.'</option>';

    //By year
    $out .= '<optgroup label="'.lg_reports_years.'">';
    for ($year = date('Y', $end); $year >= date('Y', $start); $year--) {
        $v = mktime(0, 0, 0, 1, 1, $year).'|'.hs_showCustomDate(mktime(0, 0, 0, 1, 1, $year), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|'.mktime(23, 59, 59, 12, 31, $year).'|'.hs_showCustomDate(mktime(23, 59, 59, 12, 31, $year), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|date_month';
        $out .= '<option value="'.$v.'">'.date('Y', mktime(0, 0, 0, 6, 1, $year)).'</option>';
    }
    $out .= '</optgroup>';

    //By Month
    $out .= '<optgroup label="'.lg_reports_months.'">';
    for ($year = date('Y', $end); $year >= date('Y', $start); $year--) {
        for ($m = 12; $m >= 1; $m--) {
            if (mktime(23, 59, 59, $m, 31, $year) > $start && mktime(0, 0, 0, $m, 1, $year) < $end) {
                $dim = date('t', mktime(0, 0, 0, $m, 1, $year));
                $v = mktime(0, 0, 0, $m, 1, $year).'|'.hs_showCustomDate(mktime(0, 0, 0, $m, 1, $year), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|'.mktime(23, 59, 59, $m, $dim, $year).'|'.hs_showCustomDate(mktime(23, 59, 59, $m, $dim, $year), hs_setting('cHD_POPUPCALSHORTDATEFORMAT')).'|date_day';
                $out .= '<option value="'.$v.'">'.strftime_win32(str_replace(' %e', '', hs_setting('cHD_SHORTDATEFORMAT')), mktime(0, 0, 0, $m, 1, $year)).'</option>';
            }
        }
    }
    $out .= '</optgroup>';

    return '<select name="'.$name.'" id="'.$name.'" onChange="qtSet(\''.$name.'\',\''.$from.'\',\''.$to.'\');">'.$out.'</select>';
}

/*****************************************
DISPLAY BIZ HOUR SELECT LIST
*****************************************/
function hs_ShowBizHours($selected = false)
{
    $out = '';
    $selected = $selected ? $selected : date('Y');

    for ($i = 0; $i <= 23.75; $i = $i + .25) {
        $sel = selectionCheck($i, $selected);

        $out .= '<option value="'.$i.'" '.$sel.'>'.hs_ShowBizHoursFormat($i).'</option>';
    }

    //$out .= '<option value="0">------</option>';
    $out .= '<option value="24" '.selectionCheck(24, $selected).'>11:59 PM - (23:59)</option>';

    return $out;
}

/*****************************************
DISPLAY BIZ HOUR FORMAT
*****************************************/
function hs_ShowBizHoursFormat($i)
{
    $hour = floor($i);
    $min = explode('.', $i);
    $min = (isset($min[1]) ? round(('.'.$min[1] * 60) * 100, 2) : 0);

    //before each new hour block put a line
    //if($min == 0) $out .= '<option value="0">------</option>';

    //Format time
    if ($hour < 12) {
        $show = $hour.':'.str_pad($min, 2, '0');
    } else {
        $show = ($hour > 12 ? $hour - 12 : 12).':'.str_pad($min, 2, '0').' PM - ';
        $show = $show.'('.$hour.':'.str_pad($min, 2, '0').')';
    }

    return $show;
}

function renderSelectMultiPermission($prefix, $rs, $fieldname, $selected='', $onchange='') {
	$out = '<div class="select-multiple '.$prefix.'-select-multiple">';
    $subscribe = false;
	$i=0;
	while($p = $rs->FetchRow()){
		$out .= '<a href="" onclick="ms_select(\''.$prefix.'-select-multiple-'.$p['xGroup'].'\',\''.$p['xGroup'].'\',\''.$fieldname.'\');'.$onchange.';return false;" id="'.$prefix.'-select-multiple-'.$p['xGroup'].'" class="select-multiple-row '.(($i % 2 != 0) ? 'select-multiple-row-alt' : '').' '.(in_array($p['xGroup'],$selected) ? 'select-multiple-selected' : '').'"><div class="icon"></div><span class="name">'.$p['sGroup'].'</span></a>';
		$out .= (in_array($p['xGroup'],$selected) ? '<input type="hidden" id="'.$prefix.'-select-multiple-'.$p['xGroup'].'-hidden" name="'.$fieldname.'[]" value="'.$p['xGroup'].'" />' : '');
		$i++;
	}
	$out .= '</div>';
	$out .= '<div class="select-multiple-buttons">
				<button onclick="ms_select_all(\''.$prefix.'\');'.$onchange.';return false;" class="btn inline-action">'.lg_addallstaff.'</button>
				<!--<a href="" onclick="ms_expand(\''.$prefix.'\');return false;" class="btn inline-action"><img src="'.static_url().'/static/img5/expand-solid.svg" /></a>-->
				'.($subscribe ? '<div class="select-multiple-subscribe"><input type="checkbox" name="subscribe_all_ccstaff" value="1" /> '.lg_subscribeall.'</div>' : '').'
			</div>';

    return $out;
}

/*****************************************
SELECT MULTIPLE
*****************************************/
function renderSelectMulti($prefix, $people, $selected, $onchange = '', $fieldname = 'sPersonList', $subscribe = false)
{
    global $user;
    $out = '<div style="display:flex;flex-direction:column;">';
    $out .= '<div class="select-multiple '.$prefix.'-select-multiple">';

    if (hs_empty($selected) or ! is_array($selected)) {
        $selected = [];
    }

    $i = 0;
    foreach ($people as $k=>$p) {
        //if we're offering subscribe don't include this user
        if ($subscribe && $p['xPerson'] == $user['xPerson']) {
            continue;
        }

        $out .= '<a href="" onclick="ms_select(\''.$prefix.'-select-multiple-'.$p['xPerson'].'\',\''.$p['xPerson'].'\',\''.$fieldname.'\');'.$onchange.';return false;" id="'.$prefix.'-select-multiple-'.$p['xPerson'].'" class="select-multiple-row '.(($i % 2 != 0) ? 'select-multiple-row-alt' : '').' '.(in_array($p['xPerson'], $selected) ? 'select-multiple-selected' : '').'"><span class="name">'.$p['sFname'].' '.$p['sLname'].'</span></a>';
        $out .= (in_array($p['xPerson'], $selected) ? '<input type="hidden" id="'.$prefix.'-select-multiple-'.$p['xPerson'].'-hidden" name="'.$fieldname.'[]" value="'.$p['xPerson'].'" />' : '');
        $i++;
    }

    $out .= '</div>';
    $out .= '<div class="select-multiple-buttons">
				<button onclick="ms_select_all(\''.$prefix.'\');'.$onchange.';return false;" class="btn inline-action">'.lg_addallstaff.'</button>
				'.($subscribe ? '<div class="select-multiple-subscribe"><input type="checkbox" name="subscribe_all_ccstaff" value="1" /> '.lg_subscribeall.'</div>' : '').'
			</div>
            </div>';
    return $out;
}

/*****************************************
YES/NO BUTTON GROUP
*****************************************/
function renderYesNo($field, $value, $yestext = '', $notext = '', $width = 90, $yesval = 1, $noval = 0)
{
    return '
		<div style="display:flex;justify-content: flex-start;" class="yes_no_group">
			<a href="" onclick="yes_no_btn(\'yes\',\''.$field.'\',\''.$yesval.'\');return false;" style="width:'.$width.'px;" id="'.$field.'-yes" class="btn inline-action '.($value == $yesval ? 'btn-selected' : 'btn-yes-no').'">'.$yestext.'</a>
			<a href="" onclick="yes_no_btn(\'no\',\''.$field.'\',\''.$noval.'\');return false;" style="width:'.$width.'px;" id="'.$field.'-no" class="btn inline-action '.($value != $yesval ? 'btn-selected' : 'btn-yes-no').'">'.$notext.'</a>
		</div>
		<input type="hidden" name="'.$field.'" id="'.$field.'" value="'.$value.'">
	';
}

/*****************************************
TAG UI
*****************************************/
function tagUI($tags, $title, $field, $ajax = false, $forRequests = false)
{
    $tagtable = '';

    $tagtable .= '<div id="tagWrap">';
    if (is_array($tags) && ! empty($tags)) {
        foreach ($tags as $k=>$tag) {
            $tagtable .= renderTag($k, $tag, $field, true, false);
        }
    } else {
        $tagtable .= '<span id="rt-notags">'.lg_tags_none.'</span>';
    }
    $tagtable .= '</div>';

    $tagtable .= '
		<div class="nice-line"></div>
		<label class="datalabel" for="reportingTagsInput">'.lg_tags_label.'</label>
		<input tabindex="105" name="tagInput" id="tagInput" type="text" size="40" style="width:360px;">
		<button type="button" id="tagButton" class="btn inline-action" style="margin-left: 6px;" onClick="addTag();return false;">'.lg_tags_add.'</button>';

    if (! $ajax) {
        $out = displayContentBoxTop($title, '', false, '100%', '', 'box_body_solid');
    }
    $out .= $tagtable;
    if (! $ajax) {
        $out .= displayContentBoxBottom();
    }

    $out .= '
		<script type="text/javascript">
			//Prevent an enter in the tag box from submitting the form
			$jq(document).ready(function(){
                //first form is the logout form so find the second
				$jq(document.forms[1]).find("#tagInput").keypress(function(e){
					if(e.which == 13){
						addTag();
						e.preventDefault();
					}
				});
			});

			function addTag(){
				if($F("tagInput") == "") return false;

				var randid = Math.floor(Math.random()*99999);
				var temp = new Template(\''.renderTag('#{id}', '#{tag}', $field, false, false).'&nbsp;\');
				var HTML = temp.evaluate({id:randid,tag:$F("tagInput")});

				//If no tags yet empty placeholder text
				if($("rt-notags")) $("rt-notags").remove();

				$("tagWrap").insert({bottom:HTML});

				clickTag("tag_"+randid);

				$("tagInput").value = "";
			}

			function deleteTag(id){
				hs_confirm("'.lg_tags_confirmdel.'",function(){$(id).remove();Tips.hideAll();});
			}

			function editTag(id){
				Tips.hideAll();

				var input = $$(".taghook_"+id+" input")[0];
				$(id+"_text").update(input.getValue());
				$(id+"_hidden").setValue(input.getValue());

				'.($forRequests ? '$jq.post("'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'ajax_gateway', 'action' => 'update_reporting_tag']).'",{xReportingTag:id,new_value:input.getValue()});' : '').'

				var tselect = $$(".taghook_"+id+" select")[0];
				var selectedVal = $F(tselect);
				if(selectedVal != ""){
					//Loop over all tags and find one with matching value
					$$("div[id^=\"tag_\"]").each(function(tag){
						if($(tag.id + "_hidden").getValue() == selectedVal){
							$(tag).insert({after:$(id)});
						}
					});
				}
			}

			function clickTag(id){
				var edit = new Template(\'<div style="padding:5px;" id="tagedit_box"><input type="text" class="noshadow" name="" id="" value="#{value}" /><br /><select></select><div class="button-bar space"><button class="btn inline-action accent" onclick="editTag(\\\'#{id}\\\');">'.lg_tags_save.'</button><button class="btn inline-action altbtn" onclick="deleteTag(\\\'#{id}\\\');">'.lg_tags_delete.'</button></div></div>\');

				new Tip(id, edit.evaluate({id:id,value:decodeURIComponent(encodeURIComponent($F(id+"_hidden")))}), {
						title: "",
						className: "hstinytipfat autoclose taghook_"+id,
						border: 0,
						radius: 0,
						showOn: "click",
                        stem: "bottomMiddle",
						hideOn: false,
						hideOthers: true,
						width: "250px",
						hook: { target: "topMiddle", tip: "bottomMiddle" }
					});

				//Populate reorder list
				$(id).observe("prototip:shown", function() {
					var sel = $$(".taghook_"+id+" select")[0];

					sel.length = 0;

					//Find all tags
					j=1;
					sel.options[0] = new Option("'.lg_tags_reorder.'","");
					$$("input[id^=\"tag_\"]").each(function(elem){
						sel.options[j] = new Option(decodeURIComponent(encodeURIComponent($F(elem))),$F(elem));
						j++;
					});

				});
			}

			document.observe("dom:loaded", function(){
				$$("div[id^=\"tag_\"]").each(function(elem){
					clickTag(elem.id);
				});
			});

		</script>
	';

    return $out;
}

/*****************************************
PERM SELECTION UI
*****************************************/
function permSelectUI($selected = 2, $permgroup = 1, $people = [], $permGroups = [])
{
    global $user;

    $out = '';

    $staff = apiGetAllUsers();
    $perms = apiPermsGetAll();
    $staffincats = apiGetStaffInUserCats($user['xPerson']);

    if (perm('fLimitedToAssignedCats')) {
        //Filter out staff who this user cannot see
        while ($p = $staff->FetchRow()) {
            if (in_array($p['xPerson'], $staffincats)) {
                $allStaff[] = $p;
            }
        }
    } else {
        $allStaff = rsToArray($staff, 'xPerson', false);
        //Remove this user, include automatically
        // unset($allStaff[$user['xPerson']]);
    }

    $out .= '<div style="display:flex;flex-direction:column;">';
    $out .= '<div style="text-align: right;"><select name="fType" id="fType" style="width:100%">';

    if (isAdmin()) {
        $out .= '<option value="1" '.selectionCheck(1, $selected).'>'.lg_everyone.'</option>';
    }

    $out .= '<option value="2" '.selectionCheck(2, $selected).'>'.lg_onlyme.'</option>';

    if (isAdmin()) {
        $out .= '<option value="3" '.selectionCheck(3, $selected).'>'.lg_permgroup.'</option>';
    } else {
        $perm = apiPermGetById($user['fUserType']);
        $out .= '<option value="3" '.selectionCheck(3, $selected).'>'.lg_mypermgroup.': '.$perm['sGroup'].'</option>';
    }

    $out .= '<option value="4" '.selectionCheck(4, $selected).'>'.lg_selectedppl.'</option>';

    $out .= '</select></div>';

    // Render the extra selectors needed
    if (isAdmin()) {
        $out .= '
            <div id="ps-group-pick" style="display:none;margin-top:10px;">
                '.renderSelectMultiPermission('groupperm', $perms, 'fPermissionGroup', $permGroups).'
            </div>';
    } else {
        $out .= '<input type="hidden" name="fPermissionGroup[]" id="fPermissionGroup" value="'.$perm['xGroup'].'" />';
    }

    $out .= '
            <div id="ps-person-pick" style="display:none;margin-top:10px;">
                '.renderSelectMulti('peopleperm', $allStaff, $people).'
            </div>
        </div>
		<script type="text/javascript">
        function showSelectors(){
            if($jq("#fType").val() == 3){
                $jq("#ps-group-pick").show();
            }

            if($jq("#fType").val() != 3){
                $jq("#ps-group-pick").hide();
            }

            if($jq("#fType").val() == 4){
                $jq("#ps-person-pick").show();
            }

            if($jq("#fType").val() != 4){
                $jq("#ps-person-pick").hide();
            }
        }

        $jq("#fType").bind("change", showSelectors);

		$jq(document).ready(function(){
            showSelectors();
		});
		</script>
	';

    return $out;
}

/*****************************************
REPORTING TAG HTML
*****************************************/
function renderTag($id, $tag, $field, $active = false)
{
    return '<div id="tag_'.$id.'" class="rt '.($active ? 'active' : '').'"><span class="rt-x"></span><span class="rt-btn" id="tag_'.$id.'_text">'.$tag.'</span><input type="hidden" id="tag_'.$id.'_hidden" name="'.$field.'" value="'.hs_htmlspecialchars($tag).'"></div>';
}

/*****************************************
BUILD CATEGORY SELECT LIST
*****************************************/
function categorySelectOptions(&$catsList, $selected, $optionlist = '')
{
    global $user;
    $catsSelect = '';

    if (hs_rscheck($catsList)) {
        $catsList->Move(0);
        $current_group = '';
        if (! empty($optionlist)) {
            $catsSelect = $optionlist;
        } else {
            $catsSelect = '<option value="0" '.selectionCheck(0, $selected).'></option>';
        }
        if (hs_rscheck($catsList)) {
            while ($c = $catsList->FetchRow()) {
                //If setting to assign in cat only and user is a guest/L2 dont show unless in cat
                $catpeople = hs_unserialize($c['sPersonList']);
                if (perm('fLimitedToAssignedCats') && ! perm('fCanTransferRequests') && ! in_array($user['xPerson'], $catpeople)) {
                    continue;
                }
                if (! hs_empty($c['sCategoryGroup']) && $current_group != $c['sCategoryGroup']) {
                    if (! empty($current_group)) {
                        $catsSelect .= '</optgroup>';
                    } //close if prev had been in another group
                    $current_group = $c['sCategoryGroup'];
                    $catsSelect .= '<optgroup label="'.hs_htmlspecialchars($c['sCategoryGroup']).'">';
                }
                $catsSelect .= '<option value="'.$c['xCategory'].'" '.selectionCheck($c['xCategory'], $selected).'>'.$c['sCategory'].'</option>';
            }

            if (! empty($current_group)) {
                $catsSelect .= '</optgroup>';
            }
        }
    }

    return $catsSelect;
}

/*****************************************
PLACEHOLDER TAG ARRAY
*****************************************/
function placeholderTags($general_only = false, $for_subject = false)
{
    $defaults = [];

    if (! $general_only) {
        $defaults = [
            '{{ $requestid }}'=>lg_placeholderspopup_reqid,
            '{{ $accesskey }}'=>lg_placeholderspopup_acckey,
            '{{ $replyabove }}'=>lg_placeholderspopup_replyabove,
            '{{ $portal_email }}'=>lg_placeholderspopup_portalemail,
            '{{ $portal_password }}'=>lg_placeholderspopup_portalpassword,
            '{{ $customerfirst }}'=>lg_placeholderspopup_custfirst,
            '{{ $customerlast }}'=>lg_placeholderspopup_custlast,
            '{{ $customerid }}'=>lg_placeholderspopup_custid,
            '{{ $customeremail }}'=>lg_placeholderspopup_custemail,
            '{{ $customerphone }}'=>lg_placeholderspopup_custphone,
            '{{ $status }}'=>lg_placeholderspopup_status,
            '{{ $category }}'=>lg_placeholderspopup_category,
            '{{ $urgent }}'=>lg_placeholderspopup_urgent,
            '{{ $open_closed }}'=>lg_placeholderspopup_openclosed,
            '{{ $date_opened }}'=>lg_placeholderspopup_dateopened,
            '{{ $date_now }}'=>lg_placeholderspopup_datenow,
            '{{ $assigned_first }}'=>lg_placeholderspopup_assignedfirst,
            '{{ $assigned_last }}'=>lg_placeholderspopup_assignedlast,
            '{{ $assigned_email }}'=>lg_placeholderspopup_assignedemail,
            '{{ $assigned_phone }}'=>lg_placeholderspopup_assignedphone,
            '{{ $logged_in_first }}'=>lg_placeholderspopup_loggedinfirst,
            '{{ $logged_in_last }}'=>lg_placeholderspopup_loggedinlast,
            '{{ $logged_in_email }}'=>lg_placeholderspopup_loggedinemail,
            '{{ $logged_in_phone }}'=>lg_placeholderspopup_loggedinphone,
            '{{ $subject }}'=>lg_placeholderspopup_origsubject,
            '{{ $mobilelink }}'=>lg_placeholderspopup_mobilelink,
            ];

        //Don't allow init request in subject lines
        if (! $for_subject) {
            $defaults['{{ $initialrequest }}'] = lg_placeholderspopup_initialrequest;
        }

        if (is_array($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$v) {
                $defaults['{{ $custom'.$v['fieldID'].' }}'] = lg_placeholderspopup_customfield.': '.$v['fieldName'];
            }
        }
    }

    //General values
    $defaults['{{ $orgname }}'] = lg_placeholderspopup_orgname;
    $defaults['{{ $helpdeskurl }}'] = lg_placeholderspopup_helpdeskurl;
    $defaults['{{ $requestformurl }}'] = lg_placeholderspopup_reqformurl;
    $defaults['{{ $requestcheckurl }}'] = lg_placeholderspopup_reqcheckurl;
    $defaults['{{ $knowledgebookurl }}'] = lg_placeholderspopup_kburl;
    $defaults['{{ $mobilelink }}'] = lg_placeholderspopup_mobilelink;

    //Multiportal values
    $portals = apiGetAllPortals(0);
    if (hs_rscheck($portals)) {
        while ($p = $portals->FetchRow()) {
            $defaults['{{ $portal'.$p['xPortal'].'_orgname }}'] = $p['sportalName'].': '.lg_placeholderspopup_orgname;
            $defaults['{{ $portal'.$p['xPortal'].'_helpdeskurl }}'] = $p['sPortalName'].': '.lg_placeholderspopup_helpdeskurl;
            $defaults['{{ $portal'.$p['xPortal'].'_requestformurl }}'] = $p['sPortalName'].': '.lg_placeholderspopup_reqformurl;
            $defaults['{{ $portal'.$p['xPortal'].'_requestcheckurl }}'] = $p['sPortalName'].': '.lg_placeholderspopup_reqcheckurl;
            $defaults['{{ $portal'.$p['xPortal'].'_knowledgebookurl }}'] = $p['sPortalName'].': '.lg_placeholderspopup_kburl;
        }
    }

    return $defaults;
}

/*****************************************
TAG DROP DOWN
*****************************************/
function tagDrop($field, $custom_tags = [], $width = '200px', $use_standard_tags = true, $only_general_tags = false)
{
    if ($use_standard_tags) {
        $tags = placeholderTags($only_general_tags, (substr($field, -7) == 'subject' ? true : false));
    } else {
        $tags = [];
    }

    $out = '<select id="'.$field.'_tag_select" onchange="insertAtCursor($(\''.$field.'\'), $F(\''.$field.'_tag_select\'));$(\''.$field.'_tag_select\').selectedIndex=0;" style="width:'.$width.'">';
    $out .= '<option value="">'.(empty($custom_tags) ? lg_insertplaceholder : lg_inserttemplatetag).'</option>';
    $out .= '<option value="">-------------------</option>';

    if (! empty($custom_tags)) {
        foreach ($custom_tags as $k=>$v) {
            $out .= '<option value="'.$k.'">'.$v.'</option>';
        }

        $out .= '<option value="">-------------------</option>';
    }

    foreach ($tags as $k=>$v) {
        $out .= '<option value="'.$k.'">'.$v.'</option>';
    }
    $out .= '</select>';

    return $out;
}

/*****************************************
LINK TO USERSCAPE STORE
*****************************************/
function createStoreLink($renew = false)
{
    return $link = 'https://store.helpspot.com';

    // defunkt
    $link = 'https://www.helpspot.com/customers/index.php/purchase/order/'.md5(hs_setting('cHD_CUSTOMER_ID').'232akaa$%a!T');
    if ($renew) {
        $link = $link.'/renew';
    }

    return $link;
}

/*****************************************
DISPLAY PAGE NAME
*****************************************/
function displayPageName($name)
{
    return 	'<div class="pagename">'.$name.'</div>';
}

/*****************************************
DISPLAY FEEDBACK BOX
*****************************************/
function displayFeedbackBox($fb)
{
    return displaySystemBox(hs_htmlspecialchars($fb) ,'hdfeedbackbox');
}

/********************************************
ERROR BOX
*********************************************/
function errorBox($string)
{
    return displaySystemBox(hs_htmlspecialchars($string) ,'hderrorbox');
}

/*****************************************
DISPLAY SYSTEM BOX
*****************************************/
function displaySystemBox($fb, $class = 'hdsystembox')
{
    $out = '<div class="hsnotificationbox '.$class.'">
			 '.$fb.'
            </div>';

    return $out;
}

/********************************************
DISPLAY THE REQUEST PAGE PERSON STATUS
*********************************************/
function renderRequestPagePersonStatus($reqid, $xperson)
{
    $out = '';
    $ppl_list = [];

    $person_status = new person_status();
    $people = $person_status->get_request_page($reqid, $xperson);

    $rscount = count($people);

    if ($rscount > 0) {
        foreach ($people as $k=>$person) {

            $avatar = new HS\Avatar\Avatar();

            if ($person['fType'] == 1) {
                $ppl_list[] = $person['xPerson'];
                $class = '';
            } else {
                $ppl_list[] = $person['xPerson'].'e';
                $class = 'person-status-being-edited';
            }

            $out .= '
            <div style="display:flex;padding:10px 20px;align-items: center;" class="person-status-info '.$class.'">
                <div style="width:40px;margin-right:10px;">'.$avatar->xPerson($person['xPerson'])->size(40)->html().'</div>
                <div style="line-height: 18px;align-self: center;display:flex;justify-content:space-between;flex-grow: 1;align-items: center;flex-wrap:wrap;">
                    <span>'.$person['sFname'].' '.$person['sLname'].'</span>
                    <span>'.$person['sDetails'].'</span>
                </div>
            </div>';
        }
        sort($ppl_list);

        //Return info if something has changed or else return empty
        if ($_GET['ppl_list'] == implode(',', $ppl_list)) {
            $out = '';
        } else {
            $out .= '<div id="person_status_user_list" style="display:none;">'.implode(',', $ppl_list).'</div>';
        }
    } elseif ($rscount == 0) {
        //No people, send back empty div
        $out = '<div></div>';
    }

    return $out;
}

/********************************************
DISPLAY TIME TRACKER TIME TABLE
*********************************************/
function renderTimeTrackerTable($rows, $highlight = 0, $showdelete = true, $width = '100%')
{
    $out = '';
    $totaltime = 0;

    $people = apiGetAllUsersComplete();

    $out .= '<div style="width:'.$width.';padding:20px;">';
    $out .= displayContentBoxTop(lg_request_timeevents, '', '', '100%', 'box_body_tight_top', '');

    if ($rows) {
        if (hs_rscheck($rows)) {
            while ($row = $rows->FetchRow()) {
                $avatar = new HS\Avatar\Avatar();
                $photo = $avatar->name($people[$row['xPerson']]['sFname'].' '.$people[$row['xPerson']]['sLname'])->size(40)->html();

                $out .= '
				<div class="note-stream-item note-stream-timestamp">
					<div class="note-stream-item-sidebar">
						<div class="note-stream-item-icon user-icon-wrap">
							'.$photo.'
						</div>
					</div>
					<div class="note-stream-item-body">
						<div class="note-stream-item-header" style="margin-bottom: 0;">
							<div class="note-stream-item-name">
								'.hs_htmlspecialchars($people[$row['xPerson']]['fullname']).'
							</div>
							<div style="display: flex;align-items: center;">
								'.($showdelete ? '
								<div style="margin-right:5px;">
									<span class="cancel" onClick="deleteTime('.$row['xTimeId'].');">'.lg_request_timetrackerdel.'</span>
								</div>' : '').'
								<span class="note-stream-item-time">'.parseSecondsToTime($row['iSeconds']).'</span>
							</div>
						</div>
						<div class="note-stream-item-text">
							'.hs_htmlspecialchars($row['tDescription']).'

							<div class="note-stream-item-text-meta">
								<strong>'.lg_request_timeevents_for.':</strong>
								<span class="highlight">'.hs_showShortDate($row['dtGMTDate']).'</span>
								&mdash;
								<strong>'.lg_request_timeevents_entered.':</strong>
								<span>'.hs_showDate($row['dtGMTDateAdded']).'</span>
								'.($row['fBillable'] ? '&mdash; <span class="highlight">'.lg_request_billable.'</span>' : '').'
							</div>
						</div>
					</div>
				</div>';
            }
        }
    }

    $out .= displayContentBoxBottom();
    $out .= '</div>';

    return $out;
}

/*****************************************
ADDRESS BOOK CONTACT LIST
*****************************************/
function addressBookList($ppl, $type = 'internal')
{
    $out = '';
    $header = '';

    if (hs_rscheck($ppl)) {
        $atoz = range('A', 'Z');

        while ($person = $ppl->FetchRow()) {
            $last_letter = utf8_ucwords(utf8_substr($person['sLastName'], 0, 1));

            //Output new header if we need to
            if (empty($header) && $person['fHighlight'] == 1) {
                $out .= '<h3 class="ab-list-header">'.lg_addressbook_highlighted.'</h3>';
                $header = 'highlight';
            } elseif ($person['fHighlight'] != 1 && $header != $last_letter && in_array($last_letter, $atoz)) {
                $out .= '<h3 class="ab-list-header" id="ab-list-header-'.$last_letter.'">'.utf8_ucwords($last_letter).'</h3>';
                $header = $last_letter;
            }

            //Person
            $out .= '
				<div class="ab-list-contact">
					<div class="ab-list-options">
						<a href="javascript:$(\'addto_email\').value=\''.hs_htmlspecialchars($person['sEmail']).'\';add_email(\'to\');addressBookDeActivateLink('.$person['xContact'].',\'to\');" id="ab-link-to-'.$person['xContact'].'" class="ab-link-to" style="display:none;">TO</a> |
						<a href="javascript:$(\'addcc_email\').value=\''.hs_htmlspecialchars($person['sEmail']).'\';add_email(\'cc\');addressBookDeActivateLink('.$person['xContact'].',\'cc\');" id="ab-link-cc-'.$person['xContact'].'">CC</a> |
						<a href="javascript:$(\'addbcc_email\').value=\''.hs_htmlspecialchars($person['sEmail']).'\';add_email(\'bcc\');addressBookDeActivateLink('.$person['xContact'].',\'bcc\');" id="ab-link-bcc-'.$person['xContact'].'">BCC</a> ';

            if ($type == 'internal') {
                $out .= '| <a href="#" onclick="if(confirm(\''.hs_jshtmlentities(lg_addressbook_confirmdelete).'\')){ return addressBookDeleteContact('.$person['xContact'].'); }" class="ab-list-delete">X</a>';
            }

            $out .= '
					</div>
					<span class="ab-list-person">'.hs_htmlspecialchars($person['sLastName']).', '.hs_htmlspecialchars($person['sFirstName']).'</span><br />
					<span class="ab-list-title">'.(! hs_empty($person['sTitle']) ? '('.hs_htmlspecialchars($person['sTitle']).')' : '').'</span> '.hs_htmlspecialchars($person['sDescription']).'
					<span class="ab-list-email" style="display:none;">'.hs_htmlspecialchars($person['sEmail']).'###'.$person['xContact'].'</span>
				</div>
			';
        }
    }

    return $out;
}

/*****************************************
DISPLAY A SECONDARY SUBMIT BUTTON
*****************************************/
function save_as_button($linktext, $body_text, $hidden_field, $set_name_from_field, $width = '280', $target = 'bottomMiddle', $secondary_text = '', $submit_action = '')
{
    $submit_action = (! empty($submit_action) ? $submit_action : '$(\'submit\').click();');
    $out = '<a href="" onclick="return false;" id="secondary_formbutton" class="btn">'.$linktext.'</a>';

    $out .= '
	<!-- secondary button details -->
	<div style="display:none;" id="secondary_formbutton_content">
		'.$body_text.'<br />
		<input type="text" id="save_as_name" name="save_as_name" size="20" style="width: 70%;box-shadow:none;" value="" />
		<div class="tiptext">'.$secondary_text.'</div>
		<button type="button" name="secondary_formbutton_saveas_button" class="btn inline-action" onclick="$(\''.$hidden_field.'\').value = $F(\'save_as_name\');'.$submit_action.'">'.$linktext.'</button>
	</div>
	<input type="hidden" id="'.$hidden_field.'" name="'.$hidden_field.'" value="" />

	<script type="text/javascript" language="javascript">
		new Tip("secondary_formbutton", $("secondary_formbutton_content"), {
				  title: false,
                  className: "hstinytipfat",
                  stem: "bottomMiddle",
				  border: 0,
				  radius: 0,
				  showOn: "click",
				  hideOn: "click",
				  hideOthers: true,
				  width: '.$width.',
				  hook: { target: "topMiddle", tip: "'.$target.'" }
				});

		$("secondary_formbutton").observe("prototip:shown", function() {
		  '.(! empty($set_name_from_field) ? '$("save_as_name").value=$F("'.$set_name_from_field.'");' : '').'
		  $("save_as_name").focus();
		  //$("submit").disable();
		});

		$("secondary_formbutton").observe("prototip:hidden", function() {
		  //$("submit").enable();
		});
	</script>
	';

    return $out;
}

/********************************************
OUTPUT A SPACER IMAGE
*********************************************/
function hs_spacerimg($w = 1, $h = 1)
{
    return '<img src="'.static_url().'/static/img5/space.gif" width="'.$w.'" height="'.$h.'" alt="">';
}

/********************************************
FORMAT STREAM NOTES
*********************************************/
function formatStreamNote($string, $type)
{
    //Prevent striping HTML tags from leaving lots of blank space. This still doesn't fix breaks inside a string of html only at end of tag
    if (hs_setting('cHD_STRIPHTML') == 1) {
        $string = preg_replace("/([a-zA-Z1-9]+)>{1}\s*\r\n/", '$1>', $string);
    }

    //Try and leave breaks in text which strip tags can sometimes remove
    $string = str_replace(['<br />', '<br>'], ' ', $string);

    //Clean up string
    $clean = strip_tags(trim($string));

    //Strip any UTF8 control characters which are mixed into the string. For now only do this on non-utf8 charsets
    //if(helpspot_charset != 'UTF-8') $clean = preg_replace( '/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/', '', $clean);

    //Cut string down to 140 characters
    $out = utf8_substr($clean, 0, hs_setting('cHD_STREAM_VIEW_CHARS'));
    if (utf8_strlen($clean) > hs_setting('cHD_STREAM_VIEW_CHARS')) {
        $out = trim($out).'...';
    }

    //Encode
    //$out = hs_htmlspecialchars($out);

    return $out;
}

/********************************************
FORMAT REQUEST NOTES
*********************************************/
function formatNote($string, $hisid, $type, $isfromemail = false, $tokenargs = [])
{
    global $user;

    //Prevent striping HTML tags from leaving lots of blank space. This still doesn't fix breaks inside a string of html only at end of tag
    if (hs_setting('cHD_STRIPHTML') == 1) {
        $string = preg_replace("/([a-zA-Z1-9]+)>{1}\s*\r\n/", '$1>', $string);
    }

    if ($type == 'html') {
        //used to format a text string to HTML
        //not for strings which were originally HTML!! Sorry that's wierd I know.
        $out = hs_strip_tags($string);
        $out = hs_htmlspecialchars($out);
    } else {
        $out = $string;
    }

    //Strip any UTF8 control characters which are mixed into the string. For now only do this on non-utf8 charsets
    //if(helpspot_charset != 'UTF-8') $out = preg_replace( '/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/', '', $out);

    // if html and was from an email then format email-note
    $hisblock = 1;
    $emailblocklines = [];
    if ($type == 'html' && $isfromemail == true) {
        //Figure out if it's from Outlook. If so handle special
        $mslookpos = preg_match(lg_outlookseparator, $out, $match);
        if ($mslookpos) {
            $sep_position = utf8_strpos($out, $match[0]);
            $history = utf8_substr($out, $sep_position);
            //Do not add a break in this expand line
            $expand = '<a href="#" title="hidden" id="hidden_history_block_'.$hisid.'_'.rand(1, 999999).'" onclick="$(this.id).next().toggle();return false;">'.lg_hiddenemail.'</a><div style="display:none;padding:0px;margin:0px;padding-top:2px;">%s</div>';
            $expand = sprintf($expand, $history);
            $out = str_replace($history, $expand, $out);
        } else {
            // Find email history blocks
            $emailblock = explode("\n", $out);
            $inblock = false;
            foreach ($emailblock as $line) {
                if (! empty($line)) {
                    if (utf8_substr(trim($line), 0, 4) == '&gt;') {
                        $emailblocklines[$hisblock][] = $line;
                        $inblock = true;
                    } else {
                        if ($inblock == true) {
                            $inblock = false;
                            $hisblock++;
                        }
                    }
                }
            }

            foreach ($emailblocklines as $k=>$v) {
                $temp = implode("\n", $v);

                // HTML history code, Do not add a break in this expand line
                $expand = '<a href="#" title="hidden" id="hidden_history_block_'.$hisid.'_'.rand(1, 999999).'" onclick="$(this.id).next().toggle();return false;">'.lg_hiddenemail.'</a><div style="display:none;padding:0px;margin:0px;padding-top:2px;">%s</div>';
                $expand = sprintf($expand, $temp);
                $out = str_replace($temp, $expand, $out);
            }
        }

        $out = trim($out);
    } elseif ($type == 'is_html' && $isfromemail == true) {
        //Figure out if it's from Outlook. If so handle special
        if (preg_match('/<div.*OutlookMessageHeader.*>/', $out, $matches, PREG_OFFSET_CAPTURE)) {
            $new = utf8_substr($out, 0, $matches[0][1]);
            $out = $new.'<blockquote>'.utf8_substr($out, $matches[0][1]).'</blockquote>';

            //Remove default blue color that outlook uses in replies
            $out = str_replace('#0000ff', '#000', $out);
        }

        //Find top line of quote email in Outlook 2007
        if (preg_match('/<div>(\r\n|\n|\r)<div style=\"border:none;border-top:solid\ \#B5C4DF\ 1.0pt;padding:3.0pt/m', $out, $matches, PREG_OFFSET_CAPTURE)) {
            $new = utf8_substr($out, 0, $matches[0][1]);
            $out = $new.'<blockquote>'.utf8_substr($out, $matches[0][1]).'</blockquote>';

            //Remove default blue color that outlook uses in replies
            $out = str_replace('#1F497D', '#000', $out);
        }

        $out = preg_replace('/<blockquote/', '<br><a href="#" title="hidden" id="hidden_history_block_'.rand(1, 999999).'" onclick="$(this.id).next().toggle();return false;">'.lg_hiddenemail.'</a><blockquote style="display:none;"', $out, 1);
    }

    if ($type == 'is_html') {
        //probably need to do token replacement here
        $out = parseShortcuts($out, (! IN_PORTAL ? false : true), true);
    }

    // do htmly things if in html format
    if ($type == 'html') {
        $out = tokenReplace($out, $tokenargs);
        $out = parseShortcuts($out);
        $out = linkUrls($out);
        $out = nl2br(trim($out));
        $out = makeBold($out);
    }

    if ($type == 'privmail') {
        if (! hs_setting('cHD_HTMLEMAILS')) {
            $out = hs_strip_tags($out);
        }
        $out = tokenReplace($out, $tokenargs);
        $out = parseShortcuts($out, false);
    }

    if ($type == 'pubmail') {
        if (! hs_setting('cHD_HTMLEMAILS')) {
            $out = hs_strip_tags($out);
        }
        $out = tokenReplace($out, $tokenargs);
        $out = parseShortcuts($out, true);
    }

    $out = str_replace('index.php?pg=file&amp;from=0', 'admin?pg=file&amp;from=0', $out);

    return $out;
}

/********************************************
LINK ANY URL OR EMAIL
*********************************************/
function linkUrls($text, $newwin = true)
{
    $text = ' '.$text.' ';
    $target = ($newwin) ? ' target="_blank" rel="noopener noreferrer"' : '';

    // link domains
    $text = preg_replace_callback(
        '#([\s{}\(\)\[\]])([A-Za-z0-9\-\.]+)\.(com|org|net|gov|edu|us|info|biz|ws|name|tv|co|xyz|io)((?:/[^\s{}\(\)\[\]]*[^\.,\s{}\(\)\[\]]?)?)#i',
        function ($m) use ($target) {
            return "$m[1]<a href=\"http://$m[2].$m[3]$m[4]\" title=\"http://$m[2].$m[3]$m[4]\"$target>$m[2].$m[3]$m[4]</a>";
        },
        $text
    );

    // link other protos
    $text = preg_replace_callback(
        '#([\s{}\(\)\[\]])(([a-z]+?)://([A-Za-z_0-9\-]+\.([^\s{}\(\)\[\]]+[^\s,\.\;{}\(\)\[\]])))#i',
        function ($m) use ($target) {
            return "$m[1]<a href=\"$m[2]\" title=\"$m[2]\"$target>$m[2]</a>";
        },
        $text
    );

    // link e-mail addresses
    $text = preg_replace_callback(
        '#([\s{}\(\)\[\]])([A-Za-z0-9\-_\.]+?)@([^\s,{}\(\)\[\]]+\.[^\s.,{}\(\)\[\]]+)#i',
        function ($m) use ($target) {
            return "$m[1]<a href=\"mailto:$m[2]@$m[3]\" $target>$m[2]@$m[3]</a>";
        },
        $text
    );

    return utf8_substr($text, 1, utf8_strlen($text) - 2);
}

/********************************************
MAKE RELATIVE URLS ABSOLUTE
*********************************************/
function relToAbs($text, $base, $trailing_slash = true)
{
    if (empty($base)) {
        return $text;
    }
    // base url needs trailing /
    if ($trailing_slash && substr($base, -1, 1) != '/') {
        $base .= '/';
    }
    // Replace links
    $pattern = '/<a([^>]*) href="(?!http|file|ftp|https|mailto|tel|sms|helpspot|##|#)([^"]*)"/';
    $replace = '<a${1} href="'.$base.'${2}"';
    $text = preg_replace($pattern, $replace, $text);
    // Replace images
    $pattern = '/<img([^>]*) src="(?!http|file|ftp|https|cid|##)([^"]*)"/';
    $replace = '<img${1} src="'.$base.'${2}"';
    $text = preg_replace($pattern, $replace, $text);
    // Done
    return $text;
}

/********************************************
PARSE OUT PLACEHOLDERS IN MAIL
*********************************************/
function tokenReplace($msg, $vars)
{

    //Do array replace by breaking keys out

    if (is_array($vars) && ! empty($vars)) {
        //Keys (search) array
        $keys = array_keys($vars);

        $msg = str_replace($keys, $vars, $msg);
        /*
        foreach($vars AS $key=>$value){
            $search = chr(35).chr(35).strtoupper($key).chr(35).chr(35);
            $msg = str_replace($search,$value,$msg);
        }
        */
    }

    return $msg;
}

/********************************************
LIST OF GENERAL TOKENS
*********************************************/
function getPlaceholders($tokens, $req = false, $reqid_known = true)
{
    $defaults = [];

    // Universal placeholders
    $uph = [
        'orgname'=>hs_setting('cHD_ORGNAME'),
        'helpdeskurl'=>cHOST,
        'requestcheckurl'=>cHOST.'/index.php?pg=request.check',
        'knowledgebookurl'=>cHOST.'/index.php?pg=kb',
        'forumurl'=>cHOST.'/index.php?pg=forums',
        'requestformurl'=>cHOST.'/index.php?pg=request',
        'replyabove_text'=>hs_setting('cHD_EMAIL_REPLYABOVE'), ];

    //Multiportal values
    $portals = apiGetAllPortals(0);
    if (hs_rscheck($portals)) {
        while ($p = $portals->FetchRow()) {
            $uph['portal'.$p['xPortal'].'_orgname'] = $p['sPortalName'];
            $uph['portal'.$p['xPortal'].'_helpdeskurl'] = $p['sHost'];
            $uph['portal'.$p['xPortal'].'_requestformurl'] = $p['sHost'].'/index.php?pg=request';
            $uph['portal'.$p['xPortal'].'_requestcheckurl'] = $p['sHost'].'/index.php?pg=request.check';
            $uph['portal'.$p['xPortal'].'_knowledgebookurl'] = $p['sHost'].'/index.php?pg=kb';
            $uph['portal'.$p['xPortal'].'_forumurl'] = $p['sHost'].'/index.php?pg=forums';
        }
    }

    // Request specific placeholders
    if ($req) {
        $a_user = apiGetUserPlaceholders($req['xPersonAssignedTo']);

        //Get logged in users info
        $user = apiGetLoggedInUser();

        $defaults = [
            'customerfirst'=>$req['sFirstName'],
            'customerlast'=>$req['sLastName'],
            'customerid'=>$req['sUserId'],
            'customeremail'=>$req['sEmail'],
            'customerphone'=>$req['sPhone'],
            'status'=>apiGetStatusName($req['xStatus']),
            'category'=>apiGetCategoryName($req['xCategory']),
            'urgent'=>($req['fUrgent'] ? lg_isurgent : ''),
            'open_closed'=>($req['fOpen'] == 1 ? lg_isopen : lg_isclosed),
            'date_opened'=>($req['dtGMTOpened'] ? hs_showDate($req['dtGMTOpened']) : ''),
            'date_now'=>hs_showDate(time()),
            'assigned_first'=>$a_user['sFname'],
            'assigned_last'=>$a_user['sLname'],
            'assigned_email'=>$a_user['sEmail'],
            'assigned_phone'=>$a_user['sPhone'],
            'logged_in_first'=>$user['sFname'],
            'logged_in_last'=>$user['sLname'],
            'logged_in_email'=>$user['sEmail'],
            'logged_in_phone'=>$user['sPhone'],
            'subject'=>$req['sTitle'],
            'initialrequest'=>'',
            'portal_email'=>$req['sEmail'],
            'portal_password'=>apiPortalAddLoginIfNew($req['sEmail']), ];

        //Some replacements are done before a reqid is known
        if ($reqid_known and $req['xRequest']) {
            $defaults['mobilelink'] = cHOST.'/mobileredirect?request='.$req['xRequest'];
            $defaults['requestid'] = $req['xRequest'];
            $password = (isset($req['sRequestPassword']) && trim($req['sRequestPassword']) != '') ? $req['sRequestPassword'] : apiGetRequestPassword($req['xRequest']);
            $defaults['accesskey'] = $req['xRequest'].$password;

            $initial_request = apiGetInitialRequest($req['xRequest']);
            $defaults['initialrequest'] = (hs_setting('cHD_HTMLEMAILS') == 0 ? hs_html_2_markdown($initial_request['tNote']) : $initial_request['tNote']);
            // remove any image tags from initial request since we don't include attachments. https://github.com/UserScape/helpspot5/issues/521
            $cleaner = app('html.cleaner');
            $defaults['initialrequest'] = $cleaner->clean($defaults['initialrequest'], true);
            $defaults['initialrequest_type'] = $initial_request['fPublic'];

            //Get date opened. The way email currently works this is not available here. we need to change this...
            $opened_check = $GLOBALS['DB']->GetOne('SELECT dtGMTOpened FROM HS_Request WHERE xRequest = ?', [$req['xRequest']]);
            $defaults['date_opened'] = hs_showDate($opened_check);
        }

        //Custom field values
        if (is_array($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
            foreach ($GLOBALS['customFields'] as $k=>$v) {
                if ($v['fieldType'] == 'drilldown') {
                    $defaults['custom'.$v['fieldID'].''] = cfDrillDownFormat($req['Custom'.$v['fieldID']]);
                } elseif ($v['fieldType'] == 'checkbox') {
                    $defaults['custom'.$v['fieldID'].''] = boolShow($req['Custom'.$v['fieldID']], lg_checked, lg_notchecked);
                } elseif ($v['fieldType'] == 'date') {
                    $defaults['custom'.$v['fieldID'].''] = hs_showShortDate($req['Custom'.$v['fieldID']]);
                } elseif ($v['fieldType'] == 'datetime') {
                    $defaults['custom'.$v['fieldID'].''] = hs_showDate($req['Custom'.$v['fieldID']]);
                } else {
                    if (isset($defaults['custom'.$v['fieldID'].''])) {
                        $defaults['custom'.$v['fieldID'].''] = $req['Custom'.$v['fieldID']];
                    }
                }
            }
        }
    }

    return array_merge($uph, $tokens, $defaults);
}

/********************************************
REPLACE THE SPECIAL TOKENS THAT APPEAR IN THE MAILBOX FROM
*********************************************/
function replyNameReplace($name, $userid)
{
    $a_user = apiGetUserPlaceholders($userid);
    $user = apiGetLoggedInUser();

    $templateName = uniqid() . '_reply_name_replace';
    \HS\View\Mail\TemplateTemporaryFile::create($templateName, $name);

    return (string)restrictedView($templateName, [
        'assigned_first' => $a_user['sFname'],
        'assigned_last' => $a_user['sLname'],
        'logged_in_first' =>  $user['sFname'],
        'logged_in_last' => $user['sLname'],
    ]);
}

/********************************************
SPECIAL LOWERCASE FUNCTION FOR REPLY NAME
*********************************************/
function replyNameDisplay($name)
{
    // This function shows the proper display text for a reply name
    $vars = [
        '{{ $assigned_first . $assigned_last }}'=>lg_placeholderspopup_assignedfull,
        '{{ $assigned_last . \', \' . $assigned_first }}'=>lg_placeholderspopup_assignedlastfirst,
        '{{ $assigned_first }}'=>lg_placeholderspopup_assignedfirst,
        '{{ $logged_in_first . $logged_in_last }}'=>lg_placeholderspopup_loggedinfull,
        '{{ $logged_in_last . \', \' . $logged_in_first }}'=>lg_placeholderspopup_loggedinlastfirst,
        '{{ $logged_in_first }}'=>lg_placeholderspopup_loggedinfirst,
    ];

    // Keep the usage of tokenReplace here, it's just used
    // to build <select> menu labels
    return tokenReplace($name, $vars);
}

/******************************************
ORDER BY SELECT BOX
******************************************/
function orderBySelect($selected = 'dtGMTOpened')
{
    $orderByGroups = [];
    $orderByGroups[lg_filter_requests_ogcustinfo] = [
                                'sUserId',
                                'sLastName',
                                'sEmail',
                                'sPhone', ];
    $orderByGroups[lg_filter_requests_ogreqdetails] = [
                                'fOpen',
                                'xRequest',
                                'xPersonOpenedBy',
                                'xPersonAssignedTo',
                                'xStatus',
                                'sTitle', ];
    if (isset($GLOBALS['customFields']) && is_array($GLOBALS['customFields'])) {
        foreach ($GLOBALS['customFields'] as $cfV) {
            $orderByGroups[lg_filter_requests_ogcustomfields][] = 'Custom'.$cfV['fieldID'];
        }
    }
    $orderByGroups[lg_filter_requests_ogdatetime] = [
                                'dtGMTOpened',
                                'dtGMTClosed',
                                'lastupdate',
                                'lastpubupdate',
                                'lastcustupdate',
                                'speedtofirstresponse', ];
    $orderByGroups[lg_filter_requests_thermostat] = [
        'thermostat_nps_score',
        'thermostat_csat_score', ];
    $orderByGroups[lg_filter_requests_ogother] = [
                                'timetrack',
                                'ctPublicUpdates', ];

    $orderBySel = '';
    foreach ($orderByGroups as $group=>$options) {
        //Output optgroup
        $orderBySel .= '<optgroup label="'.$group.'">';
        //Output options
        foreach ($options as $k=>$v) {
            $label = isset($GLOBALS['filterCols'][$v]['label2']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
            $orderBySel .= '<option value="'.$v.'" '.selectionCheck($v, $selected).'>'.$label.'</option>';
        }
        $orderBySel .= '</optgroup>';
    }

    return $orderBySel;
}

/******************************************
OUTPUT CUSTOM FIELDS
******************************************/
function renderCustomFields($fm, $customFields, $tindex = 100, $ignorereq = false, $convertlrgtxt = false, $drill_sep = ' ', $wrapper = false, $tablelayout = false, $divlayout=false)
{
    $customfieldsdisplay = '';
    $isreq = '';
    if (is_array($customFields)) {
        foreach ($customFields as $fvalue) {
            $fid = 'Custom'.$fvalue['fieldID'];
            $fieldhtml = '';

            // Setup custom fields
            $fm[$fid] = isset($fm[$fid]) ? $fm[$fid] : '';

            //tab index
            $tindex = $tindex ? $tindex + 10 : '';

            if ($fvalue['isRequired'] == 1 && ! $ignorereq) {
                $isreq = ' req';
            } else {
                $isreq = '';
            }

            if ($convertlrgtxt && $fvalue['fieldType'] == 'lrgtext') {
                $fvalue['fieldType'] = 'text';
                $fvalue['sTxtSize'] = '20';
            }

            switch ($fvalue['fieldType']) {
            case 'select':
                $fieldhtml .= '<select tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" class="cf-select">';
                $items = hs_unserialize($fvalue['listItems']);
                //provide an empty box first
                $fieldhtml .= '<option value=""></option>';
                if (is_array($items)) {
                    foreach ($items as $v) {
                        $fieldhtml .= '<option value="'.formClean($v).'" '.selectionCheck($v, $fm[$fid]).'>'.formClean($v).'</option>';
                    }
                }
                $fieldhtml .= '</select>';

                break;
            case 'text':
                $fieldhtml .= '<input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="text" size="'.formClean($fvalue['sTxtSize']).'" class="cf-text" value="'.formClean($fm[$fid]).'">';

                break;
            case 'lrgtext':
                $fieldhtml .= '<textarea tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" rows="'.formCleanHtml($fvalue['lrgTextRows']).'" cols="30" class="cf-textarea">'.formClean($fm[$fid]).'</textarea>';

                break;
            case 'checkbox':
                if ($tablelayout || $divlayout) {
                    $fieldhtml .= '
                        <input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="checkbox" class="checkbox" value="1" '.checkboxCheck(1, $fm[$fid]).'>';
                } else {
                    $fieldhtml .= '
						<input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="checkbox" class="checkbox" value="1" '.checkboxCheck(1, $fm[$fid]).'>
                        <label class="datalabel '.$isreq.'" for="'.$fid.'" style="margin: 0 0 0 5px; display: inline-block;">'.$fvalue['fieldName'].'</label>';
                }

                break;
            case 'numtext':
                $fieldhtml .= '<input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="text" size="10" maxlength="10" class="cf-numeric" value="'.formClean($fm[$fid]).'">';

                break;
            case 'drilldown':

                $drilldown_array = hs_unserialize($fvalue['listItems']);

                //Create array of selected values
                if (! hs_empty($fm[$fid])) {
                    $keys = [];
                    $depth = find_max_array_depth($drilldown_array);	//Find number of select boxes
                    $values = explode('#-#', $fm[$fid]);					//Create array out of selected values string
                    $values = array_pad($values, $depth, '');				//Fill values array full to start, this is important since values are stored in a way that only the selected values are kept. So a 4 tier list if only the first 2 are selected only they are stored so the array would be short if we didn't fill it
                    for ($i = 1; $i <= $depth; $i++) {
                        $keys[] = $fid.'_'.$i;
                    }	//Create keys array with name of each select box
                    $values = array_combine($keys, $values);				//Combine keys with selected values
                } else {
                    $values = [];
                }

                $fieldhtml .= '<div>'.RenderDrillDownList($fvalue['fieldID'], $drilldown_array, $values, $drill_sep, '', $tindex).'</div>';

                break;
            case 'date':

                //If exists and numeric pass through, else make numeric or leave blank
                $date = $fm[$fid] ? (is_numeric($fm[$fid]) ? $fm[$fid] : hs_strtotime($fm[$fid], date('U'))) : '';

                $fieldhtml .= calinput($fid, $date);

                break;
            case 'datetime':

                //If exists and numeric pass through, else make numeric or leave blank
                $date = $fm[$fid] ? (is_numeric($fm[$fid]) ? $fm[$fid] : hs_strtotime($fm[$fid], date('U'))) : '';

                $fieldhtml .= calinput($fid, $date, true);

                break;
            case 'regex':

                $fieldhtml .= '
                    <div style="display:flex;align-items:center;">
                        <input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="text" size="25" class="cf-regex" value="'.formClean($fm[$fid]).'" style="flex:1;">
						<img src="'.static_url().'/static/img5/'.($fvalue['isRequired'] ? 'remove.svg' : 'match.svg').'" class="svg28" id="regex_img_'.$fid.'" align="top" border="0" alt="" style="margin-left:6px;" />
						 <script type="text/javascript">
						 Event.observe("'.$fid.'", "keyup", function(event){ if('.hs_jshtmlentities($fvalue['sRegex']).'.test($("'.$fid.'").value)){ $("regex_img_'.$fid.'").src="'.static_url().'/static/img5/match.svg"; }else{ $("regex_img_'.$fid.'").src="'.static_url().'/static/img5/remove.svg"; } });
						 </script>
					</div>';

                break;
            case 'ajax':

                $fieldhtml .= '
                    <div style="display:flex;align-items:center;">
                        <input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="text" class="cf-ajax" value="'.formClean($fm[$fid]).'" style="flex:1;">
						<img src="'.static_url().'/static/img5/sync-solid.svg" id="ajax_img_'.$fid.'" onclick="custom_ajax_field_lookup(\''.$fid.'_ajax_lookup\',\''.$fvalue['sAjaxUrl'].'\', \''.$fid.'\',\''.lg_loading.'\');" style="vertical-align:middle;margin-left:6px;height:26px;" class="hand" align="top" border="0" alt="" />
                    </div>
                    <div id="'.$fid.'_ajax_lookup" style="display:none;margin-bottom:12px;"></div>';

                break;
            case 'decimal':
                $fieldhtml .= '<input tabindex="'.$tindex.'" name="'.$fid.'" id="'.$fid.'" type="text" size="10" maxlength="10" class="cf-decimal" value="'.formClean($fm[$fid]).'">';

                break;
            }

            if ($tablelayout) {
                $customfieldsdisplay .= '
					<tr class="trr">
						<td class="tdl '.($fvalue['fieldType'] == 'checkbox' ? 'tdlcheckbox' : '').'"><label class="datalabel'.$isreq.'" for="'.$fid.'">'.$fvalue['fieldName'].'</label></td>
						<td class="tdr">'.$fieldhtml.'</td>
					</tr>

					<tr><td colspan="2" class="tdspace">&nbsp;</td></tr>
				';
            } elseif($divlayout) {
                $customfieldsdisplay .= '
                    <div class="fr">
                        <div class="label">
                            <label class="datalabel'.$isreq.'" for="'.$fid.'">'.$fvalue['fieldName'].'</label>
                        </div>
                        <div class="control">'.$fieldhtml.'</div>
                    </div>

                    <div class="hr"></div>
                ';
            } else {
                //Wrapper used to show/hide for categories
                if ($wrapper) {
                    $display = $fvalue['isAlwaysVisible'] == 0 ? 'display:none;' : '';
                    $customfieldsdisplay .= '<div id="'.$fid.'_wrapper" style="'.$display.'">';
                }

                $customfieldsdisplay .= '<div class="field-wrap-cf">';
                if ($fvalue['fieldType'] != 'checkbox') {
                    $customfieldsdisplay .= '<label class="datalabel'.$isreq.'" for="'.$fid.'">'.$fvalue['fieldName'].'</label>';
                }

                $customfieldsdisplay .= $fieldhtml;

                $customfieldsdisplay .= '</div>';
                if ($wrapper) {
                    $customfieldsdisplay .= '</div>';
                }
            }
        }

        return $customfieldsdisplay;
    } else {
        return '';
    }
}

/********************************************
CREATE A CALENDAR
*********************************************/
function calinput($field, $value, $time = false, $futureonly = false)
{
    $out = '
        <div style="display:flex;align-items:center;">
            <input type="text" name="'.$field.'" id="'.$field.'" value="'.formClean($value).'" style="flex:1;">
            <img id="'.$field.'_show_calendar" src="'.static_url().'/static/img5/calendar.svg" style="height: 30px; cursor: pointer; vertical-align: middle;margin-left:4px;">
        </div>
        <script type="text/javascript">
            $jq().ready(function(){
                mobiscroll.settings = {
                    lang: "en",
                    theme: "ios",
                    display: "bubble"
                };
                var dateFormat = "'.formatJsDate(($time ? cHD_POPUPCALDATEFORMAT : cHD_POPUPCALSHORTDATEFORMAT)).'";
                var timeFormat = "'.formatJsTime(($time ? cHD_POPUPCALDATEFORMAT : cHD_POPUPCALSHORTDATEFORMAT)).'";
                var '.$field.'_calendar = $jq("#'.$field.'").mobiscroll().calendar({
                    controls: ['. (($time) ? '"calendar", "time"' : '"calendar"') .'],
                    touchUi: false,
                    showOnTap: false,
                    showOnFocus: false,
                    dateFormat: dateFormat,
                    timeFormat: timeFormat,
                    firstDay: '.lg_cal_fdow.',   // first day of week for this locale; 0 = Sunday, 1 = Monday, etc.
                    amText: "'.lg_cal_am.'",
                    pmText: "'.lg_cal_pm.'",
                    monthNames : [ "'.lg_cal_mn_jan.'",
                       "'.lg_cal_mn_feb.'",
                       "'.lg_cal_mn_mar.'",
                       "'.lg_cal_mn_apr.'",
                       "'.lg_cal_mn_may.'",
                       "'.lg_cal_mn_jun.'",
                       "'.lg_cal_mn_jul.'",
                       "'.lg_cal_mn_aug.'",
                       "'.lg_cal_mn_sep.'",
                       "'.lg_cal_mn_oct.'",
                       "'.lg_cal_mn_nov.'",
                       "'.lg_cal_mn_dec.'" ],
                    monthNamesShort : [ "'.lg_cal_mn_jan.'",
                        "'.lg_cal_mn_feb.'",
                        "'.lg_cal_mn_mar.'",
                        "'.lg_cal_mn_apr.'",
                        "'.lg_cal_mn_may.'",
                        "'.lg_cal_mn_jun.'",
                        "'.lg_cal_mn_jul.'",
                        "'.lg_cal_mn_aug.'",
                        "'.lg_cal_mn_sep.'",
                        "'.lg_cal_mn_oct.'",
                        "'.lg_cal_mn_nov.'",
                        "'.lg_cal_mn_dec.'" ],
                    dayNames : [ "'.lg_cal_dn_su.'",
                       "'.lg_cal_dn_mo.'",
                       "'.lg_cal_dn_tu.'",
                       "'.lg_cal_dn_we.'",
                       "'.lg_cal_dn_th.'",
                       "'.lg_cal_dn_fr.'",
                       "'.lg_cal_dn_sa.'",
                       "'.lg_cal_dn_su.'" ],
                    dayNamesShort : [ "'.lg_cal_sdn_su.'",
                        "'.lg_cal_sdn_mo.'",
                        "'.lg_cal_sdn_tu.'",
                        "'.lg_cal_sdn_we.'",
                        "'.lg_cal_sdn_th.'",
                        "'.lg_cal_sdn_fr.'",
                        "'.lg_cal_sdn_sa.'",
                        "'.lg_cal_sdn_su.'" ],
                    '.($futureonly ? 'min: new Date(),' : '').'
                    onInit: function (event, inst) {
                        // Values in minutes
                        var helpspotUtcOffset = '.(date('Z')/-60).';
                        var browserUtcOffset = (new Date().getTimezoneOffset());
                        var userDisplayOffset = helpspotUtcOffset - browserUtcOffset;

                        // value is timestamp in seconds
                        var value = '. (((int) $value > 0) ? $value : 'null') .';

                        if (value) {
                            // Apply offset to value to get custom field time to show correctly to user
                            var offsetSeconds = userDisplayOffset*60;
                            value = value - offsetSeconds;

                            var setValue = new Date(value * 1000); // Convert to milliseconds
                            inst.setVal(setValue, true);
                        }
                    }
                }).mobiscroll("getInst");
                $jq("#'.$field.'_show_calendar").click(function () {
                    '.$field.'_calendar.show();
                    return false;
                });
            });
        </script>';

    return $out;
}

/********************************************
DISPLAY FILTER COLUMNS LIST
*********************************************/
function createFilterColumnList($selected = [])
{
    $columnGroups = [];
    $columnGroups[lg_filter_requests_ogspecial] = [
                                'iLastReplyBy',
                                'takeitfilter',
                                ];
    $columnGroups[lg_filter_requests_ogcustinfo] = [
                                'sUserId',
                                'fullname',
                                'sLastName',
                                'sEmail',
                                'sPhone', ];
    $columnGroups[lg_filter_requests_ogreqdetails] = [
                                'fOpen',
                                'xRequest',
                                'xPersonAssignedTo',
                                'sCategory',
                                'mailbox',
                                'reportingTags',
                                'xPortal',
                                'xPersonOpenedBy',
                                'xStatus',
                                'sTitle',
                                'fOpenedVia',
                                'attachment', ];
    $columnGroups[lg_filter_requests_ogreqhistory] = [
                                'reqsummary',
                                'lastpublicnote',
                                'lastpublicnoteby',
                                'lastupdateby',
                                'ctPublicUpdates',
                                'timetrack', ];
    if (isset($GLOBALS['customFields']) && is_array($GLOBALS['customFields'])) {
        foreach ($GLOBALS['customFields'] as $cfV) {
            $columnGroups[lg_filter_requests_ogcustomfields][] = 'Custom'.$cfV['fieldID'];
        }
    }
    $columnGroups[lg_filter_requests_ogdatetime] = [
                                'age',
                                'dtGMTOpened',
                                'dateTimeOpened',
                                'dtGMTClosed',
                                'dateTimeClosed',
                                'lastupdate',
                                'lastpubupdate',
                                'lastcustupdate',
                                'speedtofirstresponse',
                                'speedtofirstresponse_biz', ];

    $columnGroups[lg_filter_requests_ointegrations] = [
        'thermostat_nps_score', 'thermostat_csat_score', 'thermostat_feedback', ];

    $columnSel = '';
    foreach ($columnGroups as $group=>$options) {
        //Output optgroup
        $columnSel .= '<optgroup label="'.$group.'">';
        //Output options
        foreach ($options as $k=>$v) {
            if (! in_array($v, $selected)) {
                $label = isset($GLOBALS['filterCols'][$v]['label2']) ? $GLOBALS['filterCols'][$v]['label2'] : $GLOBALS['filterCols'][$v]['label'];
                $columnSel .= '<option value="'.$v.'@@@'.$GLOBALS['filterCols'][$v]['width'].'@@@'.(isset($GLOBALS['filterCols'][$v]['hideflow']) ? 'hideflow' : '').'" id="select_col_'.$v.'">'.$label.'</option>';
            }
        }
        $columnSel .= '</optgroup>';
    }

    return $columnSel;
}

/********************************************
REPLACE DRILL DOWN SEP WITH NICER FORMAT
*********************************************/
function cfDrillDownFormat($string)
{
    if (utf8_strpos($string, '#-#')) {
        $f = [];
        $a = explode('#-#', $string);
        foreach ($a as $v) {
            if (trim($v) != '') {
                $f[] = $v;
            }
        }

        return implode(', ', $f);
    } else {
        return $string;
    }
}

/********************************************
REPLACE SHORTCUTS r:xxxx , f:xxxx, k:xxxx
*********************************************/
function parseShortcuts($string, $forpubemail = false, $ishtml = false)
{
    if (hs_setting('cHD_DISABLESHORTCUTS')) {
        return $string;
    }

    $patterns = [
        '/(\s|^|>)[Kk]:(\d{1,11})(?![A-Za-z])/',
        '/(\s|^|>)[Ff]:(\d{1,11})(?![A-Za-z])/',
    ];

    if ($ishtml) {
        $replacements = [
            '$1<a href="'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.page', 'page' => '$2'])).'">'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.page', 'page' => '$2'])).'</a> ',
        ];
        // Push the request regex.
        if (! IN_PORTAL && ! $forpubemail) {
            array_push($patterns, '/(\s|^|>)[Rr]:(\d{5,11})(?![A-Za-z])/');
            array_push($replacements, '$1<a href="'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '$2'])).'">'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '$2'])).'</a> ');
        }
    } else {
        $replacements = [
            '$1'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.page', 'page' => '$2'])).' ',
        ];
        if (! IN_PORTAL && ! $forpubemail) {
            array_push($patterns, '/(\s|^|>)[Rr]:(\d{5,11})(?![A-Za-z])/');
            array_push($replacements, '$1'.str_replace('%24', '$', action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'request', 'reqid' => '$2'])).' ');
        }
    }

    return preg_replace($patterns, $replacements, $string);
}

//Readable byte sizes
function decodeSize($bytes)
{
    $types = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);

    return  round($bytes, 2).' '.$types[$i];
}

// inputs must be unix timestamp (seconds)
// $newer_date variable is optional
function time_since($older_date, $newer_date = false, $gotoseconds = false)
{
    static $chunks;

    // array of time period chunks
    if (empty($chunks)) {
        $chunks = [
        [60 * 60 * 24 * 365, [lg_year, lg_years]],
        [60 * 60 * 24 * 30, [lg_month, lg_months]],
        [60 * 60 * 24 * 7, [lg_week, lg_weeks]],
        [60 * 60 * 24, [lg_day, lg_days]],
        [60 * 60, [lg_hour, lg_hours]],
        [60, [lg_minute, lg_minutes]],
        ];
    }

    if ($gotoseconds) {
        $chunks[] = [1, [lg_second, lg_seconds]];
    }

    // $newer_date will equal false if we want to know the time elapsed between a date and the current time
    // $newer_date will have a value if we want to work out time elapsed between two known dates
    $newer_date = ($newer_date == false) ? time() : $newer_date;

    //Newer date should never be less than older date, if it is make them the same (can occur when load balanced on servers with wrong times)
    if ($newer_date < $older_date) {
        $newer_date = $older_date;
    }

    // difference in seconds
    $since = $newer_date - $older_date;

    // we only want to output two chunks of time here, eg:
    // x years, xx months
    // x days, xx hours
    // so there's only two bits of calculation below:

    // step one: the first chunk
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];

        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

    // set output var
    $output = ($count == 1) ? '1 '.$name[0] : "$count ".$name[1];

    // step two: the second chunk
    if ($i + 1 < $j) {
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];

        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
            // add to output var
            $output .= ($count2 == 1) ? ', 1 '.$name2[0] : ", $count2 ".$name2[1];
        }
    }

    return $output;
}

/********************************************
Convert assigned to ID into a name
*********************************************/
function hs_personName($xperson)
{
    static $hs_personNameList = [];
    if (empty($hs_personNameList)) {
        $pnrs = apiGetAllUsersComplete();
        foreach ($pnrs as $k=>$v) {
            $hs_personNameList[$k] = $v['fullname'];
        }
    }

    if (isset($hs_personNameList[$xperson])) {
        return $hs_personNameList[$xperson];
    } else {
        return '';
    }
}

/********************************************
Convert person number on history table notes to name
*********************************************/
function hs_personNameNotes($xperson)
{
    if ($xperson == 0) {
        return lg_label_customer;
    }

    return hs_personName($xperson);
}

/********************************************
Convert mailbox ID into names
*********************************************/
function hs_mailbox_from_id($id)
{
    static $boxes = [];

    if (empty($boxes)) {
        $query = $GLOBALS['DB']->Execute('SELECT HS_Mailboxes.* FROM HS_Mailboxes');
        $boxes = rsToArray($query, 'xMailbox', false);
    }

    return isset($boxes[$id]) ? replyNameDisplay($boxes[$id]['sReplyName']) : '';
}

/********************************************
clean init request if it has extra "-" from concat
clean out request form blurbs
*********************************************/
function initRequestClean($string, $remove_subject = false)
{
    // Now that we use HTML Purify it tightens up all the HTML and leaves no spaces.
    // That causes this function to end up producing words that run together if
    // Used with strip_tags which it often is. So here we'll hackily try and stop that
    $string = str_replace('<', ' <', $string);

    $string = utf8_trim($string);

    if (utf8_substr($string, 0, 3) == '#-#') {
        $string = utf8_substr($string, 3);
    } else {
        $t = explode('#-#', $string);
        if ($remove_subject) {
            $string = (isset($t[1]) ? $t[1] : '');
        } else {
            $string = '<span class="initsubject">'.$t[0].'</span> - '.(isset($t[1]) ? $t[1] : '');
        }
    }

    return stripFormBlurbs($string);
}

/********************************************
STRIP ALL BLURB TEXT (DETAIL FORM)
*********************************************/
function stripFormBlurbs($string)
{
    //Request form detail blurbs
    $string = str_replace(lg_portal_req_did.':', '', $string);
    $string = str_replace(lg_portal_req_expected.':', '', $string);
    $string = str_replace(lg_portal_req_actual.':', '', $string);
    $string = str_replace(lg_portal_req_additional.':', '', $string);

    return $string;
}

/********************************************
WHEN RENDERING AS HTML MAKE DETAILS REQUEST FORM
LABELS BOLD
*********************************************/
function makeBold($string)
{
    $string = stripAdditionalDetails($string);

    $string = str_replace(lg_portal_req_did.':', '<b>'.lg_portal_req_did.':</b>', $string);
    $string = str_replace(lg_portal_req_expected.':', '<b>'.lg_portal_req_expected.':</b>', $string);
    $string = str_replace(lg_portal_req_actual.':', '<b>'.lg_portal_req_actual.':</b>', $string);
    $string = str_replace(lg_portal_req_additional.':', '<b>'.lg_portal_req_additional.':</b>', $string);

    return $string;
}

function syntaxHighligherJS()
{
    return '
		<script type="text/javascript">
		SyntaxHighlighter.defaults["toolbar"] = false;
		SyntaxHighlighter.all();
		</script>
	';
}

/********************************************
 * STRIP ADDITIONAL INFO FROM NOTE
 ********************************************
 * @param $string
 * @return mixed|string
 */
function stripAdditionalDetails($string)
{
    if (IN_PORTAL) {
        $pos = utf8_strpos($string, lg_portal_req_additional);
        if ($pos) {
            return utf8_substr($string, 0, $pos);
        } else {
            //no additional info was sent so just return string
            return $string;
        }
    } else {
        return $string;
    }
}

//Wrapper for isUnread, used for filters other than myq
function isRepliedTo($reqid, $lastreplyby, $returnBool = false)
{
    $users = apiGetAllUsersComplete();

    if ($lastreplyby > 0) {
        if ($returnBool) {
            return true;
        } else {
            return '<div class="table-icons table-icons-replied hand" border="0" title="'.lg_lookup_filter_repliedtoby.': '.$users[$lastreplyby]['sFname'].' '.$users[$lastreplyby]['sLname'].'"></div>';
        }
    } else {
        if ($returnBool) {
            return false;
        } else {
            return '';
        }
    }
}

//Function compares the number of history events the request has vs the last time the assigned user read it.
//	Show image if there are new events.
function isUnread($reqid, $history_ct, $readct, $iLastReplyBy = 0, $returnBool = false)
{
    //If last read is less than history count show the icon
    if ($history_ct != $readct) {
        if ($returnBool) {
            return true;
        } else {
            return '<div class="table-icons table-icons-unread hand" id="replied_img_'.$reqid.'" onClick="toggleRead(this,'.$reqid.');" title="'.lg_lookup_filter_reqhaschanged.'"></div>';
        }
    } else {
        if ($returnBool) {
            return false;
        } elseif ($iLastReplyBy > 0) {
            return '<div class="table-icons table-icons-replied hand" id="replied_img_'.$reqid.'" onClick="toggleRead(this,'.$reqid.');" class="hand" title="'.lg_lookup_filter_repliedtoby.'"></div>';
        } else {
            return '<div class="table-icons table-icons-read hand" id="replied_img_'.$reqid.'" onClick="toggleRead(this,'.$reqid.');" title="'.lg_lookup_filter_markunread.'"></div>';
        }
    }
}

/********************************************
PHOTO URL WHEN USING XPERSON VARIABLE
*********************************************/
function xPersonPhotoUrl($xperson)
{
    $avatar = new HS\Avatar\Avatar();
    return $avatar->xPerson($xperson)->size(24)->html();
}

/********************************************
SHOW A MIME ICON IN PORTAL
*********************************************/
function hs_showMimePortal($filename)
{
    return hs_showMime($filename);
}

/********************************************
SHOW A MIME ICON
*********************************************/
function hs_showMime($filename)
{
    $f = explode('.', $filename);
    $ext = $f[(count($f) - 1)];

    //Override special extensions
    switch ($ext) {
        case 'htm': $ext = 'html';
        break;

        case 'docx': $ext = 'doc';
        break;

        case 'xlsx': $ext = 'xls';
        break;

        case 'pptx': $ext = 'ppt';
        break;

        case 'unknown_filename': $ext = 'N/A';
        break;
    }

    return '<div class="file-extension" style="background-color:'.(inDarkMode() ? '#000000' : '#3A2D23').';color:#fff;font-weight:700;padding-top: 8px;padding-bottom:8px;width:48px;text-align:center;border-radius:3px;text-transform: uppercase;">'.trim($ext).'</div>';
}

/********************************************
RETURN LIST OF UNSERIALIZED USERS W/EMAILS
*********************************************/
function createUserListEmails($modlist)
{
    static $allusers = [];
    $out = '';

    //If not created then do so
    if (empty($allusers)) {
        $allusers = apiGetAllUsersComplete();
    }

    $mods = hs_unserialize($modlist);
    if (is_array($mods) && ! empty($mods)) {
        foreach ($mods as $p) {
            if (! $allusers[$p]['fDeleted']) {

                $avatar = new HS\Avatar\Avatar();

                $out .= '
				<div style="display:flex;margin-top:14px;">
                    <div style="margin-right:10px;">'.$avatar->xPerson($p)->size(30)->html().'</div>
					<div class="request-assignedto-name">'.$allusers[$p]['fullname'].'</div>
				</div>';
            }
        }
    }

    return $out;
}

function editEmailTemplate($subject, $populate, &$templates, $base, $name, $xmailbox, $cust_tags_subject = [], $cust_tags_body = [])
{
    $htmlhide = $base == 'sms' ? true : false;

    $out = '
    <script type="text/javascript" language="JavaScript">
        $jq().ready(function(){
            $jq(".'.$base. '_edit_btn").on("click", function(){
                hs_overlay("' . $base . '_edit", {
                    onOpen: function() {
                        setTimeout(function(){
                            $$(".'.$base. 'tabs").each(function(tabs){
                                new Control.Tabs(tabs);
                            });
                        }, 70);
                    }
                });
            });
            $jq(".'.$base. '_view_btn").on("click", function(){
                hs_overlay("' . $base . '_view", {
                    onOpen: function() {
                        setTimeout(function(){
                            $$(".'.$base. 'tabs_view").each(function(tabs){
                                new Control.Tabs(tabs);
                            });
                        }, 70);
                    }
                });
                return false;
            });
        });
    </script>
	<div style="overflow: hidden;">
    <a href="#" onclick="return false;" class="'.$base.'_edit_btn btn inline-action" style="margin-right:4px;">'.lg_admin_mailboxes_edit. '</a>
    <a href="#" onclick="return false;" class="'.$base.'_view_btn btn inline-action">'.lg_admin_mailboxes_view.'</a>

    <div id="'.$base.'_savemsg" style="display:none;" class="savemsg">'.lg_admin_mailboxes_savetoview.'</div>

	'.($subject ? '<input type="hidden" name="'.$base.'_subject" id="'.$base.'_subject" value="'.formClean($templates[$base.'_subject']).'" />' : '').'
	<input type="hidden" name="'.$base.'_html" id="'.$base.'_html" value="'.formCleanHtml($templates[$base.'_html']).'" />
	<input type="hidden" name="'.$base.'" id="'.$base.'" value="'.formClean($templates[$base]).'" />

	<div id="'.$base.'_view" style="display:none; padding: 20px;">

		<div class="tab_wrap">
			<ul class="tabs '.$base.'tabs_view">
				'.(! $htmlhide ? '<li class="noicon"><a href="#'.$base.'_tab_view_html"><span>'.lg_htmlversion.'</span></a></li>' : '').'
				<li class="noicon"><a href="#'.$base.'_tab_view_text"><span>'.lg_textversion.'</span></a></li>
				'.($subject ? '<li class="noicon"><a href="#'.$base.'_tab_view_subject"><span>'.lg_subjectline.'</span></a></li>' : '').'
			</ul>

			'.(! $htmlhide ? '
			<div name="'.$base.'_tab_view_html" id="'.$base.'_tab_view_html">
					<iframe id="'.$base.'_iframe" src="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'email.template.preview', 'xMailbox' => $xmailbox, 'template' => $base.'_html', 'type' => 'html']).'" width="100%" height="300" frameborder="0" scrolling="auto" style="background-color:#fff;border:3px solid #555;height:325px;"></iframe>
			</div>' : '').'

			<div name="'.$base.'_tab_view_text" id="'.$base.'_tab_view_text">
					<iframe id="'.$base.'_iframe" src="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'email.template.preview', 'xMailbox' => $xmailbox, 'template' => $base]).'" width="100%" height="300" frameborder="0" scrolling="auto" style="background-color:#fff;border:3px solid #555;height:325px;"></iframe>
			</div>

			'.($subject ? '
			<div name="'.$base.'_tab_view_subject" id="'.$base.'_tab_view_subject">
				'.$templates[$base.'_subject'].'
			</div>' : '').'
		</div>
	</div>

	<div id="'.$base.'_edit" style="display:none; padding: 20px;">

		<div class="tab_wrap" style="min-width:400px;">
			<ul class="tabs '.$base.'tabs">
				'.(! $htmlhide ? '<li class="noicon"><a href="#'.$base.'_tab_html"><span>'.lg_htmlversion.'</span></a></li>' : '').'
				<li class="noicon"><a href="#'.$base.'_tab_text"><span>'.lg_textversion.'</span></a></li>
				'.($subject ? '<li class="noicon"><a href="#'.$base.'_tab_subject"><span>'.lg_subjectline.'</span></a></li>' : '').'
			</ul>

			'.(! $htmlhide ? '
			<div name="'.$base.'_tab_html" id="'.$base.'_tab_html">
					<textarea name="ta_'.$base.'_html" id="ta_'.$base.'_html" style="width:500px;height:300px;">'.formCleanHtml($templates[$base.'_html']).'</textarea>
					<br />'.tagDrop('ta_'.$base.'_html', $cust_tags_body).'
			</div>' : '').'

			<div name="'.$base.'_tab_text" id="'.$base.'_tab_text">
					<textarea name="ta_'.$base.'" id="ta_'.$base.'" style="width:95%;height:300px;">'.formClean($templates[$base]).'</textarea>
					<br />'.tagDrop('ta_'.$base, $cust_tags_body).'
			</div>

			'.($subject ? '
			<div name="'.$base.'_tab_subject" id="'.$base.'_tab_subject">
				<input type="text" id="ta_'.$base.'_subject" name="ta_'.$base.'_subject" value="'.(! empty($templates[$base.'_subject']) ? $templates[$base.'_subject'] : '').'" size="30" style="width:500px;" />
				<br />'.tagDrop('ta_'.$base.'_subject', $cust_tags_subject).'
			</div>' : '').'
		</div>

		'.($populate ? '
		<a href="" class="btn inline-action" style="position:absolute;top:14px;right:20px;" onclick="$(\'ta_'.$base.'_subject\').value = $F(\'et_default_'.$base.'_subject\');$(\'ta_'.$base.'_html\').value = $F(\'et_default_'.$base.'_html\');$(\'ta_'.$base.'\').value = $F(\'et_default_'.$base.'\');return false;">'.lg_admin_mailboxes_insertdefault.'</a>
		<input type="hidden" id="et_default_'.$base.'_subject" name="et_default_'.$base.'_subject" value="'.hs_htmlspecialchars(trim($templates[$populate.'_subject'])).'" />
		<input type="hidden" id="et_default_'.$base.'_html" name="et_default_'.$base.'_html" value="'.hs_htmlspecialchars(trim($templates[$populate.'_html'])).'" />
		<input type="hidden" id="et_default_'.$base.'" name="et_default_'.$base.'" value="'.hs_htmlspecialchars(trim($templates[$populate])).'" />' : ''). '

        <div class="footer-btns">
            <button type="button" class="btn inline-action accent" onclick="insertTemplates(\''.$base.'\');">'.lg_done.'</button>
        </div>

	</div>
	</div>';

    return $out;
}

/********************************************
PUT DASH IN EMPTY STRING
*********************************************/
function fillEmpty($string)
{
    if (hs_empty($string)) {
        return '&mdash;';
    } else {
        return $string;
    }
}

function chartSetup($series1meta = 'data1meta')
{
    return '
		var series1_data = [];
		var series1_labels = [];
		var series1_color = (('.$series1meta.'.type == "column" || '.$series1meta.'.type == "bar") && '.$series1meta.'.stacked == false ? "rgba(206, 85, 32, 0.7)" : "'.(inDarkMode() ? '#ffffff' : '#3A2D23').'");
		var series1_color_text = "'.(inDarkMode() ? '#ffffff' : '#3A2D23').'";
		var series2_data = [];
		var series2_labels = [];
        var series2_color = "#87abe0";
        var series2_color_text = "#87abe0";
		var series3_data = [];
		var series3_labels = [];
        var series3_color = "#b0c8b0";
        var series3_color_text = "#b0c8b0";
		var yAxisGroup = [];
		var plotLineData = false;
        var final_series_group = [];
        var grid_line_color = "'.(inDarkMode() ? '#373b45' : '#e6e6e6').'";
	';
}

function chartTooltip($shared = true)
{
    return '{
				 formatter: function() {
					var s = "<b>"+ this.points[0].point.name +"</b>";

					$jq.each(this.points, function(i, point) {
						var tiplabel = point.series.name;
						//Remove count data, used by dash_requests
						if(tiplabel.search(/\(/) && tiplabel.search(/\)/)){
							var parts = tiplabel.split("(");
							tiplabel = parts[0];
						}

						s += "<br/>"+ $jq.strPad(tiplabel,13,".") +" "+ point.y;
					});

					return s;
				 },
				 shared: true,
				 backgroundColor: "#fff",
                 shadow: false,
				 borderRadius: 1,
				 borderColor: "#737373",
				 style: {
				 	color: "#3a2d23",
				 	padding: 12,
				 }
			  }';
}

function chartYAxisDefault($series1meta = 'data1meta', $label_offset = [0, 15])
{
    return '
		{
         gridLineColor: grid_line_color,
         lineColor: grid_line_color,
		 min: 0,
		 max: '.$series1meta.'.max,
		 title: {
			text: '.$series1meta.'.ylabel,
			style: {
				color: series1_color_text
			}

		 },
		 labels: {
			align: "left",
			style: {
				color: series1_color_text
			},
			x: '.$label_offset[0].',
			y: '.$label_offset[1].'
		 },
		 showFirstLabel: false,
		 plotLines: plotLineData,
		 allowDecimals: false
	  }
	';
}

function reportFolders($fs, $selected = '')
{
    $folderSel = '';
    if (! array_key_exists('My Reports', $fs)) {
        $folderSel = '<option value="'.lg_reports_myfolder.'">'.lg_reports_myfolder.'</option>';
    }
	if(is_array($fs) && !empty($fs)){
		foreach($fs AS $k=>$v){
		    if ($v['sFolder'] == '') continue;
			$folderSel .= '<option value="'.$v['sFolder'].'" '.selectionCheck($v['sFolder'],hs_htmlspecialchars($selected)).'>'.$v['sFolder'].'</option>';
		}
	}
	return $folderSel;
}

function chartSave($resourceid, $fm, $folders, $buttons, $pg, $show, $report){
    $out = '<div id="options_tab" name="data_tab" style="">
        <div class="card padded">
            <div class="fr">
                <div class="label">
                    <label class="req" for="fm_report">' . lg_reports_name . '</label>
                    <div class="info">'.lg_reports_filterconditions.'</div>
                </div>
                <div class="control">
                    <input type="text" name="fm_report" id="fm_report" value="' . formClean($fm['sReport']) . '" size="30" class="' . errorClass('fm_report') . '">' . errorMessage('fm_report') . '
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sFilterFolder">' . lg_reports_folder . '</label>
                </div>
                <div class="control">
                    <div style="display:flex;align-items:center;">
                        <select name="sFilterFolder" id="sFilterFolder" style="flex:1;">
                           ' . reportFolders($folders, $fm['sFolder']) . '
                        </select>
                        <a href="javascript:addFolder(\'' . hs_jshtmlentities(lg_report_myreports) . '\',\'sFilterFolder\');" class="btn inline-action">' . lg_filter_reports_addfolder . '</a>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sFilterFolder">' . lg_report_perms . '</label>
                </div>
                <div class="control">
                    ' . permSelectUI($fm['fType'],$fm['fPermissionGroup'],
                            $GLOBALS['DB']->GetCol('SELECT xPerson FROM HS_Report_People WHERE xReport = ?', array($resourceid)),
                            $GLOBALS['DB']->GetCol('SELECT xGroup FROM HS_Report_Group WHERE xReport = ?', array($resourceid))
                        )
                    . '
                </div>
            </div>

            '.chartEmail($report).'
        </div>

		<input type="hidden" name="sPage" value="'.$pg.'" />
		<input type="hidden" name="sShow" value="'.$show.'" />
	';

    $out .= $buttons;

    $out .= '
    <script type="text/javascript">
		function deleteReport(report_id){
			$jq.ajax({
				type: "POST",
				url: "'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'report_delete']).'",
				data: { xReport: report_id }
			})
			.done(function( msg ) {
				goPage("'.route('admin', ['pg' => 'todayboard']).'");
			});
		}
	</script>';
	return $out;
}

function chartEmail($report)
{
    $data = $report->tData;

    if ($data['sDataRange'] == '') {
        $data['sDataRange'] = 'yesterday';
    }
    $selectedStaff = explode(',', $report->fSendToStaff);
    $allUsers = apiGetAllUsers(0, '');
    $out = displayContentBoxTop(lg_reports_email_label);
    $out .= '
        <div class="fr">
            <div class="label">
                <label class="" for="fEmail">' . lg_reports_email_on . '</label>
            </div>
            <div class="control">
                <input type="checkbox" name="fEmail" id="fEmail" class="checkbox" value="1" ' . checkboxCheck(1, $report->fEmail) . '>
                <label for="fEmail" class="switch"></label>
            </div>
        </div>

        <div class="email-options" style="">
            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fSendEvery">' . lg_reports_email_schedule . '</label>
                </div>
                <div class="control">
                    <div style="display:flex;align-items:center;">
                        <select name="fSendEvery" id="fSendEvery" style="width: 180px;">
                            <option value="daily" '.(($report->fSendEvery == "daily") ? "selected=selected" : "") .'>Every Day</option>
                            <option value="weekly" '.(($report->fSendEvery == "weekly") ? "selected=selected" : "") .'>Every Week</option>
                            <option value="monthly" '.(($report->fSendEvery == "monthly") ? "selected=selected" : "") .'>Monthly</option>
                        </select>
                        <span id="send_on_days" style="display: flex;align-items: center;">
                            <label class="datalabel req" for="fSendDay" style="display: inline;width: 30px;text-align:center;">on</label>
                            <select name="fSendDay" id="fSendDay">
                                <option value="Monday" '.(($report->fSendDay == "Monday") ? "selected=selected" : "") .'>Monday</option>
                                <option value="Tuesday" '.(($report->fSendDay == "Tuesday") ? "selected=selected" : "") .'>Tuesday</option>
                                <option value="Wednesday" '.(($report->fSendDay == "Wednesday") ? "selected=selected" : "") .'>Wednesday</option>
                                <option value="Thursday" '.(($report->fSendDay == "Thursday") ? "selected=selected" : "") .'>Thursday</option>
                                <option value="Friday" '.(($report->fSendDay == "Friday") ? "selected=selected" : "") .'>Friday</option>
                                <option value="Saturday" '.(($report->fSendDay == "Saturday") ? "selected=selected" : "") .'>Saturday</option>
                                <option value="Sunday" '.(($report->fSendDay == "Sunday") ? "selected=selected" : "") .'>Sunday</option>
                            </select>
                        </span>
                        <label class="datalabel req monthly_label" for="fSendTime" style="display: none;margin:0 8px;">on the last day at</label>
                        <label class="datalabel req at_label" for="fSendTime" style="display: inline;width: 30px;text-align:center;">at</label>
                        <select name="fSendTime" id="fSendTime">
                            '.hs_ShowBizHours(($report->fSendTime) ? $report->fSendTime : 8).'
                        </select>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sDataRange">' . lg_reports_email_data_range . '</label>
                </div>
                <div class="control">
                    <div style="display:flex;align-items:center;">
                        <select name="sDataRange" id="sDataRange">
                            <option value="today" '.(($data['sDataRange'] == "today") ? "selected=selected" : "") .'>Today</option>
                            <option value="yesterday" '.(($data['sDataRange'] == "yesterday") ? "selected=selected" : "") .'>Yesterday</option>
                            <option value="last7days" '.(($data['sDataRange'] == "last7days") ? "selected=selected" : "") .'>Last 7 Days</option>
                            <option value="last30days" '.(($data['sDataRange'] == "last30days") ? "selected=selected" : "") .'>Last 30 Days</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fSendToStaff">' . lg_reports_email_to . '</label>
                </div>
                <div class="control">
                    '.renderSelectMulti('fSendToStaff', rsToArray($allUsers, 'xPerson', false), $selectedStaff, '', 'fSendToStaff').'
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="fSendToStaff">' . lg_reports_email_external . '</label>
                    <div class="info">'.lg_reports_email_external_txt.'</div>
                </div>
                <div class="control">
                    <textarea name="fSendToExternal" placeholder="john@example.org, jane@example.org" style="">'.$report->fSendToExternal.'</textarea>
                    <span>'.errorMessage('fSendToExternal').'</span>
                </div>
            </div>
        </div>
    '.displayContentBoxBottom();

    $out .= '<script>
    $jq(document).ready(function(){
        showHideRecurring();
        $jq("#fEmail").on("click", function(e){
            showHideRecurring();
        });
        function showHideRecurring() {
            if($jq("#fEmail").is(":checked")) {
                $jq(".email-options").show();
            } else {
                $jq(".email-options").hide();
            }
        }
        function showDaily() {
            $jq("#send_on_days").hide();
            $jq(".monthly_label").hide();
            $jq(".at_label").show();
        }
        function showWeekly() {
            $jq("#send_on_days").show();
            $jq(".monthly_label").hide();
            $jq(".at_label").show();
        }
        function showMonthly() {
            $jq("#send_on_days").hide();
            $jq(".at_label").hide();
            $jq(".monthly_label").show().css("display", "inline");
        }
        $jq("#send_on_days").hide();
        $jq("#send_on_date").hide();
        '. (($report->fSendEvery == "monthly") ? "showMonthly();" : "") .'
        '. (($report->fSendEvery == "weekly") ? "showWeekly();" : "") .'
        '. (($report->fSendEvery == "daily") ? "showDaily();" : "") .'
        $jq("#fSendEvery").on("change", function(){
            if ($jq(this).val() == "daily") {
                showDaily();
            } else if ($jq(this).val() == "monthly") {
                showMonthly();
            } else {
                showWeekly();
            }
        });
    });
    </script>';
    return $out;
}

/********************************************
An AJAX request for use inline in an onClick
*********************************************/
function hsAJAXinline($function, $action, $params, $method = 'get')
{
    return "new Ajax.Request('".action('Admin\AdminBaseController@adminFileCalled')."',{method:'".$method."',parameters:'pg=ajax_gateway&action=".$action.'&'.$params."' + '&rand=' + ajaxRandomString(),onComplete:".$function.'});';
}

/********************************************
Render the page top header
*********************************************/
function renderPageheader($title, $menu='', $count=false, $nomargin=false){
    return '
        <div class="page-header '.($nomargin == true ? 'nomargin' : '').'">
            <div class="page-header-title">
                '.($count !== false ? "<div class=\"page-header-count\">{$count}</div>" : '').'
                '.$title.'
            </div>
            <div class="page-header-menu">
                '.$menu.'
            </div>
        </div>
    ';
}

/********************************************
Render inner page header
*********************************************/
function renderInnerPageheader($title, $desc=''){
    return '
        <div class="page-header inner">
            <div class="page-header-title">
                '.$title.'
            </div>
            <div class="page-header-desc">
                '.$desc.'
            </div>
        </div>
    ';
}
/**
 * Create a block heading for when the desc is super long.
 * @param string $title
 * @param string $desc
 * @return string
 */
function renderInnerPageheaderBlock($title, $desc=''){
    return '
        <div class="page-header" style="height: auto; display:block; padding-bottom: 20px;">
            <div class="page-header-title">
                '.$title.'
            </div>
            <div class="desc">
                '.$desc.'
            </div>
        </div>
    ';
}

/*****************************************
DISPLAY TABLE DATA
//string
array(
    'type'=>'string'
    'label'=>'string label'
    'sort'=>1 (optional - not array fields)
    'fields'=>'fields' //can be array
    'chars'=>0 // number of chars to show. 0 is all.
    'function'=>'name' //name of function to apply to string
    'hideflow'=>true //optionally hide text overflow
    'nowrap'=>true //optionally td nowrap
)

//bool
array(
    'type'=>'bool'
    'label'=>'string label'
    'sort'=>1 (optional)
    'fields'=>'fields' //can be array
    'img'=>'path' (optional)
)

//lookup
array(
    'type'=>'lookup'
    'label'=>'string label'
    'sort'=>1 (optional)
    'fields'=>'fields' //can be array
    'dataarray'=>$array // must be array - key is used to find output
)

//number
array(
    'type'=>'number'
    'label'=>'string label'
    'sort'=>1 (optional)
    'fields'=>'fields' //can be array
    'format'=>'num format string'
)

//checkbox
array(
    'type'=>'checkbox'
    'label'=>'string label'
    'sort'=>1 (optional)
    'code'=>'checkbox code %s'
    'fields'=>'fields' //can be array
)

//link
array(
    'type'=>'link'
    'label'=>'string label'
    'sort'=>1 (optional)
    'sorton'=>'db column' (optional)
    'fields'=>''
    'code'=>'link code %s'
    'linkfields'=>array('fields') //should always be an array
)
*****************************************/
function recordSetTable(&$rs, $fields, $options = [], $basepgurl = '')
{
    global $user;

    //use to differentiate multiple recordsettables on one page
    static $groupid = 0;
    $groupid++;
    // setup options
    $rcount = 0;
    $title = isset($options['title']) ? $options['title'] : '';
    $title_right = isset($options['title_right']) ? $options['title_right'] : false;
    $colcount = count($fields);
    $sortable = isset($options['sortable']);
    $sf = isset($options['sortablefields']) ? $options['sortablefields'] : false; //array where first elem is ID and second is name
    $sortable_callback = isset($options['sortable_callback']) ? $options['sortable_callback'] : '';
    $sorttitle = $title.': '.lg_sort;
    $paginate = isset($options['paginate']) ? $options['paginate'] : false;
    $paginate_ct = isset($options['paginate_ct']) ? $options['paginate_ct'] : hs_setting('cHD_MAXSEARCHRESULTS');
    $paginate_sim = isset($options['paginate_sim']) ? $options['paginate_sim'] : false;
    $onlyshow = isset($options['onlyshow']) ? $options['onlyshow'] : false; //only show X number of results in the table. Useful when you want the recordcount to still show all the matches
    $summary = isset($options['summary']) ? $options['summary'] : '';
    $sortby = isset($options['sortby']) ? $options['sortby'] : '';
    $sortord = isset($options['sortord']) ? $options['sortord'] : '';
    $groupby = isset($options['groupby']) ? $options['groupby'] : '';
    $groupord = isset($options['groupord']) ? $options['groupord'] : '';
    $dellink = isset($options['dellink']) ? $options['dellink'] : '';
    $rsslink = isset($options['rsslink']) ? $options['rsslink'] : '';
    $printlink = isset($options['printlink']) ? $options['printlink'] : '';
    $exportlink = isset($options['exportlink']) ? $options['exportlink'] : '';
    $showcount = isset($options['showcount']) ? $options['showcount'] : false;
    $showdeleted = isset($options['showdeleted']) ? $options['showdeleted'] : false;
    $filterid = isset($options['filterid']) ? $options['filterid'] : false; //Is a filter so show options
    $filter_creator = isset($options['filter_creator']) ? $options['filter_creator'] : 0;
    $from_run_filter = isset($options['from_run_filter']) ? $options['from_run_filter'] : false; //flag used to turn off sorting on ajax filter requests
    $popup = isset($options['popup']) ? $options['popup'] : false;
    $icon = isset($options['icon']) ? $options['icon'] : 'datatable.gif';
    $width = isset($options['width']) ? $options['width'] : '100%';
    $no_table_borders = isset($options['no_table_borders']);
    $footer = isset($options['footer']) ? $options['footer'] : '';
    $showing = isset($options['showing']) ? $options['showing'] : false;
    $page = isset($options['page']) ? $options['page'] : false;
    $rightfooter = isset($options['rightfooter']) ? $options['rightfooter'] : '';
    $checkbox = isset($options['checkbox']) ? $options['checkbox'] : false; //to create checkbox table pass in column name to use as checkbox id
    $rowsonly = isset($options['rowsonly']) ? $options['rowsonly'] : false;
    $noresults = isset($options['noresults']) ? $options['noresults'] : lg_noresults;
    $actionmenu = isset($options['actionmenu']) ? $options['actionmenu'] : false;
    $hideOverFlow = isset($options['hideOverFlow']) ? $options['hideOverFlow'] : false;
    $labelcolors = [];
    $tbborders = 'tcell';
    $so = 'DESC'; //sort order
    $tso = ''; 	// temp sort order
    $simg = '';		// sort image
    $tb = '';
    $current_group = '';		// The currently active group if using grouping

    // On filters with viewer support we need to add in an extra column to the count to account for it
    if ($checkbox) {
        $colcount = $colcount + 1;
    }

    if (! $rowsonly) {
        $tb .= $checkbox ? '<form action="'.$basepgurl.'" method="POST" style="padding:0px;margin:0px;" onSubmit="return checkform('.$groupid.');" id="rsform_'.$groupid.'">'.csrf_field() : '';

        if ($filterid) {
            $linktodefault = ($user['sWorkspaceDefault'] != $filterid ? '<li class="tooltip-menu-divider"><a href="" onclick="setDefaultWorkspace();return false;"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_workspace_mkdefault).'</span></a></li>' : '');
            $topmenu = '<div class="table-top-menu">
                            '.(is_numeric($filterid) && (isAdmin() || $filter_creator == $user['xPerson']) ? '<a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests', 'filterid' => $filterid]).'">'.hs_jshtmlentities(lg_workspace_edit).'</a>' : '').'
                            <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace.stream', 'show' => $filterid]).'">'.lg_workspace_stream.'</a>
                            <a href="" id="table-top-menu-options" onclick="return false;">'.lg_workspace_filter_options.'</a>
                        </div>
                        <div id="filter_options_tmpl" style="display:none;">
                            <ul class="tooltip-menu">
                                '.(is_numeric($filterid) && (isAdmin() || $filter_creator == $user['xPerson']) ? '<li class="tooltip-menu-divider"><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'filter.requests', 'filterid' => $filterid, 'delete' => 1]).'" onclick="return hs_confirm(\''.hs_jshtmlentities(lg_workspace_deleteconf).'\',this.href);"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_workspace_delete).'</span></a></li>' : '').'
                                '.(in_array($filterid, ['myq','inbox']) ? '<li><a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'workspace.customize', 'area' => $filterid]).'"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_workspace_customize).'</span></a></li>' : '').'
                                '.($rsslink ? '<li class="tooltip-menu-divider"><a href="'.$rsslink.'" target="_blank"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_workspace_rss).'</span></a></li>' : '').'
                                '.($exportlink ? '<li><a href="'.$exportlink.'"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_workspace_export).'</span></a></li>' : '').'
                                '.$linktodefault.'
                            </ul>
                        </div>
                        <script type="text/javascript">
                            new Tip("table-top-menu-options", $("filter_options_tmpl"),{
                                    title: "",
                                    border: 0,
                                    radius: 0,
                                    className: "hstinytipfat",
                                    stem: false,
                                    showOn: "click",
                                    hideOn: false,
                                    hideAfter: 1,
                                    width: "auto",
                                    offset:{x:0,y:8},
                                    hook: { target: "bottomRight", tip: "topRight" }
                                });

                            // See if any rows are checked from a refresh or back button.
                            $jq(document).ready(checkRows);
                        </script>
                        ';
        }

        if($sortable) $topmenu .=  '<div class="table-top-menu"><a href="javascript:HS_Effects.RsSetOrder(\'rsgroup_'.$groupid.'\',\'box_id_'.md5($sorttitle).'\');">'.lg_setorder.'</a></div>';
        if($title_right) $topmenu .= '<div class="table-top-menu">'.$title_right.'</div>';

        if (! empty($summary)) {
            $tb .= displaySystemBox($summary);
        }

        if($title){
            $tb .= renderPageHeader($title, $topmenu, ($showcount !== false && is_object($rs) ? (is_numeric($showcount) ? $showcount : $rs->RecordCount()) : false));
        }

        $tb .= '<table cellpadding="0" cellspacing="0" border="0" class="tablebody '.($no_table_borders ? 'no_borders' : '').'" width="'.$width.'" id="rsgroup_'.$groupid.'">';
    }

    if (is_object($rs) && $rs->RecordCount() > 0) {
        if (! $rowsonly) {
            $tb .= '<tr class="tableheaders" valign="bottom">';
            if ($checkbox) {
                $tb .= '
                    <th width="5" class="viewing-header"></th>
                    <th style="text-align:left;">
                    	<label style="display:flex;">
                    		<input type="checkbox" class="form-checkbox groupcheckbox" name="checkallbox_top" id="checkallbox_top" value="" onClick="return checkUncheckRsAll(this.id);">
                    	</label>
                    </th>';
            }
            foreach ($fields as $val) {
                $tb .= '<td scope="col" id="'.$groupid.'_table_header_'.$val['fields'].'">';
                if (isset($val['sort']) && $val['sort'] == 1 && ! $from_run_filter) {
                    if (trim($sortby) == $val['fields'] && trim($sortord) == $so) {			// switch to asc
                        $tso = 'ASC';
                        $simg = '<img src="'.static_url().'/static/img5/navigate-down.svg" alt="" style="height: 14px;margin-left: 2px;">';
                        $path = '&sortby='.$val['fields'].'&sortord='.$tso;
                    } elseif (trim($sortby) == $val['fields'] && trim($sortord) != $so) {		// back to page start (desc)
                        $tso = $so;
                        $simg = '<img src="'.static_url().'/static/img5/navigate-up.svg" alt="" style="height: 14px;margin-left: 2px;">';
                        $path = '';
                    } else {														// field not currently in sort mode
                        $tso = $so;
                        $simg = '';
                        $path = '&sortby='.$val['fields'].'&sortord='.$tso;
                    }
                    $tb .= '<a href="'.$basepgurl.$path.'" class="tableheaderlink">'.$val['label'].'</a> '.$simg;
                } else {
                    $tb .= $val['label'];
                }
                $tb .= '</td>';
            }
            if ($checkbox) { //small column for holding menu
                $tb .= '<td></td>';
            }
            $tb .= '</tr>';
        }

        while ($row = $rs->FetchRow()) {
            $rowclass = ($rcount % 2) ? 'tablerowon' : 'tablerowoff';

            $rcount++;

            //Paginated data not done via filter always returns all results so this stops the looping after the pagination level is reached
            if ($paginate_sim == true && $paginate !== false) {
                if ($rcount <= $paginate || $rcount > ($paginate + $paginate_ct)) {
                    continue;
                }
            }
            //Might work if we go between rcount!

            //handle grouping
            if (! empty($groupby)) {
                //Define grouping name and label
                if (substr($groupby, 0, 4) == 'time') {
                    if ($groupby == 'time:today-yesterday') {
                        if (strftime_win32('%d%m%y', $row['dtGMTOpened']) == strftime_win32('%d%m%y')) {
                            $grouping_name = lg_lookup_filter_timegroup_today;
                        } elseif (strftime_win32('%d%m%y', $row['dtGMTOpened']) == strftime_win32('%d%m%y', (time() - 86400))) {
                            $grouping_name = lg_lookup_filter_timegroup_yesterday;
                        } else {
                            $grouping_name = lg_lookup_filter_timegroup_older;
                        }
                    } elseif ($groupby == 'time:hourly') {
                        $timebyhour = mktime(date('H', $row['dtGMTOpened']), 0, 0, date('n', $row['dtGMTOpened']), date('j', $row['dtGMTOpened']), date('Y', $row['dtGMTOpened'])); //convert to one the hour
                        $grouping_name = strftime_win32(hs_setting('cHD_DATEFORMAT'), $timebyhour);
                    } elseif ($groupby == 'time:daily') {
                        $grouping_name = strftime_win32(hs_setting('cHD_SHORTDATEFORMAT'), $row['dtGMTOpened']);
                    } elseif ($groupby == 'time:monthly') {
                        $grouping_name = strftime_win32('%B %Y', $row['dtGMTOpened']);
                    }

                    $grouping_label = lg_lookup_filter_timegroup_label;
                } else { //This handles everything except time (above)
                    $grouping_name = (empty($row[$groupby]) ? '-' : $row[$groupby]);
                    $grouping_label = isset($GLOBALS['filterCols'][$groupby]['label2']) ? $GLOBALS['filterCols'][$groupby]['label2'] : $GLOBALS['filterCols'][$groupby]['label'];
                    //Special cases
                    switch ($groupby) {
                        case 'fOpenedVia': $grouping_name = $GLOBALS['openedVia'][$grouping_name];

break;
                        case 'fOpen': $grouping_name = ($grouping_name == 1 ? lg_isopen : lg_isclosed);

break;
                        case 'sPersonAssignedTo':
                            $grouping_label = lg_lookup_filter_assignedto;

                            break;
                        case 'sStatus':
                            $grouping_label = lg_lookup_filter_status;

                            break;
                    }

                    //Handle custom date fields
                    if (isset($GLOBALS['timeGroupings'][$groupby]) && ! empty($row[$groupby])) {
                        $grouping_name = strftime_win32(hs_setting('cHD_SHORTDATEFORMAT'), $row[$groupby]);
                    }
                }

                if ($current_group != $grouping_name || $rcount == 0) {
                    $current_group = $grouping_name;
                    $unique_grouping_id = 'grouping'.rand(11111111, 99999999); //Used to find check boxes and mark/unmark
                    $tb .= '<tr class="table-group">';
                    $tb .= '<td class="table-checkbox"><label style="display:flex;"><input type="checkbox" class="form-checkbox groupcheckbox" id="groupcheckbox_'.$unique_grouping_id.'" value="1" onclick="return checkUncheckRequestGroup(\''.$unique_grouping_id.'\');" /></label></td>';
                    $tb .= '<td colspan="'.$colcount.'">
								<div class="yui-g">
									<div class="yui-u first">
										<span class="table-group-value">'.$grouping_name.'</span>
									</div>
									<div class="yui-u" align="right">
										<span class="table-group-label">'.$grouping_label.'</span>
									</div>
								</div>
							</td>';
                    $tb .= '</tr>';
                }
            }
            //handle urgent rows
            if (isset($row['fUrgent']) && $row['fUrgent'] == 1) {
                $rowclass .= ' urgentrow';
            }

            if ($checkbox) {
                $tb .= '<tr class="item-row '.$rowclass.' row-'.$row['xRequest'].'" id="tr-'.$row['xRequest'].'">';
                $tb .= '<td class="viewing" id="viewing-'.$row['xRequest'].'" width="5"></td>';
                $tb .= '<td class="table-checkbox" id="td-'.$row['xRequest'].'" style="position:relative;">
    						<div class="display:flex;">
    							<input type="checkbox" class="form-checkbox '.$unique_grouping_id.'" name="checktable[]" value="'.$row[$checkbox].'" onClick="rowChecked(\''.$row['xRequest'].'\');" id="'.$row['xRequest'].'_checkbox">
    						</div>
						</td>';
            } else {
                $tb .= '<tr class="'.$rowclass.'">';
            }
            foreach ($fields as $field_key=>$val) {
                $val['width'] = isset($val['width']) ? $val['width'] : '';
                if ($hideOverFlow) { // don't do the onclick.
                    $overflow = isset($val['hideflow']) ? '<table class="hideflow-table"><tr><td class="js-request">' : '';
                } else {
                    $overflow = isset($val['hideflow']) ? '<table class="hideflow-table hand"><tr><td class="js-request" onClick="'.($row['xRequest'] ? 'showOverflow('.$row['xRequest'].');' : '').'">' : '';
                }
                $overflowend = isset($val['hideflow']) ? '</td></tr></table>' : '';
                $nowrap = isset($val['nowrap']) ? ' style="white-space: nowrap;"' : '';

                //If there's a custom field that could have color labels let's check to see if they do.
                //Only run this once per field that could have it.
                if ($val['fieldType'] == 'select' && ! isset($labelcolors[$field_key])) {
                    $custom_id = str_replace('Custom', '', $field_key);
                    $labelcolors[$field_key] = hs_unserialize($GLOBALS['customFields'][$custom_id]['listItemsColors']);
                }

                //If an overflow col type wordwrap any long strings so they don't pull table
                if ($val['hideflow']) {
                    $str = $row[$val['fields']];
                    if ($val['fields'] == 'tNote') {
                        $str = strip_tags($str);
                    }
                    // Removing wordwrap so it's handled by the css.
                    $row[$val['fields']] = $str;
                }

                //clickable columns
                if (isset($val['click']) && ! isset($val['hideflow'])) {
                    $clickable = 'class="hand" id="clickitem_'.$groupid.'_'.$rcount.'_'.$row[$val['clickarg']].'" onclick="'.sprintf($val['click'], $row[$val['clickarg']]).'"';
                } else {
                    $clickable = '';
                }

                switch ($val['type']) {
                    case 'string':
                        $charlen = isset($val['chars']) ? $val['chars'] : 0;
                        $right = isset($val['align-right']) ? 'align="right"' : '';
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'" '.$right.' '.$nowrap.'>'.$overflow;
                        $tempv = recordSetStringItem($row, $val['fields'], $charlen, (isset($val['default']) ? $val['default'] : '-'));
                        //Handle color lables on predefined list custom fields
                        $colorkey = utf8RawUrlEncode($tempv);
                        if ($val['fieldType'] == 'select' && isset($labelcolors[$field_key][$colorkey]) && $labelcolors[$field_key][$colorkey] != '') {
                            $tempv = '<span class="color-label" style="background-color:'.$labelcolors[$field_key][$colorkey].';">'.$tempv.'</span>';
                        }
                        //Handle single arg functions
                        if (! isset($val['function_args']) && ($val['fieldType'] == 'date' || $val['fieldType'] == 'datetime')) {
                            $tb .= ! empty($val['function']) && $tempv != 0 ? call_user_func($val['function'], $tempv) : '-';
                        } elseif (! isset($val['function_args']) && in_array($val['function'], ['hs_showShortDate', 'hs_showDate'])) {
                            $tb .= !empty($val['function']) && $tempv != 0 ? call_user_func($val['function'], $tempv) : '-';
                        } elseif (! isset($val['function_args'])) {
                            $tb .= ! empty($val['function']) ? call_user_func($val['function'], $tempv) : $tempv;
                        }
                        //Handle functions with multiple args
                        if (! empty($val['function']) && isset($val['function_args'])) {
                            $args = [];
                            foreach ($val['function_args'] as $arg) {
                                $args[] = $row[$arg];
                            }
                            $tb .= call_user_func_array($val['function'], $args);
                        }
                        $tb .= $overflowend;

                        break;
                    case 'bool':
                        $img = isset($val['img']) ? $val['img'] : '';
                        $noimg = isset($val['noimg']) ? $val['noimg'] : '';
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'" align="center">';
                        $tb .= recordSetBoolItem($row[$val['fields']], $img, $noimg, $clickable);

                        break;
                    case 'lookup':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';
                        $tb .= recordSetLookupItem($row[$val['fields']], $val['dataarray']);

                        break;
                    case 'openedvia':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';
                        $tb .= recordSetOpenedviaItem($row[$val['fields']]);

                        break;
                    case 'number':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';

                        $value = recordSetNumberItem($row[$val['fields']], $val['decimals']);

                        if (isset($val['function'])) {
                            $tb .= $val['function']($value);
                        } else {
                            $tb .= $value;
                        }

                        break;
                    case 'checkbox':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';
                        $tb .= recordSetCheckboxItem($val['code'], $row[$val['fields']]);

                        break;
                    case 'json':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';
                        $tb .= '<pre style="margin: 0;"><code>'.$row[$val['fields']].'</code></pre>';

                        break;
                    case 'html':
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'">';
                        $tb .= $row[$val['fields']];

                        break;
                    case 'link':
                        $right = isset($val['align-right']) ? 'align="right"' : '';
                        $val['hideifempty'] = isset($val['hideifempty']) ? $val['hideifempty'] : false;
                        $tb .= '<td class="'.$tbborders.'" width="'.$val['width'].'" '.$right.' '.$nowrap.'>';

                        if ($val['hideifempty'] == true && empty($row[$val['fields']])) {
                            $tb .= '';
                        } else {
                            if ($popup) {
                                $val['code'] = str_replace('target=""', 'target="_blank"', $val['code']);
                            }
                            if (! isset($val['normallink'])) {
                                $tb .= recordSetLinkItem($row, $val['code'], $val['linkfields']);
                            } else {
                                $tb .= recordSetNormalLinkItem($row, $val['code'], $val['linkfields']);
                            }
                        }

                        break;
                }
                $tb .= '</td>';
            }
            if($actionmenu){
                $tb .= '
                    <td style="width:1px;position:relative;">
                        <div class="action-menu">
                                <a href="" onclick="showOverflow(\''.$row['xRequest'].'\');return false;" title="'.lg_preview.'"><img src="'.static_url().'/static/img5/loupe.svg" /></a>
                                '.(perm('fCanManageSpam') ? '<a href="" onclick="simplemenu_action(\'spam\',\''.$row['xRequest'].'\');return false;" title="'.lg_spam.'"><img src="'.static_url().'/static/img5/ban-solid.svg" /></a>' : '').'
                                '.(perm('fCanManageTrash') ? '<a href="" onclick="simplemenu_action(\'trash\',\''.$row['xRequest'].'\');return false;" title="'.lg_trash.'"><img src="'.static_url().'/static/img5/trash-solid.svg" /></a>' : '').'
                        </div>
                    </td>
                </tr>';
            }
            //Break out if only showing a certain number
            if ($onlyshow && $rcount == $onlyshow) {
                break;
            }
        }
    } else {
        if (! $rowsonly) {
            $tb .= '<tr><td colspan="'.($colcount + 1).'"><div class="table-no-results">'.$noresults.'</div></td></tr>';
        }
    }

    if (! $rowsonly) {
        //Page bar
        if (is_object($rs) && $paginate !== false && $showcount >= $paginate_ct) {
            $tb .= '<tr id="recordset-load-more-wrap"><td class="tablefooter" colspan="'.($colcount + 1).'" align="center" style="height:50px;">';
            $tb .= '<a href="'.$basepgurl.'&sortby='.$sortby.'&sortord='.$sortord.'&ajax=1&rowsonly=1&showdeleted='.$showdeleted.'" class="btn" style="float:none;width:400px;" id="recordset-load-more" onclick="loadRows();return false;">'.lg_loadmore.'</a>';
            $tb .= '<input type="hidden" name="paginate_count" id="paginate_count" value="'.($paginate + $paginate_ct).'" />';
            $tb .= '
				<script type="text/javascript">
					function loadRows(){
						//Prevent live refreshing after button pushed
						formHasChanged = true;

						$("recordset-load-more").update("'.lg_loading.'");

						new Ajax.Request($("recordset-load-more").href,
						{
							method: 	"get",
							parameters: {paginate: $F("paginate_count")},
							onComplete: function(transport){
								eval("var response = " + transport.responseText);
								$("recordset-load-more-wrap").insert({before:response.html});

								//If this last paginate covered all items then hide load more button
								if($F("paginate_count") >= '.$showcount.'){
									$("recordset-load-more-wrap").hide();
								}

								$("recordset-load-more").update("'.lg_loadmore.'");
							}
						});

						//Add more for next page
						$("paginate_count").value = parseInt($F("paginate_count")) + '.$paginate_ct.';
					}
				</script>
			';
            $tb .= '</td></tr>';

            /*
            $tb .= '<tr><td class="tablefooter" colspan="'.($colcount + 1).'" align="center">';
            $prev = $basepgurl.'&sortby='.$sortby.'&sortord='.$sortord.'&paginate='.max(0,$paginate-$paginate_ct);
            $next = $basepgurl.'&sortby='.$sortby.'&sortord='.$sortord.'&paginate='.($paginate + $paginate_ct);

            //Different logic for simulated pagination vs real
            $next_inactive = $paginate_sim ? (($paginate + $paginate_ct) >= $rs->RecordCount()) : ($paginate != 0 && $paginate != $rs->RecordCount());

            $tb .= '
                <div style="padding-left:'.($paginate_sim ? '9px' : '41px').';">
                <a href="'.$prev.'" class="btn thin thin-first '.($paginate == 0 ? 'thin-inactive' : '').'" '.($paginate == 0 ? 'onclick="return false;"' : '').'>&laquo; '.lg_prev.'</a>
                <a href="'.$next.'" class="btn thin thin-last '.($next_inactive ? 'thin-inactive' : '').'" '.($next_inactive ? 'onclick="return false;"' : '').'>'.lg_next.' &raquo;</a>
                </div>';

            //$tb .= ($paginate != 0 ? '<a href="'.$prev.'">'.lg_prev.'</a>' : lg_prev).' |
            //	   '.(is_object($rs) && $rs->RecordCount() == cHD_MAXSEARCHRESULTS ? '<a href="'.$next.'">'.lg_next.'</a>' : lg_next);
            $tb .= '</td></tr>';
            */
        }

        //Footer
        $tb .= '<tr><td class="tablefooter" colspan="'.($colcount + 1).'">';
        if (! empty($dellink)) {
            $tb .= $dellink;
            if ($rightfooter) {
                $tb .= '<div style="float:right;">'.$rightfooter.'</div>';
            }
        } elseif (! empty($footer)) {
            $tb .= '<table cellpadding="0" cellspacing="0" width="100%" style="margin:0px;padding:0px;"><tr>';

            $tb .= '<td>';
            $tb .= $footer;
            $tb .= '</td>';

            if ($rightfooter) {
                $tb .= '<td align="right">'.$rightfooter.'</td>';
            }
            $tb .= '</tr></table>';
        } elseif (! empty($rightfooter)) {
            $tb .= '<div style="padding:2px;padding-right:5px;float:right;">'.$rightfooter.'</div>';
        }
        $tb .= '</td></tr>';
        $tb .= '</table>';

        $tb .= $checkbox ? '</form>' : '';
        /*
        $tip_menu = '
            <div id="fast-menu-body" style="display:none;">
            <ul class="tooltip-menu">
                <li><a href="" onclick="showOverflow(\'%{reqid}\',\'fullhistory\',\''.hs_jshtmlentities(lg_loading).'\');return false;" class="tooltip-menu-img-base tooltip-menu-img-view"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_tm_view).'</span></a></li>';

        if($page == 'workspace' && $showing != "spam" && $showing != "trash" && $showing != "subscriptions" && $showing != "reminders"){
            if($showing == 'myq') $tip_menu .= '<li><a href="" onclick="rs_quickmenu_action(\'unread\',\'%{reqid}\');return false;" class="tooltip-menu-img-base tooltip-menu-img-unread"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_tm_markunread).'</span></a></li>';
            $tip_menu .= '<li><a href="" onclick="rs_quickmenu_action(\'spam\',\'%{reqid}\');return false;" class="tooltip-menu-img-base tooltip-menu-img-spam"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_tm_actionspam).'</span></a></li>';
            if(isAdmin()) $tip_menu .= '<li><a href="" onclick="rs_quickmenu_action(\'trash\',\'%{reqid}\');return false;" class="tooltip-menu-img-base tooltip-menu-img-trash"><span class="tooltip-menu-maintext">'.hs_jshtmlentities(lg_tm_actiontrash).'</span></a></li>';
        }

        $tip_menu .= '</ul></div>';

        $tb .= $tip_menu;
        */

        //If using sorting then setup here
        if ($sortable) {
            $sortlist = '<ul class="sortablelist" id="tablesort" style="">';
            $rs->move(0);
            while ($row = $rs->FetchRow()) {
                $sortlist .= '<li class="sortable" id="tablesort_'.$row[$sf[0]].'">'.$row[$sf[1]].'</li>';
            }
            $sortlist .= '</ul>';

            $tb .= displayContentBoxTop($sorttitle, '', $sortlist, '100%', 'box-no-top-margin', '', true, true);

            $tb .= displayContentBoxBottom('<a href="javascript:window.location.href=window.location.href" class="btn inline-action">'.lg_done.'</a>');

            $tb .= '
				<script type="text/javascript">
				 // <![CDATA[
				   Sortable.create("tablesort",
					 {dropOnEmpty:true,constraint:false,onUpdate:'.$sortable_callback.'});
				 // ]]>
				 </script>
			';
        }
    }

    return $tb;
}

/********************************************
RENDER A TIP
*********************************************/
function getTip(){
    $language = hs_setting('cHD_LANG', 'english-us');
    require_once cBASEPATH.'/helpspot/lang/'.$language.'/lg.pg.tips.php';

    $nextTip = count(lg_tips)-1 <= auth()->user()->iLastTipViewed ? 0 : auth()->user()->iLastTipViewed+1;

    $type = lg_tips[$nextTip]['type'];

    $res = $GLOBALS['DB']->Execute('UPDATE HS_Person SET iLastTipViewed = ? WHERE xPerson = ?', [$nextTip, auth()->user()->xPerson]);

    return '<div class="noresults">
                <div class="icon">
                    <img src="'.static_url().'/static/img5/tips-'.$type.($type == 'helpspot' && inDarkMode() ? '-dark' : '').'.svg" />
                </div>
                <h3>'. constant('lg_tips_header_' . lg_tips[$nextTip]['type']) .'</h3>
                <div class="tip">'.lg_tips[$nextTip]['tip'].'</div>
            </div>';
}

/********************************************
TABLE HELPER FUNCTIONS
*********************************************/
function recordSetStringItem(&$row, $field, $charlen, $default = '-')
{
    $out = '';
    if (is_array($field)) {
        foreach ($field as $f) {
            $out .= $row[$f].' ';
        }
    } elseif (isset($row[$field])) {
        $out = $row[$field];
        if ($out == '0') {
            $out = intval($out);
        }
    } else {
        $out = '';
    }

    //Special case to strip subject line from non-email requests
    if ($field == 'tNote' && $row['fOpenedVia'] != 1 && ! empty($out)) {
        $t = explode('#-#', $out);
        if (isset($t[1])) {
            $out = '#-#'.$t[1]; //initRequestClean function expects #-# to be present
        }
    }

    //limit length of field shown
    if ($charlen != 0) {
        $size = utf8_strlen($out);
        $out = utf8_substr($out, 0, $charlen);
        if ($size > $charlen) {
            $out .= '...';
        }
    }

    if (! empty($out) || $out === 0) { //check for fields that have 0 as a value
        $stripped = strip_tags($out);

        return $stripped;
    } else {
        return $default;
    }
}

function recordSetBoolItem($value, $img = '', $noimg = '', $clickable = '')
{
    $out = '';
    if (intval($value) === 0) {	//convert bools
        if (! empty($noimg)) {
            $out = '<img src="'.$noimg.'" alt="" width="16" height="16">';
        } else {
            $out = lg_no;
        }
    } else {
        if (! empty($img)) {
            $out = '<img src="'.$img.'" alt="" width="16" height="16" '.$clickable.'>';
        } else {
            $out = lg_yes;
        }
    }

    return $out;
}

function recordSetLookupItem($value, $lookup)
{
    if (trim($lookup[$value])) {
        return $lookup[$value];
    } else {
        return '-';
    }
}

function recordSetOpenedviaItem($id)
{
    return '<div class="table-icons table-icons-ov-'.$id.'" alt="" title="'.$GLOBALS['openedVia'][$id].'"></div>';
}

function recordSetNumberItem($value, $decimals = 0)
{
    if (trim($value)) {
        return number_format($value, $decimals);
    } else {
        return '-';
    }
}

function recordSetCheckboxItem($code, $value)
{
    return sprintf($code, $value);
}

function recordSetLinkItem(&$row, $code, $fields)
{
    $temp = [];
    $ct = count($fields);
    $i = 1;
    foreach ($fields as $f) {
        $temp[] = isset($row[$f]) ? hs_htmlspecialchars($row[$f]) : '';
    }

    return vsprintf($code, $temp);
}

//When we know the last item being passed to sprintf should be html encoded but other values need to be url encoded
function recordSetNormalLinkItem(&$row, $code, $fields)
{
    $temp = [];
    $ct = count($fields);
    $i = 1;
    foreach ($fields as $f) {
        if (isset($row[$f])) {
            if ($i < $ct) {
                $temp[] = urlencode($row[$f]);
            } else {
                $temp[] = hs_htmlspecialchars($row[$f]);
            }
        }
        $i++;
    }

    return vsprintf($code, $temp);
}

function displayCheckAll($style = '')
{
    return '<input id="checkAll" type="checkbox" class="check-all" value="" style="vertical-align:middle;"><label for="checkAll" class="datalabel" style="display: inline; margin-left: 13px;">'.lg_checkbox_checkall.'</label>';
}
