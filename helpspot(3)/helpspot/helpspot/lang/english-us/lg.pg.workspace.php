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

define('lg_workspace_title', 'Workspace');

define('lg_workspace_options', 'Options');
define('lg_workspace_stream', 'Stream');

define('lg_workspace_filterop', 'Filter Options');
define('lg_workspace_mkdefault', 'Make Default Workspace');
define('lg_workspace_edit', 'Edit');
define('lg_workspace_delete', 'Delete Filter');
define('lg_workspace_filter_options', 'Options');
define('lg_workspace_report', 'Report View');
define('lg_workspace_triage', 'Request Triage');
define('lg_workspace_rss', 'RSS Feed');
define('lg_workspace_export', 'CSV Export');
define('lg_workspace_triageassign', 'Assign');
define('lg_workspace_triagecategory', 'Category');
define('lg_workspace_triageassignto', 'Assign To');
define('lg_workspace_deleteconf', 'Are you sure you want to delete this filter?');
define('lg_workspace_merge', 'Merge');
define('lg_workspace_movetotrash', 'Trash');
define('lg_workspace_changestatus', 'Status');
define('lg_workspace_markspam', 'SPAM');
define('lg_workspace_marknotspam', 'Mark as NOT SPAM');
define('lg_workspace_nottrash', 'Restore to Inbox');
define('lg_workspace_close', 'Close');
define('lg_workspace_respond', 'Batch Respond');
define('lg_workspace_markdelspam', 'Delete SPAM');
define('lg_workspace_moveto', 'Reassign');
define('lg_workspace_selectcat', 'Select a Category');
define('lg_workspace_assignto', 'Assign to');
define('lg_workspace_selstatus', 'Choose a Status');
define('lg_workspace_closestatus', 'Choose a Closing Status');
define('lg_workspace_selmerge', 'Merge all selected requests into:');
define('lg_workspace_customize', 'Customize Columns');
define('lg_workspace_notemail', 'Some requests were not marked as SPAM because they were not email or from the web form');
define('lg_workspace_notemailqm', 'The request was not marked as SPAM because it was not from email or the web form');
define('lg_workspace_spammessage', 'Note: spam must be deleted in order to train the spam filter');
define('lg_workspace_spammessageoff', 'Note: the spam filter is set to off in Admin->Settings->Email Integration. In this configuration deleting spam will delete the requests, but not train the spam filter.');
define('lg_workspace_trashmessage', 'Trash will be deleted %s after being marked trash. Once deleted they are are unrecoverable.');
define('lg_workspace_trashmessagenever', 'Requests in the trash will not be deleted.');
define('lg_workspace_stuckmsg', 'HelpSpot is unable to pull one or more emails from a mailbox.');
define('lg_workspace_stuckmsg3', 'To remove this message, manually delete the email(s) via web mail or desktop client as soon as possible.');
define('lg_workspace_stuckmsg2', 'One reason this can occur is if there is not enough PHP memory to import the email. <a href="https://support.helpspot.com/index.php?pg=kb.page&id=34" target="_blank">Click here</a> for details on how to adjust the memory settings. Another reason is if the email is improperly formatted and cannot be parsed/imported.');
define('lg_workspace_stucklist', 'Stuck Email List');
define('lg_workspace_stuckbox', 'Mailbox');
define('lg_workspace_stuckfrom', 'Email From');
define('lg_workspace_stuckdate', 'Date');
define('lg_workspace_stuckviewdetails', 'View details.');
define('lg_workspace_stuckretry', 'Clear and Retry');
define('lg_workspace_markunread_msg', 'Marked Unread');
define('lg_workspace_markread_msg', 'Marked Read');
define('lg_workspace_batchchange', 'Processing Batch');
define('lg_workspace_viewable', 'Viewable By');
define('lg_workspace_viewable_onlyme', 'Only Me');
define('lg_workspace_viewable_permstaff', 'Permitted Staff');
define('lg_workspace_viewable_everyone', 'Everyone');
define('lg_workspace_viewable_group', 'Group');
