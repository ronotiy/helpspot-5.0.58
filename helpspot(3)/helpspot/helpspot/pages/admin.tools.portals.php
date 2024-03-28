<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

/*****************************************
LIBS
 *****************************************/
include cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';
include cBASEPATH.'/helpspot/lib/api.forums.lib.php';
include cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';

/*****************************************
VARIABLE DECLARATIONS
 *****************************************/
$basepgurl = route('admin', ['pg' => 'admin.tools.portals']);
$pagetitle = lg_admin_portals_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_portals';
$showdeleted  = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;
$dellable = $showdeleted == 1 ? lg_inactive : '';
$showdeleted = isset($_GET['showdeleted']) ? 1 : 0;
$pagebody = '';
if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$path_sep = (strpos(cBASEPATH, '\\') !== false ? '\\' : '/');
$overLimit = false;

$fm['xPortal'] = isset($_REQUEST['xPortal']) ? $_REQUEST['xPortal'] : '';
$fm['sPortalName'] = isset($_POST['sPortalName']) ? $_POST['sPortalName'] : '';
$fm['sPortalPhone'] = isset($_POST['sPortalPhone']) ? $_POST['sPortalPhone'] : '';
$fm['tPortalMsg'] = isset($_POST['tPortalMsg']) ? $_POST['tPortalMsg'] : '';
$fm['sHost'] = isset($_POST['sHost']) ? $_POST['sHost'] : '';
$fm['sPortalPath'] = isset($_POST['sPortalPath']) ? rtrim($_POST['sPortalPath'], '\\/') : '';
$fm['tDisplayKBs'] = isset($_POST['tDisplayKBs']) ? serialize($_POST['tDisplayKBs']) : '';
$fm['tDisplayForums'] = isset($_POST['tDisplayForums']) ? serialize($_POST['tDisplayForums']) : '';
$fm['tDisplayCategories'] = isset($_POST['tDisplayCategories']) ? serialize($_POST['tDisplayCategories']) : '';
$fm['tDisplayCfs'] = isset($_POST['tDisplayCfs']) ? serialize($_POST['tDisplayCfs']) : '';
$fm['tHistoryMailboxes'] = isset($_POST['tHistoryMailboxes']) ? serialize($_POST['tHistoryMailboxes']) : '';
$fm['xMailboxToSendFrom'] = isset($_POST['xMailboxToSendFrom']) ? $_POST['xMailboxToSendFrom'] : 0;
$fm['sPortalTerms'] = isset($_REQUEST['sPortalTerms']) ? $_REQUEST['sPortalTerms'] : '';
$fm['sPortalPrivacy'] = isset($_REQUEST['sPortalPrivacy']) ? $_REQUEST['sPortalPrivacy'] : '';
$fm['fIsPrimaryPortal'] = isset($_POST['fIsPrimaryPortal']) ? $_POST['fIsPrimaryPortal'] : 0;
$fm['fRequireAuth'] = isset($_POST['fRequireAuth']) ? $_POST['fRequireAuth'] : 0;

/* Anytime this page is visited clear the cache.
   Allows any changes to clear the cache and also acts as an emergency cache clear */
\Facades\HS\Cache\Manager::forgetGroup('portals');

/*****************************************
PERFORM ACTIONS
 *****************************************/
if ($action == 'add' || $action == 'edit') {
    $formerrors = [];

    if (empty($fm['sPortalName'])) {
        $formerrors['sPortalName'] = lg_admin_portals_ername;
    }

    if (empty($fm['sHost'])) {
        $formerrors['sHost'] = lg_admin_portals_erhost;
    }

    if (empty($fm['sPortalPath'])) {
        $formerrors['sPortalPath'] = lg_admin_portals_erpath;
    }

    if ($action == 'add') {
        // If we're adding a new portal, we can check if ANY other are set as being used as primary
        if ($fm['fIsPrimaryPortal'] == 1 && \HS\MultiPortal::asPrimary()->count() > 0) {
            $formerrors['fIsPrimaryPortal'] = lg_admin_portals_erprimary;
        }
    }

    if ($action == 'edit') {
        // If we're editing a portal, we check if there's some already marked as primary which is NOT the one we're editing
        if ($fm['fIsPrimaryPortal'] == 1 && \HS\MultiPortal::asPrimary()->where('xPortal', '!=', $fm['xPortal'])->count() > 0) {
            $formerrors['fIsPrimaryPortal'] = lg_admin_portals_erprimary;
        }
    }

    if ($fm['fIsPrimaryPortal'] == 0 && $fm['sPortalPath'] == public_path()) {
        $formerrors['sPortalPath'] = lg_admin_portals_ernonprimarypath;
    }

    if (! empty($formerrors)) {
        $formerrors['errorBoxText'] = lg_errorbox;
        setErrors($formerrors);
    }

    // If portal is being used as primary,
    // we set the sHost and sPortalPath path
    if ($fm['fIsPrimaryPortal'] == 1) {
        $fm['sHost'] = config('app.url');
        $fm['sPortalPath'] = public_path();
    }
}

