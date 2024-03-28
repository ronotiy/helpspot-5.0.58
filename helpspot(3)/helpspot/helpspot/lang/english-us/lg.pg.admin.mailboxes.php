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

define('lg_admin_mailboxes_title', 'Mailboxes');
define('lg_admin_mailboxes_nomailboxes', 'No Mailboxes Created Yet');
define('lg_admin_mailboxes_view', 'View');
define('lg_admin_mailboxes_edit', 'Edit');
define('lg_admin_mailboxes_preview', 'Preview');
define('lg_admin_mailboxes_showdel', 'Show Inactive Mailboxes');
define('lg_admin_mailboxes_noshowdel', 'Return to Mailboxes');
define('lg_admin_mailboxes_options', 'Options');
define('lg_admin_mailboxes_emailtemplates', 'Email Templates');
define('lg_admin_mailboxes_insertdefault', 'Populate with default');
define('lg_admin_mailboxes_trackidmissing', 'Tracking ID ({{ $tracking_id }}) is missing from the subject line of Public Note to Customer. Omitting this will prevent HelpSpot from correctly threading responses.');
define('lg_admin_mailboxes_help', 'Configuring a mailbox allows HelpSpot to check mail from a specified email account. Incoming messages are filtered for spam and turned into requests.
									Administrators can opt to specify automated assignment to categories and corresponding default staff member. ');
define('lg_admin_mailboxes_mailbox', 'Mailbox');
define('lg_admin_mailboxes_archive', 'Archive Mail');
define('lg_admin_mailboxes_archive_note', 'With this enabled mail will be moved to a "helpspot_archive_folder" instead of deleting the email. This only works with imap.');
define('lg_admin_mailboxes_deletemsg', 'Note: HelpSpot deletes emails from the mailbox if this is disabled.');
define('lg_admin_mailboxes_mbuser', 'Username');
define('lg_admin_mailboxes_mbhost', 'Host Name');
define('lg_admin_mailboxes_mbpass', 'Password');
define('lg_admin_mailboxes_mbpass_confirm', 'Confirm Password');
define('lg_admin_mailboxes_mbport', 'Port');
define('lg_admin_mailboxes_mbtype', 'Account Type');
define('lg_admin_mailboxes_mbsecurity', 'Security Type');
define('lg_admin_mailboxes_mbsecurityex', 'Used for secure mailbox connections');
define('lg_admin_mailboxes_recommended', 'recomended');
define('lg_admin_mailboxes_depreciated', 'depreciated');
define('lg_admin_mailboxes_secure', '(secure)');
define('lg_admin_mailboxes_replyname', 'Account Name');
define('lg_admin_mailboxes_replyto', 'Reply To');
define('lg_admin_mailboxes_replyemail', 'Reply to Email Account');
define('lg_admin_mailboxes_replyemailnote', 'The email account that maps to this box ie: support@mydomain.com');
define('lg_admin_mailboxes_replynamenote', 'Name shown in emails sent from this account');
define('lg_admin_mailboxes_enablear', 'Enable Auto Reply');
define('lg_admin_mailboxes_etar', 'Auto Reply');
define('lg_admin_mailboxes_etpublic', 'Public Notes to Customers');
define('lg_admin_mailboxes_etexternal', 'External Notes');
define('lg_admin_mailboxes_etreqcreatedbyform', 'Request Created by Portal Form');
define('lg_admin_mailboxes_etreqcreatedbyform_note', 'Note: This template is used only for secondary portals where this mailbox has been defined as the "Send Emails From".');
define('lg_admin_mailboxes_enablearnote', 'Automatically reply to all emails sent to this mailbox');
define('lg_admin_mailboxes_mbusernote', 'Ex: bjones, bjones@mycompany.com');
define('lg_admin_mailboxes_mbhostnote', 'Ex: pop.mycompany.com, mail.mycompany.com');
define('lg_admin_mailboxes_mbpassnote', 'Account Password (only enter if changing)');
define('lg_admin_mailboxes_mbpassnote_confirm', 'Confirm Account Password');
define('lg_admin_mailboxes_mailboxnote', 'Normally INBOX but can be any mailbox within the account');
define('lg_admin_mailboxes_mbportnote', 'Usually 110 for POP3, 143 - IMAP, 995 - POP3S, 993 - IMAPS');
define('lg_admin_mailboxes_mbtypenote', 'Usually POP3 or IMAP');
define('lg_admin_mailboxes_msgnote', 'Templates here will override versions found in <a href="'.action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'admin.tools.email']).'">system email templates</a> when emails are sent from this mailbox.');
define('lg_admin_mailboxes_defcat', 'Default Category');
define('lg_admin_mailboxes_defcatnote', 'Email to this mailbox will automatically be assigned to the selected category.');
define('lg_admin_mailboxes_nodefault', 'No Default');
define('lg_admin_mailboxes_testmailbox', 'Test Mailbox Settings');
define('lg_admin_mailboxes_testmailboxex', 'This test will confirm that your mailbox is setup correctly. You must still configure your server to call the tasks.php script every few minutes to retrieve email.');
define('lg_admin_mailboxes_testing', 'Testing ...');
define('lg_admin_mailboxes_testnotesecure', 'This mailbox may require a secure connection:');
define('lg_admin_mailboxes_testnotepop', 'Common POP Settings: POP3S, Port 995, SSL-no validate');
define('lg_admin_mailboxes_testnoteimap', 'Common IMAP Settings: IMAPS, Port 993, SSL-no validate');
define('lg_admin_mailboxes_smtppass_msg', 'Leave blank unless changing');
define('lg_admin_mailboxes_addbox', 'Add a Mailbox');
define('lg_admin_mailboxes_addbutton', 'Add Mailbox');
define('lg_admin_mailboxes_editbox', 'Edit: ');
define('lg_admin_mailboxes_editbutton', 'Save Edits');
define('lg_admin_mailboxes_savetoview', 'Save to view changes');
define('lg_admin_mailboxes_samplesubject', 'RE: Customers original subject');
define('lg_admin_mailboxes_er_mailbox', '<br>Please enter a mailbox');
define('lg_admin_mailboxes_er_username', '<br>Please enter a username');
define('lg_admin_mailboxes_er_hostname', '<br>Please enter a hostname');
define('lg_admin_mailboxes_er_pass', '<br>Please enter a password');
define('lg_admin_mailboxes_er_pass_confirm', '<br>Please enter a password to confirm');
define('lg_admin_mailboxes_er_passbadsymbol', '<br>Your password can not contain an @ symbol');
define('lg_admin_mailboxes_er_type', '<br>Please select an account type');
define('lg_admin_mailboxes_er_port', '<br>Please provide a port number');
define('lg_admin_mailboxes_er_autoname', '<br>Please provide an account name');
define('lg_admin_mailboxes_er_autoemail', '<br>Please provide a reply to email account');
define('lg_admin_mailboxes_er_autoemail2', '<br>The email account can not be the same as the email of a HelpSpot user');
define('lg_admin_mailboxes_er_autoresp', '<br>You have enabled auto response but not provided a reply message');
define('lg_admin_mailboxes_fbadded', 'Mailbox added');
define('lg_admin_mailboxes_fbedited', 'Mailbox edited');
define('lg_admin_mailboxes_fbdeleted', 'Mailbox made inactive');
define('lg_admin_mailboxes_fbundeleted', 'Mailbox restored');
define('lg_admin_mailboxes_outbound', 'Outbound Email');
define('lg_admin_mailboxes_outboundex', 'You can use the system default SMTP settings from Admin->Settings or set specific SMTP settings for outbound email just for this mailbox.');
define('lg_admin_mailboxes_outbounduse', 'SMTP Setting to Use');
define('lg_admin_mailboxes_outboundinternal', 'System Default');
define('lg_admin_mailboxes_outboundcustom', 'Custom');
define('lg_admin_mailboxes_noimap', 'The PHP IMAP extension is not installed. This extension is required to use the email integration features.');
    define('lg_admin_mailboxes_colid', 'ID');
    define('lg_admin_mailboxes_colbox', 'Mailbox');
    define('lg_admin_mailboxes_coldel', 'Make Mailbox Inactive');
    define('lg_admin_mailboxes_coldelwarn', 'Are you sure you want to make this mailbox inactive?');
