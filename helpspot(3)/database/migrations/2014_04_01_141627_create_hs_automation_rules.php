<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsAutomationRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Automation_Rules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xAutoRule');
            $table->string('sRuleName', 255)->default('');
            $table->integer('fOrder')->default(0);
            $table->tinyInteger('fDeleted')->default(0);
            $table->longText('tRuleDef');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Automation_Rules');
    }
}
