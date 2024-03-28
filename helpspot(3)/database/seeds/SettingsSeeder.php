<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        DB::table('HS_Settings')->insert(['sSetting' => 'cAUTHTYPE', 'tValue' => 'internal']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cAUTHTYPE_LDAP_OPTIONS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cAUTHTYPE_SAML_OPTIONS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_CUSTOMER_ID', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LICENSE', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ORGNAME', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LANG', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EXCLUDEMIMETYPES', 'tValue' => 'exe,vbs']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAIL_ALLOWMAILATTACHMENTS', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_TIMEZONE', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_TIMEZONE_OVERRIDE', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DATEFORMAT', 'tValue' => '%b %e %Y, %I:%M %p']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SHORTDATEFORMAT', 'tValue' => '%b %e, %Y']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_POPUPCALDATEFORMAT', 'tValue' => '%m/%d/%Y %I:%M %p']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_POPUPCALSHORTDATEFORMAT', 'tValue' => '%m/%d/%Y']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAIL_MAXATTACHSIZE', 'tValue' => '10000000']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PLACEHOLDERS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAIL_OUTTYPE', 'tValue' => 'mail']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAIL_SMTPCONN', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_CONTACTVIA', 'tValue' => '2']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_BAYESIAN_PROB_SPAM', 'tValue' => '.9']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_BAYESIAN_PROB_SPAM', 'tValue' => '.8']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_SPAM_LINK_CT', 'tValue' => '4']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_SPAM_FORMVALID', 'tValue' => '1200']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_SPAM_FORMVALID_ENABLED', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_SPAM_AUTODELETE', 'tValue' => '0']);
        // 25
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_CAPTCHA', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_LOGIN_SEARCHONTYPE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_LOGIN_AUTHTYPE', 'tValue' => 'internal']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LIVELOOKUP', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LIVELOOKUP_SEARCHES', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LIVELOOKUPAUTO', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_NOTIFICATIONEMAILACCT', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_NOTIFICATIONEMAILNAME', 'tValue' => 'internal']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DEFAULTMAILBOX', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMMAILACCT', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMMAILNAME', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_VERSION', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_NEWVERSION', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_NEWVERSIONCHECKED', 'tValue' => time()]);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_BATCHCLOSE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_BATCHRESPOND', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FEEDSENABLED', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FEEDCOPYRIGHT', 'tValue' => 'Not for use without permission']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAXSEARCHRESULTS', 'tValue' => '50']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DEBUG', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMPAGESIZE', 'tValue' => '40']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMFEEDSIZE', 'tValue' => '10']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_FORUMFEEDS', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMNEWESTSIZE', 'tValue' => '30']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMNEWESTPOSTSIZE', 'tValue' => '30']);
        //50
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FORUMCLOSEAFTER', 'tValue' => '30']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WYSIWYG', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WYSIWYG_STYLES', 'tValue' => '.HelpSpot_Highlight{ background-color: #FFFF99; }']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_PHONE', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_MSG', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_FORMFORMAT', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_TEMPLATES', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STRIPHTML', 'tValue' => '2']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLALLOWED', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_TASKSDEBUG', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_TIMETRACKER', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_LIVEREFRESH_TIME', 'tValue' => '300']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAILLOOP_TIME', 'tValue' => '600']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_DAYS_AFTER_CLOSE', 'tValue' => '30']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAILS_MAX_TO_IMPORT', 'tValue' => '10']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAILPREFIX', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_LOOPCHECK_TIME', 'tValue' => '-1 hour']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_LOOPCHECK_CTMAX', 'tValue' => '5']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_LOOPCHECK_EXISTING_TIME', 'tValue' => '-24 hour']);
        //75
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_ALLOWUPLOADS', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_EXCLUDEMIMETYPES', 'tValue' => 'exe,vbs']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMBED_MEDIA', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DAYS_TO_LEAVE_TRASH', 'tValue' => '30']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLEMAILS', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLEMAILS_EDITOR', 'tValue' => 'wysiwyg']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLEMAILS_WYSIWYG', 'tValue' => serialize(['"undo", "insert", "style", "emphasis", "align", "listindent", "format", "tools"'])]);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLEMAILS_WYSIWYG_CSS', 'tValue' => '.Highlight{ background-color: #FFFF99; }']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WSPUBLIC', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WSPRIVATE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WSPUBLIC_EXCLUDECOLUMNS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_IMAGE_THUMB_SIZE', 'tValue' => '560']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_HTMLEMAILS_FILTER_IMG', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAINTENANCE_MODE', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SAVE_DRAFTS_EVERY', 'tValue' => '20']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ROUNDROBIN', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DEFAULT_HISTORYSEARCH', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SPAMFILTER', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ATTACHMENT_LOCATION', 'tValue' => 'file']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ATTACHMENT_LOCATION_PATH', 'tValue' => storage_path('documents')]);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_AUTOREPLY', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_RECAPTCHA_PUBLICKEY', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_RECAPTCHA_PRIVATEKEY', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_RECAPTCHA_THEME', 'tValue' => 'clean']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_RECAPTCHA_LANG', 'tValue' => 'en']);
        //100
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FILTER_COUNT_CACHE', 'tValue' => '300']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FILTER_COUNT_SYSTEM_TRASH', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_FILTER_COUNT_SYSTEM_SPAM', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_DISABLESHORTCUTS', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_IMAGE_THUMB_MAXBYTES', 'tValue' => '10000000']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_TAKEIT_DOCHECK', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_BUSINESS_HOURS', 'tValue' => 'a:2:{s:8:"bizhours";a:7:{i:0;b:0;i:1;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:2;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:3;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:4;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:5;a:2:{s:5:"start";i:9;s:3:"end";i:17;}i:6;b:0;}s:8:"holidays";a:0:{}}']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_WIDGET_TAB_ACTIVE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_GLOBALBCC', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_GLOBALBCC_TYPE', 'tValue' => 'public']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_THEME', 'tValue' => 'blue']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_THEME_PORTAL', 'tValue' => 'clean']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SAVE_LAST_SEARCH', 'tValue' => '1200']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_REQ_PAGE_STATUS', 'tValue' => '20']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ORGLOGO', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_EMAIL_REPLYABOVE', 'tValue' => lg_replyabove]); // TODO: Does this exist at this point?
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STREAM_VIEW_CHARS', 'tValue' => '250']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STREAM_VIEW_REFRESH', 'tValue' => '20']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SPAM_WHITELIST', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SPAM_BLACKLIST', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ALLOW_ERROR_REPORTS', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_SERIOUS', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_IDLE_TIMEOUT', 'tValue' => '120']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_REFRESH_SECONDS', 'tValue' => '300']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_VIRTUAL_ARCHIVE', 'tValue' => '365']);

        DB::table('HS_Settings')->insert(['sSetting' => 'cFORCE_MERGE_LOCKING', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_CAPTCHA_WORDS', 'tValue' => $this->getCaptchaWords()]);

        DB::table('HS_Settings')->insert(['sSetting' => 'cSTAFFREPLY_AS_PUBLIC', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_CUSTCONNECT_ACTIVE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_META', 'tValue' => '{}']);

        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STATUS_ACTIVE', 'tValue' => '1']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_STATUS_SPAM', 'tValue' => '2']);

        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_ALLOWCC', 'tValue' => '0']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_ALLOWSUBJECT', 'tValue' => '0']);

        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAX_REQUEST_HISTORY', 'tValue' => '1500']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_MAX_AUTO_RUNS', 'tValue' => '50']);

        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ADMIN_JS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_ADMIN_CSS', 'tValue' => '']);
        DB::table('HS_Settings')->insert(['sSetting' => 'cHD_PORTAL_REQUIRE_AUTH', 'tValue' => '0']);

        // Email Templates
        $temps = [];
        $temps['portal_reqcreated'] = lg_inst_et_portal_reqcreated;
        $temps['forumreply'] = lg_inst_et_forumreply;
        $temps['staff'] = lg_inst_et_staff;
        $temps['newstaff'] = lg_inst_et_newstaff;
        $temps['ccstaff'] = lg_inst_et_ccstaff;
        $temps['public'] = lg_inst_et_public;
        $temps['reminder'] = lg_inst_et_reminder;
        $temps['external'] = lg_inst_et_external;

        $temps['portal_reqcreated_html'] = lg_inst_et_portal_reqcreated_html;
        $temps['external_html'] = lg_inst_et_external_html;
        $temps['staff_html'] = lg_inst_et_staff_html;
        $temps['ccstaff_html'] = lg_inst_et_ccstaff_html;
        $temps['public_html'] = lg_inst_et_public_html;
        $temps['reminder_html'] = lg_inst_et_reminder_html;
        $temps['forumreply_html'] = lg_inst_et_forumreply_html;
        $temps['newstaff_html'] = lg_inst_et_newstaff_html;
        $temps['sms'] = lg_inst_et_sms;

        $temps['portal_reqcreated_subject'] = lg_inst_et_portal_reqcreated_subject;
        $temps['external_subject'] = lg_inst_et_external_subject;
        $temps['staff_subject'] = lg_inst_et_staff_subject;
        $temps['ccstaff_subject'] = lg_inst_et_ccstaff_subject;
        $temps['public_subject'] = lg_inst_et_public_subject;
        $temps['reminder_subject'] = lg_inst_et_reminder_subject;
        $temps['forumreply_subject'] = lg_inst_et_forumreply_subject;
        $temps['newstaff_subject'] = lg_inst_et_newstaff_subject;

        $temps['partials_replyabove'] = lg_inst_et_partials_replyabove;
        $temps['partials_replyabove_html'] = lg_inst_et_partials_replyabove_html;

        $serializedTemplates = serialize($temps);
        DB::table('HS_Settings')
            ->where('sSetting', 'cHD_EMAIL_TEMPLATES')
            ->update(['tValue' => $serializedTemplates]);
    }

    protected function getCaptchaWords()
    {
        return 'and
that
was
for
his
with
but
which
they
had
have
you
from
this
her
all
are
him
their
were
she
them
one
when
who
said
there
been
shall
will
would
what
out
more
into
other
some
then
upon
man
than
any
may
very
now
could
time
your
great
these
only
our
such
unto
people
can
made
about
should
like
before
over
after
did
two
those
little
down';
    }
}
