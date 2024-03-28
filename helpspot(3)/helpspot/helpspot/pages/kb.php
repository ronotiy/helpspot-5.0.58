<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
LIBS
*****************************************/
include cBASEPATH.'/helpspot/lib/api.kb.lib.php';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = '';
$pagetitle = lg_kb_title;
$rsslink = '';
$pubbooks = '';
$privbooks = '';

$pubrs = apiGetBooks(0);
if (hs_rscheck($pubrs)) {
    while ($row = $pubrs->FetchRow()) {
        $public_books .= bookCard($row);
    }
}

$privrs = apiGetBooks(1);
if (hs_rscheck($privrs)) {
    while ($row = $privrs->FetchRow()) {
        $private_books .= bookCard($row);
    }
}

if(empty($public_books) && empty($private_books)){
    return redirect()->action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.manage']);
}

function bookCard($book){
    return '
        <div class="card kbcard '.($book['fPrivate'] == 1 ? 'kbcard-private' : '').'">
            <div class="kbcard-title">
                <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.book', 'book' => $book['xBook']]).'">
                '.hs_htmlspecialchars($book['sBookName']).'
                </a>
                <div class="kbcard-menu">
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.modchapter', 'book' => $book['xBook']]).'" class="btn inline-action tiny">'.lg_kb_addchap.'</a>
                    <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.manage', 'xBook' => $book['xBook']]).'" class="btn inline-action tiny" style="margin-left:5px;">'.lg_kb_editbook.'</a>
                </div>
            </div>

            <div class="kbcard-body">'.hs_htmlspecialchars($book['tDescription']).'</div>
        </div>
    ';
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';
$onload = '';

/*****************************************
PAGE OUTPUTS
*****************************************/
$pagebody .= '
	<div class="kbcards">
        '.$public_books.'
        '.$private_books.'
	</div>
';
