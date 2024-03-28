<?php

namespace HS\Trials;

use Faker;
use HS\Cloud\IsHosted;

class PopulateDataCommand
{
    use IsHosted;

    //The HS license file.
    public $license;

    public function __construct()
    {
        ob_start();
        // Boilerplate for HelpSpot internal API
        require_once cBASEPATH.'/helpspot/lib/error.lib.php';
        require_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        require_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.mailboxes.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.license.php';
        require_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
        require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        require_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        require_once cBASEPATH.'/helpspot/lib/api.kb.lib.php';
        ob_clean();

        //Get License
        $licenseObj = new \usLicense(hs_setting('cHD_CUSTOMER_ID'), hs_setting('cHD_LICENSE'), hs_setting('SSKEY'));
        $this->license = $licenseObj->getLicense();
    }

    public function run()
    {
        if ($this->isSafe()) {
            $faker = Faker\Factory::create();

            // Add users
            for ($i = 1; $i < 10; $i++) {
                apiAddEditUser([
                    'sFname' => $faker->firstName,
                    'sLname' => $faker->lastName,
                    'sEmail' => $faker->safeEmail,
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);
            }

            // If we're on cloud populate UserScape staff
            if ($this->isHosted() || config('app.debug')) {
                apiAddEditUser([
                    'sFname' => 'Rebecca',
                    'sLname' => 'Hellemans',
                    'sEmail' => 'rebecca@userscape.com',
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);

                apiAddEditUser([
                    'sFname' => 'Ian',
                    'sLname' => 'Landsman',
                    'sEmail' => 'ian@userscape.com',
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);

                apiAddEditUser([
                    'sFname' => 'Matt',
                    'sLname' => 'Stenson',
                    'sEmail' => 'matt@userscape.com',
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);

                apiAddEditUser([
                    'sFname' => 'Chris',
                    'sLname' => 'Fidao',
                    'sEmail' => 'chris@userscape.com',
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);

                apiAddEditUser([
                    'sFname' => 'Eric',
                    'sLname' => 'Barnes',
                    'sEmail' => 'eric@userscape.com',
                    'sPassword' => 'start',
                    'fUserType' => 1,
                ], __FILE__, __LINE__);
            }

            $people = $GLOBALS['DB']->GetCol('SELECT xPerson FROM HS_Person');

            // Add categories
            apiAddEditCategory([
                'sCategory' => 'Prospect',
                'sCategoryGroup' => 'Sales',
                'fAllowPublicSubmit' => 0,
                'sPersonList' => serialize($people),
            ], __FILE__, __LINE__);
            apiAddEditCategory([
                'sCategory' => 'Upgrade',
                'sCategoryGroup' => 'Sales',
                'fAllowPublicSubmit' => 0,
                'sPersonList' => serialize($people),
            ], __FILE__, __LINE__);
            apiAddEditCategory([
                'sCategory' => 'Demo',
                'sCategoryGroup' => 'Sales',
                'fAllowPublicSubmit' => 0,
                'sPersonList' => serialize($people),
            ], __FILE__, __LINE__);
            apiAddEditCategory([
                'sCategory' => 'General Sales',
                'sCategoryGroup' => '',
                'fAllowPublicSubmit' => 1,
                'sPersonList' => serialize($people),
            ], __FILE__, __LINE__);
            apiAddEditCategory([
                'sCategory' => 'General Support',
                'sCategoryGroup' => '',
                'fAllowPublicSubmit' => 1,
                'sPersonList' => serialize($people),
            ], __FILE__, __LINE__);

            $categories = $GLOBALS['DB']->GetCol('SELECT xCategory FROM HS_Category');

            // Mailboxes
            apiAddEditMailbox([
                'sUsername' => 'systest',
                'sHostname' => 'imap.emailsrvr.com',
                'sPassword' => 'abc123',
                'sType' => 'imap',
                'sPort' => '143',
                'sSecurity' => '',
                'sReplyName' => 'Brand B',
                'sReplyEmail' => 'brand@example.com',
            ], __FILE__, __LINE__);

            // Requests
            for ($i = 1; $i < 2000; $i++) {
                // Build requests
                $via = (mt_rand(0, 10) < 9 ? 1 : 7);

                $open = (mt_rand(0, 100) < 95 ? 0 : 1);

                $daysback = $faker->biasedNumberBetween(1, 365, 'sqrt'); // make a nicer curve in charts

                // Set realistic open/closed dates
                if (! $open) {
                    $opened = $faker->dateTimeBetween("-{$daysback} days", 'now')->getTimestamp();
                    $closed_in = mt_rand(480, 172800);

                    $dtGMTOpened = $opened;
                    $dtGMTClosed = $opened + $closed_in;
                } else {
                    // For currently open keep it to within the last few days
                    $dtGMTOpened = $faker->dateTimeBetween('-3 days', 'now')->getTimestamp();
                    $dtGMTClosed = 0;
                }

                $fname = $faker->firstName;

                $assigned = $this->random($people);

                $result = apiAddEditRequest([
                    'fOpenedVia' => $via,
                    'xOpenedViaId' => ($via == 1 ? 1 : 0),
                    'xMailboxToSendFrom' => 1,
                    'xPersonOpenedBy' => 0,
                    'fPublic' => 1,
                    'xPersonAssignedTo' => $assigned,
                    'fOpen' => $open,
                    'dtGMTOpened' => $dtGMTOpened,
                    'dtGMTClosed' => $dtGMTClosed,
                    'xStatus' => ($open == 1 ? (mt_rand(0, 100) < 99 ? 1 : 4) : (mt_rand(0, 100) < 90 ? 3 : 6)), // put some open in escalated, some closed in alternate close status
                    'xCategory' => $this->random($categories),
                    //'sTitle' => $faker->sentence(mt_rand(3,10)),
                    'sTitle' => $faker->realText(mt_rand(20, 60), 5),
                    'sUserId' => $faker->biasedNumberBetween(3000, 5000),
                    'sFirstName' => $fname,
                    'sLastName' => $faker->lastName,
                    'sEmail' => $faker->safeEmail,
                    'sPhone' => $faker->phoneNumber,
                    //'tBody' => $faker->text(800)."\n\n".$faker->text(600)."\n\n".$fname,
                    'tBody' => $faker->realText(800, 5)."\n\n".$faker->realText(600, 5)."\n\n".$fname,
                    'fNoteIsHTML' => 0,
                    'fNoteIsClean' => true,
                ], 0, __FILE__, __LINE__);

                // Add variable number of notes
                apiAddRequestHistory([
                    'xRequest' => $result['xRequest'],
                    'xPerson' => $assigned,
                    'dtGMTChange' => mt_rand($dtGMTOpened, ($dtGMTClosed ? $dtGMTClosed - 600 : time())),
                    'fPublic' => 0,
                    //'tNote'		=> $faker->text(800),
                    'tNote' => $faker->realText(800, 5),
                    'fNoteIsHTML' => 0,
                    'fNoteIsClean' => 1,
                ]);

                apiAddRequestHistory([
                    'xRequest' => $result['xRequest'],
                    'xPerson' => $assigned,
                    'dtGMTChange' => mt_rand($dtGMTOpened, ($dtGMTClosed ? $dtGMTClosed : mt_rand($dtGMTOpened, time()))),
                    'fPublic' => 1,
                    //'tNote'		=> $faker->text(800),
                    'tNote' => $faker->realText(800, 5),
                    'fNoteIsHTML' => 0,
                    'fNoteIsClean' => 1,
                ]);
            }

            //Create a filter to show open requests in each category and each user
            foreach ($categories as $catid) {
                unset($f);
                $f = [];
                $f['mode'] = 'add';
                $f['sFilterName'] = $GLOBALS['DB']->GetOne('SELECT sCategory FROM HS_Category WHERE xCategory = ?', [$catid]);
                $f['anyall'] = 'all';
                $f['displayColumns'] = ['fOpenedVia', 'fullname', 'reqsummary', 'age'];
                $f['sFilterFolder'] = 'Open by Category';
                $f['fShowCount'] = 1;
                $f['fType'] = 1;
                $f['xPerson'] = 1;
                $f['sPersonList'] = 1;
                $f['conditioninit_1'] = 'fOpen';
                $f['conditioninit_2'] = 1;
                $f['conditiontime_1'] = 'xCategory';
                $f['conditiontime_2'] = 'is';
                $f['conditiontime_3'] = $catid;

                $rule = new \hs_auto_rule();
                $rule->SetAutoRule($f);

                $f['tFilterDef'] = hs_serialize($rule);

                apiAddEditFilter($f);
            }

            foreach ($people as $personid) {
                unset($f);
                $f = [];
                $f['mode'] = 'add';
                $f['sFilterName'] = $GLOBALS['DB']->GetOne('SELECT '.dbConcat(' ', 'HS_Person.sFname', 'HS_Person.sLname').' FROM HS_Person WHERE xPerson = ?', [$personid]);
                $f['anyall'] = 'all';
                $f['displayColumns'] = ['fOpenedVia', 'fullname', 'reqsummary', 'age'];
                $f['sFilterFolder'] = 'Open by Agent';
                $f['fShowCount'] = 1;
                $f['fType'] = 1;
                $f['xPerson'] = 1;
                $f['conditioninit_1'] = 'fOpen';
                $f['conditioninit_2'] = 1;
                $f['conditiontime_1'] = 'xPersonAssignedTo';
                $f['conditiontime_2'] = 'is';
                $f['conditiontime_3'] = $personid;

                $rule = new \hs_auto_rule();
                $rule->SetAutoRule($f);

                $f['tFilterDef'] = hs_serialize($rule);

                apiAddEditFilter($f);
            }

            unset($f);
            $f = [];
            $f['mode'] = 'add';
            $f['sFilterName'] = 'Escalated';
            $f['anyall'] = 'all';
            $f['displayColumns'] = ['fOpenedVia', 'fullname', 'reqsummary', 'age'];
            $f['sFilterFolder'] = 'Global Filters';
            $f['fShowCount'] = 1;
            $f['fType'] = 1;
            $f['xPerson'] = 1;
            $f['conditioninit_1'] = 'fOpen';
            $f['conditioninit_2'] = 1;
            $f['conditiontime_1'] = 'xStatus';
            $f['conditiontime_2'] = 'is';
            $f['conditiontime_3'] = 4;

            $rule = new \hs_auto_rule();
            $rule->SetAutoRule($f);

            $f['tFilterDef'] = hs_serialize($rule);

            apiAddEditFilter($f);

            // Update business hours
            storeGlobalVar('cHD_BUSINESS_HOURS', 'a:1:{s:8:"bizhours";a:7:{i:1;a:2:{s:5:"start";s:1:"8";s:3:"end";s:2:"17";}i:2;a:2:{s:5:"start";s:1:"8";s:3:"end";s:2:"17";}i:3;a:2:{s:5:"start";s:1:"8";s:3:"end";s:2:"17";}i:4;a:2:{s:5:"start";s:1:"8";s:3:"end";s:2:"17";}i:5;a:2:{s:5:"start";s:1:"8";s:3:"end";s:2:"17";}i:6;b:0;i:0;b:0;}}');

            // Add a KB
            $GLOBALS['DB']->Execute('INSERT INTO HS_KB_Books(sBookName,iOrder,fPrivate,tDescription,tEditors) VALUES (?,?,?,?,?)',
                [
                    'Documentation',
                    0,
                    0,
                    'HelpSpot knowledge books can be used for manuals, FAQs, and more.',
                    serialize($people),
                ]
            );

            $bookid = dbLastInsertID('HS_KB_Books', 'xBook');

            apiAddChapter([
                'xBook' => $bookid,
                'sChapterName' => 'Release Notes',
                'fAppendix' => 0,
                'fHidden' => 0,
                'orderafter' => 0,
            ]);

            apiAddChapter([
                'xBook' => $bookid,
                'sChapterName' => 'Administration',
                'fAppendix' => 0,
                'fHidden' => 0,
                'orderafter' => 0,
            ]);

            $chapgs = apiAddChapter([
                'xBook' => $bookid,
                'sChapterName' => 'Getting Started',
                'fAppendix' => 0,
                'fHidden' => 0,
                'orderafter' => 0,
            ]);

            $chapid = apiAddChapter([
                'xBook' => $bookid,
                'sChapterName' => 'Installation',
                'fAppendix' => 0,
                'fHidden' => 0,
                'orderafter' => 0,
            ]);

            apiAddPage([
                'xBook' => $bookid,
                'xChapter' => $chapid,
                'sPageName' => 'HelpSpot Commands',
                'tPage' => '<p>HelpSpot 4.0.0 now comes with a command-line tool, which has some tools to help you automate a few things. The functionality of this is limited for now, but will likely expand with added features.</p><h1>General</h1><h2>Linux:</h2><p>The <strong>hs </strong>command installed with HelpSpot is the command line utility. On Linux servers, you can run it using PHP:</p><pre># List the available commands and options:$ php hs</pre><h2>Windows:</h2><p>On Windows, you need to run the <strong>hs</strong> command using the PHP that comes with HelpSpot using Cmd or PowerShell, and let it know which configuration file to use:</p><pre>&gt; C:\\\'Program Files (x86)\'\\helpspot\\php\\php.exe -c C:\\\'Program Files (x86)\'\\helpspot\\php\\php.ini C:\\\'Program Files (x86)\'\\helpspot\\helpspot\\hs</pre><p>It might be easier to first \"change directory\" into the HelpSpot directory so these file paths are shorter:</p><pre>&gt; cd C:\\\'Program Files (x86)\'\\helpspot\\helpspot&gt; ..\\php\\php.exe -c ..\\php\\php.ini .\\hs</pre><blockquote><p>For both Linux and Windows, running <strong>hs</strong> without any parameters will simply list out the available commands.</p></blockquote><p>Here are the commands available for HelpSpot:</p><ul><li><strong>install</strong> - A command to install/configure HelpSpot</li><li><strong>update</strong> - A command to run updates for HelpSpot from 4.0.0+</li><li><strong>attachments:tofile</strong> - A command to convert attachments from the database to the filesystem</li><li><strong>convert:base</strong> - A command to start the update HelpSpot from version 3 to 4</li><li><strong>convert:request</strong> - A command to complete the update from HelpSpot version 3 to 4</li><li><strong>db:exists</strong> - Check if a HelpSpot database exists</li><li><strong>search:config</strong> - Generate a Sphinx Search configuration file</li></ul><h1>Install</h1><p>HelpSpot can be installed via the command line instead of using the web interface. This might be useful for automating the install of HelpSpot.</p><p>The simplest way to use this command is to download the HelpSpot files, and then run the \"install\" command. This will ask you for the information it needs to complete the installation.</p><pre>php hs install</pre><p>This asks you all the information needed to install HelpSpot. You can also pass the command all the needed information directly, allowing the install to be automated. The following are the flags/options you can set:</p><p>Note that you can run `php hs install -h`, which will show you the available flags/options to use for the install command.</p><ul><li><strong>--agree=yes</strong> - \"Yes\" to say you agree to the terms and conditions</li><li><strong>--name=\'Your Full Name\'</strong> - The administrator user\'s full name (first and last)</li><li><strong>--admin-email=admin@email.com</strong> - The administrator user\'s email address</li><li><strong>--password=your_password</strong> - The administrator user\'s passwor</li><li><strong>--company=\'Your Company\'</strong> - The company as displayed publicly</li><li><strong>--timezone=\'America/New_York\'</strong> - The <a href=\"http://php.net/manual/en/timezones.php\">timezone</a> to use within HelpSpot</li><li><strong>--customer-id=123456</strong> - Your customer ID</li><li><strong>--license-file=/path/to/license-file.txt</strong> - The path to the license file, which should be saved to the server before running the server</li><li><strong>--notification-email=notification@email.com</strong> - The email used for sending notifications</li><li><strong>--ask-smtp=yes</strong> - Add in your outgoing email settings, using the following \"smtp\" options. Use \"no\" to configure this later, the smtp configuration is optional</li><li><strong>--smtp-host=somehostname</strong> - (Conditional on \"--ask-smtp\") The outgoing email server</li><li><strong>--smtp-port=1234</strong> - (Conditional on \"--ask-smtp\") The outgoing email port</li><li><strong>--smtp-user=some@user.com</strong> - (Conditional on \"--ask-smtp\") The outgoing email user name</li><li><strong>--smtp-password=some_password</strong> - (Conditional on \"--ask-smtp\") The outgoing email password</li><li><strong>--smtp-protocol=none</strong> - (Conditional on \"--ask-smtp\") The connection protocol. One of \"none\" (no security), \"ssl\" or \"tls\"</li></ul><h1>Update</h1><p>You can use this tool to update HelpSpot as well. This will be useful when versions greater than 4.0.0 is released. For example, you can run this command when updating from version 4.0.0 to 4.0.1.</p><p>Once you update the HelpSpot files on your server, you can run the update command:</p><pre>php hs update</pre><p>This will gather the old version and new version of HelpSpot and run any scripted or database updates needed. This will not install the latest HelpSpot file - that step must still be done outside of this command line tool.</p><ul><li><strong>--language=english.us</strong> - Defaults to the \"english.us\" language</li></ul><p>This command is much simpler than the install command, as it will determine the needed information from available system information.</p><h1>Attachments</h1><p>This command will allow you to save your file attachments from the database (the default location prior to HelpSpot version 4) to your server file system. This command can only be used after you convert your database from version 3 to version 4, due to differences in the database structure.</p><blockquote><p>This command will delete file attachment data from your database. We highly suggest backing up your database before running this command.</p></blockquote><p>To begin saving database attachments to the filesystem, run the following command.</p><pre>php hs attachments:tofile</pre><p>This will ask you the file path to save your attachments. The default value is the <strong>data/documents</strong> directory, which HelpSpot 4 comes with for this purpose.</p><p>Note that you can run `php hs attachments:tofile -h`, which will show you the available flags/options to use for the install command.</p><ul><li><strong>--path=data/documents</strong> - The path (relative to the \"hs\" command location or absolute) where the files should be saved to. This location must be writable by the user running the command.</li></ul><p>This command may take a considerable amount of time, depending on the size of your database. The command can safely be run multiple times, including if it stops on failure or timeout.</p><h1>Convert</h1><p>The convert commands are <strong>used specifically to update HelpSpot from Version 3 to Version 4</strong>. HelpSpot requires a conversion of the current data from its current character set to UTF-8 (in order to support multiple language alphabets and characters properly). This process can take a relatively long time and should be run via the command-line, which has no time limit like an update from within the browser may have.</p><p>Before updating from Version 3 to Version 4, you must create a new, empty database that is setup for UTF-8 character sets. The new database credentials should be added into the .env file. However, <strong>keep a note of the old credentials</strong>, as the conversion process requires access to both databases.</p><p>There are two commands for conversion:</p><ul><li><strong>convert:base</strong> - Creates the database tables in the new database, and converts the \"base\" (not Request-related) data into the new database from the old database.</li><li><strong>convert:requests</strong> - Converts Requests and all related data from the old database to the new database. This can go back in time a specific amount of days, or do all requests. This command can be run more than once without fear of duplicating data.</li></ul><p>Because the conversion requires two databases, it\'s useful to set the terms used:</p><ul><li><strong>source database</strong> - the old database from which data will be converted and copied into the new, UTF8-Capable database</li><li><strong>destination database</strong> - the new, UTF8-Capable database which data will be converted and copied into.</li></ul><p>The destination database credentials must be set in the .env file, while the source database credentials will be asked for in while using the convert commands.</p><h2>convert:base</h2><p>The convert:base command should be run first. This command will do two things:</p><ol><li>Create the needed database schema for HelpSpot version 4 in the new database</li><li>Copy all database data, except for that related to Requests, into the new database</li></ol><p>Running this command will ask you for the necessary input.</p><pre>php hs convert:base</pre><p>You can also provide it the needed information head of time:</p><ul><li><strong>--db-type=mysql</strong> - The <em>source</em> database type you are using. Note that this is the <strong>old</strong> database, <span style=\"text-decoration: underline;\">not the new UTF-8 capable database</span>. The default is \"mysql\". Valid types are: mysql or sqlsrv</li><li><strong>--db-host=localhost</strong> - The hostname to use to access the source database. The default is \"localhost\".</li><li><strong>--db-user=some_username</strong> - The username used to access the source database</li><li><strong>--db-pass=some_password</strong> - The password used to access the source database</li><li><strong>--db-name=some_db_name</strong> - The name of the source database</li><li><strong>--from-encoding=iso-8859-1 -</strong> <strong>Optional</strong> parameter, the character encoding used to convert data from. Character set encoders need to know what character set they are encoding from in order to properly encode to other character sets such as UTF-8. The default for HelpSpot prior to version 4 was \"iso-8859-1\", which is also commonly referred to as \"latin1\"<ul><li>This script will guess the character set if it\'s not explicitly set via the <strong>--from-encoding</strong> parameter. It gets the character set from the cHD_CHARSET variable in the HS_Settings table in the source database.</li></ul></li><li><strong>--schema-only=yes</strong> - Set the database schema only, don\'t copy and data</li><li><strong>--data-only=yes</strong> - Copy data only, assuming the schema is already in place (via --schema-only=yes complete previously)</li></ul><p>This script takes about 10 minutes to run on a 2GB database. See the \"Speed Tips\" section within the Overview chapter for more information.</p><h3>Custom Database Schema</h3><p>Some HelpSpot customers add new tables or adjust the schema of the standard HelpSpot database. For those customers, the <strong>convert:base</strong> command needs to be handled in a way that allows you to add custom tables, columns or schema adjustments before database data is copied to the new database.</p><p>To accomplish this, the <strong>convert:base</strong> command can be run in two parts via the <strong>--schema-only</strong> and <strong>--data-only</strong> options. The workflow will look like this:</p><ol><li>Run <strong>convert:base --schema-only</strong>, which will update the new database with the new schema, but won\'t copy and data</li><li>Add any new tables, columns, or schema adjustments as needed</li><li>Run <strong>convert:base --data-only</strong>, which will copy data from the old database to the new database. This should copy custom columns and handle data from adjusted existing columns. However, it will not copy new tables that are not part of HelpSpot standard database installation. Copying of any additional custom tables created will need to be done by the system administrator.</li></ol><h2>convert:requests</h2><p>The convert:requests command is used to convert Requests and all related data. Requests have a lot of related data, so each request is converted and copied one by one from the source database to the destination database.</p><pre>php hs convert:requests</pre><p>This command will save requests starting with the newest first. This let\'s you quickly get up and running by converting the latest requests (for example, the last 90 days worth of requests). Then, if HelpSpot support is critical enough, your staff can continue working within HelpSpot while the command is run again to complete the remaining requests in the system.</p><p>Since many systems have been up and running for years, some systems will have hundreds of thousands of database rows of requests and request history. The use case above (copying the last 90 days of requests and then the remaining in the background while the system is in active use) is for such cases.</p><p>On a database of about 2GB of size with about 30,000 requests and 285,000 items of request history, the conversion process took about 20-30 minutes.</p><p>The options for this command are similar to the convert:base:</p><ul><li><strong>--db-type=mysql </strong>- The <em>source </em>database type you are using. Note that this is the <strong>old </strong>database, not the new UTF-8 capable database. The default is \"mysql\". Valid types are: mysql, mssql, or postgres_helpspot</li><li><strong>--db-host=localhost </strong>- The hostname to use to access the source database. The default is \"localhost\".</li><li><strong>--db-user=some_username </strong>- The username used to access the source database</li><li><strong>--db-pass=some_password </strong>- The password used to access the source database</li><li><strong>--db-name=some_db_name </strong>- The name of the source database</li><li><strong>--from-encoding=iso-8859-1 </strong>- Optional parameter, the character encoding used to convert data from. Character set encoders need to know what character set they are encoding from in order to properly encode to other character sets such as UTF-8. The default for HelpSpot prior to version 4 was \"iso-8859-1\", which is also commonly referred to as \"latin1\"<ul><li>This script will guess the character set if it\'s not explicitly set via the <strong>--from-encoding </strong>parameter. It gets the character set from the cHD_CHARSET variable in the HS_Settings table in the source database.</li></ul></li><li><strong>--days-back=90</strong> - The number of days of requests to go back in time. The default is 90 days. Use \"all\" to copy all requests. Note that HelpSpot will not be usable until this command completes running at least once, upon which it updates HelpSpot\'s internal setting to the correct version, making HelpSpot able to reach the admin login page.</li></ul><p>This command tracks requests that have been successfully copied to the new database. <strong>This means you can run the command multiple times safel</strong>y. Note that the <strong>--days-back</strong> option is relative to \"now\", the time when you run the command. If you run the command \"now\", going back 90 days, it will copy the last 90 days of requests. Running the identical request immediately a second time will result in no requests being copied, as the last 90 days worth of requests have already been copied. To go back further than 90 days, increase the number of days set, or use \"all\": <strong>--days-back=all</strong></p><pre>php hs convert:requests --days-back=all</pre><h1>Database Exists</h1><p>The <strong>db:exist</strong> command determines if a populated HelpSpot database already exists based on the credentials used in the <strong>.env</strong> file. This is a utility function, used by the Windows installer primarily, but available to all customers. This command may be useful to those automating an install or update of HelpSpot.</p><pre>php hs db:exists</pre></p>',
                'iOrder' => 2,
            ], __FILE__, __LINE__);

            apiAddPage([
                'xBook' => $bookid,
                'xChapter' => $chapid,
                'sPageName' => 'Requirements',
                'tPage' => 	'<h2>Language</h2><p>HelpSpot requires PHP 5.3+. The free ionCube Loader or Zend Guard Loader is required.</p><p>* Note, Windows users our installer installs all required components for you.</p><h2>Operating System</h2><p>HelpSpot has been tested with:</p><ul class=\"bulleted\"><li>Linux</li><li>Unix</li><li>Windows 2003</li><li>Windows 2008</li><li>Windows 2008R2</li><li>Windows 2012</li></ul><h2>Database</h2><ul class=\"bulleted\"><li>Microsoft SQL Server 2005 / 2008 / 2012</li><li>MySQL 5+</li><li>PostgreSQL 9</li></ul><h2>Email Servers and Services</h2><p>HelpSpot supports any email server or service that supports IMAP or POP including but not limited to:</p><ul><li>Microsoft Exchange</li><li>Gmail</li><li>Live.com</li><li>Office365.com</li><li>Rackspace.com</li><li>any other IMAP or POP mail server</li></ul><h2>Mobile Access</h2><p>Requires an HTML compliant mobile browser.</p>',
                'iOrder' => 0,
            ], __FILE__, __LINE__);

            apiAddPage([
                'xBook' => $bookid,
                'xChapter' => $chapgs,
                'sPageName' => 'Creating Requests',
                'tPage' => '<p>The <em>create request</em> button, below the top navigation, is used to manually create requests. Requests would typically enter the system via email, portal form, or API. However, there is a means to create request for such customer encounters such as phone or IM.</p><p>Once a User clicks the <em>create request</em> button, the request page loads.</p>',
                'iOrder' => 1,
            ], __FILE__, __LINE__);

            apiAddPage([
                'xBook' => $bookid,
                'xChapter' => $chapgs,
                'sPageName' => 'Big Picture Overview',
                'tPage' => 	'<p>If you\'re reading this manual you\'ve realized that email folders, sticky notes, or even your current help desk tool are no longer the way to handle Customer inquiries. You\'re looking for an improved experience for Staff and Customers with more advanced capabilities to manage your support operation...<strong><span style=\"color: #0000ff;\">Welcome to HelpSpot</span>!</strong></p><p>HelpSpot\'s core function can be summed up as:</p><p><strong>\"HelpSpot is a web-based </strong><strong>application</strong><strong> that empowers companies to effectively manage Customer inquiries.\"</strong></p><p>To create a product that is optimized to support this core function, we kept a few guiding principles top-of-mind:</p><h2><strong>Accommodate many support channels</strong></h2><p>Customer inquiries come at you from every direction. HelpSpot accounts for this by supporting the channels you need:</p><ul><li>Email (your current support email account)â<strong>HelpSpot is optimized for email-based exchanges</strong>,</li><li>Web-form (using the portal),</li><li>Phone/walk-up/fax/IM (via User creation), and</li><li>API (covered in API Manual, linked)</li></ul><h2><strong>Create structured flexibility</strong></h2><p>The needs of every company differ; some require elaborate issue categorization and meta-data collection capability, while others only loosely categorize issues. To accommodate all types of companies, we provide a structured framework of data collection/categorization that is fully customizable yet still easy to report against.</p><h2><strong>Require request ownership</strong></h2><p>Individual ownership is required in HelpSpot. Requests cannot be assigned to a nebulous workgroup or department. Individual ownership is the cornerstone of driving processing efficiency and ultimately Customer satisfaction.</p><h2><strong>Create a clear, concise, request history</strong></h2><p>Every request has a complete log of all actions taken and messages sent. This allows any User, at anytime, to become current with a request.</p><div> </div>',
                'iOrder' => 3,
            ], __FILE__, __LINE__);

            return true;
        } else {
            return false;
        }
    }

    public function depopulate()
    {
        if (isset($this->license['trial'])) {

            // Remove all but the initial user
            $GLOBALS['DB']->Execute('DELETE FROM HS_Person WHERE xPerson > 1');

            // Remove all but the starting categories
            $GLOBALS['DB']->Execute('DELETE FROM HS_Category WHERE xCategory > 6');

            // Find all requests and remove them
            $results = $GLOBALS['DB']->GetCol('SELECT xRequest FROM HS_Request WHERE xRequest <> 12400');

            foreach ($results as $k => $id) {
                apiDeleteRequest($id);
            }

            // Find all filters and remove them
            $results = $GLOBALS['DB']->GetCol('SELECT xFilter FROM HS_Filters WHERE xFilter <> 1 AND xFilter <> 2');

            foreach ($results as $k => $id) {
                apiDeleteFilter($id);
            }

            // Remove KBs
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Books');
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Chapters');
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Pages');
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_Documents');
            $GLOBALS['DB']->Execute('DELETE FROM HS_KB_RelatedPages');

            return true;
        } else {
            return false;
        }
    }

    private function isSafe()
    {
        // Ensure this is a trial license and an empty database. If not, abort!
        if (! isset($this->license['trial'])) {
            return false;
        }

        if ($GLOBALS['DB']->GetOne('SELECT COUNT(*) as ct FROM HS_Request') > 1) {
            return false;
        }

        return true;
    }

    private function random($array)
    {
        $k = mt_rand(0, count($array) - 1);

        return $array[$k];
    }
}
