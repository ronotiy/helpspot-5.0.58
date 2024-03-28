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

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'kb.book']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = '';
$pagetitle = lg_kb_title;
$rsslink = '';

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$bookid = isset($_GET['book']) ? $_GET['book'] : '';

$book = apiGetBook($bookid);
$editors = hs_unserialize($book['tEditors']);
$showhidden = (is_array($editors) && in_array($user['xPerson'], $editors)) ? true : false;
$tree = apiBuildChapPageTree($bookid, $showhidden);
$chaps = apiTocChaps($tree);

$icon = $book['fPrivate'] == 1 ? 'book_red.gif' : 'public.gif';

//Protect page
if ($book['fPrivate'] == 1 && ! perm('fModuleKbPriv')) {
    return redirect()
        ->route('admin', ['pg' => 'pg=kb']);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
$jq().ready(function(){
	//$jq(".kb-chapter-row").hoverIntent( function(){$jq(this).find(".kb-chapter-menu").show();} , function(){$jq(this).find(".kb-chapter-menu").hide();} );
});
</script>
';
$onload = '';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= renderPageheader('<b class="breadcrumb"><a href="'.route('admin', ['pg' => 'kb']).'">'.lg_kb_home.'</a> &nbsp; / &nbsp; '.$book['sBookName'].' &mdash; '.lg_kb_toc.'</b>', '
            <div class="table-top-menu">
                '.$topbar.'
            </div>');

    if (! empty($fb)) {
        $pagebody .= $fb;
    }

    //Editor Controls
    $topbar = '';
    if (is_array($editors) && in_array($user['xPerson'], $editors)) {
        $topbar .= '<a href="'.route('admin', ['pg' => 'kb.modchapter', 'book' => $bookid]).'" class="btn full" style="margin-bottom:14px;">'.lg_kb_addchap.'</a>';
        $topbar .= '<a href="'.route('admin', ['pg' => 'kb.manage', 'xBook' => $bookid]).'" class="btn full">'.lg_kb_editbook.'</a>';
    } elseif (isAdmin()) {
        $topbar .= '<a href="'.route('admin', ['pg' => 'kb.manage', 'xBook' => $bookid]).'" class="btn full">'.lg_kb_editbook.'</a>';
    }

    $pagebody .= '<table class="kb-table"><tr valign="top"><td class="card kb-body" width="66%">';
        if (is_array($chaps) && count($chaps) > 0) {
            $pagebody .= '<ul class="kbtoc">';
            $i = 0;
            foreach ($chaps as $chapid=>$c) {
                $pagebody .= '<li class="kb-chapter-row">
									<a name="chapter'.$chapid.'"></a>
									'.(is_array($editors) && in_array($user['xPerson'], $editors) ? '
									<div class="kb-chapter-menu">
										<a href="'.route('admin', ['pg' => 'kb.modpage', 'chapter' => $chapid]).'" class="btn inline-action tiny" id="add_kb_page">'.lg_kb_addpage.'</a>
										<a href="'.route('admin', ['pg' => 'kb.modchapter', 'book' => $c['xBook'], 'chapter' => $chapid]).'" class="btn inline-action tiny">'.lg_kb_editchap.'</a>
									</div>' : '').'
									<h2 class="kb-chapter '.$c['class'].'">'.$c['name'].'</h2>
								  <ul>';

                $pages = apiTocPages($tree, $chapid);
                if (is_array($pages)) {
                    foreach ($pages as $pageid=>$p) {
                        $pagebody .= '<li><a href="'.route('admin', ['pg' => 'kb.page', 'page' => $pageid]).'" class="kb-page '.$p['class'].'">'.$p['name'].'</a></li>';
                    }
                }

                $pagebody .= '</ul></li>';
                $i++;
            }
            $pagebody .= '</ul>';
        } else {
            $pagebody .= '<div class="table-no-results">'.lg_kb_nochapters.'</div>';
        }
    $pagebody .= '</td><td class="kb-sidebar">';
        $pagebody .= $topbar;
        $pagebody .= '<div class="sectionhead">'.lg_kb_editors.'</div>';
        $pagebody .= createUserListEmails($book['tEditors']);

    $pagebody .= '</td></tr></table>';
