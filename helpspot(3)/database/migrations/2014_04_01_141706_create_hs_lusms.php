<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsLusms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_luSMS', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xSMSService');
            $table->string('sName', 255)->default('');
            $table->string('sFromSize', 10)->default('');
            $table->string('sMsgSize', 10)->default('');
            $table->string('sTotalSize', 10)->default('');
            $table->string('sPrefixType', 40)->default('');
            $table->string('sAddress', 255)->default('');
            $table->integer('fTop')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_luSMS');
    }
}
