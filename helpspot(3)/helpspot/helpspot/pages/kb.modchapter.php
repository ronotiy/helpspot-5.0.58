<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('kb');

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';
include cBASEPATH.'/helpspot/lib/api.requests.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'kb.modchapter']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = '';
$pagetitle = lg_kb_title;
$bookid = isset($_GET['book']) ? $_GET['book'] : die();	//required
$chapid = isset($_GET['chapter']) ? $_GET['chapter'] : '';
$topiccrumb = '';
$feedback = '';
$vmode = isset($_GET['vmode']) ? $_GET['vmode'] : 'add';

$allStaff = apiGetAllUsersComplete();

$fm['xChapter'] = isset($_POST['xChapter']) && is_numeric($_POST['xChapter']) ? $_POST['xChapter'] : 0;
$fm['xBook'] = isset($_POST['xBook']) && is_numeric($_POST['xBook']) ? $_POST['xBook'] : 0;
$fm['sChapterName'] = isset($_POST['sChapterName']) ? $_POST['sChapterName'] : '';
$fm['iOrder'] = isset($_POST['iOrder']) ? $_POST['iOrder'] : 0;
$fm['fAppendix'] = isset($_POST['fAppendix']) ? $_POST['fAppendix'] : 0;
$fm['fHidden'] = isset($_POST['fHidden']) ? $_POST['fHidden'] : 0;
$fm['orderafter'] = isset($_POST['orderafter']) ? $_POST['orderafter'] : '';
$fm['vmode'] = isset($_POST['vmode']) ? $_POST['vmode'] : '';

$book = apiGetBook($bookid);
$editors = hs_unserialize($book['tEditors']);

$tree = apiBuildChapPageTree($bookid, true);
$chaps = apiTocChaps($tree);

//Editors only
if (is_array($editors) && ! in_array($user['xPerson'], $editors)) {
    die();
}

//Protect page
if ($book['fPrivate'] == 1 && ! perm('fModuleKbPriv')) {
    return redirect()
        ->route('admin', ['pg' => 'pg=kb']);
}

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($fm['vmode'] == 'add') {
    if (empty($fm['sChapterName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sChapterName'] = lg_kb_er_nochapname;
    }

    if (empty($formerrors)) {
        $ids = apiAddChapter($fm);

        return redirect()
            ->route('admin', ['pg' => 'kb.book', 'book' => $bookid]);
    }
} elseif ($fm['vmode'] == 'update') {
    $redirect = route('admin', ['pg' => 'kb.book', 'book' => $bookid]);

    if (empty($fm['sChapterName'])) {
        $feedback = errorBox(lg_errorbox);
        $formerrors['sChapterName'] = lg_kb_er_nochapname;
    }

    if (empty($formerrors)) {
        $redir_to = $_POST['original_xBook'];

        //If we're moving books then redirect back to the new book
        if ($_POST['original_xBook'] != $fm['xBook']) {
            $redir_to = $fm['xBook'];
        }

        $ids = apiUpdateChapter($fm);

        return redirect()
            ->route('admin', ['pg' => 'kb.book', 'book' => $redir_to]);
    }
} elseif ($vmode == 'delete') {
    $GLOBALS['DB']->StartTrans();	/******* START TRANSACTION ******/

    //get pages. start at 0 and go to end (-1)
    $pages = apiGetChapPages($chapid, true);
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

    //delete chapters
    $delchap = $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Chapters WHERE xChapter = ?', [$chapid]);

    $hasBadTransaction = $GLOBALS['DB']->HasFailedTrans();
    $GLOBALS['DB']->CompleteTrans();	/******* END TRANSACTION ******/

    if (! $hasBadTransaction) {
        event('knowledgebooks.chapter.delete', [$chapid]);
    }

    return redirect()
        ->route('admin', ['pg' => 'kb.book', 'book' => $bookid]);
}
/*****************************************
PAGE TEMPLATE COMPONENTS
*****************************************/
if (! empty($chapid)) {
    $fm = apiGetChapter($chapid);
    $title = lg_kb_edit.' '.$fm['sChapterName'];
    $button = lg_kb_editbutton;
    $vmode = 'update';
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_kb_deletechapwarn.'\',\''.$basepgurl.'&vmode=delete&book='.$bookid.'&chapter='.$chapid.'\');">'.lg_kb_deletechap.'</button>';
} else {
    $title = lg_kb_addchap;
    $button = lg_kb_addchap;
    $delbutton = '';
}

$afterorderlist = '<option value="0">'.lg_kb_firstchap.'</option>';
if (is_array($chaps)) {
    foreach ($chaps as $c) {
        if ($c['xChapter'] != $chapid) {
            $afterorderlist .= '<option value="'.$c['xChapter'].'" '.selectionCheck($c['iOrder'], ($fm['iOrder'] - 1)).'>'.lg_kb_after.' '.$c['sChapterName'].'</option>';
        }
    }
}

$allbooks = apiGetAllBooks();
$booklist = '';
if ($allbooks) {
    while ($bk = $allbooks->FetchRow()) {
        $editors = hs_unserialize($bk['tEditors']);
        if (is_array($editors) && in_array($user['xPerson'], $editors)) {
            $booklist .= '<option value="'.$bk['xBook'].'" '.selectionCheck($bk['xBook'], $fm['xBook']).'>'.$bk['sBookName'].' '.($bk['fPrivate'] == 1 ? '('.lg_kb_privatelabel.')' : '').'</option>';
        }
    }
}

if ($vmode == 'add') {
    $bookfield = '<input type="hidden" name="xBook" value="'.$bookid.'">';
} else {
    $bookfield = '
		<div class="fr">
			<div class="label">
			    <label class="datalabel" for="xBook" >'.lg_kb_inbook.'</label>
			</div>
			<div class="control">
				<select tabindex="100" name="xBook" id="xBook" onChange="$(\'orderafter\').update();new Ajax.Updater(\'orderafter\',\'admin?pg=ajax_gateway&action=kb_afterorder_list&xChapter='.$chapid.'&xBook=\' + $F(\'xBook\'));" class="'.errorClass('xBook').'">
					'.$booklist.'
				</select>
			</div>
		</div>

		<div class="hr"></div>
	';
}
/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';
$onload = 'setFieldFocus(document.getElementById(\'sChapterName\'))';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($chapid)) {
    $chapter_crumb = '  &nbsp; : &nbsp;  <a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'#chapter'.$chapid.'">'.$fm['sChapterName'].'</a>';
}

