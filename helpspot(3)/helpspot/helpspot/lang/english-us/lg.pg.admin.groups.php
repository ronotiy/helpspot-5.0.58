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

define('lg_admin_groups_title', 'Permission Groups');
define('lg_admin_groups_addcat', 'Add a Group');
define('lg_admin_groups_addbutton', 'Add Group');
define('lg_admin_groups_delbutton', 'Delete Group');
define('lg_admin_groups_delbuttonwarn', 'Are you sure you want to delete this group? This cannot be undone.');
define('lg_admin_groups_delbuttoncant', 'Group still contains staff. Can not be deleted.');
define('lg_admin_groups_editcat', 'Edit: ');
define('lg_admin_groups_editbutton', 'Save Edits');
define('lg_admin_groups_adminmsg', 'The administrator level cannot be edited');

define('lg_admin_groups_permissions', 'Module Permissions');
define('lg_admin_groups_permissionsaccess', 'Access Permissions');
define('lg_admin_groups_fModuleReports', 'Reports');
define('lg_admin_groups_fModuleForumsPriv', 'View private forums');
define('lg_admin_groups_fModuleKbPriv', 'View private KB\'s');
define('lg_admin_groups_fViewInbox', 'Workspace inbox');

define('lg_admin_groups_fCanBatchRespond', 'Batch respond to requests');
define('lg_admin_groups_fCanMerge', 'Merge requests');
define('lg_admin_groups_fCanViewOwnReqsOnly', 'Can view own requests ONLY');
define('lg_admin_groups_fCanViewOwnReqsOnlyex', 'This is a very limiting permission allowing a user to only view their own requests as well as limiting searching and filtering.');
define('lg_admin_groups_fLimitedToAssignedCats', 'Limit to assigned categories ONLY');
define('lg_admin_groups_fLimitedToAssignedCatsex', 'The user can only see other users and assign to other users in the same categories they are in.');
define('lg_admin_groups_fCanTransferRequests', 'User can transfer requests to staff in other categories');
define('lg_admin_groups_fCanTransferRequestsex', 'This permission applies when the "Limit to assigned categories ONLY" permission is selected. Allows staff to transfer requests to categories they are not assigned to.');
define('lg_admin_groups_fCanAdvancedSearch', 'Access the advanced search page');
define('lg_admin_groups_fCanManageSpam', 'Manage SPAM (mark as and delete)');
define('lg_admin_groups_fCanManageTrash', 'Manage Trash (mark as and delete)');
define('lg_admin_groups_fCanManageKB', 'Manage Knowledge Books (create, edit, delete)');
define('lg_admin_groups_fCanManageForum', 'Manage forums  (create, edit, delete)');

define('lg_admin_groups_colid', 'ID');
define('lg_admin_groups_colgroup', 'Name');
define('lg_admin_groups_colclonegrp', 'Clone Group');
define('lg_admin_groups_colclone', 'Clone');

define('lg_admin_groups_fbdeleted', 'Group deleted');
define('lg_admin_groups_fbedited', 'Group edited');
define('lg_admin_groups_fbadded', 'Group added');
define('lg_admin_groups_er_groups', 'Please fill in group name');
