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

define('lg_admin_responses_title', 'Request Responses');
define('lg_admin_responses_perms', 'Usable By');
define('lg_admin_responses_recurring', 'Recurring');
define('lg_admin_responses_foldernameex', 'You can create sub folders by separating folder names with a / for up to 3 levels. ex: Accounts / Login / Passwords');
define('lg_admin_responses_myfolder', 'My Responses');
define('lg_admin_responses_addfolder', 'Add Folder');
define('lg_admin_responses_folder', 'Folder');
define('lg_admin_responses_addattachment', 'Add Attachment');
define('lg_admin_responses_advoptions', 'Request Actions');
define('lg_admin_responses_adv_change', 'Change');
define('lg_admin_responses_adv_add', 'Insert Into');
define('lg_admin_responses_adv_subject', 'Email Subject');
define('lg_admin_responses_adv_status', 'Status');
define('lg_admin_responses_adv_category', 'Category');
define('lg_admin_responses_adv_reptags', 'Reporting Tags');
define('lg_admin_responses_adv_assigned', 'Assigned Staffer');
define('lg_admin_responses_adv_assignednote', 'Staffer must be part of the assigned category');
define('lg_admin_responses_adv_note', 'Note');
define('lg_admin_responses_adv_pub', 'Public Note');
define('lg_admin_responses_adv_priv', 'Private Note');
define('lg_admin_responses_adv_ext', 'External Note');
define('lg_admin_responses_adv_tofield', 'TO Field');
define('lg_admin_responses_adv_tofield_note', '(for external notes only)');
define('lg_admin_responses_adv_cc', 'CC Field');
define('lg_admin_responses_adv_bcc', 'BCC Field');
define('lg_admin_responses_adv_sepcomma', 'Separate each email with a comma');
define('lg_admin_responses_adv_emailfrom', 'Send Email From');
define('lg_admin_responses_showdel', 'Show Inactive Responses');
define('lg_admin_responses_noshowdel', 'Return to Active Responses');
define('lg_admin_responses_colrestitle', 'Response Title');
define('lg_admin_responses_resdel', 'Set Inactive');
define('lg_admin_responses_resdelwarn', 'Are you sure you want to make this response inactive?');
define('lg_admin_responses_restorewarn', 'Are you sure you want to restore this response?');
define('lg_admin_responses_search', 'Search: Responses...');
define('lg_admin_responses_add', 'Add a Response');
define('lg_admin_responses_edit', 'Edit: ');
define('lg_admin_responses_addbutton', 'Add Response');
define('lg_admin_responses_editbutton', 'Save Edits');
define('lg_admin_responses_restitle', 'Response Title');
define('lg_admin_responses_response', 'Response Note Text');
define('lg_admin_responses_createdby', 'Created By');
define('lg_admin_responses_typeuser', 'Creator Only');
define('lg_admin_responses_typegroup', 'Group');
define('lg_admin_responses_typeppl', 'Selected People');
define('lg_admin_responses_er_title', 'Please provide a title for this response');
define('lg_admin_responses_er_response', 'Please provide a response');
define('lg_admin_responses_er_folder', 'Please select/create a folder for this response');
define('lg_admin_responses_fbedited', 'Response edited');
define('lg_admin_responses_fbadded', 'Response added');
define('lg_admin_responses_setactive', 'The response has been activated');
define('lg_admin_responses_setinactive', 'The response has been set inactive');
define('lg_admin_responses_explanation', 'Request responses are predefined answers to commonly asked questions.');
define('lg_admin_responses_saveas_details', 'Name new response.');
define('lg_admin_responses_togglerd', 'Request Details');
define('lg_admin_responses_togglecf', 'Custom Fields');
define('lg_admin_responses_togglenote', 'Note Options');
define('lg_admin_responses_scheduling', 'Convert to Recurring Request');
define('lg_admin_responses_scheduling_enabled', 'Enable Recurring Request');
define('lg_admin_responses_scheduling_info', 'HelpSpot creates a new request at the defined interval based on this response. Perfect for regular maintenance tasks or recurring reminders.');
define('lg_admin_responses_scheduling_customerinfo', 'Customer Details (at least one required)');
define('lg_admin_responses_dontemail', 'Do not send email');
define('lg_admin_responses_owner', 'Response Owner');
define('lg_admin_responses_create_schedule', 'Create Request');
define('lg_admin_responses__time', 'at/on');
define('lg_admin_responses_first_name', 'First Name');
define('lg_admin_responses_last_name', 'Last Name');
define('lg_admin_responses_email', 'Email');
define('lg_admin_responses_phone', 'Phone');
define('lg_admin_responses_customer_id', 'Customer ID');
