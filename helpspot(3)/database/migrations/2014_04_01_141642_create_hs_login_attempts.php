<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsLoginAttempts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Login_Attempts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xAttempt');
            $table->string('sUsername', 255)->default('');
            $table->integer('dtDateAdded')->default(0);
            $table->tinyInteger('fValid')->default(0);

            // MySqlGrammar doesn't handle mixed index types well
            // so we'll skip it for this table
            //$table->index(array (  0 => 'sUsername',  1 => 'dtDateAdded',), null, 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Login_Attempts');
    }
}
