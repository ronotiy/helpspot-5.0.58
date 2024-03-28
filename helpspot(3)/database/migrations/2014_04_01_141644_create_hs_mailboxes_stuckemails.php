<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsMailboxesStuckemails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Mailboxes_StuckEmails', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xStuckEmail');
            $table->integer('xMailbox')->default(0);
            $table->string('sEmailMessageId', 255)->default('');
            $table->string('sEmailFrom', 255)->default('');
            $table->string('sEmailDate', 255)->default('');

            $table->index([0 => 'xMailbox']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Mailboxes_StuckEmails');
    }
}
