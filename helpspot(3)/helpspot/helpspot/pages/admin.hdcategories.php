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

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.hdcategories']);
$pagetitle = lg_admin_categories_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_cats';
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';
$sortord = isset($_GET['sortord']) ? $_GET['sortord'] : '';
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

$fm['sCategory'] = isset($_POST['sCategory']) ? $_POST['sCategory'] : '';
$fm['sCategoryGroup'] = isset($_POST['sCategoryGroup']) ? $_POST['sCategoryGroup'] : '';
$fm['fAllowPublicSubmit'] = isset($_POST['fAllowPublicSubmit']) ? $_POST['fAllowPublicSubmit'] : 0;
$fm['xPersonDefault'] = isset($_POST['xPersonDefault']) ? $_POST['xPersonDefault'] : 0;
$fm['fAutoAssignTo'] = isset($_POST['fAutoAssignTo']) ? $_POST['fAutoAssignTo'] : 0;
$fm['sReportingTagList'] = isset($_POST['sReportingTagList']) && is_array($_POST['sReportingTagList']) ? $_POST['sReportingTagList'] : [];
//init sCustomFieldList - store as integers
if (isset($_POST['sCustomFieldList']) && is_array($_POST['sCustomFieldList'])) {
    foreach ($_POST['sCustomFieldList'] as $v) {
        $tempcustlist[] = intval($v);
    }
    $fm['sCustomFieldList'] = hs_serialize($tempcustlist);
} else {
    $fm['sCustomFieldList'] = hs_serialize([]);
}

//init sPersonList - store as integers
if (isset($_POST['sPersonList']) && is_array($_POST['sPersonList'])) {
    foreach ($_POST['sPersonList'] as $v) {
        if (! empty($v)) {
            $tempperlist[] = intval($v);
        }
    }

    if (! empty($tempperlist)) {
        $fm['sPersonList'] = hs_serialize($tempperlist);
    } else {
        $fm['sPersonList'] = '';
    }
} else {
    $fm['sPersonList'] = '';
}

/* Anytime this page is visited clear the cache.
   Allows any changes to clear the cache and also acts as an emergency cache clear */
\Facades\HS\Cache\Manager::forgetGroup('categories');

/*****************************************
PERFORM ACTIONS
 *****************************************/
if ($action == 'add' || $action == 'edit') {
    // add these two items to fm array then pass entire thing in to be processed
    $fm['resourceid'] = $resourceid;
    $fm['mode'] = $action;

    $Res = apiAddEditCategory($fm, __FILE__, __LINE__);
    // if it's an array of errors than skip else continue
    if (! is_array($Res)) {
        $feedback = $resourceid != '' ? lg_admin_categories_fbedited : lg_admin_categories_fbadded;
        return redirect()
            ->route('admin', ['pg' => 'admin.hdcategories'])
            ->with('feedback', $feedback);
    } else {
        $formerrors = $Res;
        if (empty($formerrors['errorBoxText'])) {
            $formerrors['errorBoxText'] = lg_errorbox;
        }
        setErrors($formerrors);
    }
}

