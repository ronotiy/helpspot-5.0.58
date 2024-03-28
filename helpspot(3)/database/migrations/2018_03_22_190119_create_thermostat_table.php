<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateThermostatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Thermostat', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xThermostat');
            $table->unsignedInteger('xSurvey');
            $table->unsignedInteger('xResponse');
            $table->unsignedInteger('xRequest');
            $table->tinyInteger('iScore')->unsigned();
            $table->text('tFeedback')->nullable(); // filter to see column in grid view
            $table->timestamps();

            $table->unique(['xSurvey', 'xRequest']);
            $table->index('xSurvey');
            $table->index('iScore');
            $table->index('xRequest');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Thermostat');
    }
}
