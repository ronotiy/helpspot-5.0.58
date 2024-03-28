<?php
// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

//protect to only admins
if (! isAdmin()) {
    die();
}

/*****************************************
LANG
*****************************************/
$GLOBALS['lang']->load('admin.mailboxes');

/*****************************************
VARIABLE DECLARATIONS
*****************************************/
$basepgurl = route('admin', ['pg' => 'admin.tools.email']);
$hidePageFrame = 0;
$pagetitle = lg_admin_emailtemplates_title;
$tab = 'nav_admin';
$subtab = 'nav_admin_tools';
$feedback = '';

/*****************************************
ACTION
*****************************************/
if (isset($_POST['submit'])) {
    $t = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));
    $t['portal_reqcreated'] = $_POST['portal_reqcreated'];
    $t['staff'] = $_POST['staff'];
    $t['newstaff'] = $_POST['newstaff'];
    $t['ccstaff'] = $_POST['ccstaff'];
    $t['public'] = $_POST['public'];
    $t['reminder'] = $_POST['reminder'];
    $t['external'] = $_POST['external'];
    $t['sms'] = $_POST['sms'];
    $t['retrievepass'] = $_POST['retrievepass'];
    $t['newportalpass'] = $_POST['newportalpass'];

    $t['portal_reqcreated_html'] = $_POST['portal_reqcreated_html'];
    $t['staff_html'] = $_POST['staff_html'];
    $t['ccstaff_html'] = $_POST['ccstaff_html'];
    $t['public_html'] = $_POST['public_html'];
    $t['external_html'] = $_POST['external_html'];
    $t['reminder_html'] = $_POST['reminder_html'];
    $t['newstaff_html'] = $_POST['newstaff_html'];
    $t['retrievepass_html'] = $_POST['retrievepass_html'];
    $t['newportalpass_html'] = $_POST['newportalpass_html'];

    $t['portal_reqcreated_subject'] = $_POST['portal_reqcreated_subject'];
    $t['staff_subject'] = $_POST['staff_subject'];
    $t['ccstaff_subject'] = $_POST['ccstaff_subject'];
    $t['public_subject'] = $_POST['public_subject'];
    $t['external_subject'] = $_POST['external_subject'];
    $t['reminder_subject'] = $_POST['reminder_subject'];
    $t['newstaff_subject'] = $_POST['newstaff_subject'];
    $t['retrievepass_subject'] = $_POST['retrievepass_subject'];
    $t['newportalpass_subject'] = $_POST['newportalpass_subject'];

    $t['partials_replyabove'] = $_POST['partials_replyabove'];
    $t['partials_replyabove_html'] = $_POST['partials_replyabove_html'];

    $t = hs_serialize($t);

    storeGlobalVar('cHD_EMAIL_TEMPLATES', $t);
    return redirect()
        ->route('admin', ['pg' => 'admin.tools.email', 's' => 1]);
}

if ($_GET['s'] == 1) {
    $feedback = displayFeedbackBox(lg_admin_emailtemplates_saved);
}

$templates = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));

/*****************************************
JAVASCRIPT
*****************************************/
$headscript = '
<script type="text/javascript" language="JavaScript">
Event.observe(window, "load", function() {
	Event.observe("emailTemplateForm", "submit", function(event){
		if(!$F("public_subject").include("$tracking_id") && !getCookie("tracking_id_check")){
			hs_alert("'.hs_jshtmlentities(lg_admin_emailtemplates_trackidmissing). '");
			setCookie("tracking_id_check", "1", new Date ( 2020, 1, 1 ));
			Event.stop(event);
		}
	});
});

$jq(document).bind("cbox_complete", function(){
	$$("#cboxLoadedContent .tabs").each(function(tabs){
		new Control.Tabs(tabs);
	});
});
</script>
';

/*****************************************
PAGE OUTPUTS
****************************************/

