<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsLustatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_luStatus', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xStatus');
            $table->string('sStatus', 255)->default('');
            $table->tinyInteger('fDeleted')->default(0);
            $table->integer('fOrder')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_luStatus');
    }
}
