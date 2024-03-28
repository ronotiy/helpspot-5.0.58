<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSavedReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Saved_Reports', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xReport');
            $table->integer('xPerson')->default(0);
            $table->string('sReport', 255)->default('');
            $table->string('sPage', 255)->default('');
            $table->string('sShow', 255)->default('');
            $table->longText('tData');

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
        Schema::drop('HS_Saved_Reports');
    }
}
