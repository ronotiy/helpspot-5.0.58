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

define('lg_portal_phonesupport', 'Phone Support');
define('lg_portal_home', 'Home');
define('lg_portal_submitrequest', 'Submit a Request');
define('lg_portal_checkrequest', 'Check on a Request');
define('lg_portal_create_login', 'Create an account');
define('lg_portal_loginrequired', 'Login Required');
define('lg_portal_login_forgot', 'Forgot Password');
define('lg_portal_requesthistory', 'Request History');
define('lg_portal_accessidheader', 'Your access key');
define('lg_portal_accessnote', 'The access key provided above can be used to check the status of your request. <br> For quick access to updates bookmark this page.');
define('lg_portal_updatebox', 'Have additional information? Use the box below to provide an update to your request.');
define('lg_portal_kb', 'Knowledge Books');
define('lg_portal_kbprinter', 'Printer Friendly Version');
define('lg_portal_downloads', 'Downloads');
define('lg_portal_relatedpages', 'Related Pages');
define('lg_portal_search', 'Search');
define('lg_portal_helpfulpages', 'Knowledge Book Pages Rated Most Helpful');
define('lg_portal_highlightedpages', 'Highlighted Knowledge Book Pages');
define('lg_portal_searchkb', 'Knowledge Books');
define('lg_portal_tags', 'Knowledge Tags');
define('lg_portal_searchtags', 'Related Tags');
define('lg_portal_hf', 'This page was');
define('lg_portal_helpful', 'Helpful');
define('lg_portal_nothelpful', 'Not Helpful');
define('lg_portal_hasvoted', 'Thank you for your feedback');
define('lg_portal_sticky', 'Sticky');
define('lg_portal_email', 'email');
define('lg_portal_postreply', 'Post a Reply');
define('lg_portal_reply', 'Reply');
define('lg_portal_to', 'To');
define('lg_portal_subject', 'Subject');
define('lg_portal_message', 'Message');
define('lg_portal_yourname', 'Your Name');
define('lg_portal_youremail', 'Your Email');
define('lg_portal_sendemail', 'Send Email');
define('lg_portal_yourpost', 'Your post');
define('lg_portal_postername', 'Name');
define('lg_portal_posteremail', 'Email');
define('lg_portal_posterurl', 'Your Website');
define('lg_portal_er_topic', 'Please provide a topic');
define('lg_portal_er_message', 'Please provide a message');
define('lg_portal_er_name', 'Please provide a name');
define('lg_portal_er_unique_email', 'That username already exists. <a href=index.php?pg=login.forgot>Reset your password here.</a>');
define('lg_portal_er_validcaptcha', 'Please type the security word');
define('lg_portal_er_validrecaptcha', 'Please check off that you are not a robot');
define('lg_portal_prev', 'Previous Page');
define('lg_portal_next', 'Next Page');
define('lg_portal_emailupdate', 'Subscribe to replies via email');
define('lg_portal_request', 'Submit a Request for Assistance');
define('lg_portal_req_account', 'Account ID');
define('lg_portal_req_name', 'Name');
define('lg_portal_req_file_upload', 'Attach a supporting document (optional)');
define('lg_portal_req_note', 'Please complete the form below detailing your request and a member of our support staff will respond as soon as possible.');
define('lg_portal_req_firstname', 'First Name');
define('lg_portal_req_lastname', 'Last Name');
define('lg_portal_req_email', 'Email');
define('lg_portal_req_cc_email', 'Also Notify Email');
define('lg_portal_req_subject', 'Subject');
define('lg_portal_req_phone', 'Phone');
define('lg_portal_req_urgent', 'Is this request urgent?');
define('lg_portal_req_category', 'How would you categorize this request?');
define('lg_portal_req_yes', 'Yes');
define('lg_portal_req_no', 'No');
define('lg_portal_req_submitrequest', 'Submit Request');
define('lg_portal_req_update', 'Provide an update');
define('lg_portal_req_updaterequest', 'Update Request');
define('lg_portal_req_detailsheader', 'Request Details');
define('lg_portal_req_generalerror', 'There has been an error submitting your request. Please try again.');
define('lg_portal_req_required', 'This field is required');
define('lg_portal_req_numberreq', 'This field must be a number');
define('lg_portal_req_validemail', 'Please provide a valid email');
define('lg_portal_req_enterkey', 'Enter your access key to check a single request');
define('lg_portal_subjectdefaultnew', 'Information on Your Request');
define('lg_portal_req_login', 'Login to view your complete request history');
define('lg_portal_create_login_ex', 'Create an account to view your request history');
define('lg_portal_login_forgot_ex', 'Reset Your Password');
define('lg_portal_req_emailpassword', 'Forgot Password');
define('lg_portal_req_logincreate', 'Create an Account');
define('lg_portal_req_loginemail', 'Email');
define('lg_portal_req_loginusername', 'Username');
define('lg_portal_req_loginpassword', 'Password');
define('lg_portal_req_loginpassword_confirm', 'Confirm Password');
define('lg_portal_req_loginbutton', 'Login');
define('lg_portal_req_createbutton', 'Create Account');
define('lg_portal_req_pw_reset_link', 'Send Password Reset Link');
define('lg_portal_req_loginfailed', 'Your login failed. Please try again.');
define('lg_portal_req_logout', 'Log Out');
define('lg_portal_req_changepassword', 'Change Password');
define('lg_portal_req_newpassword', 'New Password');
define('lg_portal_req_confirm', 'Confirm');
define('lg_portal_req_save', 'Save');
define('lg_portal_req_sending', 'Sending...');
define('lg_portal_req_passwordsaved', 'Password saved');
define('lg_portal_req_passwordposterror', 'Could not save password');
define('lg_portal_req_passworderror', 'Passwords must match. Please try again.');
define('lg_portal_req_emailempty', 'Please enter your email');
define('lg_portal_req_emailerror', 'Please enter a valid email');
define('lg_portal_req_passwordsent', 'Reset email sent. Please check your email account.');
define('lg_portal_norequesthistory', 'There are no requests for this email.');

define('lg_portal_check', 'Check');
define('lg_portal_invalidkey', 'This access key is not on file');
define('lg_portal_requestclosed', 'This request has been closed and is no longer available for public viewing. If you need to create a new request please do so by clicking the link below or login to see your complete request history.');
define('lg_portal_closedsubmitnew', 'Submit a new request');
define('lg_portal_closedlogin', 'Login');
define('lg_portal_closedor', 'or');
define('lg_portal_checkboxchecked', 'Checked');
define('lg_portal_checkboxempty', 'Not Checked');

define('lg_portal_spamredirect', 'Moderated Post');
define('lg_portal_spamrenote', 'This post has been flagged for moderation. It will appear on the site shortly after site administrators have had a chance to review it.');
define('lg_portal_spamreturn', 'Click to return');
define('lg_portal_captcha', 'Please type the security word');
define('lg_portal_recaptcha', 'Please type the security words');
define('lg_portal_recaptcha_changewords', 'change words');

define('lg_portal_maintenance_title', 'Maintenance Mode');
define('lg_portal_maintenance_note', 'The help desk is currently offline for maintenance. Please check back in a few minutes.');

define('lg_portal_password_reset', 'A new password has been created for your account. Please check your email.');

define('lg_portal_tagsearch', 'Tag');
define('lg_portal_tagsearch_books', 'Knowledge Book Matches');
