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
$basepgurl = route('admin', ['pg' => 'admin.groups']);
$pagetitle = lg_admin_groups_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_groups';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : 'fOrder';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : 'ASC';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$resourceid = isset($_GET['resourceid']) && is_numeric($_GET['resourceid']) ? $_GET['resourceid'] : 0;
$showdeleted = isset($_GET['showdeleted']) ? $_GET['showdeleted'] : 0;

if (session('feedback')) {
    $feedbackArea = displayFeedbackBox(session('feedback'), '100%');
}

$feedbackArea = '';
$datatable = '';
$headscript = '';
$delbutton = '';

if (isset($_GET['clone'])) {
    $fm = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Permission_Groups WHERE xGroup = ?', [$_GET['clone']]);
} else {
    $fm['sGroup'] = isset($_POST['sGroup']) ? $_POST['sGroup'] : '';
    $fm['fModuleReports'] = isset($_POST['fModuleReports']) ? $_POST['fModuleReports'] : 0;
    $fm['fModuleKbPriv'] = isset($_POST['fModuleKbPriv']) ? $_POST['fModuleKbPriv'] : 0;
    $fm['fViewInbox'] = isset($_POST['fViewInbox']) ? $_POST['fViewInbox'] : 0;

    $fm['fCanBatchRespond'] = isset($_POST['fCanBatchRespond']) ? $_POST['fCanBatchRespond'] : 0;
    $fm['fCanMerge'] = isset($_POST['fCanMerge']) ? $_POST['fCanMerge'] : 0;
    $fm['fCanAdvancedSearch'] = isset($_POST['fCanAdvancedSearch']) ? $_POST['fCanAdvancedSearch'] : 0;
    $fm['fCanManageSpam'] = isset($_POST['fCanManageSpam']) ? $_POST['fCanManageSpam'] : 0;
    $fm['fCanManageTrash'] = isset($_POST['fCanManageTrash']) ? $_POST['fCanManageTrash'] : 0;
    $fm['fCanManageKB'] = isset($_POST['fCanManageKB']) ? $_POST['fCanManageKB'] : 0;

    //Leave limiting ones at bottom
    $fm['fCanViewOwnReqsOnly'] = isset($_POST['fCanViewOwnReqsOnly']) ? $_POST['fCanViewOwnReqsOnly'] : 0;
    $fm['fLimitedToAssignedCats'] = isset($_POST['fLimitedToAssignedCats']) ? $_POST['fLimitedToAssignedCats'] : 0;
    $fm['fCanTransferRequests'] = isset($_POST['fCanTransferRequests']) ? $_POST['fCanTransferRequests'] : 0;

}

/*****************************************
PERFORM ACTIONS
*****************************************/
if ($action == 'add' || $action == 'edit') {
    $formerrors = [];

    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    if (hs_empty($fm['sGroup'])) {
        $formerrors['sGroup'] = lg_admin_groups_er_groups;
    }

    if ($action == 'add' && empty($formerrors)) {
        $res = $GLOBALS['DB']->Execute('INSERT INTO HS_Permission_Groups(sGroup,fModuleReports,fModuleKbPriv,fViewInbox,fCanBatchRespond,fCanMerge,fCanViewOwnReqsOnly,fLimitedToAssignedCats,fCanAdvancedSearch,
																			fCanManageSpam,fCanManageTrash,fCanManageKB,fCanTransferRequests)
											VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                                            [$fm['sGroup'],
                                                  $fm['fModuleReports'],
                                                  $fm['fModuleKbPriv'],
                                                  $fm['fViewInbox'],
                                                  $fm['fCanBatchRespond'],
                                                  $fm['fCanMerge'],
                                                  $fm['fCanViewOwnReqsOnly'],
                                                  $fm['fLimitedToAssignedCats'],
                                                  $fm['fCanAdvancedSearch'],
                                                  $fm['fCanManageSpam'],
                                                  $fm['fCanManageTrash'],
                                                  $fm['fCanManageKB'],
                                                  $fm['fCanTransferRequests']]);
    } elseif ($action == 'edit' && empty($formerrors)) {
        $res = $GLOBALS['DB']->Execute('UPDATE HS_Permission_Groups SET sGroup=?, fModuleReports=?, fModuleKbPriv=?, fViewInbox=?, fCanBatchRespond=?, fCanMerge=?, fCanViewOwnReqsOnly=?, fLimitedToAssignedCats=?, fCanAdvancedSearch=?, fCanManageSpam=?, fCanManageTrash=?, fCanManageKB=?, fCanTransferRequests=? WHERE xGroup = ?',
                                            [$fm['sGroup'],
											$fm['fModuleReports'],
											$fm['fModuleKbPriv'],
											$fm['fViewInbox'],
											$fm['fCanBatchRespond'],
											$fm['fCanMerge'],
											$fm['fCanViewOwnReqsOnly'],
											$fm['fLimitedToAssignedCats'],
											$fm['fCanAdvancedSearch'],
											$fm['fCanManageSpam'],
											$fm['fCanManageTrash'],
                                            $fm['fCanManageKB'],
                                            $fm['fCanTransferRequests'], $resourceid]);
    }

    // if it's an array of errors than skip else continue
    if (empty($formerrors)) {
        $feedback = $resourceid != 0 ? lg_admin_groups_fbedited : lg_admin_groups_fbadded;
        return redirect()
            ->route('admin', ['pg' => 'admin.groups'])
            ->with('feedback', $feedback);
    } else {
        if (empty($formerrors['errorBoxText'])) {
            $formerrors['errorBoxText'] = lg_errorbox;
        }
        setErrors($formerrors);
    }
}

