<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsResetPassword extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Note that this is super-ceded by the later migration that drops this
        // table and re-creates it with the Laravel columns
        Schema::create('HS_Reset_Password', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xReset');
            $table->integer('dtCreatedOn')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('xLogin')->default(0);
            $table->string('sToken', 60)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Reset_Password');
    }
}
