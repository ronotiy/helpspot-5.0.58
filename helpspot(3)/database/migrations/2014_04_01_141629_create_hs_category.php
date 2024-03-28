<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Category', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xCategory');
            $table->string('sCategory', 200)->default('');
            $table->string('sCategoryGroup', 255)->default('');
            $table->tinyInteger('fDeleted')->default(0);
            $table->tinyInteger('fAllowPublicSubmit')->default(1);
            $table->integer('xPersonDefault')->default(0);
            $table->tinyInteger('fAutoAssignTo')->default(0);
            $table->longText('sPersonList');
            $table->longText('sCustomFieldList');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Category');
    }
}
