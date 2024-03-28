<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsAddressBook extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Address_Book', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xContact');
            $table->string('sFirstName', 255)->default('');
            $table->string('sLastName', 255)->default('');
            $table->string('sEmail', 255)->default('');
            $table->string('sTitle', 255)->default('');
            $table->string('sDescription', 255)->default('');
            $table->integer('fHighlight')->default(0);

            $table->index([0 => 'sFirstName'], null, 10);
            $table->index([0 => 'sLastName'], null, 10);
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
        Schema::drop('HS_Address_Book');
    }
}
