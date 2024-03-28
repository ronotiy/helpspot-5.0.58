<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsErrors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Errors', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xErrors');
            $table->integer('dtErrorDate')->default(0);
            $table->string('sType', 40)->default('');
            $table->string('sFile', 255)->default('');
            $table->string('sLine', 10)->default('');
            $table->string('sDesc', 255)->default('');

            $table->index([0 => 'dtErrorDate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Errors');
    }
}
