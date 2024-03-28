<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsMultiPortal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Multi_Portal', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPortal');
            $table->integer('xMailboxToSendFrom')->default(0);
            $table->integer('fDeleted')->default(0);
            $table->string('sHost', 255)->default('');
            $table->string('sPortalPath', 255)->default('');
            $table->string('sPortalName', 255)->default('');
            $table->string('sPortalPhone', 255)->default('');
            $table->longText('tPortalMsg');
            $table->longText('tDisplayKBs');
            $table->longText('tDisplayForums');
            $table->longText('tDisplayCategories');
            $table->longText('tDisplayCfs');
            $table->longText('tHistoryMailboxes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Multi_Portal');
    }
}
