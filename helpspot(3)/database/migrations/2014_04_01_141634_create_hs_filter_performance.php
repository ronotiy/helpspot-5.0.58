<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsFilterPerformance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Filter_Performance', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xID');
            $table->integer('xFilter')->default(0);
            $table->decimal('dTime', 6, 4)->default(0);
            $table->integer('dtRunAt')->default(0);
            $table->string('sType', 100)->default('');

            $table->index([0 => 'xFilter',  1 => 'dtRunAt']);
            $table->index([0 => 'dtRunAt',  1 => 'xFilter']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Filter_Performance');
    }
}
