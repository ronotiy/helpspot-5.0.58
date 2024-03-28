<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xRequest');
            $table->integer('fOpenedVia')->default(2);
            $table->integer('xOpenedViaId')->default(0);
            $table->integer('xPortal')->default(0);
            $table->integer('xMailboxToSendFrom')->default(0);
            $table->integer('xPersonOpenedBy')->default(0);
            $table->integer('xPersonAssignedTo')->default(0);
            $table->tinyInteger('fOpen')->default(1);
            $table->integer('xStatus')->default(1);
            $table->tinyInteger('fUrgent')->default(0);
            $table->integer('xCategory')->default(0);
            $table->integer('dtGMTOpened')->default(0);
            $table->integer('dtGMTClosed')->default(0);
            $table->integer('iLastReplyBy')->default(0);
            $table->integer('iLastReadCount')->default(0);
            $table->tinyInteger('fTrash')->default(0);
            $table->integer('dtGMTTrashed')->default(0);
            $table->string('sRequestPassword', 20)->default('');
            $table->string('sTitle', 255)->default('');
            $table->string('sUserId', 80)->default('');
            $table->string('sFirstName', 40)->default('');
            $table->string('sLastName', 40)->default('');
            $table->string('sEmail', 100)->default('');
            $table->string('sPhone', 40)->default('');
            $table->string('sRequestHash', 32)->default('');

            $table->index([0 => 'fOpen',  1 => 'xPersonAssignedTo']);
            $table->index([0 => 'xPersonAssignedTo',  1 => 'dtGMTOpened']);
            $table->index([0 => 'xCategory',  1 => 'dtGMTOpened']);
            $table->index([0 => 'xStatus',  1 => 'dtGMTOpened']);
            $table->index([0 => 'xPortal']);
            $table->index([0 => 'dtGMTOpened']);
            $table->index([0 => 'dtGMTClosed']);
            $table->index([0 => 'sUserId'], null, 10);
            $table->index([0 => 'sFirstName'], null, 10);
            $table->index([0 => 'sLastName'], null, 10);
            $table->index([0 => 'sEmail'], null, 20);
            $table->index([0 => 'fTrash']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request');
    }
}
