@extends('public/layout')

@section('title', __('HelpSpot Updated!'))

@section('content')
<style>
    pre, code {
        background: #283142;
        color: white;
    }
</style>
<div class="text-lg" style="color:#3A2D23;">
    <div class="py-6">
        <div class="max-w-5xl mx-auto">
            <header class="flex">
                <div>
                    <img src="{{ url('/static/img5/helpspot-logo-color.svg') }}" class="h-8 w-auto" />
                </div>
                <div class="flex flex-1 justify-end items-center">
                    <a href="{{ route('login') }}" class="" style="color: #E00000">Login to Admin</a>
                </div>
            </header>
        </div>
    </div>

    <div>
        <div class="max-w-5xl mx-auto mt-16">
            <header class="mb-12">
                <h1 class="font-bold mb-6" style="font-size:4rem;">What's new in HelpSpot 5</h1>
                <p class="mb-2 text-xl">There are important updates and changes made in HelpSpot 5 to be aware of.</p>
                <p class="mb-2 text-xl">This documentation pertains to Windows Server users.</p>
            </header>
        </div>
    </div>

    <div style="background: #F7F6F3" class="py-16 border-b border-gray-300">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Configuration</h2>
                    <p class="mb-2">The <code class="text-white rounded p-1">config.php</code> file is now a <code class="text-white rounded p-1">.env</code> file.</p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div class="pb-2">
                        ✅ <strong class="font-medium">Action Required:</strong> None
                    </div>
                    <div>
                        👉 <strong class="font-medium">Recommendation:</strong> Double check that .env exists and is has valid values
                    </div>
                </div>
                <div>
                    <p class="mb-2 font-bold">This file should have been created for you already.</p>
                </div>
                <div class="flex flex-wrap">
                    <div class="w-full md:w-1/2">
                        <p class="mb-2">Your configuration file may have looked like this previously:</p>
                        <pre class="m-2 p-2 text-white rounded mb-2 text-base"><code>&lt;?php
define('cDBTYPE',      'mysql');
define('cDBHOSTNAME',  'localhost');
define('cDBUSERNAME',  'some_user');
define('cDBPASSWORD',  'some_pass');
define('cDBNAME',      'helpspot');
define('cDBCHARSET',   'utf8mb4');
define('cDBCOLLATION', 'utf8mb4_unicode_ci');

define('cHOST','https://support.example.org');
define('cBASEPATH',   dirname(__FILE__));
define('cDATADIR',    cBASEPATH.'/data');</code></pre>
                    </div>
                    <div class="w-full md:w-1/2">
                        <p class="mb-2">Your new configuration file should look similar to this:</p>
                        <pre class="m-2 p-2 text-white rounded mb-2 text-base"><code>APP_DEBUG=false
APP_URL=https://support.example.org
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=helpspot
DB_USERNAME=some_user
DB_PASSWORD=some_pass

QUEUE_CONNECTION=database</code></pre>
                    </div>
                </div>

                <div>
                    <p class="mb-2">If you need to generate a .env file from a config.php file, you can use the <code class="rounded p-1">hs config:convert</code> command.</p>
                    <pre class="m-2 p-2 text-white rounded mb-2 text-base"><code>cd "C:\Program Files (x86)\helpspot\helpspot"
..\php\php.exe -c ..\php\php.ini hs config:convert</code></pre>
                </div>

            </article>
        </div>
    </div>
    <div class="py-16">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">URL's</h2>
                    <p class="mb-2">URL's for HelpSpot have dropped the need for the <code class="text-white rounded p-1">.php</code> extension.</p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div>
                        ✅ <strong class="font-medium">Action Required:</strong> None
                    </div>
                </div>
                <div>
                    <p class="mb-2">For example, the URL <code class="text-white rounded p-1">/admin.php?pg=request</code> becomes <code class="text-white rounded p-1">/admin?pg=request</code>.</p>
                    <p class="mb-2">URL's with the <code class="text-white rounded p-1">.php</code> file extension will still work.</p>
                </div>
            </article>
        </div>
    </div>
    <div style="background: #F7F6F3" class="py-16 border-b border-gray-300">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Webroot</h2>
                    <p class="mb-2">
                        HelpSpot's web root used to be the base HelpSpot directory (e.g. <code class="text-white rounded p-1">C:\Program Files (x86)\helpspot\helpspot</code>.
                    </p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div class="pb-2">
                        🟡 <strong class="font-medium">Action Required:</strong> Check customizations to IIS/Apache to see if web root needs adjustments
                    </div>
                    <div>
                        👉 <strong class="font-medium">Likelihood of impact:</strong> Low, unless specific customizations were made to HelpSpot's IIS/Apache configuration.
                    </div>
                </div>
                <div>
                    <p class="mb-2">
                        For better security, HelpSpot 5 has a nested directory that should be used as the web server's root directory.
                        This is the <code class="text-white rounded p-1">public</code> directory (e.g. <code class="text-white rounded p-1">C:\Program Files (x86)\helpspot\helpspot\public</code>).
                    </p>
                    <p class="mb-2 font-bold">This configuration change should be done for you already.</p>
                </div>
            </article>
        </div>
    </div>
    <div class="py-16">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Secondary Portals</h2>
                    <p class="mb-2">
                        Your HelpSpot secondary portals likely need adjusting due to the change in the webroot as defined above.
                    </p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div class="pb-2">
                        🔴 <strong class="font-medium">Action Required:</strong> Some secondary portals may need to be moved/updated.
                    </div>
                    <div>
                        👉 <strong class="font-medium">Likelihood of impact:</strong> Medium: Secondary portals may need to be updated.
                    </div>
                </div>
                <div>
                    <p class="mb-2">
                        <span class="font-bold">The updater has attempted to update your portals for you</span> but may have not been able to update all of them.
                    </p>
                    <p class="mb-2">You can head to each active secondary portal and update the settings from there. The <code class="text-white rounded p-1">index.php</code> should get updated automatically.</p>
                    <h3 class="text-2xl font-bold mb-3 mt-4">Additional information:</h3>
                    <p class="mb-2">
                        Any portal that should be accessible as a sub-directory of your HelpSpot site (e.g. <code class="text-white rounded p-1">support.example.org/helpspot/my-secondary-portal</code>) should be moved to the new <code class="text-white rounded p-1">public</code> directory.
                    </p>
                    <p class="mb-2">All secondary portals have a simpler <code class="text-white rounded p-1">index.php</code> file that wil look something like the following.</p>
                    <p class="mb-2 text-sm">Note that the portal ID, URL, and required file path will need adjusting per instance.</p>
                    <pre class="m-2 p-2 text-white rounded mb-2 text-base"><code class="text-white rounded p-1">&lt?php
