<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsStatsResponses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Stats_Responses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xEvent');
            $table->integer('xResponse')->default(0);
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('dtGMTOccured')->default(0);

            $table->index([0 => 'xPerson']);
            $table->index([0 => 'xRequest']);
            $table->index([0 => 'xResponse']);
            $table->index([0 => 'dtGMTOccured']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Stats_Responses');
    }
}
