<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//clean out any data in the buffer
ob_end_clean();

//Make sure /admin doesn't output any headers
$pagebody = '';
$tab = '';
$subtab = '';
$htmldirect = true;

if (! IN_PORTAL) {
    hs_cache_forever();

    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : 0;

    if ($id != 0) {
        // Get file contents
        if ($file = $GLOBALS['DB']->GetRow('SELECT sFilename,sFileMimeType,blobPhoto,sSeries FROM HS_Person_Photos WHERE xPersonPhotoId = ?', [$id])) {
            // Serve it
            header('Content-Type: '.$file['sFileMimeType'], true);
            header('Content-Disposition: inline; filename="'.urldecode($file['sFilename']).'"', true);

            //Clean any items that may have been sent before this point
            //ob_clean();

            if ($file['sSeries'] && $file['sSeries'] != 'upload') {
                echo file_get_contents(cBASEPATH.'/static/img/avatars/'.$file['sSeries'].'/'.$file['sFilename']);
            } else {
                echo $file['blobPhoto'];
            }
        }
    }
}
