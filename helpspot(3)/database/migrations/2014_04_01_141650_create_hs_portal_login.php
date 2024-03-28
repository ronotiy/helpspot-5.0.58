<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPortalLogin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Portal_Login', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xLogin');
            $table->string('sEmail', 100)->default('');
            $table->string('sPasswordHash', 60)->default('');

            $table->unique('sEmail');
            $table->index([0 => 'sEmail'], null, 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Portal_Login');
    }
}
