<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPortalBayesianMsgcounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Portal_Bayesian_MsgCounts', function (Blueprint $table) {
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
        Schema::drop('HS_Portal_Bayesian_MsgCounts');
    }
}
