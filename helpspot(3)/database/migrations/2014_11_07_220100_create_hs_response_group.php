<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsResponseGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Response_Group', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xResponse')->default(0);
            $table->integer('xGroup')->default(0);

            $table->index([0 => 'xResponse']);
            $table->index([0 => 'xGroup']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Response_Group');
    }
}
