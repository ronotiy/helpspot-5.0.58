<?php
// SECURITY: Don't allow direct calls
if (!defined('cBASEPATH')) die();

//protect to only admins
if(!isAdmin()) die();

/*****************************************
Libs
 *****************************************/
include(cBASEPATH.'/helpspot/lib/api.requests.lib.php');
include(cBASEPATH.'/helpspot/lib/class.destroy.php');
include(cBASEPATH.'/helpspot/lib/class.export.php');

/*****************************************
LANG
 *****************************************/
$GLOBALS['lang']->load('admin.tools.customer');

/*****************************************
VARIABLE DECLARATIONS
 *****************************************/
$basepgurl     = cHOST.'/admin.php?pg=admin.tools.customer';
$hidePageFrame = 0;
$pagetitle     = lg_admin_tools_customer_title;
$tab           = 'nav_admin';
$subtab        = 'nav_admin_tools';
$feedback      = '';
$pagebody      = '';

/*****************************************
HELPERS
 *****************************************/

/**
 * @param $basepgurl
 * @param $id
 * @param $reqs
 * @param $action
 * @return string
 */
function buildConfirmation($basepgurl, $id, $reqs, $action)
{
    $delbutton = '<button type="submit" class="btn altbtn" id="delete" style="margin-right: 20px;">'.lg_admin_tools_customer_delete.'</button>';
    $out = '<form action="'.$basepgurl.'" method="POST">'. csrf_field();
    $out .= '<input type="hidden" name="action" value="'.$action.'">';
    $out .= '<input type="hidden" name="id" value="'.formClean($id).'">';
    $out .= displayContentBoxTop('Delete for '.$id);
    if (hs_rscheck($reqs)){
        $out .= '<p>'.sprintf(lg_admin_tools_customer_mutli_found, $reqs->RecordCount(), $id).'</p>';
    } else {
        $out .= '<p>'.lg_admin_tools_customer_one_found.'</p>';
    }

    $out .= '<p>'.lg_admin_tools_customer_warning.'</p>';
    $out .= '
            <table class="ft">
                <tr class="trr">
                    <td class="tdl"><label class="datalabel">'.lg_admin_tools_customer_confirm.'</label></td>
                    <td class="tdr">
                        <input type="text" name="agree" autocomplete="off">
                    </td>
                </tr>
            </table>';
    $out .= displayContentBoxBottom($delbutton.'<a href="'.$basepgurl.'" class="btn theme nofloat">Cancel</a>');
    $out .= '</form>';
    return $out;
}

/*****************************************
ACTIONS
 *****************************************/
$destroy = new Destroy();

if(isset($_GET['action']) and $_GET['action'] == 'delete_note') {
    $id = (int) $_GET['id'];
    $req = $destroy->getRequestHistory($id);
    if (! $req) {
        $pagebody = errorBox('Request Note '.$id.' not found');
    } else {
        $pagebody = buildConfirmation($basepgurl, $id, $req, 'delete_note');
    }
}

if(isset($_GET['action']) and $_GET['action'] == 'delete_request') {
    $id = (int) $_GET['id'];
    $req = apiGetRequest($id);
    if (! $req) {
        $pagebody = errorBox('Request '.$id.' not found');
    } else {
        $pagebody = buildConfirmation($basepgurl, $id, $req, 'delete_request');
    }
}

if(isset($_GET['action']) and $_GET['action'] == 'delete_email') {
    $email = hs_htmlentities($_GET['id']);
    $reqs = $destroy->findRequestsByEmail($email);
    if (! $reqs) {
        $pagebody = errorBox('No Requests for '.$email.' found');
    } else {
        $pagebody = buildConfirmation($basepgurl, $email, $reqs, 'delete_email');
    }
}

if(isset($_GET['action']) and $_GET['action'] == 'delete_customer_id') {
    $id = hs_htmlentities($_GET['id']);
    $reqs = $destroy->findRequestsByCustomerId($id);
    if (! $reqs) {
        $pagebody = errorBox('No Requests for '.$id.' found');
    } else {
        $pagebody = buildConfirmation($basepgurl, $id, $reqs, 'delete_customer_id');
    }
}


/*****************************************
Exporting
 *****************************************/
if (isset($_POST['action']) and $_POST['action'] == 'export') {
    // first make sure we have the ability to create a zip
    if (! class_exists(ZipArchive::class)) {
        die('You will need to compile PHP with --enable-zip before this tool can be used.');
    }
    $email = hs_htmlentities($_POST['email']);
    $export = new Export($email);
    $file = $export->requestsByEmail();
    //then send the headers to force download the zip file
    if (! file_exists($export->zipFile)){
        die('The file '. $export->zipFile.' can not be found');
    }

    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=export.zip");
    header("Content-length: " . filesize($export->zipFile));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile($export->zipFile);
    unlink($export->zipFile);
    exit;
}


/*****************************************
This is the real deletes
 *****************************************/

// Delete history
if (isset($_POST['action']) and $_POST['action'] == 'delete_note') {
    if ($_POST['agree'] != 'I AGREE') {
        $pagebody = errorBox(lg_admin_tools_customer_agree);
    } else {
        $id = (int) $_POST['id'];
        $req = $destroy->getRequestHistory($id);
        if (! $req) {
            $pagebody = errorBox('No Request note for ' . $id . ' found');
        } else {
            $destroy->history($id);
            hs_redirect('Location: ' . $basepgurl . '&s=1');
        }
    }
}


