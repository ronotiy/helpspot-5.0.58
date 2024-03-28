<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! perm('fCanManageKB')) {
    die();
}

//Don't let the operation timeout
set_time_limit(0);

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('kb');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'kb.manage']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = 'nav_managekb';
$pagetitle = lg_kb_manage.' '.lg_kb_title;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$fm['xBook'] = hs_numeric($_REQUEST, 'xBook') ? $_REQUEST['xBook'] : 0;
$fm['vmode'] = isset($_REQUEST['vmode']) ? $_REQUEST['vmode'] : '';
$fm['sBookName'] = isset($_POST['sBookName']) ? $_POST['sBookName'] : '';
$fm['iOrder'] = hs_numeric($_POST, 'iOrder') ? $_POST['iOrder'] : 0;
$fm['fPrivate'] = hs_numeric($_POST, 'fPrivate') ? $_POST['fPrivate'] : 0;
$fm['tDescription'] = isset($_POST['tDescription']) ? $_POST['tDescription'] : '';
$fm['sPersonList'] = isset($_POST['sPersonList']) ? $_POST['sPersonList'] : [$user['xPerson']];

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($fm['vmode'] == 'add') {
    if (empty($fm['sBookName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sBookName'] = lg_kb_er_noname;
    }

    if (empty($formerrors)) {
        $addbook = $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Books(sBookName,iOrder,fPrivate,tDescription,tEditors) VALUES (?,?,?,?,?)',
                                                                [$fm['sBookName'],
                                                                $fm['iOrder'],
                                                                $fm['fPrivate'],
                                                                $fm['tDescription'],
                                                                hs_serialize($fm['sPersonList']), ]);

        /* Since this redirects to a different page we clear the cache after adding/updating unlike other admin cache breaks */
        \Facades\HS\Cache\Manager::forgetGroup('kb');

        $newbookid = dbLastInsertID('HS_KB_Books', 'xBook');
        return redirect()
            ->route('admin', ['pg' => 'kb.book', 'book' => $newbookid])
            ->with('feedback', lg_kb_bookadded);
    }
} elseif ($fm['vmode'] == 'edit') {
    if (empty($fm['sBookName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sBookName'] = lg_kb_er_noname;
    }

    if (empty($formerrors)) {
        $updatebook = $GLOBALS['DB']->Execute('UPDATE HS_KB_Books SET sBookName=?,iOrder=?,fPrivate=?,tDescription=?,tEditors=?
												WHERE xBook = ?',
                                                    [$fm['sBookName'],
                                                    $fm['iOrder'],
                                                    $fm['fPrivate'],
                                                    $fm['tDescription'],
                                                    hs_serialize($fm['sPersonList']),
                                                    $fm['xBook'], ]);

        /* Since this redirects to a different page we clear the cache after adding/updating unlike other admin cache breaks */
        \Facades\HS\Cache\Manager::forgetGroup('kb');

        return redirect()
            ->route('admin', ['pg' => 'kb.book', 'book' => $fm['xBook']])
            ->with('feedback', lg_kb_bookupdated);
    }
} elseif ($fm['vmode'] == 'delete') {
    //get chapters. start at 0 and go to end (-1)
    $chapters = apiGetBookChapters($fm['xBook'], true);

    $hasBadTransaction = false;

    if (hs_rscheck($chapters)) {
        while ($chap = $chapters->FetchRow()) {
            $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

            //get pages. start at 0 and go to end (-1)
            $pages = apiGetChapPages($chap['xChapter'], true);
            if (hs_rscheck($pages)) {
                while ($page = $pages->FetchRow()) {
                    //delete related pages
                    $delrel = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_RelatedPages WHERE xPage = ?', [$page['xPage']]);
                    //delete docs
                    $deldoc = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Documents WHERE xPage = ?', [$page['xPage']]);
                    //delete page
                    $delpage = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Pages WHERE xPage = ?', [$page['xPage']]);
                }
            }

            $hasBadTransaction = $GLOBALS['DB']->HasFailedTrans();
            $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/
        }
    }

    //delete chapters
    $delchaps = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Chapters WHERE xBook = ?', [$fm['xBook']]);

    //delete book
    $delchaps = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Books WHERE xBook = ?', [$fm['xBook']]);

    // Fire book deleted event, if no transaction failed
    if (! $hasBadTransaction) {
        event('knowledgebooks.book.delete', [$fm['xBook']]);
    }

    /* Since this redirects to a different page we clear the cache after adding/updating unlike other admin cache breaks */
    \Facades\HS\Cache\Manager::forgetGroup('kb');

    return redirect()
        ->route('admin', ['pg' => 'kb.manage'])
        ->with('feedback', lg_kb_bookdel);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript .= '
<script type="text/javascript" language="JavaScript">
function listUpdate(){

}
</script>';
$onload = '';

if (empty($fm['xBook'])) {
    $title = lg_kb_addkb;
    $button = lg_kb_addbutton;
    $delbutton = '';
    $mode = 'add';

    $def = [];
    $def[] = ['type'=>'link', 'label'=>lg_kb_book, 'sort'=>0, 'width'=>'600', 'fields'=>'sBookName',
                        'code'=>'<a href="'.str_replace('%25s', '%s', route('admin', ['pg' => 'kb.manage', 'xBook' => '%s'])).'">%s</a>', 'linkfields'=>['xBook', 'sBookName'], ];
} else {
    $fm = apiGetBook($fm['xBook']);
    $fm['sPersonList'] = hs_unserialize($fm['tEditors']);
    $title = lg_kb_edit.' '.$fm['sBookName'];
    $button = lg_kb_editbutton;
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_kb_deletekbcheck.'\',\''.$basepgurl.'&vmode=delete&xBook='.$fm['xBook'].'\');">'.lg_kb_deletekb.'</button>';
    $mode = 'edit';
}

//get list of staff
$staffList = apiGetAllUsers();
$staffList = rsToArray($staffList, 'xPerson', false);

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($feedback)) {
    $pagebody .= $feedback;
}

$pagebody .= renderPageheader($title);

$pagebody .= '<form action="'.$basepgurl.'" method="post" name="managebooksform" onSubmit="">';
$pagebody .= csrf_field();
$pagebody .= '
    <input type="hidden" name="vmode" value="'.$mode.'">
    <input type="hidden" name="xBook" value="'.$fm['xBook'].'">
    <input type="hidden" name="iOrder" value="'.$fm['iOrder'].'">

    <div class="card padded">

        <div class="fr">
            <div class="label">
                <label class="datalabel req" for="sBookName">'.lg_kb_name.'</label>
            </div>
            <div class="control">
                <input type="text" name="sBookName" id="sBookName" value="'.formClean($fm['sBookName']).'" size="60" maxlength="255" class="'.errorClass('sBookName').'">
                '.errorMessage('sBookName').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fPrivate" class="datalabel req">'.lg_kb_booktype.'</label>
                <div class="info">'.lg_kb_privatelabeldesc.'</div>
            </div>
            <div class="control">
                '.renderYesNo('fPrivate', $fm['fPrivate'], lg_kb_booktype_private, lg_kb_booktype_public).'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="datalabel req" for="sPersonList">'.lg_kb_chooseeditors.'</label>
                <div class="info">'.lg_kb_editordesc.'</div>
            </div>
            <div class="control">
                '.renderSelectMulti('editors', $staffList, $fm['sPersonList'], 'listUpdate()').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="datalabel" for="tDescription">'.lg_kb_desc.'</label>
                <div class="info">'.lg_kb_descnote.'</div>
            </div>
            <div class="control">
                <textarea name="tDescription" id="tDescription" rows="6" cols="70">'.formClean($fm['tDescription']).'</textarea>
                '.errorMessage('tDescription').'
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">'.$button.'</button>
        '.$delbutton.'
    </div>
</form>';
