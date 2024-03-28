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

define('lg_admin_rules_title', 'Mail Rules');
define('lg_admin_rules_namecol', 'Rule Name');
define('lg_admin_rules_add', 'Add a Mail Rule');
define('lg_admin_rules_button', 'Add Rule');
define('lg_admin_rules_buttonedit', 'Save Rule');
define('lg_admin_rules_edit', 'Edit Rule');
define('lg_admin_rules_name', 'Name');
define('lg_admin_rules_anyall', 'If %s of the following conditions are met');
define('lg_admin_rules_then', 'Perform these actions');
define('lg_admin_rules_any', 'any');
define('lg_admin_rules_all', 'all');
define('lg_admin_rules_colid', 'ID');
define('lg_admin_rules_del', 'Make Rule Inactive');
define('lg_admin_rules_delwarn', 'Are you sure you want to make this rule inactive?');
define('lg_admin_rules_showdel', 'Show Inactive Rules');
define('lg_admin_rules_noshowdel', 'Return to Rules');
define('lg_admin_rules_noname', 'Please provide a name for this rule');
define('lg_admin_rules_fbinactive', 'Rule made inactive');
define('lg_admin_rules_fbrestored', 'Rule restored');
define('lg_admin_rules_fbadded', 'Rule added');
define('lg_admin_rules_fbedited', 'Rule edited');
define('lg_admin_rules_sorttitle', 'Mail Rules Order');
define('lg_admin_rules_note', 'Rules apply only to new request emails; any follow-ups to existing requests will not be impacted.');
define('lg_admin_rules_hourlabel', 'Rule in effect');
define('lg_admin_rules_anyhours', 'At all times');
define('lg_admin_rules_bizhours', 'Business hours only');
define('lg_admin_rules_offhours', 'Outside of business hours only');
define('lg_admin_rules_options', 'Options');