if ($action == 'delete' || $action == 'undelete') {
    if ($action == 'delete') {
        $boxCheck = $GLOBALS['DB']->GetRow('SELECT COUNT(*) as cattotal FROM HS_Mailboxes WHERE xCategory = ?', [$resourceid]);

        if ($boxCheck['cattotal'] != 0) {
            $action = '';
            $fm = apiGetCategory($resourceid);
            $formerrors['errorBoxText'] = lg_admin_categories_er_inmailbox;
        }
    }

    if (empty($formerrors)) {
        $feedback = $action == 'delete' ? lg_admin_categories_fbdeleted : lg_admin_categories_fbundeleted;
        $delCat = apiDeleteResource('HS_Category', 'xCategory', $resourceid, $action);
        // Redirect Back
        return redirect()
            ->route('admin', ['pg' => 'admin.hdcategories'])
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
        $fm = apiGetCategory($resourceid);
    }
    $formaction = 'edit';
    $title = lg_admin_categories_editcat.$fm['sCategory'];
    $button = lg_admin_categories_editbutton;
    $showdellink = '';

    $onload = 'BuildDefaultContactList();';

    $fm['sPersonList'] = hs_unserialize($fm['sPersonList']);
    $fm['sCustomFieldList'] = hs_unserialize($fm['sCustomFieldList']);
} elseif ($action == '' || ! empty($formerrors)) {

    // Get category info
    $sortCols = ['sCategory', 'fullname', 'fAutoAssignTo', 'fAllowPublicSubmit'];
    $sortby = in_array($sortby, $sortCols) ? $sortby : '';
    $data = apiGetAllCategories($showdeleted, ($sortby . ' ' . getSortOrder($sortord)));
    $formaction = 'add';
    $title = lg_admin_categories_addcat;
    $button = lg_admin_categories_addbutton;
    if (! $showdeleted) {
        $showdellink = '<a href="'.$basepgurl.'&showdeleted=1" class="">'.lg_admin_categories_showdel.'</a>';
    } else {
        $showdellink = '<a href="'.$basepgurl.'" class="">'.lg_admin_categories_noshowdel.'</a>';
    }

    // build data table
    $datatable = recordSetTable($data,[['type'=>'string', 'label'=>lg_admin_categories_colid, 'sort'=>0, 'width'=>'20', 'fields'=>'xCategory'],
            ['type'=>'string', 'label'=>lg_admin_categories_colgrouping, 'sort'=>0, 'width'=>'100', 'fields'=>'sCategoryGroup'],
            ['type'=>'link', 'label'=>lg_admin_categories_colcat, 'sort'=>1,
                  'code'=>'<a href="'.$basepgurl.'&resourceid=%s&showdeleted='.$showdeleted.'">%s</a>',
                  'fields'=>'sCategory', 'linkfields'=>['xCategory', 'sCategory'], ],
            ['type'=>'string', 'label'=>lg_admin_categories_coldefcon, 'sort'=>1, 'width'=>'150', 'fields'=>'fullname', 'default'=>lg_inbox],
            ['type'=>'bool', 'label'=>lg_admin_categories_colassign, 'sort'=>1, 'width'=>'100', 'fields'=>'fAutoAssignTo'],
            ['type'=>'bool', 'label'=>lg_admin_categories_colwf, 'sort'=>1, 'width'=>'80', 'fields'=>'fAllowPublicSubmit'], ],
            //options
            ['sortby'=>$sortby,
                   'sortord'=>$sortord,
                   'title_right'=>$showdellink,
                   'title'=>$pagetitle.$dellable, ], $basepgurl);

    //Show rep tags on error
    if (! empty($formerrors)) {
        $onload = 'BuildDefaultContactList();';
    }
}

// If looking at a specific category show delete/restore option
if (! empty($resourceid) && $showdeleted == 0) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_admin_categories_coldelwarn.'\',\''.$basepgurl.'&action=delete&resourceid='.$resourceid.'\');">'.lg_admin_categories_coldel.'</button>';
}
if (! empty($resourceid) && $showdeleted == 1) {
    $delbutton = '<button type="button" class="btn altbtn" onClick="return hs_confirm(\''.lg_restorewarn.hs_jshtmlentities($fm['sCategory']).'\',\''.$basepgurl.'&action=undelete&resourceid='.$resourceid.'\');">'.lg_restore.'</button>';
}

// dynamic form components
//get list of staff
$staffList = apiGetAllUsers();
$staffList = rsToArray($staffList, 'xPerson', false);
$perList = $fm['sPersonList'];

if (! empty($formerrors)) {
    $reportingTagList = $fm['sReportingTagList'];
} else {
    $reportingTagList = apiGetReportingTags($resourceid);
}

$categoryGroupSel = '<option value="">'.lg_admin_categories_nogroup.'</option>';
foreach (apiGetCategoryGroups() as $v) {
    if (! empty($v)) {
        $categoryGroupSel .= '<option value="'.hs_htmlspecialchars($v).'" '.selectionCheck($v, $fm['sCategoryGroup']).'>'.hs_htmlspecialchars($v).'</option>';
    }
}

