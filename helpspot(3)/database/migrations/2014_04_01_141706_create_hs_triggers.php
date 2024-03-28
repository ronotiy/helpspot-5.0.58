<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsTriggers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Triggers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xTrigger');
            $table->string('sTriggerName', 255)->default('');
            $table->integer('fOrder')->default(0);
            $table->tinyInteger('fDeleted')->default(0);
            $table->tinyInteger('fType')->default(0);
            $table->longText('tTriggerDef');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Triggers');
    }
}
