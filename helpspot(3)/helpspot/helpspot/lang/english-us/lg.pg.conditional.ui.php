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

define('lg_conditional_remcon', 'Remove condition');
define('lg_conditional_addcon', 'Add condition');
define('lg_conditional_phpregex', 'Enter PHP Regular Expression');
define('lg_conditional_ftwarning', 'Full text searches in a filter should be used with caution; they have the potential to be database intensive. If you are using Sphinx Search (now standard with HelpSpot), this is less likely to be an issue.');
define('lg_conditional_ftwarning2', 'Counts cannot be displayed in the workspace for filters using full text search');

define('lg_conditional_mr_to', 'To');
define('lg_conditional_mr_from', 'From');
define('lg_conditional_mr_cc', 'Cc');
define('lg_conditional_mr_subject', 'Subject');
define('lg_conditional_mr_headers', 'Headers');
define('lg_conditional_mr_emailbody', 'Email body');
define('lg_conditional_mr_customerid', 'Customer ID');
define('lg_conditional_mr_mailbox', 'Mailbox');
define('lg_conditional_mr_hasattach', 'Has attachments');
define('lg_conditional_mr_urgent', 'Is urgent');
define('lg_conditional_mr_spam', 'Is SPAM');
define('lg_conditional_mr_notspam', 'Is Not SPAM');

define('lg_conditional_mr_is', 'Is');
define('lg_conditional_mr_isnot', 'Is Not');
define('lg_conditional_mr_begins', 'Begins with');
define('lg_conditional_mr_ends', 'Ends with');
define('lg_conditional_mr_contains', 'Contains');
define('lg_conditional_mr_notcontain', 'Does not contain');
define('lg_conditional_mr_matches', 'Matches');

define('lg_conditional_mra_setcat', 'Set category/staffer');
define('lg_conditional_mra_setcustom', 'Set custom fields');
define('lg_conditional_mra_close', 'Close request');
define('lg_conditional_mra_setstatus', 'Set status');
define('lg_conditional_mra_markurgent', 'Mark urgent');
define('lg_conditional_mra_marknoturgent', 'Mark not urgent');
define('lg_conditional_mra_movetotrash', 'Move to trash');
define('lg_conditional_mra_movetoinbox', 'Move to inbox');
define('lg_conditional_mra_notify', 'Send email notification to');
define('lg_conditional_mra_instantreply', 'Instantly reply');
define('lg_conditional_mra_addprivatenote', 'Add a Private Note');