$pagebody = '
<form action="'.$basepgurl.'" method="post" name="emailTemplateForm" id="emailTemplateForm">
	'.csrf_field().'
	'.$feedback. '

    ' . renderPageheader(lg_admin_emailtemplates_title) . '

        <div class="card padded">

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_public . '</label>
                    <div class="info">' . lg_admin_emailtemplates_publicex . '</div>
                </div>
                <div class="control">
                    ' . editEmailTemplate(true, false, $templates, 'public', lg_admin_emailtemplates_public, false,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}' => lg_placeholderspopup_accesskey,
                            '{{ $message }}' => lg_placeholderspopup_message,
                            '{{ $fullpublichistoryex }}' => lg_placeholderspopup_fullpublichistory_ex,
                            '{{ $fullpublichistory }}' => lg_placeholderspopup_fullpublichistory,
                            '{{ $lastcustomernote }}' => lg_placeholderspopup_lastcustomernote,
                        ]
                    ) . '
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_mailboxes_etexternal . '</label>
                    <div class="info">' . lg_admin_emailtemplates_externalex . '</div>
                </div>
                <div class="control">
                    ' . editEmailTemplate(true, false, $templates, 'external', lg_admin_mailboxes_etexternal, false,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}' => lg_placeholderspopup_accesskey,
                            '{{ $message }}' => lg_placeholderspopup_message,
                            '{{ $fullpublichistory }}' => lg_placeholderspopup_fullpublichistory,
                        ]
                    ) . '
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_mailboxes_etreqcreatedbyform . '</label>
                </div>
                <div class="control">
                    ' . editEmailTemplate(true, false, $templates, 'portal_reqcreated', lg_admin_mailboxes_etreqcreatedbyform, false,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                            '{{ $accesskey }}' => lg_placeholderspopup_accesskey,
                            '{{ $message }}' => lg_placeholderspopup_message,
                        ]
                    ) . '
                </div>
            </div>

            <div class="hr"></div>


            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_staff . '</label>
                </div>
                <div class="control">
                    ' . editEmailTemplate(true, false, $templates, 'staff', lg_admin_emailtemplates_staff, false,
                    [
                        '{{ $email_subject }}' => lg_placeholderspopup_subject,
                        '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                    ],
                    [
                        '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                        '{{ $accesskey }}' => lg_placeholderspopup_accesskey,
                        '{{ $message }}' => lg_placeholderspopup_message,
                        '{{ $fullpublichistoryex }}' => lg_placeholderspopup_fullpublichistory_ex,
                        '{{ $fullpublichistory }}' => lg_placeholderspopup_fullpublichistory,
                        '{{ $lastcustomernote }}' => lg_placeholderspopup_lastcustomernote,
                        '{{ $requestdetails }}' => lg_placeholderspopup_reqdetails,
                        '{{ $requestdetails_html }}' => lg_placeholderspopup_reqdetailshtml,
                    ]
                ) . '
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_ccstaff . '</label>
                </div>
                <div class="control">
                    ' . editEmailTemplate(true, false, $templates, 'ccstaff', lg_admin_emailtemplates_ccstaff, false,
                        [
                            '{{ $email_subject }}' => lg_placeholderspopup_subject,
                            '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                        ],
                        [
                            '{{ $requestcheckurl }}' => lg_placeholderspopup_requestcheckurl,
                            '{{ $name }}' => lg_placeholderspopup_staffname,
                            '{{ $message }}' => lg_placeholderspopup_message,
                            '{{ $fullpublichistoryex }}' => lg_placeholderspopup_fullpublichistory_ex,
                            '{{ $fullpublichistory }}' => lg_placeholderspopup_fullpublichistory,
                            '{{ $lastcustomernote }}' => lg_placeholderspopup_lastcustomernote,
                        ]
                    ) . '
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_reminders . '</label>
                </div>
                <div class="control">
                    '.editEmailTemplate(true, false, $templates, 'reminder', lg_admin_emailtemplates_reminders, false,
                            [
                                '{{ $email_subject }}' => lg_placeholderspopup_subject,
                                '{{ $tracking_id }}' => lg_placeholderspopup_trackerid
                            ],
                            [
                                '{{ $message }}'=>lg_placeholderspopup_message,
                                '{{ $requestdetails }}'=>lg_placeholderspopup_reqdetails,
                                '{{ $requestdetails_html }}'=>lg_placeholderspopup_reqdetailshtml, ]
                            ).'
                </div>
            </div>

            <div class="hr"></div>

            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_newstaff . '</label>
                </div>
                <div class="control">
                    '.editEmailTemplate(true, false, $templates, 'newstaff', lg_admin_emailtemplates_newstaff, false,
                    ['{{ $email_subject }}' => lg_placeholderspopup_subject,],
                    [
                        '{{ $email }}'=>lg_placeholderspopup_email,
                        '{{ $password }}'=>lg_placeholderspopup_password,
                        '{{ $helpspoturl }}'=>lg_placeholderspopup_helpspoturl,
                    ]).'
                </div>
            </div>

            <div class="hr"></div>


            <div class="fr">
                <div class="label">
                    <label class="" for="sCategory">' . lg_admin_emailtemplates_sms . '</label>
                </div>
                <div class="control">
                    '.editEmailTemplate(false, false, $templates, 'sms', lg_admin_emailtemplates_sms, false, false,
                    [
                        '{{ $label }}'=>lg_placeholderspopup_label,
                        '{{ $message }}'=>lg_placeholderspopup_message,
                    ]).'
                </div>
            </div>

            <div class="hr"></div>

            <fieldset>
                <div class="sectionhead">'.lg_admin_emailtemplates_partials.'</div>
                <div class="fr">
                    <div class="label">
                        <label class="" for="sCategory">' . lg_admin_emailtemplates_replyabove . '</label>
                    </div>
                    <div class="control">
                        '.editEmailTemplate(false, false, $templates, 'partials_replyabove', lg_admin_emailtemplates_replyabove, false,
                            [
                                '{{ $email_subject }}' => lg_placeholderspopup_subject,
                                '{{ $portal_email }}'=>lg_placeholderspopup_portalemail
                            ],
                            [
                                '{{ $portal_email }}'=>lg_placeholderspopup_portalemail,
                                '{{ $portal_password }}'=>lg_placeholderspopup_portalpassword, ]
                            ). '
                    </div>
                </div>
            </fieldset>

        </div>

        <div class="button-bar space">
            <button type="submit" name="submit" class="btn accent">' . lg_admin_emailtemplates_savebutton . '</button>
        </div>
</form>';
