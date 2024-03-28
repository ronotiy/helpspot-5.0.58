<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAutomationRunsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Automation_Runs', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            $table->integer('xRequest')->unsigned();
            $table->integer('xAutomationId')->unsigned();
            $table->integer('iRunCount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Automation_Runs');
    }

}