define('lg_conditional_at_userid', 'Customer ID');
define('lg_conditional_at_xrequest', 'Request ID');
define('lg_conditional_at_email', 'Email');
define('lg_conditional_at_fname', 'First name');
define('lg_conditional_at_lname', 'Last name');
define('lg_conditional_at_phone', 'Phone');
define('lg_conditional_at_openvia', 'Contacted via');
define('lg_conditional_at_status', 'Status');
define('lg_conditional_at_open', 'Open/Closed');
define('lg_conditional_at_urgent', 'Is urgent');
define('lg_conditional_at_not_urgent', 'Is Not urgent');
define('lg_conditional_at_urgency', 'Urgency');
define('lg_conditional_at_category', 'Category');
define('lg_conditional_at_reportingtags', 'Reporting Tags');
define('lg_conditional_at_unassigned', 'Unassigned');
define('lg_conditional_at_currentlyloggedin', 'Logged In User');
define('lg_conditional_at_assignedto', 'Assigned to');
define('lg_conditional_at_acwasever', 'Was ever assigned to');
define('lg_conditional_at_acfromto', 'Reassigned from/to');
define('lg_conditional_at_acreassignedby', 'Reassigned by');
define('lg_conditional_at_openedby', 'Opened By');
define('lg_conditional_at_updatedby', 'Updated By (use with other conditions)');
define('lg_conditional_at_relativedate', 'Relative Date Since Opened');
define('lg_conditional_at_relativedateclosed', 'Relative Date Since Closed');
define('lg_conditional_at_relativedatetoday', 'Request Updated');
define('lg_conditional_at_relativedatelastpub', 'Request Publicly Updated');
define('lg_conditional_at_relativedatelastcust', 'Customer Updated Request');
define('lg_conditional_at_today', 'Today');
define('lg_conditional_at_tomorrow', 'Tomorrow');
define('lg_conditional_at_yesterday', 'Yesterday');
define('lg_conditional_at_dateset', 'Date is Set');
define('lg_conditional_at_datenotset', 'Date is not Set');
define('lg_conditional_at_past7', 'Past 7 Days');
define('lg_conditional_at_past14', 'Past 14 Days');
define('lg_conditional_at_past30', 'Past 30 Days');
define('lg_conditional_at_past60', 'Past 60 Days');
define('lg_conditional_at_past90', 'Past 90 Days');
define('lg_conditional_at_past365', 'Past 365 Days');
define('lg_conditional_at_next7', 'Next 7 Days');
define('lg_conditional_at_next14', 'Next 14 Days');
define('lg_conditional_at_next30', 'Next 30 Days');
define('lg_conditional_at_next90', 'Next 90 Days');
define('lg_conditional_at_next365', 'Next 365 Days');
define('lg_conditional_at_thisweek', 'This Week (Sun - Sat)');
define('lg_conditional_at_thismonth', 'This Month');
define('lg_conditional_at_thisyear', 'This Year');
define('lg_conditional_at_lastweek', 'Last Week (Sun - Sat)');
define('lg_conditional_at_lastmonth', 'Last Month');
define('lg_conditional_at_lastyear', 'Last Year');
define('lg_conditional_at_nextweek', 'Next Week (Sun - Sat)');
define('lg_conditional_at_nextmonth', 'Next Month');
define('lg_conditional_at_nextyear', 'Next Year');
define('lg_conditional_at_beforedate', 'Opened Before Date');
define('lg_conditional_at_afterdate', 'Opened After Date');
define('lg_conditional_at_closedbeforedate', 'Closed Before Date');
define('lg_conditional_at_closedafterdate', 'Closed After Date');
define('lg_conditional_at_title', 'Email subject');
define('lg_conditional_at_mailbox', 'Mailbox');
define('lg_conditional_at_portal', 'Portal');
define('lg_conditional_at_portal_default', 'Primary Portal');
define('lg_conditional_at_sincecreated', 'Minutes since opened');
define('lg_conditional_at_sinceclosed', 'Minutes since closed');
define('lg_conditional_at_sincelastupdate', 'Minutes since last update');
define('lg_conditional_at_sincelastpubupdate', 'Minutes since last public update');
define('lg_conditional_at_sincelastcustupdate', 'Minutes since last customer update');
define('lg_conditional_at_speedtofirstresponse', 'Minutes since first response (all hours)');
define('lg_conditional_at_pubupdates', 'Number of public updates');
define('lg_conditional_at_lastreplyby', 'Last public reply from');
define('lg_conditional_at_lastreplyby_cust', 'Customer');
define('lg_conditional_at_lastreplyby_staff', 'Any staff member');
define('lg_conditional_at_acting_person_cust', 'Customer');
define('lg_conditional_at_search', 'Request history full-text search for');
define('lg_conditional_at_wheresql', 'Custom "where" clause (SQL)');
define('lg_conditional_at_subcondand', 'ALL of the following are true');
define('lg_conditional_at_subcondor', 'ANY of the following are true');
define('lg_conditional_at_testcond', 'Test Conditions');
define('lg_conditional_at_showall', 'Show all results');
define('lg_conditional_at_openreq', 'Open request');
define('lg_conditional_at_stafftonotify', 'Staff Member');
define('lg_conditional_at_externalemail', 'Email Address');
define('lg_conditional_at_assignedstaffer', 'Assigned Staffer');
define('lg_conditional_at_mailboxselect', 'Send From');
define('lg_conditional_at_subject', 'Subject');
define('lg_conditional_at_frommailbox', 'Mailbox received from (if available)');
define('lg_conditional_at_calcmin', 'Calculate Minutes');
define('lg_conditional_at_notifysms', 'Send SMS notification to');
define('lg_conditional_at_notifyexternal', 'Send to external email address');

