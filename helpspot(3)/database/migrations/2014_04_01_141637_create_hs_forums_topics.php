<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsForumsTopics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Forums_Topics', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xTopicId');
            $table->integer('xForumId')->default(0);
            $table->tinyInteger('fClosed')->default(0);
            $table->tinyInteger('fSticky')->default(0);
            $table->integer('dtGMTPosted')->default(0);
            $table->string('sName', 50)->default('');
            $table->string('sTopic', 200)->default('');

            $table->index([0 => 'xForumId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Forums_Topics');
    }
}
