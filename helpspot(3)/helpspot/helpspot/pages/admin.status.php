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

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.status']);
$pagetitle = lg_admin_status_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_status';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : 'fOrder';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : 'ASC';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$resourceid = isset($_GET['resourceid']) && is_numeric($_GET['resourceid']) ? $_GET['resourceid'] : 0;
$showdeleted = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;

$fb = (session('feedback'))
    ?  displayFeedbackBox(session('feedback'), '100%')
    : '';

$feedbackArea = '';
$dellable = $showdeleted == 1 ? lg_inactive : '';
$datatable = '';
$headscript = '';
$delbutton = '';

$fm['sStatus'] = isset($_POST['sStatus']) ? $_POST['sStatus'] : '';

/* Anytime this page is visited clear the cache.
   Allows any changes to clear the cache and also acts as an emergency cache clear */
\Facades\HS\Cache\Manager::forgetGroup('status');

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($action == 'add' || $action == 'edit') {
    $formerrors = [];

    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    if (hs_empty($fm['sStatus'])) {
        $formerrors['sStatus'] = lg_admin_status_er_status;
    }

    if ($action == 'add' && empty($formerrors)) {
        $order = $GLOBALS['DB']->GetOne('SELECT MAX(fOrder) AS ct FROM HS_luStatus');
        $res = $GLOBALS['DB']->Execute('INSERT INTO HS_luStatus(sStatus,fDeleted,fOrder) VALUES (?,?,?)', [$fm['sStatus'], 0, $order + 1]);
    } elseif ($action == 'edit' && empty($formerrors)) {
        $res = $GLOBALS['DB']->Execute('UPDATE HS_luStatus SET sStatus=? WHERE xStatus = ?', [$fm['sStatus'], $resourceid]);
    }

    // if it's an array of errors than skip else continue
    if (empty($formerrors)) {
        $feedback = !empty($resourceid) ? lg_admin_status_fbedited : lg_admin_status_fbadded;
        return redirect()
            ->route('admin', ['pg' => 'admin.status'])
            ->with('feedback', $feedback);
    } else {
        if (empty($formerrors['errorBoxText'])) {
            $formerrors['errorBoxText'] = lg_errorbox;
        }
        setErrors($formerrors);
    }
}

if ($action == 'delete' || $action == 'undelete') {
    if (empty($formerrors)) {
        $feedback = $action == 'delete' ? lg_admin_status_fbdeleted : lg_admin_status_fbundeleted;
        $delCat = apiDeleteResource('HS_luStatus', 'xStatus', $resourceid, $action);

        // Redirect Back
        return redirect()
            ->route('admin', ['pg' => 'admin.status'])
            ->with('feedback', $feedback);
    }
}

/*****************************************
SETUP VARIABLES AND DATA FOR PAGE
*****************************************/
if (! empty($resourceid)) {

    //Get resource info if there are no form errors. If there was an error then we don't want to get data again
    // that would overwrite any changes the user made
    if (empty($formerrors)) {
        $fm = $GLOBALS['DB']->GetRow('SELECT * FROM HS_luStatus WHERE xStatus = ?', [$resourceid]);
    }
    $formaction = 'edit';
    $title = lg_admin_status_editcat.$fm['sStatus'];
    $button = lg_admin_status_editbutton;
} elseif ($action == '' || ! empty($formerrors)) {
    // Get status info
    $data = apiGetStatusByDel($showdeleted, ($sortby . ' ' . $sortord));
    $formaction = 'add';
    $title = lg_admin_status_addcat;
    $button = lg_admin_status_addbutton;
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_status_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_status_noshowdel.'</a>';
    }

    // build data table
    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_status_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xStatus'],
                                                ['type'=>'link', 'label'=>lg_admin_status_colstatus, 'sort'=>1,
                                                  'code'=>'<a href="'.$basepgurl.'&resourceid=%s&showdeleted='.$showdeleted.'">%s</a>',
                                                  'fields'=>'sStatus', 'linkfields'=>['xStatus', 'sStatus'], ], ],
                                //options
                                ['sortby'=>$sortby,
                                       'sortord'=>$sortord,
                                       'sortable'=>true,
                                       'sortable_callback'=>'sort_status',
                                       'sortablefields'=>['xStatus', 'sStatus'],
                                       'title_right'=>$showdellink,
                                       'title'=>$pagetitle.$dellable, ], $basepgurl);
}

// If looking at a specific status show delete/restore option
if (! empty($resourceid) && $showdeleted == 0) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_status_coldelwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');">'.lg_admin_status_coldel.'</button>';
}
if (! empty($resourceid) && $showdeleted == 1) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sStatus']).'\',\''.$basepgurl.'&action=undelete&resourceid='.$resourceid.'\');">'.lg_restore.'</button>';
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
	function sort_status(id){
		reorder_call(id,"status_order");
	}
</script>
';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($fb)) {
    $feedbackArea = $fb;
}
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

$pagebody .= '
<form action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" name="statusform" onSubmit="return submitCheck();">
	'.csrf_field().'
	'.$feedbackArea.'
	'.$datatable.'

	'. renderInnerPageheader($title);

$pagebody .= '
    <div class="card padded">
        <div class="fr">
			<div class="label">
			    <label class="datalabel req" for="sStatus">'.lg_admin_status_colstatus.'</label>
            </div>
			<div class="control">
				<input tabindex="100" name="sStatus" id="sStatus" type="text" size="40" value="'.formClean($fm['sStatus']).'" class="'.errorClass('sStatus').'">
				'.errorMessage('sStatus'). '
			</div>
        </div>
    </div>
    <div class="button-bar space">
        <button type="submit" name="submit" class="btn accent">' . $button . '</button>
        '.$delbutton.'
    </div>
</form>';
