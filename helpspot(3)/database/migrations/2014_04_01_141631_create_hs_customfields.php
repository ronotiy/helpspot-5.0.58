<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsCustomfields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_CustomFields', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xCustomField');
            $table->string('fieldName', 200)->default('');
            $table->tinyInteger('isRequired')->default(0);
            $table->tinyInteger('isPublic')->default(0);
            $table->tinyInteger('isAlwaysVisible')->default(0);
            $table->string('fieldType', 20)->default('');
            $table->integer('iOrder')->default(0);
            $table->integer('iDecimalPlaces')->default(2);
            $table->string('sTxtSize', 10)->default('');
            $table->string('sRegex', 255)->default('');
            $table->string('sAjaxUrl', 255)->default('');
            $table->string('lrgTextRows', 10)->default('');
            $table->longText('listItems');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_CustomFields');
    }
}
