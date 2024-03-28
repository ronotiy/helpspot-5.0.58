<?php

// SECURITY: Don't allow direct calls to this file outside the context of HelpSpot
if (! defined('cBASEPATH')) {
    die();
}

/* EDITING NOTES
1. Some strings contain %s symbols, these must be maintained
2. Any single quotes must be preceded by a slash like this:  \'  ex: there\'s
3. If you modify this file, be sure to back it up in case you overwrite it during an upgrade by accident
*/

define('lg_request_batch_title', 'Process Batch Requests');
define('lg_request_batch_instr', 'Do not leave this page until the process completes');
define('lg_request_batch_processed', '%s of %s Completed');
define('lg_request_batch_processing', 'Processing...');
define('lg_request_batch_complete_link', 'Go to batch filter');
define('lg_request_batch_smtp', 'SMTP Error, email not sent');
define('lg_request_batch_ergeneral', 'Error, request not updated');
define('lg_request_batch_batch', 'Batch');
define('lg_request_batch_batchhistory', 'Batch History');
