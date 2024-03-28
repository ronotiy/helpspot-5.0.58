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

//Language used in WYSIWYG - to change you must provide a 639-2 abbreviation from this list: http://www.loc.gov/standards/iso639-2/englangn.html
// The language must also be available. You can see the ones available in this folder: js/tiny_mce/langs
define('lg_WYSIWYG_Lang', 'en');

define('lg_inbox', 'Inbox');
define('lg_email', 'Email');
define('lg_username', 'Username');
define('lg_password', 'Password');
define('lg_sessionexpired', 'HelpSpot Session Expired');
define('lg_js_notification', 'Notification');
define('lg_js_error', 'Error');
define('lg_js_confirmation', 'Confirm');
define('lg_button_ok', 'OK');
define('lg_button_cancel', 'Cancel');
define('lg_button_close', 'Close');
define('lg_logintitle', 'Login');
define('lg_loginbutton', 'Sign In');
define('lg_logbackin', 'Log Back In');
define('lg_logged_out', 'You have been logged out!');
define('lg_login_trouble', 'Having trouble logging in?');
define('lg_login_use_default', 'Use HelpSpot\'s default authentication to login and fix any issues');
define('lg_login_authenticating', 'Authenticating...');
define('lg_emailpassword', 'Reset Password');
define('lg_emailpasswordsent', 'Reset link sent. Please check your email.');
define('lg_providemail', 'Please provide an email');
define('lg_logout', 'Log Out');
define('lg_prefs', 'Preferences');
define('lg_options', 'Options');
define('lg_spam', 'SPAM');
define('lg_trash', 'Trash');
define('lg_error', 'Error');
define('lg_done', 'Done');
define('lg_errorbox', 'There has been an error, please see below');
define('lg_databaseerror', 'There has been a database error');
define('lg_inactive_text', 'inactive');
define('lg_inactive', '<span class="page-header-label">'.lg_inactive_text.'</span>');
define('lg_restore', 'Restore');
define('lg_restorewarn', 'Are you sure you want to restore ');
define('lg_default', 'Default');
define('lg_out', 'Out');
define('lg_enable', 'Enable');
define('lg_disable', 'Disable');
define('lg_cancel', 'Cancel');
define('lg_close', 'Close');
define('lg_or', 'or');
define('lg_saveas', 'Save As');
define('lg_download', 'Download');
define('lg_attach', 'Attach');
define('lg_reattach', 'Reattach');
define('lg_admin', 'Administrator');
define('lg_loadingwait', 'Now loading ..... please wait');
define('lg_noassign', 'Not assigned');
define('lg_documentation', 'Support & Docs');
define('lg_forums', 'Forums');
define('lg_academy', 'Training');
define('lg_rss', 'RSS Feed');
define('lg_social', 'Social');
define('lg_partner', 'Affiliate Program');
define('lg_twitter', 'Twitter');
define('lg_twitter_ex', 'Chat with us on Twitter');
define('lg_facebook', 'Facebook');
define('lg_facebook_ex', 'Become a fan on our Facebook page');
define('lg_email_ex', 'Spread the word');
define('lg_trialexpires', 'Your trial expires');
define('lg_freestart', 'Need more users, support, or hosting?');
define('lg_upgrade', 'Upgrade to Paid');
define('lg_purchase', 'Purchase');
define('lg_purchase_here', 'Purchase here');
define('lg_enabled', 'Enabled');
define('lg_disabled', 'Disabled');
define('lg_setorder', 'Set Order');
define('lg_trialover', 'Your Trial or Subscription is expired');
define('lg_trialnote', 'Your version of HelpSpot has expired. Please visit the UserScape website to purchase/renew a license for your installation.');
define('lg_trialcustid', 'Customer ID');
define('lg_trialupload', 'License');
define('lg_trialbutton', 'Upload License');
define('lg_mailrule', 'Mail Rule Match');
define('lg_automation', 'Automation Match');
define('lg_trigger', 'Trigger Match');
define('lg_notified', 'Notified');
define('lg_notifiedsms', 'Notified via SMS');
define('lg_sp_check', 'Check Spelling');
define('lg_sp_resume', 'Resume Editing');
define('lg_sp_nomisspell', 'No Misspellings Found');
define('lg_sp_nosugg', 'No Suggestions');
define('lg_sp_revert', 'Revert To');
define('lg_sp_close', 'Close');
define('lg_sp_checking', 'Checking...');
define('lg_quicktimeselect', 'Quick Time Select');
define('lg_quicktimeselectalltime', 'All Time');
define('lg_quicktimeselect_today', 'Today');
define('lg_quicktimeselect_yesterday', 'Yesterday');
define('lg_mr_emailcusterror', 'Mail Rule ERROR: instant reply email to customer FAILED. The email system returned this error');
define('lg_at_emailcusterror', 'Automation ERROR: EMAIL FAILED. The email system returned this error');
define('lg_at_reqpusherror1', 'Request Push ERROR: ');
define('lg_at_reqpusherror2', 'Request Push ERROR: Your class could not be initiated. Check that class name in your push file to be sure it matches the file name.');
define('lg_systemname', 'System');
define('lg_systemnameportal', 'Help Desk Message');
define('lg_jsmenu_appresponseempty', 'None available. Use button above to create.');
define('lg_response_shortcut', 'Type # for autocompletion');
define('lg_jsmenu_nokb', 'No knowledge books available');
define('lg_jsmenu_respmostused', 'Most Commonly Used');
define('lg_jsmenu_manageresponses', 'Manage Responses');
define('lg_textversion', 'Text Version');
define('lg_htmlversion', 'HTML Version');
define('lg_subjectline', 'Subject Line');
define('lg_no_wysiwyg_textarea_formatting', 'Formatting options');
define('lg_markdown_format_label', 'Formatted Text Syntax');
define('lg_preview', 'Preview');
define('lg_details', 'Details');
define('lg_requests', 'Requests');
define('lg_cumulative_percent', 'Cumulative Percentage');
define('lg_maintenance_title', 'Maintenance Mode');
define('lg_maintenance_mode', 'HelpSpot has been placed in maintenance mode by an administrator. Please check back in a few minutes.');
define('lg_maintenance_off', 'Turn Off Maintenance Mode (admins only)');
define('lg_default_mailbox', 'Default Mailbox');
define('lg_primaryportal', 'Primary Portal');
define('lg_selectportal', 'Select a Portal');
define('lg_licenseuploader', 'Your license had less users than you currently have active so some users have been de-activated.');
define('lg_licnotvalid', 'License is not valid');
define('lg_sort', 'Sort');
define('lg_to', 'to');
define('lg_and', 'and');
define('lg_menu', 'Menu');
define('lg_everyone', 'Everyone');
define('lg_onlyme', 'Only me');
define('lg_mypermgroup', 'My group');
define('lg_permgroup', 'Permission group...');
define('lg_selectedppl', 'Specific people...');
define('lg_request_initassign', 'Assigned when created');
define('lg_inline_image', 'image');
define('lg_checkbox_checkall', 'Check/Uncheck All');
define('lg_latestblog', 'News From HelpSpot');
define('lg_newsblog', 'News');

