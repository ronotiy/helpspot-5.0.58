<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$htmldirect = 1;
$pagebody = '';

//If being called from the editor then send this header stuff as well, else just send the custom styles
if (isset($_GET['foreditor'])) {
    $pagebody .= '
	body {
		background-color: #FFFFFF;
		font-family: Verdana, Arial, Helvetica, sans-serif;
		font-size: 13px;
		scrollbar-3dlight-color: #F0F0EE;
		scrollbar-arrow-color: #676662;
		scrollbar-base-color: #F0F0EE;
		scrollbar-darkshadow-color: #DDDDDD;
		scrollbar-face-color: #E0E0DD;
		scrollbar-highlight-color: #F0F0EE;
		scrollbar-shadow-color: #F0F0EE;
		scrollbar-track-color: #F5F5F5;
	}
	
	td {
		font-family: Verdana, Arial, Helvetica, sans-serif;
		font-size: 13px;
	}
	
	pre {
		font-family: Verdana, Arial, Helvetica, sans-serif;
		font-size: 13px;
	}
	
	.mceVisualAid {
		border: 1px dashed #BBBBBB;
	}
	';
}

if (hs_setting('IN_PORTAL', false)) {
   return response($pagebody, 200, [
       'Content-Type' => 'text/css',
       'Content-Disposition' => 'inline; filename="wysiwyg.css"',
   ]);
}

header('Content-type: text/css');
header('Content-Disposition: inline; filename="wysiwyg.css"');

$pagebody .= hs_setting('cHD_WYSIWYG_STYLES');
