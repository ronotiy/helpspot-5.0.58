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
$basepgurl = action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.order']);
$hidePageFrame = 0;
$tab = 'nav_kb';
$subtab = 'nav_orderkb';
$pagetitle = lg_orderkb_nav;
$feedback = '';

$fm['vmode'] = isset($_POST['vmode']) ? $_POST['vmode'] : '';

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($fm['vmode'] == 'update') {
    $pubinfo = apiGetBooks(0);
    $privinfo = apiGetBooks(1);

    //loop over books and change order
    if (hs_rscheck($pubinfo)) {
        while ($row = $pubinfo->FetchRow()) {
            if (isset($_POST[$row['xBook']]) && is_numeric($_POST[$row['xBook']])) {
                $GLOBALS['DB']->Execute('UPDATE HS_KB_Books SET iOrder = ? WHERE xBook = ?', [$_POST[$row['xBook']], $row['xBook']]);
            }
        }
    }

    if (hs_rscheck($privinfo)) {
        while ($row = $privinfo->FetchRow()) {
            if (isset($_POST[$row['xBook']]) && is_numeric($_POST[$row['xBook']])) {
                $GLOBALS['DB']->Execute('UPDATE HS_KB_Books SET iOrder = ? WHERE xBook = ?', [$_POST[$row['xBook']], $row['xBook']]);
            }
        }
    }

    $feedback = displayFeedbackBox(lg_kb_orderchanged);
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript .= '';
$onload = '';

/*****************************************
PAGE OUTPUTS
*****************************************/
$def = [];
$def[] = ['type'=>'string', 'label'=>lg_kb_book, 'sort'=>0, 'fields'=>'sBookName'];
$def[] = ['type'=>'link', 'label'=>lg_kb_order, 'sort'=>0, 'width'=>'100', 'fields'=>'iOrder',
                    'code'=>'<input type="text" name="%s" value="%s" size="5">', 'linkfields'=>['xBook', 'iOrder'], ];

$pubrs = apiGetBooks(0);

$pubbooks = recordSetTable($pubrs, $def,
                                    ['title'=>lg_kb_public]);

$privrs = apiGetBooks(1);
$privbooks = recordSetTable($privrs, $def,
                                    ['title'=>lg_kb_private]);

$pagebody .= '<form action="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'kb.order']).'" method="post">'.$pubbooks.' '.$privbooks.'

'.csrf_field().'
<input type="hidden" name="vmode" value="update">
<button type="submit" name="submit" class="btn accent">'.lg_kb_saveorder.'</button>

</form>';
