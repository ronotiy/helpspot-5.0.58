<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsBayesianMsgcounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Bayesian_MsgCounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xCategory')->default(0);
            $table->integer('iMsgCount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Bayesian_MsgCounts');
    }
}
