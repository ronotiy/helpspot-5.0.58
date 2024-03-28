<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsAssignmentChain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Assignment_Chain', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xAssignmentChainId');
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('xPreviousPerson')->default(0);
            $table->integer('xChangedByPerson')->default(0);
            $table->integer('dtChange')->default(0);
            $table->string('sLogItem', 255)->default('');

            $table->index([0 => 'xRequest']);
            $table->index([0 => 'xPerson',  1 => 'xRequest']);
            $table->index([0 => 'xPreviousPerson',  1 => 'xPerson',  2 => 'xRequest']);
            $table->index([0 => 'dtChange']);
            $table->index([0 => 'xChangedByPerson']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Assignment_Chain');
    }
}
