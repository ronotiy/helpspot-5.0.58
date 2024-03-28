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

define('lg_admin_integrations_title', 'Integrations');
define('lg_admin_integrations_header', 'Magically push data to over 2,000 other apps with ');
define('lg_admin_integrations_info', 'Connect HelpSpot to 500+ other apps and services using Zapier.');
define('lg_admin_integrations_start_zapier','Start Zapier Integration');
define('lg_admin_integrations_docs', 'HelpSpot Zapier Documentation');

define('lg_admin_thermostat_header','Send NPS or CSAT surveys to your customers with ');
define('lg_admin_thermostat_connect','Save Thermostat API Token');
define('lg_admin_thermostat_learn_about','Learn About Thermostat');
define('lg_admin_thermostat_get_token', 'Get an API Token');
define('lg_admin_thermostat_label_api_token', 'Thermostat API Token');
define('lg_admin_thermostat_token_value', 'placeholder="<token hidden>

Enter a new (or empty) token to update (or remove) the Thermostat API token."');