if ($action == 'delete') {
    if (empty($formerrors)) {
        $feedback = lg_admin_groups_fbdeleted;
        $res = $GLOBALS['DB']->Execute('DELETE FROM HS_Permission_Groups WHERE xGroup = ?', [$resourceid]);
        // Redirect Back
        return redirect()
            ->route('admin', ['pg' => 'admin.groups'])
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
        $fm = $GLOBALS['DB']->GetRow('SELECT * FROM HS_Permission_Groups WHERE xGroup = ?', [$resourceid]);
    }
    $formaction = 'edit';
    $title = lg_admin_groups_editcat.$fm['sGroup'];
    $button = lg_admin_groups_editbutton;
} elseif ($action == '' || ! empty($formerrors)) {
    // Get status info
    $data = $GLOBALS['DB']->Execute('SELECT * FROM HS_Permission_Groups');
    $formaction = 'add';
    $title = lg_admin_groups_addcat;
    $button = lg_admin_groups_addbutton;

    // build data table
    $datatable = recordSetTable($data,[
                                        ['type'=>'string', 'label'=>lg_admin_groups_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xGroup'],
                                        ['type'=>'link', 'label'=>lg_admin_groups_colgroup, 'sort'=>1,
                                              'code'=>'<a href="'.$basepgurl.'&resourceid=%s" id="link-%s">%s</a>',
                                              'fields'=>'xGroup', 'linkfields'=>['xGroup', 'xGroup', 'sGroup'],
                                        ],
                                        ['type'=>'link', 'label'=>lg_admin_groups_colclonegrp, 'sort'=>0, 'width'=>100,
                                              'code'=>'<a href="'.$basepgurl.'&clone=%s">'.lg_admin_groups_colclone.'</a>',
                                              'fields'=>'', 'linkfields'=>['xGroup']
                                        ]
                                      ],
                                //options
                                ['sortby'=>$sortby,
                                   'sortord'=>$sortord,
                                   'sortable'=>true,
                                   'sortable_callback'=>'sort_status',
                                   'sortablefields'=>['xGroup'],
                                   'title'=>$pagetitle, ], $basepgurl);
}

// If looking at a specific status show delete/restore option
if (! empty($resourceid)) {
    if (staffInPermGp($resourceid) > 0) {
        $delbutton = '<div class="alttext">'.lg_admin_groups_delbuttoncant.'</div>';
    } else {
        $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_groups_delbuttonwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');">'.lg_admin_groups_delbutton.'</button>';
    }
}

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
document.observe("dom:loaded", function (){
    if ($("link-1")) {
        var pr = $("link-1").up();
        pr.insert({top:\'<div style="font-weight:bold;">Administrator</div>\'});
        $("link-1").remove();
    }
});
</script>
';

/*****************************************
PAGE OUTPUTS
*****************************************/
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

//Can't edit admin
if ($resourceid != 1) {
    $pagebody .= '
<form action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" name="statusform" onSubmit="return submitCheck();">
'.csrf_field().'
	'.$feedbackArea.'
	'.$datatable.'

	'. renderInnerPageheader($title). '

		<div class="card padded">
			<div class="fr">
				<div class="label">
					<label class="datalabel req" for="sGroup">' . lg_admin_groups_colgroup . '</label>
				</div>
				<div class="control">
					<input tabindex="100" name="sGroup" id="sGroup" type="text" size="40" value="' . formClean($fm['sGroup']) . '" class="' . errorClass('sGroup') . '">
					' . errorMessage('sGroup') . '
				</div>
			</div>

			<div class="sectionhead">' . lg_admin_groups_permissions . '</div>
			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fModuleReports" class="datalabel">' . lg_admin_groups_fModuleReports . '</label>
				</div>
				<div class="control">
					<input type="checkbox" name="fModuleReports" id="fModuleReports" class="checkbox" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fModuleReports'])) . '>
					<label for="fModuleReports" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanManageKB" class="datalabel">' . lg_admin_groups_fCanManageKB . '</label>
				</div>
				<div class="control">
					<input type="checkbox" name="fCanManageKB" id="fCanManageKB" class="checkbox" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanManageKB'])) . '>
					<label for="fCanManageKB" class="switch"></label>
				</div>
			</div>

			<div class="sectionhead">' . lg_admin_groups_permissionsaccess . '</div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanBatchRespond" class="datalabel">' . lg_admin_groups_fCanBatchRespond . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanBatchRespond" id="fCanBatchRespond" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanBatchRespond'])) . ' />
					<label for="fCanBatchRespond" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanMerge" class="datalabel">' . lg_admin_groups_fCanMerge . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanMerge" id="fCanMerge" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanMerge'])) . ' />
					<label for="fCanMerge" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanAdvancedSearch" class="datalabel">' . lg_admin_groups_fCanAdvancedSearch . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanAdvancedSearch" id="fCanAdvancedSearch" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanAdvancedSearch'])) . ' />
					<label for="fCanAdvancedSearch" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanManageSpam" class="datalabel">' . lg_admin_groups_fCanManageSpam . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanManageSpam" id="fCanManageSpam" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanManageSpam'])) . ' />
					<label for="fCanManageSpam" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanManageTrash" class="datalabel">' . lg_admin_groups_fCanManageTrash . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanManageTrash" id="fCanManageTrash" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanManageTrash'])) . ' />
					<label for="fCanManageTrash" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fModuleKbPriv" class="datalabel">' . lg_admin_groups_fModuleKbPriv . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fModuleKbPriv" id="fModuleKbPriv" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fModuleKbPriv'])) . ' />
					<label for="fModuleKbPriv" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fViewInbox" class="datalabel">' . lg_admin_groups_fViewInbox . '</label>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fViewInbox" id="fViewInbox" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fViewInbox'])) . ' />
					<label for="fViewInbox" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fCanViewOwnReqsOnly" class="datalabel">' . lg_admin_groups_fCanViewOwnReqsOnly . '</label>
					<div class="info">' . lg_admin_groups_fCanViewOwnReqsOnlyex . '</div>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fCanViewOwnReqsOnly" id="fCanViewOwnReqsOnly" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanViewOwnReqsOnly'])) . ' />
					<label for="fCanViewOwnReqsOnly" class="switch"></label>
				</div>
			</div>

			<div class="hr"></div>

			<div class="fr">
				<div class="label tdlcheckbox">
					<label for="fLimitedToAssignedCats" class="datalabel">' . lg_admin_groups_fLimitedToAssignedCats . '</label>
					<div class="info">' . lg_admin_groups_fLimitedToAssignedCatsex . '</div>
				</div>
				<div class="control">
					<input type="checkbox" class="checkbox" name="fLimitedToAssignedCats" id="fLimitedToAssignedCats" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fLimitedToAssignedCats'])) . ' />
					<label for="fLimitedToAssignedCats" class="switch"></label>
				</div>
            </div>
            <div class="hr"></div>
            <div class="fr">
                <div class="label tdlcheckbox">
                    <label for="fCanTransferRequests" class="datalabel">' . lg_admin_groups_fCanTransferRequests . '</label>
                    <div class="info">' . lg_admin_groups_fCanTransferRequestsex . '</div>
                </div>
                <div class="control">
                    <input type="checkbox" class="checkbox" name="fCanTransferRequests" id="fCanTransferRequests" value="1" ' . (empty($resourceid) && empty($_GET['clone']) ? 'checked="checked"' : checkboxCheck(1, $fm['fCanTransferRequests'])) . ' />
                    <label for="fCanTransferRequests" class="switch"></label>
                </div>
            </div>

		</div>
        <div class="button-bar space">
            <button type="submit" name="submit" class="btn accent">'.$button.'</button>
            '. $delbutton.'
        </div>
</form>
';
} else {
    $pagebody = displaySystemBox(lg_admin_groups_adminmsg);
}