$pagebody .= renderPageheader('<b class="breadcrumb">
                <a href="'.route('admin', ['pg' => 'kb']).'">'.lg_kb_home.'</a>  &nbsp; / &nbsp;
				<a href="'.route('admin', ['pg' => 'kb.book', 'book' => $book['xBook']]).'">'.$book['sBookName'].'</a>'.$chapter_crumb.'</b>');

$pagebody .= $feedback;

$pagebody .= '<form action="'.route('admin', ['pg' => 'kb.modchapter', 'book' => $bookid, 'chapter' => $chapid]).'" method="post">';
$pagebody .= csrf_field();
$pagebody .= renderInnerPageheader($title);
$pagebody .= '
    <div class="card padded">
        <div class="fr">
            <div class="label">
                <label class="datalabel req" for="sChapterName">'.lg_kb_chapname.'</label>
            </div>
            <div class="control">
                <input type="text" name="sChapterName" id="sChapterName" value="'.formClean($fm['sChapterName']).'" size="75" maxlength="255" class="'.errorClass('sChapterName').'">
				'.errorMessage('sChapterName').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fHidden" class="datalabel req">'.lg_kb_hidden.'</label>
                <div class="info">'.lg_kb_hiddendesc.'</div>
            </div>
            <div class="control">
                '.renderYesNo('fHidden', $fm['fHidden'], lg_yes, lg_no).'
            </div>
        </div>

        <div class="hr"></div>

        '.$bookfield.'

        <div class="fr">
            <div class="label">
                <label class="datalabel" for="orderafter">'.lg_kb_orderinbook.'</label>
            </div>
            <div class="control">
                <select tabindex="100" name="orderafter" id="orderafter" class="'.errorClass('orderafter').'">
					'.$afterorderlist.'
				</select>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="datalabel" for="fAppendix">'.lg_kb_isappen.'</label>
                <div class="info">'.lg_kb_appendesc.'</div>
            </div>
            <div class="control">
                <select tabindex="100" name="fAppendix" id="fAppendix" class="'.errorClass('fAppendix').'">
					<option value="0" '.selectionCheck(0, $fm['fAppendix']).'>'.lg_no.'</option>
					<option value="1" '.selectionCheck(1, $fm['fAppendix']).'>'.lg_yes.'</option>
				</select>
            </div>
        </div>

    </div>

    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">'.$button.'</button>'.$delbutton. '
    </div>



    <input type="hidden" name="xChapter" value="'.$chapid.'">
    <input type="hidden" name="vmode" value="'.$vmode.'">
    <input type="hidden" name="original_xBook" value="'.$fm['xBook'].'" />
';
$pagebody .= '</form>';
