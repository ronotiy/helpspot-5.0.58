<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsForums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Forums', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xForumId');
            $table->string('sForumName', 255)->default('');
            $table->tinyInteger('fPrivate')->default(0);
            $table->string('sDescription', 255)->default('');
            $table->longText('tModerators');
            $table->tinyInteger('fClosed')->default(0);
            $table->integer('iOrder')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Forums');
    }
}