define('lg_admin_mailboxes_msgdefault', '{{ $replyabove }}

Thank you for your inquiry. Your request has been received and is being reviewed by our support staff. Please note the information below as it will allow you to track the progress of your request online.

Check your request online: {{ $requestcheckurl }}&id={{ $accesskey }}
Request access key:  {{ $accesskey }}

____________________________________
Don\'t forget our other support resources:
Knowledge Books: {{ $knowledgebookurl }}');
define('lg_admin_mailboxes_msgdefault_html', '
<html>
<body>
{{ $replyabove }}

<table width="100%" cellpadding="6" cellspacing="0" bgcolor="#dfe5ff">
<tr>
<td style="font-weight:bold;">Request Received</td>
<td align="right">
<a href="{{ $requestcheckurl }}&id={{ $accesskey }}">View the complete request history</a>
</td>
</tr>
</table>

<br />

<p>Thank you for your inquiry. Your request has been received and is being reviewed by our support staff. Please note the information below as it will allow you to track the progress of your request online.</p>

<br />

<p>Check your request online: <a href="{{ $requestcheckurl }}&id={{ $accesskey }}">{{ $requestcheckurl }}&id={{ $accesskey }}</a><br />
Request access key: {{ $accesskey }}</p>

<hr width="80%">

<p>Don\'t forget our other support resources:<br />
Knowledge Books: <a href="{{ $knowledgebookurl }}">{{ $knowledgebookurl }}</a></p>

</body>
</html>
');
