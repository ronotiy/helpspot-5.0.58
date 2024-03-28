<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequestPushed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_Pushed', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPushed');
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('dtGMTPushed')->default(0);
            $table->string('sPushedTo', 255)->default('');
            $table->string('sReturnedID', 255)->default('');
            $table->longText('tComment');

            $table->index([0 => 'xRequest']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request_Pushed');
    }
}
