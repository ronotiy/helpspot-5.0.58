<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPerson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Person', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPerson');
            $table->string('sFname', 50)->default('');
            $table->string('sLname', 100)->default('');
            $table->string('sUsername', 100)->default('');
            $table->string('sEmail', 255)->default('');
            $table->string('sEmail2', 255)->default('');
            $table->string('sSMS', 50)->default('');
            $table->integer('xSMSService')->default(0);
            $table->string('sPasswordHash', 60)->default('');
            $table->string('sPhone', 32)->default('');
            $table->longText('tSignature');
            $table->longText('tSignature_HTML');
            $table->tinyInteger('fNotifyEmail')->default(1);
            $table->tinyInteger('fNotifyEmail2')->default(0);
            $table->tinyInteger('fNotifySMS')->default(0);
            $table->tinyInteger('fNotifySMSUrgent')->default(0);
            $table->integer('xPersonPhotoId')->default(0);
            $table->integer('fUserType')->default(2);
            $table->integer('xPersonOutOfOffice')->default(0);
            $table->tinyInteger('fNotifyNewRequest')->default(0);
            $table->tinyInteger('fKeyboardShortcuts')->default(0);
            $table->tinyInteger('fDefaultToPublic')->default(0);
            $table->tinyInteger('fHideWysiwyg')->default(0);
            $table->tinyInteger('fHideImages')->default(0);
            $table->tinyInteger('fReturnToReq')->default(0);
            $table->tinyInteger('fSidebarSearchFullText')->default(0);
            $table->integer('iRequestHistoryLimit')->default(10);
            $table->integer('fRequestHistoryView')->default(1);
            $table->integer('fShowWelcome')->default(0);
            $table->string('sHTMLEditor', 255)->default('');
            $table->tinyInteger('fDeleted')->default(0);
            $table->string('sWorkspaceDefault', 10)->default('myq');
            $table->longText('tWorkspace');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Person');
    }
}
