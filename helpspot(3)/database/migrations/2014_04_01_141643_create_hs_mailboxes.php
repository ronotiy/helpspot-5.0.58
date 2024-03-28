<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsMailboxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Mailboxes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xMailbox');
            $table->string('sMailbox', 100)->default('');
            $table->string('sHostname', 100)->default('');
            $table->string('sUsername', 100)->default('');
            $table->string('sPassword', 512)->default('');
            $table->string('sPort', 10)->default('');
            $table->string('sType', 5)->default('');
            $table->string('sSecurity', 20)->default('');
            $table->integer('xCategory')->default(0);
            $table->tinyInteger('fDeleted')->default(0);
            $table->tinyInteger('fAutoResponse')->default(0);
            $table->string('sReplyName', 255)->default('');
            $table->string('sReplyEmail', 255)->default('');
            $table->string('sLastImportMessageId', 255)->default('');
            $table->string('sLastImportFrom', 255)->default('');
            $table->integer('iLastImportAttemptCt')->default(0);
            $table->longText('tAutoResponse');
            $table->longText('tAutoResponse_html');
            $table->longText('sSMTPSettings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Mailboxes');
    }
}
