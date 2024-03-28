<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsFilterPeople extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Filter_People', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xFilter')->default(0);
            $table->integer('xPerson')->default(0);

            $table->index([0 => 'xFilter']);
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
        Schema::drop('HS_Filter_People');
    }
}