define('cMULTIPORTAL', 1);
define('cHOST', 'https://support.example.org/helpspot/example');

require_once('C:\Program Files (x86)\helpspot\helpspot\public\index.php');
</code></pre>
                </div>
            </article>
        </div>
    </div>
    <div style="background: #F7F6F3" class="py-16 border-b border-gray-300">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Attachment Storage Location</h2>
                    <p class="mb-2">
                        Attachments in HelpSpot 4, if saved to disk, defaulted to the <code class="text-white rounded p-1">data/documents</code> location.
                    </p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div>
                        ✅ <strong class="font-medium">Action Required:</strong> None
                    </div>
                </div>
                <div>
                    <p class="mb-2">
                        This has changed to a new default location <code class="text-white rounded p-1">storage/documents</code>. However your HelpSpot installation may still be using the older location.
                    </p>
                </div>
            </article>
        </div>
    </div>
    <div class="py-16">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Automation and Emails</h2>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div>
                        ✅ <strong class="font-medium">Action Required:</strong> None
                    </div>
                    <div>
                        👉 <strong class="font-medium">Recommendation:</strong> Check that incoming and outgoing emails are functioning
                    </div>
                </div>
                <div>
                    <p class="mb-4">
                        <span class="font-bold">Email Queue</span><br>
                        To help improve speed and reduce errors, HelpSpot now makes use of background queues for sending and receiving emails.
                    </p>
                    <p class="mb-4">
                        <span class="font-bold">Email Templates</span><br>
                        Email templates have changed to provide additional features. Templates tags in the form of <code class="text-white rounded p-1">##subject##</code> have changed to format <code class="text-white rounded p-1"><?php echo '{{ subject }} '; ?></code>.
                        <strong>This update has been made for you already.</strong>
                    </p>
                    <p class="mb-4">
                        <span class="font-bold">Scheduled Tasks</span><br>
                        HelpSpot's Scheduled Tasks have changed as well.
                        Previously HelpSpot ran a <code class="text-white rounded p-1">tasks.bat</code> and <code class="text-white rounded p-1">tasks2.bat</code> file every 1 minute or more. HelpSpot now runs a task a single task every 1 minute, which in turn runs other tasks at a set schedule per task.
                    </p>
                    <p class="mb-2">
                        Mailboxes are checked every 1 minute for new messages. See the documentation to see how to customize how often mailboxes are checked for new email.
                    </p>
                </div>
            </article>
        </div>
    </div>
    <div style="background: #F7F6F3" class="py-16 border-b border-gray-300">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Forums</h2>
                    <p class="mb-2">
                        Forums, depreciated in HelpSpot 4, have now been removed. Any forum data in the database has <strong>not</strong> been deleted.
                    </p>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div class="pb-2">
                        🟡 <strong class="font-medium">Action Required:</strong> If forums were used, contact support about the ability to export the data
                    </div>
                    <div>
                        👉 <strong class="font-medium">Likelihood of impact:</strong> None unless forums were used
                    </div>
                </div>
            </article>
        </div>
    </div>
    <div class="py-16">
        <div class="max-w-5xl mx-auto">
            <article class="px-4">
                <div>
                    <h2 class="text-3xl font-bold mb-3 mt-4">Security</h2>
                </div>
                <div class="border-t border-b border-gray-300 p-3 my-4" style="background: rgba(100,100,100,.05)">
                    <div>
                        ✅ <strong class="font-medium">Action Required:</strong> None
                    </div>
                </div>
                <div>
                    <p class="mb-2">
                        <span class="font-bold">Single Sign On (SSO)</span><br>
                        SSO is now supported in HelpSpot 5. See the documentation for setup.
                    </p>

                    <p class="mb-2">
                        <span class="font-bold">2 Factor Authentication (2FA)</span><br>
                        2FA will be available in coming releases of HelpSpot 5.
                    </p>
                </div>
            </article>
        </div>
    </div>
</div>
@endsection
