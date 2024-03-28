<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsKbChapters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_KB_Chapters', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xChapter');
            $table->integer('xBook')->default(0);
            $table->string('sChapterName', 255)->default('');
            $table->integer('iOrder')->default(0);
            $table->tinyInteger('fAppendix')->default(0);
            $table->tinyInteger('fHidden')->default(0);

            $table->index([0 => 'xBook']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_KB_Chapters');
    }
}