define('lg_conditional_at_emailcustomer', 'Email Customer');
define('lg_conditional_at_emailresults', 'Email Table of Results to');
define('lg_conditional_at_subscribestaff', 'Subscribe Staff');
define('lg_conditional_at_unsubscribestaff', 'Unsubscribe Staff');
define('lg_conditional_at_requestpush', 'Perform a Request Push');
define('lg_conditional_at_thermostat_send', 'Send a Thermostat Survey');
define('lg_conditional_at_thermostat_add_email', 'Add Email to Survey Campaign');
define('lg_conditional_at_thermostat_nps_score', 'Thermostat NPS Score');
define('lg_conditional_at_thermostat_csat_score', 'Thermostat CSAT Score');
define('lg_conditional_at_thermostat_feedback', 'Has Thermostat Feedback');
define('lg_conditional_at_thermostat_has_feedback', 'has feedback');
define('lg_conditional_at_thermostat_does_not_have_feedback', 'doesn\'t have feedback');
define('lg_conditional_at_norequestpush', 'You have not enabled any Request Push classes. <a href="http://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=153" target="_blank">Find out more about Request Push</a>.');
define('lg_conditional_at_webhook', 'POST a webhook to this URL:');
define('lg_conditional_at_pushto', 'Push To');
define('lg_conditional_at_pushcomment', 'Comment (optional)');
define('lg_conditional_at_lessthan', 'Less than');
define('lg_conditional_at_greaterthan', 'Greater than');

define('lg_conditional_at_ogadvanced', 'Advanced');
define('lg_conditional_at_ogintegrations', 'Integrations');
define('lg_conditional_at_ogcustinfo', 'Customer Information');
define('lg_conditional_at_ogreqdetails', 'Request Details');
define('lg_conditional_at_ogassignmentchain', 'Assignment Chain');
define('lg_conditional_at_ogcustomfields', 'Custom Fields');
define('lg_conditional_at_ogdatetime', 'Date and Time');
define('lg_conditional_at_ogsearch', 'Search');
define('lg_conditional_at_ogother', 'Other');
define('lg_conditional_at_ognotifications', 'Notifications');
define('lg_conditional_at_ogemaildetails', 'Email Details');

define('lg_conditional_at_otrigger', 'Trigger Specific');
define('lg_conditional_at_acting_person', 'Acting Person');
define('lg_conditional_at_notetype', 'Note Type');
define('lg_conditional_at_notecontent', 'Note Body');

define('lg_conditional_tr_is', 'Now is');
define('lg_conditional_tr_isnot', 'Now is not');
define('lg_conditional_tr_begins', 'Now begins with');
define('lg_conditional_tr_ends', 'Now ends with');
define('lg_conditional_tr_contains', 'Now contains');
define('lg_conditional_tr_notcontain', 'Now does not contain');
define('lg_conditional_tr_matches', 'Now matches');
define('lg_conditional_tr_lessthan', 'Now greater than');
define('lg_conditional_tr_greaterthan', 'Now less than');

define('lg_conditional_tr_changed', 'Changed');
define('lg_conditional_tr_changed_to', 'Changed to');
define('lg_conditional_tr_changed_from', 'Changed from');
define('lg_conditional_tr_notchanged', 'Not changed');
define('lg_conditional_tr_notchanged_to', 'Not changed to');
define('lg_conditional_tr_notchanged_from', 'Not changed from');

define('lg_conditional_tr_rt_is_selected', 'Now is selected');
define('lg_conditional_tr_rt_is_not_selected', 'Now is not selected');
define('lg_conditional_tr_rt_was_selected', 'Originally was selected');
define('lg_conditional_tr_rt_was_not_selected', 'Originally was not selected');

define('lg_conditional_tr_public', 'Public');
define('lg_conditional_tr_private', 'Private');
define('lg_conditional_tr_external', 'External');

define('lg_conditional_tr_livelookup', 'Perform a Live Lookup with');
define('lg_conditional_tr_nolivelookup', 'You have setup Live Lookup. <a href="http://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=174" target="_blank">Find out more about Live Lookup</a>.');

define('lg_conditional_tr_webhook', 'POST a webhook to this URL:');

define('lg_conditional_setreptags', 'Set Reporting Tags');
