<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsTimeTracker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Time_Tracker', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xTimeId');
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('iSeconds')->default(0);
            $table->tinyInteger('fBillable')->default(0);
            $table->integer('dtGMTDate')->default(0);
            $table->integer('dtGMTDateAdded')->default(0);
            $table->longText('tDescription');

            $table->index([0 => 'xRequest']);
            $table->index([0 => 'xPerson']);
            $table->index([0 => 'dtGMTDate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Time_Tracker');
    }
}
