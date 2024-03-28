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

define('lg_home_admin_title', 'Administrator Homepage');
define('lg_home_admin_newhelpspot', 'A new HelpSpot version is available');
define('lg_home_admin_download', 'download');
define('lg_home_admin_releasenotes', 'notes');
define('lg_home_admin_instructions', 'instructions');
define('lg_home_admin_permwarning', 'This installations source files (some or all) appear to be writable by the web server (777). Please change file permissions to be less open.');

define('lg_home_admin_install', 'HelpSpot Installation');
define('lg_home_admin_renew', 'Renew');
define('lg_home_admin_licupload', 'Upload a New License');
define('lg_home_admin_licuploadok', 'New License Uploaded');
define('lg_home_admin_licnotvalid', 'License not valid. Check the most recent one was uploaded.');
define('lg_home_admin_toomanyusers', 'You have more active users than the new license allows. Please deactivate some users and re-upload the license.');
define('lg_home_admin_customerid', 'Customer ID');
define('lg_home_admin_licusers', 'Staff/Licenses');
define('lg_home_admin_licsupport', 'Support Ends');
define('lg_home_admin_version', 'HelpSpot Version');
define('lg_home_admin_maintenance', 'Maintenance Mode');
define('lg_home_admin_maintenance_desc', 'Immediately put the system into maintenance mode (system will not receive requests and disable staff login). Maintenance mode is automatically turned on/off during upgrades.');
define('lg_home_admin_maintenance_button', 'Enable Maintenance Mode');
define('lg_home_admin_maintenance_button_conf', 'Are you sure?');
define('lg_home_admin_addlicenses', 'Add Licenses');
define('lg_home_admin_unlimited', 'Site License');
