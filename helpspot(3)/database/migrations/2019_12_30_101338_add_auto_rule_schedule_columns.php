<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutoRuleScheduleColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Automation_Rules', function (Blueprint $table) {
            $table->tinyInteger('fDirectOnly')->default(0); // Requires a task to grab each tRuleDef and populate this column from $def->option_direct_call_only
            $table->string('sSchedule')->default('every_minute');
            $table->timestamp('dtNextRun')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Automation_Rules', function (Blueprint $table) {
            $table->dropColumn(['fDirectOnly', 'sSchedule', 'dtNextRun']);
        });
    }
}