$total = \HS\MultiPortal::active()->orderBy('sPortalName')->count();
if (! subscription()->canAdd('portal', $total)) {
    $overLimit = true;
    $text = '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                <div>
                    You have reached the free plan portal limit. If you need to use secondary portals please move to a paid account
                    <a class="action" href="https://store.helpspot.com">buy now</a>
                    or <a class="action" href="https://www.helpspot.com/talk-to-sales">contact sales</a>
                </div>
            </div>';
    $pagebody .= displaySystemBox($text);
}

if (empty($formerrors)) {
    if ($action == 'add') {
        $portal = \HS\MultiPortal::create([
            'sPortalName' => $fm['sPortalName'],
            'sPortalPhone' => $fm['sPortalPhone'],
            'tPortalMsg' => $fm['tPortalMsg'],
            'sHost' => $fm['sHost'],
            'sPortalPath' => $fm['sPortalPath'],
            'tDisplayKBs' => $fm['tDisplayKBs'],
            'tDisplayForums' => '',
            'tDisplayCfs' => $fm['tDisplayCfs'],
            'tDisplayCategories' => $fm['tDisplayCategories'],
            'tHistoryMailboxes' => $fm['tHistoryMailboxes'],
            'xMailboxToSendFrom' => $fm['xMailboxToSendFrom'],
            'fIsPrimaryPortal' => $fm['fIsPrimaryPortal'],
            'fRequireAuth' => $fm['fRequireAuth'],
        ]);

        $generator = app(HS\Portals\Generate::class);
        $generator->makePortal($portal);

        return redirect()
            ->route('admin', [
                'pg' => 'admin.tools.portals',
                'xPortal' => $portal->xPortal,
                'added' => 1,
            ])
            ->with('feedback', lg_admin_portals_created);
    } elseif ($action == 'edit') {
        $portal = \HS\MultiPortal::findOrFail($fm['xPortal']);
        $oldPath = $portal->sPortalPath;
        $portal->update([
            'sPortalName' => $fm['sPortalName'],
            'sPortalPhone' => $fm['sPortalPhone'],
            'tPortalMsg' => $fm['tPortalMsg'],
            'sHost' => $fm['sHost'],
            'sPortalPath' => $fm['sPortalPath'],
            'tDisplayKBs' => $fm['tDisplayKBs'],
            'tDisplayCfs' => $fm['tDisplayCfs'],
            'tDisplayCategories' => $fm['tDisplayCategories'],
            'tHistoryMailboxes' => $fm['tHistoryMailboxes'],
            'xMailboxToSendFrom' => $fm['xMailboxToSendFrom'],
            'fIsPrimaryPortal' => $fm['fIsPrimaryPortal'],
            'fRequireAuth' => $fm['fRequireAuth'],
        ]);

        // If the path changed, then move the directory.
        if ($oldPath != $portal->sPortalPath) {
            $generator = app(HS\Portals\Generate::class);
            $generator->movePortal($portal, $oldPath);
        }

        return redirect()
            ->route('admin', [
                'pg' => 'admin.tools.portals',
                'xPortal' => $fm['xPortal'],
                'added' => 1,
            ])
            ->with('feedback', lg_admin_portals_edited);
    }
}

if ($action == 'delete') {
    // Delete and disable primary portal override, to prevent confusing errors
    // where no *active* portal is acting as primary portal
    $portal = \HS\MultiPortal::where('xPortal', $fm['xPortal'])->update(['fDeleted' => 1, 'fIsPrimaryPortal' => 0]);
    return redirect()->route('admin', ['pg' => 'admin.tools.portals']);
}

if ($action == 'undelete') {
    // Restore and (redundantly) ensure we've disabled primary portal override
    $portal = \HS\MultiPortal::where('xPortal', $fm['xPortal'])->update(['fDeleted' => 0, 'fIsPrimaryPortal' => 0]);
    return redirect()->route('admin', ['pg' => 'admin.tools.portals']);
}