// Delete by email
if (isset($_POST['action']) and $_POST['action'] == 'delete_email') {
    if ($_POST['agree'] != 'I AGREE') {
        $pagebody = errorBox(lg_admin_tools_customer_agree);
    } else {
        $email = hs_htmlentities($_POST['id']);
        $reqs = $destroy->findRequestsByEmail($email);
        if (! $reqs or ! hs_rscheck($reqs)) {
            $pagebody = errorBox('No Requests for '.$email.' found');
        } else {
            logMsg('Delete by email '. $email);
            $destroy->requests($reqs);
            $destroy->redactEmailInEvents($email);
            hs_redirect('Location: ' . $basepgurl . '&s=1');
        }
    }
}

// Delete by customer id
if (isset($_POST['action']) and $_POST['action'] == 'delete_customer_id') {
    if ($_POST['agree'] != 'I AGREE') {
        $pagebody = errorBox(lg_admin_tools_customer_agree);
    } else {
        $id = hs_htmlentities($_POST['id']);
        $reqs = $destroy->findRequestsByCustomerId($id);
        if (! $reqs or ! hs_rscheck($reqs)) {
            $pagebody = errorBox('No Requests for '.$id.' found');
        } else {
            logMsg('Delete by customer id #'. $id);
            $destroy->requests($reqs);
            $destroy->redactCustomerInEvents($id);
            hs_redirect('Location: ' . $basepgurl . '&s=1');
        }
    }
}

// Delete single request
if (isset($_POST['action']) and $_POST['action'] == 'delete_request') {
    if ($_POST['agree'] != 'I AGREE') {
        $pagebody = errorBox(lg_admin_tools_customer_agree);
    } else {
        $id = (int) $_POST['id'];
        $req = apiGetRequest($id);
        if (! $req) {
            $pagebody = errorBox('Request '.$id.' not found');
        } else {
            $destroy->request($id);
            hs_redirect('Location: ' . $basepgurl . '&s=1');
        }
    }
}

if($_GET['s'] == 1){
    $feedback = displayFeedbackBox(lg_admin_tools_customer_finished);
}

/*****************************************
JAVASCRIPT
 *****************************************/
$headscript  = '
<script type="text/javascript" language="JavaScript">
    function confirmDelete(id) {
        var url = "'.$basepgurl .'&action="+id+"&id="+getId(id);
        return hs_confirm(\''.lg_admin_tools_customer_sure.'\',url);
    }
    function getId(id) {
        return $jq("#"+id).val();
    }
</script>
';

/*****************************************
PAGE OUTPUTS
 ****************************************/
$pagebody .= $feedback.'
    '.renderPageheader(lg_admin_tools_customer_manage_data).'
        <div class="card padded">
            <div class="sectionhead">'.lg_admin_tools_customer_export_data.'</div>
            <div class="fr">
                <div class="label">
                    <label>'.lg_admin_tools_customer_export_email.'</label>
                </div>
                <div class="control">
                    <form action="'.$basepgurl.'" method="post">
                        '.csrf_field().'
                        <input type="email" name="email" placeholder="'.lg_admin_tools_customer_email.'" required>
                        <input type="hidden" name="action" value="export">
                        <button type="submit" class="btn nofloat inline">'.lg_admin_tools_customer_export.'</button>
                    </form>
                </div>
            </div>

            <div class="sectionhead">'.lg_admin_tools_customer_delete_data.'</div>
            <div class="fr">
                <div class="label">
                    <label>'.lg_admin_tools_customer_delete_request_note.'</label>
                    <div class="info">'.lg_admin_tools_customer_delete_request_note_info.'</div>
                </div>
                <div class="control">
                    <form action="#">
                        '.csrf_field().'
                        <input type="text" placeholder="'.lg_admin_tools_customer_delete_request_note_id.'" id="delete_note">
                        <button class="btn nofloat inline" onClick="return confirmDelete(\'delete_note\');">'.lg_admin_tools_customer_delete.'</button>
                    </form>
                </div>
            </div>
            <div class="hr"></div>
            <div class="fr">
                <div class="label">
                    <label>'.lg_admin_tools_customer_delete_request.'</label>
                    <div class="info">'.lg_admin_tools_customer_request_id_note.'</div>
                </div>
                <div class="control">
                    <form action="#">
                        '.csrf_field().'
                        <input type="text" placeholder="'.lg_admin_tools_customer_request_id.'" id="delete_request">
                        <button class="btn nofloat inline" onClick="return confirmDelete(\'delete_request\');">'.lg_admin_tools_customer_delete.'</button>
                    </form>
                </div>
            </div>
            <div class="hr"></div>
            <div class="fr">
                <div class="label">
                    <label>'.lg_admin_tools_customer_delete_by_email.'</label>
                    <div class="info">'.lg_admin_tools_customer_delete_by_email_note.'</div>
                </div>
                <div class="control">
                    <form action="#">
                        '.csrf_field().'
                        <input type="text" placeholder="'.lg_admin_tools_customer_email_address.'" id="delete_email">
                        <button class="btn nofloat inline" onClick="return confirmDelete(\'delete_email\');">'.lg_admin_tools_customer_delete.'</button>
                    </form>
                </div>
            </div>
            <div class="hr"></div>
            <div class="fr">
                <div class="label">
                    <label>'.lg_admin_tools_customer_delete_by_id.'</label>
                    <div class="info">'.lg_admin_tools_customer_delete_by_id_note.'</div>
                </div>
                <div class="control">
                    <form action="#">
                        '.csrf_field().'
                        <input type="text" placeholder="Customer ID" id="delete_customer_id">
                        <button class="btn nofloat inline" onClick="return confirmDelete(\'delete_customer_id\');">'.lg_admin_tools_customer_delete.'</button>
                    </form>
                </div>
            </div>

        </div>
    ';
