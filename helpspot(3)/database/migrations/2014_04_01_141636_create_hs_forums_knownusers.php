<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsForumsKnownusers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Forums_KnownUsers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xKnownUserId');
            $table->integer('xPerson')->default(0);
            $table->string('sName', 50)->default('');
            $table->string('sIP', 20)->default('');
            $table->string('sOS', 20)->default('');
            $table->string('sLabel', 50)->default('');

            $table->index([0 => 'sIP',  1 => 'sName',  2 => 'sOS']);
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
        Schema::drop('HS_Forums_KnownUsers');
    }
}