/*****************************************
JAVASCRIPT
 *****************************************/
if (empty($fm['xPortal'])) {
    $headscript = '
    <script type="text/javascript" language="JavaScript">
        jQuery( document ).ready(function( $ ) {
            var slug = function(str) {
                var $slug = \'\';
                var trimmed = $.trim(str);
                $slug = trimmed.replace(/[^a-z0-9-]/gi, \'-\').
                replace(/-+/g, \'-\').
                replace(/^-|-$/g, \'\');
                return $slug.toLowerCase();
            }
            $("#sPortalName").on("blur", function(e){
                $jq("#sHost").val("' . url('/') . '/"+slug($jq("#sPortalName").val()));
                $jq("#sPortalPath").val("' . public_path() . '/"+slug($jq("#sPortalName").val()));
            });
        });
	</script>
';
} else {
    $headscript = '';
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
 *****************************************/
if (! empty($fm['xPortal'])) {
    $action = 'edit';

    $fm = \HS\MultiPortal::findOrFail($fm['xPortal'])->toArray();

    $title = lg_admin_portals_edit;
    $button = lg_admin_portals_buttonsave;

    if (! isset($_GET['added'])) {
        $note = '<strong>'.lg_admin_portals_instructionsnote_main.'</strong> ' . lg_admin_portals_instructionsnote_secondary;
    } else {
        $note = '';
    }

    // Delete button. Only show proper one for deleted/restore
    if ($fm['fDeleted'] == 0) {
        $delbutton = '<button type="button" class="btn altbtn" name="" onClick="return hs_confirm(\''.lg_admin_portals_delwarn.'\',\''.$basepgurl.'&action=delete&xPortal='.$fm['xPortal'].'\');">'.lg_admin_portals_del.'</button>';
    }

    if ($fm['fDeleted'] == 1) {
        if ($overLimit) {
            $text = '<div style="display:flex;justify-content: space-between;margin: 20px 0;" id="notification-'.$notification->id.'">
                <div>
                    You have reached your current portal limit. You need to disable an active portal before you can restore this one
                </div>
            </div>';
            $delbutton = '<button type="button" class="btn altbtn" name="" onClick="return hs_alert(\''.$text.'\');">'.lg_restore.'</button>';
        } else {
            $delbutton = '<button type="button" class="btn altbtn" name="" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sPortalName']).'\',\''.$basepgurl.'&action=undelete&xPortal='.$fm['xPortal'].'\');">'.lg_restore.'</button>';
        }
    }
} else {
    $title = lg_admin_portals_add;
    $button = lg_admin_portals_buttonadd;
    $delbutton = '';
    $action = 'add';

    // build data table
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_portals_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_portals_noshowdel.'</a>';
    }

    if ($showdeleted) {
        $data = \HS\MultiPortal::showDeleted()->orderBy('sPortalName')->get();
    } else {
        $data = \HS\MultiPortal::active()->orderBy('sPortalName')->get();
    }
    $datatable = view('multiportal.index', [
        'portals' => $data,
    ]);
}

//Create array of custom field info
if (is_array($GLOBALS['customFields']) && ! empty($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $k=>$field) {
        if ($field['isPublic'] == 1) {
            $cf_array[$k] = $field['fieldName'];
        }
    }
}

//Create array of category info
$publiccats = apiGetPublicCategories();
if (hs_rscheck($publiccats)) {
    while ($cat = $publiccats->FetchRow()) {
        $cat_array[$cat['xCategory']] = $cat['sCategory'];
    }
}

//Create array of books
$publicbooks = apiGetAllBooks();
if (hs_rscheck($publicbooks)) {
    while ($bk = $publicbooks->FetchRow()) {
        if ($bk['fPrivate'] == 0) {
            $book_array[$bk['xBook']] = $bk['sBookName'];
        }
    }
}

//Create array of mailboxes
$mailboxes = apiGetAllMailboxes(0, '');
if (hs_rscheck($mailboxes)) {
    while ($mb = $mailboxes->FetchRow()) {
        $mailboxes_array[$mb['xMailbox']] = '#'.$mb['xMailbox'].' '.$mb['sReplyName'].' ('.$mb['sReplyEmail'].')';
    }
}

//Create sendfrom list
$sendfromoptions = '';
if (hs_rscheck($mailboxes)) {
    $mailboxes->Move(0);
    while ($mb = $mailboxes->FetchRow()) {
        $sendfromoptions .= '<option value="'.$mb['xMailbox'].'" '.selectionCheck($mb['xMailbox'], $fm['xMailboxToSendFrom']).'>#'.$mb['xMailbox'].' '.$mb['sReplyName'].' ('.$mb['sReplyEmail'].')</option>';
    }
}
/*****************************************
PAGE OUTPUTS
 *****************************************/
if (! empty($fb)) {
    $pagebody .= $fb;
}
if (! empty($formerrors)) {
    $pagebody .= errorBox($formerrors['errorBoxText']);
}

if (! $overLimit) {
    $pagebody .= $datatable;

    //If we're editing show the installation instructions
    if(isset($_GET['instructions'])){
        $pagebody .= displayContentBoxTop(lg_admin_portals_instructions, $note).'

		'.displayContentBoxTop(lg_admin_portals_inst_step1, '<p>'.lg_admin_portals_inst_step1_p1.':<br /><b>'.$fm['sPortalPath'].$path_sep.'index.php</b></p><p>'.lg_admin_portals_inst_step1_p2.'</p>').'

	<div class="code-wrap">
	<pre class="brush: php" id="config_code">
	&lt;?php
	define(\'cMULTIPORTAL\', \''.$fm['xPortal'].'\');
	define(\'cHOST\', \''.$fm['sHost'].'\');

	require_once(\''.public_path('index.php').'\')</pre>
	<div class="code-wrap-note">'.lg_double_click.'</div>
	</div>

		'.displayContentBoxBottom().'

		'.displayContentBoxTop(lg_admin_portals_inst_step2, lg_admin_portals_inst_step2_p1.': <strong>'.$fm['sPortalPath'].'</strong>').'

			<p>'.lg_admin_portals_inst_step2_p2.'</p>

			<p>'.lg_admin_portals_inst_step2_p3.' <b>'.$fm['sPortalPath'].'</b> '.lg_admin_portals_inst_step2_p4.':
			<div><img src="'.static_url().'/static/img5/secondary_portal_layout.png" border="1" /></div>
			</p>

		'.displayContentBoxBottom().'

		'.displayContentBoxTop(lg_admin_portals_inst_step3).'

		<p>'.sprintf(lg_admin_portals_inst_note2, $fm['xPortal']).'</p>

		'.displayContentBoxBottom().'

		'.displayContentBoxBottom(lg_admin_portals_inst_note1.':<br /> <a href="'.$fm['sHost'].'" target="_blank">'.$fm['sHost'].'</a>').'<br />';
    }

    if ($action == 'edit' or ! $overLimit) {
        $pagebody .= '
		<form action="'.$basepgurl.'&xPortal='.$fm['xPortal'].'&action='.$action.'" method="POST" name="ruleform" onSubmit="return checkform();">
		'.csrf_field().'
		'.$feedbackArea.'
		'. renderInnerPageheaderBlock($title, lg_admin_portals_desc).'

            <div class="card padded">

                <div class="fr">
                    <div class="label">
                        <label for="sPortalName" class="datalabel req">'.lg_admin_portals_sportalname.'</label>
                        <div class="info">'.lg_admin_portals_sportalnameex.'</div>
                    </div>
                    <div class="control">
                        <input name="sPortalName" id="sPortalName" type="text" size="40" style="width:80%;" value="'.formClean($fm['sPortalName']).'" class="'.errorClass('sPortalName').'" placeholder="My Portal">'.errorMessage('sPortalName').'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sHost" class="datalabel req">'.lg_admin_portals_shost.'</label>
                        <div class="info">'.(! empty($fm['xPortal']) ? '<i>'.lg_admin_portals_shostnote.'</i><br />' : '').lg_admin_portals_shostex.'</div>
                    </div>
                    <div class="control">
                        <input name="sHost" id="sHost" type="text" size="40" style="width:80%;" value="'.formClean($fm['sHost']).'" class="'.errorClass('sHost').'" placeholder="'.url('my-portal').'">'.errorMessage('sHost').'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sPortalPath" class="datalabel req">'.lg_admin_portals_sportalpath.'</label>
                        <div class="info">'.lg_admin_portals_sportalpathex.'</div>
                    </div>
                    <div class="control">
                        <input name="sPortalPath" id="sPortalPath" type="text" size="40" style="width:80%;" value="'.formClean($fm['sPortalPath']).'" class="'.errorClass('sPortalPath').'" placeholder="'.public_path('/my-portal').'">'.errorMessage('sPortalPath').'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sPortalPath" class="datalabel">'.lg_admin_portals_sportalprimary.'</label>
                        <div class="info">'.lg_admin_portals_sportalprimaryex.'</div>
                    </div>
                    <div class="control">
                        <div>
                            <input type="checkbox" class="checkbox" name="fIsPrimaryPortal" id="fIsPrimaryPortal" value="1" '.checkboxCheck(1, $fm['fIsPrimaryPortal']).'>
                            <label for="fIsPrimaryPortal" class="switch '.errorClass('fIsPrimaryPortal').'"></label>
                            '.errorMessage('fIsPrimaryPortal').'
                        </div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sPortalPath" class="datalabel">'.lg_admin_portals_require_auth.'</label>
                        <div class="info">'.lg_admin_portals_require_auth_ex.'</div>
                    </div>
                    <div class="control">
                        <div>
                            <input type="checkbox" class="checkbox" name="fRequireAuth" id="fRequireAuth" value="1" '.checkboxCheck(1, $fm['fRequireAuth']).'>
                            <label for="fRequireAuth" class="switch '.errorClass('fRequireAuth').'"></label>
                            '.errorMessage('fRequireAuth').'
                        </div>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="tDisplayCategories" class="datalabel">'.lg_admin_portals_cats.'</label>
                        <div class="info">'.lg_admin_portals_catsex.'</div>
                    </div>
                    <div class="control">
                        '.colCheckboxGroup($cat_array, hs_unserialize($fm['tDisplayCategories']), 'tDisplayCategories', lg_admin_portals_catsempty).'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="tDisplayCfs" class="datalabel">'.lg_admin_portals_cfs.'</label>
                        <div class="info">'.lg_admin_portals_cfsex.'</div>
                    </div>
                    <div class="control">
                        '.colCheckboxGroup($cf_array, hs_unserialize($fm['tDisplayCfs']), 'tDisplayCfs', lg_admin_portals_cfsempty).'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="tDisplayKBs" class="datalabel">'.lg_admin_portals_kbs.'</label>
                        <div class="info">'.lg_admin_portals_kbsex.'</div>
                    </div>
                    <div class="control">
                        '.colCheckboxGroup($book_array, hs_unserialize($fm['tDisplayKBs']), 'tDisplayKBs', lg_admin_portals_kbsempty).'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="xMailboxToSendFrom" class="datalabel">'.lg_admin_portals_sendfrom.'</label>
                        <div class="info">'.lg_admin_portals_sendfromex.'</div>
                    </div>
                    <div class="control">
                        <select name="xMailboxToSendFrom"><option value="0">'.lg_admin_portals_sendfromdef.'</option>'.$sendfromoptions.'</select>
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="tHistoryMailboxes" class="datalabel">'.lg_admin_portals_mailboxes.'</label>
                        <div class="info">'.lg_admin_portals_mailboxesex.'</div>
                    </div>
                    <div class="control">
                        '.colCheckboxGroup($mailboxes_array, hs_unserialize($fm['tHistoryMailboxes']), 'tHistoryMailboxes', lg_admin_portals_mailboxesempty).'
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label class="datalabel" for="sPortalTerms">'.lg_admin_portals_portalterms.'</label>
                        <div class="info">'.lg_admin_portals_portalterms_info.'</div>
                    </div>
                    <div class="control">
                        <input name="sPortalTerms" id="sPortalTerms" type="text" size="60" value="'.formClean($fm['sPortalTerms']).'">
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="sPortalPhone" class="datalabel">'.lg_admin_portals_sportalphone.'</label>
                        <div class="info">'.lg_admin_portals_sportalphoneex.'</div>
                    </div>
                    <div class="control">
                        <input name="sPortalPhone" id="sPortalPhone" type="text" size="40" value="'.formClean($fm['sPortalPhone']).'">
                    </div>
                </div>

                <div class="hr"></div>

                <div class="fr">
                    <div class="label">
                        <label for="tPortalMsg" class="datalabel">'.lg_admin_portals_tportalmsg.'</label>
                        <div class="info">'.lg_admin_portals_tportalmsgex.'</div>
                    </div>
                    <div class="control">
                        <textarea name="tPortalMsg" id="tPortalMsg" style="width:95%;height:100px;">'.formCleanHtml($fm['tPortalMsg']). '</textarea>
                    </div>
                </div>

            </div>

            <div class="button-bar space">
                <button type="submit" name="submit" class="btn accent">' . $button . '</button>' . $delbutton . '
            </div>
	    </form>';
    }

    $pagebody .= syntaxHighligherJS();
}
