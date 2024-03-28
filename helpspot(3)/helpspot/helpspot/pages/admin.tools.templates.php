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
JS
*****************************************/
$headscript = '
<script type="text/javascript"></script>
';

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.tools.templates']);
$hidePageFrame = 0;
$pagetitle = lg_admin_portaltemplates_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedback = '';
$selectedPortal = isset($_GET['xPortal']) ? $_GET['xPortal'] : 0;

//Get portal
if ($selectedPortal) {
    $current_portal = apiGetPortal($selectedPortal);
    $current_portal_path = $current_portal['sPortalPath'];
} else {
    $current_portal = false;
    $current_portal_path = public_path();
}

//Find customized files
$customized_files = listFilesInDir($current_portal_path.'/custom_templates/');

$files = listFilesInDir(base_path('helpspot/helpspot/templates/'));

foreach ($files as $k=>$file) {
    $name = str_replace('.tpl.php', '', $file);
    $name = str_replace('.', '_', $name);
    $template_list[] = ['template'=>$file, 'desc'=>constant('lg_admin_portaltemplates_temp_'.$name), 'customized'=>in_array($file, $customized_files)];
}

/*
$rs = new array2recordset;
$rs->init($template_list);

$template_table = recordSetTable($rs, array(array('type'=>'link','label'=>lg_admin_portaltemplates_headtemp,'sort'=>0, 'width'=>'20%',
                                                  'code'=>'<a href="#" onclick="showTemplate(\'%s\');return false;">%s</a>',
                                                  'fields'=>'template','linkfields'=>array('template','template')),
                                            array('type'=>'string','label'=>lg_admin_portaltemplates_headdesc,'sort'=>0,'width'=>'65%','fields'=>'desc'),
                                            array('type'=>'bool','label'=>lg_admin_portaltemplates_headcustom,'sort'=>0,'width'=>'15%','fields'=>'customized')),
                                        array('title'=>lg_admin_portaltemplates_headtable));
*/

$portals = apiGetAllPortals(0);

if (! is_writable(public_path('custom_templates'))) {
    $notwritable = '*'.lg_admin_portaltemplates_notwritable.'*';
} else {
    $notwritable = '';
}
$portalSelect = '<select id="xPortal" onchange="goPage(\'admin?pg=admin.tools.templates&xPortal=\'+$F(this));"><option value="0">'.lg_admin_portaltemplates_primary.' '.$notwritable.'</option>';
    while ($row = $portals->FetchRow()) {
        if (! is_writable($row['sPortalPath'].'/custom_templates/')) {
            $notwritable = '*'.lg_admin_portaltemplates_notwritable.'*';
        } else {
            $notwritable = '';
        }
        $portalSelect .= '<option value="'.$row['xPortal'].'" '.selectionCheck($row['xPortal'], $selectedPortal).'>'.$row['sPortalName'].' '.$notwritable.'</option>';
    }

    $portals->Move(0);

$portalSelect .= '</select>';

/*****************************************
ACTION
*****************************************/
if (isset($_POST['submit'])) {
    if (is_writable($current_portal_path.'/custom_templates/') && ! empty($_POST['template_code']) && strpos($_POST['template_name'], '/') === false && strpos($_POST['template_name'], ' ') === false) {
        //Create backup directory if it doesn't exist
        if (! is_dir($current_portal_path.'/custom_templates/backups/')) {
            mkdir($current_portal_path.'/custom_templates/backups');
        }

        //Make backup of template
        if (file_exists($current_portal_path.'/custom_templates/'.$_POST['template_name'])) {
            $name = str_replace('.php', '', $_POST['template_name']);
            copy($current_portal_path.'/custom_templates/'.$_POST['template_name'], $current_portal_path.'/custom_templates/backups/'.$name.'_'.time().'.php');
        }

        //Write out new template file
        $handle = fopen($current_portal_path.'/custom_templates/'.$_POST['template_name'], 'w');
        fwrite($handle, $_POST['template_code']);
        fclose($handle);

        return redirect()
            ->route('admin', ['pg' => 'admin.tools.templates', 'xPortal' => $selectedPortal, 's' => 1]);
    }
}

if ($_GET['s'] == 1) {
    $feedback = displayFeedbackBox(lg_admin_portaltemplates_saved);
}

/*****************************************
PAGE OUTPUTS
****************************************/

$pagebody = renderPageheader(lg_admin_portaltemplates_headtable, lg_admin_portaltemplates_selectportal. ' &nbsp; '. $portalSelect);

$pagebody .= '<table cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:0px;">';
    $rcount = 0;
    foreach ($template_list as $k=>$v) {
        $rowclass = ($rcount % 2) ? 'tablerowon' : 'tablerowoff';
        $pagebody .= '<tr id="cfrow_'.$rcount.'" class="'.$rowclass. '">
						  <td class="tcell" style="padding-left: 20px;"><a href="" class="btn inline-action" onclick="hs_overlay({href:\'admin?pg=ajax_gateway&action=edit_portal_template&xPortal=\'+$F(\'xPortal\')+\'&template='.$v['template'].'\',title:\''.lg_admin_portaltemplates_editing.': '.$v['template'].' (\'+$(\'xPortal\').options[$(\'xPortal\').selectedIndex].text+\')\',innerWidth:814});return false;">Edit</a></td>
						  <td class="tcell" style="padding-left: 20px;" width="150"><b>'.$v['template'].'</b></td>
						  <td class="tcell" style="font-size:85%;padding-left:10px;">'.$v['desc'].'</td>
						  <td class="tcell" style="" align="center" width="120">'.($v['customized'] ? '<span class="tmp_customized">'.lg_admin_portaltemplates_custom.'</span>' : '').'</td>
					  </tr>';
        $rcount++;
    }
$pagebody .= '</table>';

// $pagebody .= displayContentBoxBottom();

//Create holders for each combination of portals/templates
while ($row = $portals->FetchRow()) {
    foreach ($template_list as $k=>$v) {
        $pagebody .= '<div id="'.md5($v['template']).'_'.$row['xPortal'].'" style="display:none;"></div>';
    }
}
//for default portal
foreach ($template_list as $k=>$v) {
    $pagebody .= '<div id="'.md5($v['template']).'_0" style="display:none;"></div>';
}

/*
$pagebody = '
<div id="feedback">'.$feedback.'</div>
<div id="template_selection">'.$template_table.'</div>

<form action="'.$basepgurl.'" method="post">
'.csrf_field().'
<div id="editbox" style="height:800px;">
    <h2 id="edit_label" style="font-size:13px;">'.lg_admin_portaltemplates_editarea.'</h2>
    <textarea name="template_code" id="template_code" wrap="off" class="lined" rows="60" cols="60" style="width:100%;height:800px;"></textarea>

    <div class="formbuttondiv" style="width:98%;">
        <input type="submit" name="submit" value="'.lg_admin_portaltemplates_savebutton.'" class="formbutton">
    </div>

</div>
<input type="hidden" name="template_name" id="template_name" value="" />
</form>';
*/
