<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsReminder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Reminder', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xReminder');
            $table->integer('xRequest')->default(0);
            $table->integer('xPersonCreator')->default(0);
            $table->integer('dtGMTReminder')->default(0);
            $table->longText('tReminder');

            $table->index([0 => 'xRequest']);
            $table->index([0 => 'dtGMTReminder']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Reminder');
    }
}
