<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsTags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Tags', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xTag');
            $table->string('sTag', 255)->default(0);

            $table->index([0 => 'sTag'], null, 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Tags');
    }
}
