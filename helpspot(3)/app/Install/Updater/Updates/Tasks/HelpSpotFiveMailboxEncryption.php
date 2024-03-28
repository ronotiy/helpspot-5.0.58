<?php

namespace HS\Install\Updater\Updates\Tasks;

use HS\Mailbox;
use Illuminate\Support\Facades\DB;
use HS\Install\Updater\Updates\BaseUpdate;

class HelpSpotFiveMailboxEncryption extends BaseUpdate
{
    protected $version = '5.0.0';

    public function run()
    {
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE `HS_Mailboxes` CHANGE `sPassword` `sPassword` varchar(512) NOT NULL;');
        } elseif (config('database.default') === 'sqlsrv') {
            DB::statement('ALTER TABLE HS_Mailboxes ALTER COLUMN sPassword nvarchar(512) NOT NULL;');
        }

        // Convert mailbox passwords to laravel encryption
        Mailbox::get()->each(function(Mailbox $mailbox) {
            $decrypted = hs_decrypt($mailbox->sPassword);
            $mailbox->sPassword = encrypt($decrypted);
            $mailbox->save();
        });
    }
}
