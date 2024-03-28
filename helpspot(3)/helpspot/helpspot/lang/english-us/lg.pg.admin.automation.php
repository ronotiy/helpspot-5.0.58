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

define('lg_admin_automation_title', 'Automation Rules');
define('lg_admin_automation_namecol', 'Rule Name');
define('lg_admin_automation_add', 'Add an Automation Rule');
define('lg_admin_automation_button', 'Add Rule');
define('lg_admin_automation_buttonedit', 'Save Rule');
define('lg_admin_automation_anyall', 'If %s of the following conditions are met');
define('lg_admin_automation_edit', 'Edit Rule');
define('lg_admin_automation_name', 'Name');
define('lg_admin_automation_then', 'Perform these actions');
define('lg_admin_automation_any', 'any');
define('lg_admin_automation_all', 'all');
define('lg_admin_automation_colid', 'ID');
define('lg_admin_automation_options', 'Options');
define('lg_admin_automation_options_custom_schedule', 'Run Schedule');
define('lg_admin_automation_options_custom_scheduleex', 'How often this Automation Rule will run.');
define('lg_admin_automation_options_nonotificaitons', 'Suppress any staff notifications that result from Actions');
define('lg_admin_automation_options_directcallonly', 'Custom Schedule');
define('lg_admin_automation_options_directcallonlyex', 'Run the automation rule on demand or on a custom schedule. By checking this option this rule will not be run unless the ID of this automation rule is explicitly defined with the automation:rule command. <br /><a href="https://support.helpspot.com/index.php?pg=kb.page&id=143" target="_blank">Details on how to call automation:rule for a single ID</a>.');
define('lg_admin_automation_options_schedule_every_minute', 'Every minute');
define('lg_admin_automation_options_schedule_every_5_minutes', 'Every 5 minutes');
define('lg_admin_automation_options_schedule_every_hour', 'Every hour');
define('lg_admin_automation_options_schedule_twice_daily', 'Twice daily');
define('lg_admin_automation_options_schedule_daily', 'Daily');
define('lg_admin_automation_options_schedule_weekly', 'Weekly');
define('lg_admin_automation_options_schedule_monthly', 'Monthly (start of the month)');
define('lg_admin_automation_del', 'Make Rule Inactive');
define('lg_admin_automation_delwarn', 'Are you sure you want to make this rule inactive?');
define('lg_admin_automation_showdel', 'Show Inactive Rules');
define('lg_admin_automation_noshowdel', 'Return to Rules');
define('lg_admin_automation_noname', 'Please provide a name for this rule');
define('lg_admin_automation_nottested', 'You must test the rule before submitting');
define('lg_admin_automation_fbinactive', 'Rule made inactive');
define('lg_admin_automation_fbrestored', 'Rule restored');
define('lg_admin_automation_fbadded', 'Rule added');
define('lg_admin_automation_fbedited', 'Rule edited');
define('lg_admin_automation_sorttitle', 'Automation Rules Order');
define('lg_admin_automation_note', 'Use Automation to escalate requests, notify staff and customers, change assignments and more. Note that Automation rules can be dangerous if not implemented correctly. Always be sure to test your conditions before saving a rule. Once a rule has modified a request it cannot be undone.');
define('lg_admin_automation_confirm', 'Are you sure you want to add this rule? It will take effect immediately.');
define('lg_admin_automation_once', 'Run this rule once per request');
