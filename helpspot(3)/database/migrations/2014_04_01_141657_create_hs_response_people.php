<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsResponsePeople extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Response_People', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xResponse')->default(0);
            $table->integer('xPerson')->default(0);

            $table->index([0 => 'xResponse']);
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
        Schema::drop('HS_Response_People');
    }
}
