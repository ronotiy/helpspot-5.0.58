<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPortalBayesianCorpus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Portal_Bayesian_Corpus', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('sWord', 100)->default('');
            $table->integer('xCategory')->default(0);
            $table->integer('iCount')->default(0);

            $table->unique(['sWord', 'xCategory']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Portal_Bayesian_Corpus');
    }
}
