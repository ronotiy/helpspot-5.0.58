<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsTagsMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Tags_Map', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xTagMap');
            $table->integer('xTag')->default(0);
            $table->integer('xPage')->default(0);
            $table->integer('xTopicId')->default(0);

            $table->index([0 => 'xTag']);
            $table->index([0 => 'xPage']);
            $table->index([0 => 'xTopicId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Tags_Map');
    }
}