define('lg_passwordresetfor', 'Password reset link for: %s');
define('lg_passwordresetcomplete', 'Password reset for: %s');
define('lg_passwordresetsent', 'A new password has been sent, please check your email.');
define('lg_passwordresetexpired', 'Reset link expired, please try again.');
define('lg_passwordreset_email_reset', 'Please click the link below to reset your password. A new password will be generated and emailed to this address. Note, this link expires after 15 minutes.

RESET LINK:
%s');
define('lg_passwordreset_email_complete', 'Your password has been reset.

New Password:
%s

Note, your password can be changed in the top right navigation menu under preferences.');

//This is a regular expression to match the different ways various versions of Microsoft Outlook mark the orginal part of an
//  email message. This is known to work in Outlook 2003 and Outlook Express. You may need to customize it if your organization
//  uses a custom separator.
define('lg_outlookseparator', '/-----\s*Original Message\s*-----/');

//time
define('lg_year', 'year');
define('lg_month', 'month');
define('lg_week', 'week');
define('lg_day', 'day');
define('lg_days', 'days');
define('lg_hour', 'hour');
define('lg_hours', 'hours');
define('lg_minute', 'min');
define('lg_years', 'years');
define('lg_months', 'months');
define('lg_weeks', 'weeks');
define('lg_minutes', 'mins');
define('lg_second', 'second');
define('lg_seconds', 'seconds');
define('lg_creation', 'request created on %s');
define('lg_sincepost', '%s since original post');
define('lg_selectdate', 'Select a date');
define('lg_selectdatetime', 'Select a date & time');

// lookups
define('lg_lookup_email', 'Email');
define('lg_lookup_phone', 'Phone');
define('lg_lookup_walkin', 'Walk In');
define('lg_lookup_mail', 'Mail');
define('lg_lookup_webform', 'Web Form');
define('lg_lookup_other', 'Other');
define('lg_lookup_webservice', 'Web Service');
define('lg_lookup_forum', 'Forum');
define('lg_lookup_im', 'Instant Messenger');
define('lg_lookup_fax', 'Fax');
define('lg_lookup_voicemail', 'Voicemail');
define('lg_lookup_staffinit', 'Staff Initiated');
define('lg_lookup_widget', 'Tab Widget');
define('lg_lookup_mobile', 'HelpSpot Mobile App');

define('lg_lookup_1', 'Reassigned from "%s" to "%s"');
define('lg_lookup_2', 'Custom field "%s" changed from "%s" to "%s"');
define('lg_lookup_3', 'Request changed from "%s" to "%s"');
define('lg_lookup_4', 'Status changed from "%s" to "%s"');
define('lg_lookup_7', 'Request changed from "%s" to "%s"');
define('lg_lookup_8', 'Category changed from "%s" to "%s"');
define('lg_lookup_9', 'Customer ID changed from "%s" to "%s"');
define('lg_lookup_10', 'Customer first name changed from "%s" to "%s"');
define('lg_lookup_11', 'Customer last name changed changed from "%s" to "%s"');
define('lg_lookup_12', 'Customer email changed from "%s" to "%s"');
define('lg_lookup_13', 'Customer phone changed from "%s" to "%s"');
define('lg_lookup_14', 'Custom field "%s" updated');
define('lg_lookup_15', 'Reporting tags updated to');
define('lg_lookup_17', 'Auto Reassigned from "%s" to "%s"');
define('lg_lookup_18', 'Out of Office Reassign from "%s" to "%s"');
define('lg_lookup_19', 'Moved to trash');
define('lg_lookup_20', 'Removed from trash');
define('lg_lookup_21', 'Merged request %s');
define('lg_lookup_22', 'Batch Respond/Edit Performed');
define('lg_lookup_23', 'Email subject changed from "%s" to "%s"');
define('lg_lookup_24', 'Send from changed from "%s" to "%s"');
define('lg_lookup_25', 'Converted history note from %s into a new request');
define('lg_lookup_26', 'Converted history note (#%s) into a new request: %s');

// custom field name lookups
define('lg_lookup_cfields_dropdown', 'Predefined List');
define('lg_lookup_cfields_text', 'Text Field');
define('lg_lookup_cfields_lrgtext', 'Large Text Field');
define('lg_lookup_cfields_checkbox', 'Checkbox');
define('lg_lookup_cfields_numfield', 'Numeric Field');
define('lg_lookup_cfields_decimal', 'Decimal Field');
define('lg_lookup_cfields_regex', 'Regular Expression Field');
define('lg_lookup_cfields_ajax', 'AJAX Selection Field');
define('lg_lookup_cfields_date', 'Date Field');
define('lg_lookup_cfields_datetime', 'Date and Time Field');
define('lg_lookup_cfields_drilldown', 'Drill Down List');

// filter columns
define('lg_lookup_filter_custname', 'Customer');
define('lg_lookup_filter_lastname', 'Last Name');
define('lg_lookup_filter_custemail', 'Email');
define('lg_lookup_filter_custphone', 'Phone');
define('lg_lookup_filter_custid', 'Customer ID');
define('lg_lookup_filter_emailtitle', 'Email Subject');
define('lg_lookup_filter_timeopen', 'Opened');
define('lg_lookup_filter_timeopen2', 'Date Opened');
define('lg_lookup_filter_datetimeopen', 'Date and Time Opened');
define('lg_lookup_filter_timeclosed', 'Closed');
define('lg_lookup_filter_timeclosed2', 'Date Closed');
define('lg_lookup_filter_datetimeclosed', 'Date and Time Closed');
define('lg_lookup_filter_trashedon', 'Trashed On');
define('lg_lookup_filter_category', 'Category');
define('lg_lookup_filter_status', 'Status');
define('lg_lookup_filter_open', 'Open');
define('lg_lookup_filter_open2', 'Open/Closed');
define('lg_lookup_filter_isrepliedto', '');
define('lg_lookup_filter_isrepliedto2', 'Replied To');
define('lg_lookup_filter_assignedto', 'Assigned To');
define('lg_lookup_filter_openedby', 'Opened By');
define('lg_lookup_filter_openedvia', '');
define('lg_lookup_filter_openedvia2', 'Contacted Via');
define('lg_lookup_filter_currentlyviewing', '');
define('lg_lookup_filter_currentlyviewing2', 'Currently Being Viewed');
define('lg_lookup_filter_mailbox', 'Mailbox');
define('lg_lookup_filter_portal', 'Portal');
define('lg_lookup_filter_reqsummary', 'Initial Request');
define('lg_lookup_filter_lastpublicnote', 'Latest Public Note');
define('lg_lookup_filter_lastpublicnoteby', 'Latest Public Note By');
define('lg_lookup_filter_lastupdateby', 'Latest Update By');
define('lg_lookup_filter_age', 'Age');
define('lg_lookup_filter_attachment', '&nbsp;');
define('lg_lookup_filter_attachment2', 'Attachments');
define('lg_lookup_filter_timetrack', 'Time');
define('lg_lookup_filter_timetrack2', 'Time Tracker Total');
define('lg_lookup_filter_lastupdate', 'Last Update');
define('lg_lookup_filter_thermostat_nps_score', 'Thermostat NPS Score');
define('lg_lookup_filter_thermostat_csat_score', 'Thermostat CSAT Score');
define('lg_lookup_filter_thermostat_feedback', 'Thermostat Feedback');
define('lg_lookup_filter_lastpubupdate', 'Last Public Update');
define('lg_lookup_filter_lastcustupdate', 'Last Customer Update');
define('lg_lookup_filter_speedtofirstresponse', 'First Response Speed (all hours)');
define('lg_lookup_filter_speedtofirstresponse_biz', 'First Response Speed (biz hours)');
define('lg_lookup_filter_reqid', 'ID');
define('lg_lookup_filter_reqid2', 'Request ID');
define('lg_lookup_filter_takeit', 'Take it');
define('lg_lookup_filter_reminder', 'Reminder');
define('lg_lookup_filter_remdate', 'Reminder Set For');
define('lg_lookup_filter_reqhaschanged', 'This request has changed since you last viewed it');
define('lg_lookup_filter_markunread', 'Mark as unread');
define('lg_lookup_filter_repliedtoby', 'Last reply was from staff');
define('lg_lookup_filter_ctpublicupdates2', 'Public Update Count');
define('lg_lookup_filter_ctpublicupdates', '#');
define('lg_lookup_filter_reportingtags', 'Reporting Tags');
define('lg_lookup_filter_timegroup_today_yesterday', 'Time: Today, Yesterday');
define('lg_lookup_filter_timegroup_hourly', 'Time: Hour');
define('lg_lookup_filter_timegroup_daily', 'Time: Day');
define('lg_lookup_filter_timegroup_monthly', 'Time: Month');
define('lg_lookup_filter_timegroup_label', 'Time');
define('lg_lookup_filter_timegroup_today', 'Today');
define('lg_lookup_filter_timegroup_yesterday', 'Yesterday');
define('lg_lookup_filter_timegroup_older', 'Older');
define('lg_lookup_filter_nps', 'NPS Score');
define('lg_lookup_filter_csat', 'CSAT Score');

// workflow
define('lg_wf_open', 'Open');
define('lg_wf_single', 'Single Point of Contact');

// tz
define('lg_tz_city', 'City/Region');
define('lg_tz_date', 'Date');
define('lg_tz_time', 'Time');
define('lg_tz_offset', 'GMT Offset');

// for pages
define('lg_help', 'Help');
define('lg_loading', 'Loading...');
define('lg_yes', 'Yes');
define('lg_no', 'No');
define('lg_off', 'Off');
define('lg_on', 'On');
define('lg_required', '*required');
define('lg_all', 'all');
define('lg_checked', 'Checked');
define('lg_notchecked', 'Not Checked');
define('lg_isopen', 'OPEN');
define('lg_isclosed', 'CLOSED');
define('lg_isurgent', 'URGENT');
define('lg_isnormal', 'NORMAL');
define('lg_ispublic', 'public');
define('lg_label_public', 'Public');
define('lg_label_private', 'Private');
define('lg_label_external', 'External');
define('lg_label_customer', 'Customer');
define('lg_alwaysvis', 'always visible');
define('lg_hiddenemail', 'hidden email history');
define('lg_noresults', 'No Records Found');
define('lg_noresults_filter', 'No Requests Match This Filter');
define('lg_noresults_search', 'No results.<br /> <a href="admin?pg=search">Try advanced search</a>');
define('lg_nohistory', 'No Matching History');
define('lg_rserror', 'Query Error');
define('lg_nextpage', 'Next Page');
define('lg_prevpage', 'Previous Page');
define('lg_next', 'Next');
define('lg_prev', 'Prev');
define('lg_loadmore', 'Load More');
define('lg_save', 'Save');
define('lg_saving', 'Saving');
define('lg_add', 'Add');
define('lg_streamview_end', 'End of requests');
define('lg_streamview_desc_overflow', 'PEEK: quick request view');
define('lg_thermostat_label_see_results', 'See Survey Results');
define('lg_conditional_at_thermostat_promoter', 'promoter');
define('lg_conditional_at_thermostat_passive', 'passive');
define('lg_conditional_at_thermostat_detractor', 'detractor');
define('lg_conditional_at_thermostat_satisfied', 'satisfied');
define('lg_conditional_at_thermostat_dissatisfied', 'dissatisfied');
define('lg_requestchanged', 'Request Changed');

//No match for searches
define('lg_search_nomatch', 'No Matches');

//Email auto reply
define('lg_mailre', 'Re');

//Reply above this line
define('lg_replyabove', '## Reply ABOVE THIS LINE to add a note to this request ##');

//Syntax Highlighting
define('lg_double_click', 'Double click code above to copy');

// For filter
define('lg_globalfilters', 'Global Filters');

//Select multiple fields
define('lg_addallstaff', 'Add All');
define('lg_expand', 'Expand');
define('lg_subscribeall', 'Subscribe');

// Tags
define('lg_tags_knowledgetags', 'Knowledge Tags');
define('lg_tags_add', 'Add Tag');
define('lg_tags_label', 'Add a New Tag');
define('lg_tags_save', 'Save');
define('lg_tags_delete', 'Delete');
define('lg_tags_reorder', 'Change Order to After');
define('lg_tags_confirmdel', 'Are you sure you want to delete this tag?');
define('lg_tags_none', 'No tags created yet');
define('lg_tags_updated', 'Tags Updated');

// Tip menu
define('lg_tm_view', 'View Request');
define('lg_tm_markunread', 'Read/Unread');
define('lg_tm_actiontrash', 'Trash');
define('lg_tm_actionspam', 'SPAM');
define('lg_tm_skip', 'Skip');

// Person Status Type
define('lg_ps_viewingrequest', '<strong>viewing</strong>');
define('lg_ps_editingrequest', '<strong>editing</strong>');

// Address book
define('lg_addressbook_title', 'Address Book');
define('lg_addressbook_contacts', 'Contacts');
define('lg_addressbook_livelookup', 'Live Lookup Search');
define('lg_addressbook_livelookup_ex', 'Search against Live Lookup to find contacts');
define('lg_addressbook_livelookup_na', 'Your installation does not have Live Lookup enabled. <a href="https://www.helpspot.com/helpdesk/index.php?pg=kb.page&id=174" target="_blank">Learn More</a>');
define('lg_addressbook_addcontact', 'Add Contact');
define('lg_addressbook_search', 'Search');
define('lg_addressbook_llsource', 'Live Lookup Source');
define('lg_addressbook_llheader', 'Live Lookup Contacts');
define('lg_addressbook_fname', 'First Name');
define('lg_addressbook_lname', 'Last Name');
define('lg_addressbook_email', 'Email');
define('lg_addressbook_desc', 'Short Description');
define('lg_addressbook_add', 'Add Contact');
define('lg_addressbook_persontitle', 'Title');
define('lg_addressbook_highlight', 'Highlight');
define('lg_addressbook_highlightex', 'Will always be shown at top');
define('lg_addressbook_highlighted', 'Highlighted');
define('lg_addressbook_confirmdelete', 'Delete this contact?');

define('lg_validation_required', 'This field is required');
define('lg_validation_email', 'This field requires a valid email');

define('lg_dontemail', 'Do not send email');

/*****************************************
NAVIGATION
 *****************************************/
define('lg_adminhome', 'Admin');
define('lg_admin_categories_nav', 'Categories');
define('lg_admin_users_nav', 'Staff');
define('lg_admin_mailboxes_nav', 'Email Mailboxes');
define('lg_admin_status_nav', 'Status Types');
define('lg_admin_groups_nav', 'Permission Groups');
define('lg_admin_customize_admin_nav', 'Customize Admin');
define('lg_admin_themes_nav', 'Portal Theme');
define('lg_admin_tools_nav', 'Triggers & Rules');
define('lg_admin_data_nav', 'Organize');
define('lg_admin_customize_nav', 'Customize');
define('lg_admin_settings_nav', 'Settings');
define('lg_admin_overview_nav', 'System Overview');
define('lg_admin_widgets_nav', 'Widgets');
define('lg_admin_integration_nav', 'Integration');
define('lg_admin_system_nav', 'System');
define('lg_helpdesknav', 'Inbox');
define('lg_portalnav', 'View Portals');
define('lg_queue', 'Workspace');
define('lg_myq', 'My Queue');
define('lg_subscriptions', 'Subscriptions');
define('lg_reminders', 'Reminders');
define('lg_darkmode_on', 'Dark Mode: On');
define('lg_darkmode_off', 'Dark Mode: Off');
define('lg_todayboard', 'Todayboard');
define('lg_wallboard', 'WALLBOARD');
define('lg_filter_requests_nav', 'Create Filter');
define('lg_orderkb_nav', 'Book Order');
define('lg_newrequest', 'Create Request');
define('lg_forum', 'Forum');
define('lg_reports', 'Reports');
define('lg_search_tab', 'Search');
define('lg_search', 'Search...');
define('lg_search_history', 'Search History');
define('lg_search_kb', 'Knowledge Books');
define('lg_advsearch', 'Advanced');
define('lg_kb', 'Knowledge');
define('lg_responses', 'Responses');
define('lg_admin_tools_sysinfo', 'System Information');
define('lg_admin_tools_filtermgmt', 'Filter Management');
define('lg_admin_tools_jobsmgmt', 'Failed Jobs');
define('lg_admin_tools_responsemgmt', 'Response Management');
define('lg_admin_tools_archive', 'Archive Tool');
define('lg_admin_tools_errorlog', 'Error Log');
define('lg_admin_tools_email', 'Email Templates');
define('lg_admin_tools_templates', 'Portal Templates');
define('lg_admin_tools_reqfields', 'Custom Fields');
define('lg_admin_tools_mailrules', 'Mail Rules');
define('lg_admin_tools_auto', 'Automation Rules');
define('lg_admin_tools_triggers', 'Triggers');
define('lg_admin_tools_portals', 'Secondary Portals');
define('lg_admin_integration_surveytools', 'Survey Tools');
define('lg_custom_pages', 'Custom Pages');
define('lg_admin_integrations', 'Integrations');
define('lg_admin_customer_tools', 'Customer Tools');

/*****************************************
MAIL SUBJECTS
 *****************************************/
define('lg_mailsub_cc', 'HelpSpot: Request Notification');
define('lg_mailsub_new', 'HelpSpot: New Request');
define('lg_mailsub_mailrule', 'HelpSpot: Mail Rule Notification');
define('lg_mailsub_automation', 'HelpSpot: Automation Notification');
define('lg_mailsub_existing', 'HelpSpot: Request Update');
define('lg_mailsub_reminder', 'HelpSpot: Request Reminder');

define('lg_mail_newstaffsub', 'Your %s account information');

define('lg_feed_viewlink', 'View complete request');
define('lg_feed_customer', 'CUSTOMER DETAILS');
define('lg_feed_reqinfo', 'REQUEST INFORMATION');
define('lg_feed_reqhistory', 'REQUEST HISTORY');
define('lg_feed_cid', 'Customer ID');
define('lg_feed_cemail', 'Email');
define('lg_feed_cphone', 'Phone');
define('lg_feed_cname', 'Name');
define('lg_feed_status', 'Status');
define('lg_feed_assign', 'Assigned to');
define('lg_feed_category', 'Category');
define('lg_feed_createdby', 'Created By');
define('lg_feed_helpdeskfilters', 'Help Desk Filters');
define('lg_feed_update', 'UPDATE');
define('lg_feed_request', 'NEW REQUEST');
define('lg_feed_notification', 'NOTIFICATION');
define('lg_feed_log', 'Event Log');

/*****************************************
PLACEHOLDERS
 *****************************************/
define('lg_insertplaceholder', 'Insert Placeholder');
define('lg_insertplaceholderopt', 'Insert Placeholder (optional)');
define('lg_inserttemplatetag', 'Insert Template Tag');
define('lg_placeholderspopup_reqid', 'Request ID');
define('lg_placeholderspopup_acckey', 'Access Key');
define('lg_placeholderspopup_replyabove', '"Reply Above" Text');
define('lg_placeholderspopup_custfirst', 'Customer first name');
define('lg_placeholderspopup_custlast', 'Customer last name');
define('lg_placeholderspopup_custid', 'Customer ID');
define('lg_placeholderspopup_custemail', 'Customer email');
define('lg_placeholderspopup_custphone', 'Customer phone');
define('lg_placeholderspopup_status', 'Status');
define('lg_placeholderspopup_category', 'Category');
define('lg_placeholderspopup_urgent', 'Urgent');
define('lg_placeholderspopup_openclosed', 'Open/Closed');
define('lg_placeholderspopup_dateopened', 'Date Opened');
define('lg_placeholderspopup_datenow', 'Date Now (current date/time)');
define('lg_placeholderspopup_assignedfirst', 'Assigned staff member: first name');
define('lg_placeholderspopup_assignedlast', 'Assigned staff member: last name');
define('lg_placeholderspopup_assignedfull', 'Assigned staff member: full name');
define('lg_placeholderspopup_assignedlastfirst', 'Assigned staff member: last name, first name');
define('lg_placeholderspopup_assignedemail', 'Assigned staff member: email');
define('lg_placeholderspopup_assignedphone', 'Assigned staff member: phone');
define('lg_placeholderspopup_loggedinfirst', 'Logged in staff member: first name');
define('lg_placeholderspopup_loggedinlast', 'Logged in staff member: last name');
define('lg_placeholderspopup_loggedinfull', 'Logged in staff member: full name');
define('lg_placeholderspopup_loggedinlastfirst', 'Logged in staff member: last name, first name');
define('lg_placeholderspopup_loggedinemail', 'Logged in staff member: email');
define('lg_placeholderspopup_loggedinphone', 'Logged in staff member: phone');
define('lg_placeholderspopup_customfield', 'Custom field');
define('lg_placeholderspopup_orgname', 'Organization Name');
define('lg_placeholderspopup_helpdeskurl', 'Help Desk URL');
define('lg_placeholderspopup_reqformurl', 'Request Form URL');
define('lg_placeholderspopup_reqcheckurl', 'Request Check URL');
define('lg_placeholderspopup_kburl', 'Knowledge Book URL');
define('lg_placeholderspopup_forum', 'Forum URL');
define('lg_placeholderspopup_subject', 'Email subject line');
define('lg_placeholderspopup_origsubject', 'Original mail subject line');
define('lg_placeholderspopup_mobilelink','Mobile app request link');
define('lg_placeholderspopup_initialrequest', 'Initial Request Note');
define('lg_placeholderspopup_trackerid', 'Tracking ID - {#####}');
define('lg_placeholderspopup_message', 'Note body');
define('lg_placeholderspopup_lastcustomernote', 'Last note by customer');
define('lg_placeholderspopup_fullpublichistory', 'Public history notes (inclusive of current)');
define('lg_placeholderspopup_fullpublichistory_ex', 'Public history notes (exclusive of current)');
define('lg_placeholderspopup_requestcheckurl', 'URL to check the request');
define('lg_placeholderspopup_accesskey', 'Access key');
define('lg_placeholderspopup_label', 'Email label');
define('lg_placeholderspopup_reqdetails', 'Request summary details (Text format)');
define('lg_placeholderspopup_reqdetailshtml', 'Request summary details (HTML format)');
define('lg_placeholderspopup_staffname', 'Staff members name');
define('lg_placeholderspopup_postername', 'Name of forum poster');
define('lg_placeholderspopup_topic', 'Topic');
define('lg_placeholderspopup_replyurl', 'Topic URL');
define('lg_placeholderspopup_email', 'User Email');
define('lg_placeholderspopup_password', 'User Password');
define('lg_placeholderspopup_helpspoturl', 'HelpSpot Login URL');
define('lg_placeholderspopup_portalemail', 'Portal Login Email');
define('lg_placeholderspopup_portalpassword', 'Portal Login Password');
define('lg_placeholderspopup_reseturl', 'Password Reset URL');
define('lg_placeholderspopup_newportalpassword', 'New Portal Password');

/*****************************************
PORTAL
 *****************************************/
//These must be available outside of the portal
define('lg_portal_req_did', 'This is what I DID');
define('lg_portal_req_expected', 'This is what I EXPECTED to happen');
define('lg_portal_req_actual', 'This is what ACTUALLY happened');
define('lg_portal_req_additional', 'Additional Information');
define('lg_portal_re', 'RE');
define('lg_portal_req_simple', 'Details');

/*****************************************
SPECIAL
 *****************************************/
// Mail import
define('lg_no_subject', 'No Subject');
define('lg_loop_break', 'Request %s may be stuck in an email loop with %s. The loop has been broken.');
define('lg_autoreply_loop_break', 'A mailbox was stuck in an auto reply email loop with %s. The loop has been broken.');
define('lg_history_over_limit', 'Request %s is over the max limit for notes and the email from %s is now discarded.');

/*****************************************
CALENDAR
 *****************************************/
define('lg_cal_fdow', '0'); // first day of week for this locale; 0 = Sunday, 1 = Monday, etc.
define('lg_cal_gotoday', 'Go Today');
define('lg_cal_today', 'Today');
define('lg_cal_wk', 'wk');
define('lg_cal_weekend', '0,6'); // 6 = Saturday, 0 = Sunday, 1 = Monday, etc.
define('lg_cal_am', 'am');
define('lg_cal_pm', 'pm');
define('lg_cal_mn_jan', 'January');
define('lg_cal_mn_feb', 'February');
define('lg_cal_mn_mar', 'March');
define('lg_cal_mn_apr', 'April');
define('lg_cal_mn_may', 'May');
define('lg_cal_mn_jun', 'June');
define('lg_cal_mn_jul', 'July');
define('lg_cal_mn_aug', 'August');
define('lg_cal_mn_sep', 'September');
define('lg_cal_mn_oct', 'October');
define('lg_cal_mn_nov', 'November');
define('lg_cal_mn_dec', 'December');
define('lg_cal_dn_su', 'Sunday');
define('lg_cal_dn_mo', 'Monday');
define('lg_cal_dn_tu', 'Tuesday');
define('lg_cal_dn_we', 'Wednesday');
define('lg_cal_dn_th', 'Thursday');
define('lg_cal_dn_fr', 'Friday');
define('lg_cal_dn_sa', 'Saturday');
define('lg_cal_sdn_su', 'Su');
define('lg_cal_sdn_mo', 'Mo');
define('lg_cal_sdn_tu', 'Tu');
define('lg_cal_sdn_we', 'We');
define('lg_cal_sdn_th', 'Th');
define('lg_cal_sdn_fr', 'Fr');
define('lg_cal_sdn_sa', 'Sa');

/*****************************************
Notifications
 *****************************************/
define('lg_not_email_send_error', 'Email send error for request');
define('lg_not_see_details', 'See Details');
define('lg_not_dismiss', 'Dismiss');
