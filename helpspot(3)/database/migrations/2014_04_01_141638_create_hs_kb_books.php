<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsKbBooks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_KB_Books', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xBook');
            $table->string('sBookName', 255)->default('');
            $table->integer('iOrder')->default(0);
            $table->tinyInteger('fPrivate')->default(0);
            $table->longText('tDescription');
            $table->longText('tEditors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_KB_Books');
    }
}
