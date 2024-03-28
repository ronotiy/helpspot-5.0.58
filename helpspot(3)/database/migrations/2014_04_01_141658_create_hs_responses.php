<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsResponses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Responses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xResponse');
            $table->string('sResponseTitle', 255)->default('');
            $table->string('sFolder', 255)->default('');
            $table->longText('tResponse');
            $table->longText('tResponseOptions');
            $table->integer('xPerson')->default(0);
            $table->integer('fType')->default(0);
            $table->tinyInteger('fDeleted')->default(0);

            $table->index([0 => 'xPerson']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Responses');
    }
}