/*****************************************
JAVASCRIPT - listed out of normal place because it needs the variable $fm['xPersonDefault']
 *****************************************/
$headscript .= '
  <script type="text/javascript" language="JavaScript">
  <!--

  // FUNCTION TO BUILD DEFAULT CONTACT LIST
  function BuildDefaultContactList() {

    var deflist = $("xPersonDefault");
    var curdefindex = document.categoryform.xPersonDefault.selectedIndex;
    if(curdefindex != -1){
      var curdefval   = document.categoryform.xPersonDefault.options[curdefindex].value;
    }else{
      var curdefval = '.$fm['xPersonDefault'].';
    }

    //rebuild default list
    deflist.options.length = 0;

    //Add inbox as default option
    var selected_option = (0 === curdefval ? 1 : 0);
    deflist.options[0]= new Option("'.hs_jshtmlentities(lg_inbox).'",0,selected_option,selected_option);

    j=1;
    $$("a[id^=\'catstaff-select-multiple-\']").each(function(field){

      if($(field.id+"-hidden")){
        var val = $(field.id+"-hidden").getValue();

        newOptText=$$("#"+field.id+" .name")[0].innerHTML;
        newOptValue=val;
        if(newOptValue == curdefval){
          newOptSelected = 1;
        }else{
          newOptSelected = 0;
        }
        deflist.options[j]= new Option(newOptText,newOptValue,newOptSelected,newOptSelected);
        j++;
      }
    });
  }

  function submitCheck(){
    //Make sure staff are selected
    if(!$$(".select-multiple-selected")[0]){
      hs_alert("'.lg_admin_categories_er_staff.'");
      return false;
    }

    //Let enter be used when adding rep tags
    var val = stopFormEnter(\'reportingTagsInput\');
    if (val != true){
      $(\'reportingTagButton\').onclick();
      return false;
    } else {
      return true;
    }
  }

  function addGroup(){
    hs_overlay({href:"'.route('admin', ['pg' => 'ajax_gateway', 'action' => 'addCatGroup']). '",onOpen:function(){$jq("#new_group").focus();}});
  }

  function add_group_action(val){
    foldername = val;
    folders   = $("sCategoryGroup").options;
    folderlen = folders.length;
    newoption = folderlen;

    $("sCategoryGroup").options[newoption]= new Option(foldername,foldername);
    $("sCategoryGroup").selectedIndex = newoption;

    closeAllModals();
  }

  // -->
  </script>';

/*****************************************
PAGE OUTPUTS
 *****************************************/
if (! empty($fb)) {
    $feedbackArea = $fb;
}
if (! empty($formerrors)) {
    $feedbackArea = errorBox($formerrors['errorBoxText']);
}

$pagebody = '
<form action="'.$basepgurl.'&action='.$formaction.'&resourceid='.$resourceid.'" method="POST" name="categoryform" onSubmit="return submitCheck();">
'.csrf_field().'
'.$feedbackArea.'
 '.$datatable.'
 '.renderInnerPageheader($title, lg_admin_categories_cathelp).'
    <div class="card padded">
        <div class="fr">
            <div class="label">
                <label class="req" for="sCategory">'.lg_admin_categories_category.'</label>
            </div>
            <div class="control">
                <input tabindex="100" name="sCategory" id="sCategory" type="text" size="40" value="'.formClean($fm['sCategory']).'" class="'.errorClass('sCategory').'">
                '.errorMessage('sCategory').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="sCategoryGroup">
                    '.lg_admin_categories_categorygroup.'
                </label>
            </div>
            <div class="control">
                <div style="display:flex;align-items:center;">
                    <select name="sCategoryGroup" id="sCategoryGroup" style="margin-right:10px;flex:1;">
                    '.$categoryGroupSel.'
                    </select>
                    <a href="javascript:addGroup();" class="btn inline-action">'.lg_admin_categories_addgroup.'</a>
                </div>
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label for="fAllowPublicSubmit" class="">'.lg_admin_categories_visibility.'</label>
                <div class="info">'.lg_admin_categories_websub.'</div>
            </div>
            <div class="control">
                '.renderYesNo('fAllowPublicSubmit', $fm['fAllowPublicSubmit'], lg_admin_categories_public, lg_admin_categories_private).'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="req" for="sPersonList">'.lg_admin_categories_staffmem.'</label>
                <div class="info">'.lg_admin_categories_staffdesc.'</div>
            </div>
            <div class="control">
                '.renderSelectMulti('catstaff', $staffList, $perList, 'BuildDefaultContactList()').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="req" for="xPersonDefault">'.lg_admin_categories_defcontact.'</label>
            </div>
            <div class="control">
                <select tabindex="102" name="xPersonDefault" id="xPersonDefault" class="'.errorClass('xPersonDefault').'"></select>'.errorMessage('xPersonDefault').'
            </div>
        </div>

        <div class="hr"></div>

        <div class="fr">
            <div class="label">
                <label class="" for="fAutoAssignTo">'.lg_admin_categories_autoassign.'</label>
                <div class="info">'.lg_admin_categories_autoassignex.'</div>
            </div>
            <div class="control">
                <select name="fAutoAssignTo">
                  <option value="0" '.selectionCheck(0, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aaoff.'</option>
                  <option value="1" '.selectionCheck(1, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aadefault.'</option>
                  <option value="2" '.selectionCheck(2, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aarandom.'</option>
                  <option value="3" '.selectionCheck(3, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aarandomboth.'</option>
                  <option value="4" '.selectionCheck(4, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aaleast.'</option>
                  <option value="5" '.selectionCheck(5, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aaleastboth.'</option>
                  <option value="6" '.selectionCheck(6, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aarr.'</option>
                  <option value="7" '.selectionCheck(7, $fm['fAutoAssignTo']).'>'.lg_admin_categories_aarrnoadmin.'</option>
                </select>
            </div>
        </div>

  '.tagUI($reportingTagList, lg_admin_categories_reportingtags, 'sReportingTagList[]', false, true).'

  '.displayContentBoxTop(lg_admin_categories_customfields, lg_admin_categories_customfieldsdesc, '', '100%', '', 'box-min-padding').'
    <table cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0px;">';
$rcount = 0;
if (! empty($GLOBALS['customFields'])) {
    foreach ($GLOBALS['customFields'] as $k=>$v) {
        $rowclass = ($rcount % 2) ? 'tablerowon' : 'tablerowoff';
        $checked = (checkboxMuiltiboxCheck($v['fieldID'], $fm['sCustomFieldList']) ? 'checked' : '');
        $pagebody .= '<tr id="cfrow_'.$rcount.'" class="'.$rowclass.'">
                  <td class="tcell" align="center" width="40">'.(! $v['isAlwaysVisible'] ? '<input type="checkbox" class="canCheck" name="sCustomFieldList[]" id="field_'.$v['fieldID'].'" value="'.$v['fieldID'].'" style="vertical-align:middle;" '.$checked.' />' : '-').'</td>
                  <td class="tcell"><label for="field_'.$v['fieldID'].'" style="cursor:pointer;">'.$v['fieldName'].' '.($v['isRequired'] ? '&nbsp;&nbsp;<span class="req-label">'.lg_required.'</span>' : '').'</label></td>
                  <td class="tcell" align="" width="150">'.$GLOBALS['customFieldTypes'][$v['fieldType']].'</td>
                  <td class="tcell" align="" width="120">'.($v['isPublic'] ? lg_ispublic : '').'</td>
                  <td class="tcell" align="" width="120">'.($v['isAlwaysVisible'] ? lg_alwaysvis : '').'</td>
                </tr>';
        $rcount++;
    }
} else {
    $pagebody .= '<b style="display:block;padding:17px;">'.lg_admin_categories_customfields_none.'</b>';
}

$pagebody .= '
    </table>
    <div class="box_footer" style="padding: 7px 12px">'.displayCheckAll().'</div>
  '.displayContentBoxBottom().'

</div>

<div class="button-bar space">
    <button type="submit" name="submit" class="btn accent">'.$button.'</button>'.$delbutton.'
</div>

</form>
';
