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

define('lg_search_customer', 'Customers');
define('lg_search_data', 'Full Text');
define('lg_search_advanced', 'Detailed');
define('lg_search_tags', 'Knowledge Tags');
define('lg_search_tagspick', 'Add a Tag');
define('lg_search_tagsnone', 'No tags added to search');
define('lg_search_tips', 'Search Tips');
define('lg_search_fulltext', 'Full text data search');
define('lg_search_saveasfilter', 'Save as Filter');
define('lg_search_saveasfilterlabel', 'Filter Name');
define('lg_search_saveasfilterex', 'Note, more advanced filters can be created in Workspace->Filter Requests');
define('lg_search_reporttimeclosed', 'Requests closed between');
define('lg_search_reporttime', 'Requests opened between');

define('lg_search_title', 'Search');
define('lg_search_cust_title', 'Customer History Search');
define('lg_search_cust_titletips', 'Customer History Search Tips');
define('lg_search_info', 'Data Search');
define('lg_search_infotips', 'Full Text Search Tips');
define('lg_search_all', 'All Areas');
define('lg_search_requests', 'Requests');
define('lg_search_wild', 'Use * for wildcard searches. Ex: yahoo*, *yahoo.com, *yahoo*');
define('lg_search_boolsearching', 'Boolean searching available: + must be present, - must not, "phrase searching"');
define('lg_search_request', 'Request');
define('lg_search_my_tip1', 'Use + for must contain: +printer, +password');
define('lg_search_my_tip2', 'Use - for must not contain: -printer, -password');
define('lg_search_my_tip3', 'Use " " for a phrase: "lost my password"');
define('lg_search_my_tip4', 'Use * as a wildcard: print*');
define('lg_search_my_tip5', 'Combinations work: +printer +paper -Lexmark, "printer paper" -Lexmark');
define('lg_search_ms_tip1', 'Use AND for multiple word searches: printer AND paper');
define('lg_search_ms_tip2', 'Use OR for matching any one of multiple words: printer OR paper');
define('lg_search_ms_tip3', 'Combine AND/OR: (printer AND paper) OR Lexmark');
define('lg_search_ms_tip4', 'Use " " for a phrases: "lost my password');
define('lg_search_ms_tip5', 'Use * as a wildcard: "print*" (quotes required)');
define('lg_search_pg_tip1', 'Use " " for a phrase: "lost my password"');
