<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Subscriptions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xSubscriptions');
            $table->integer('xPerson')->default(0);
            $table->integer('xRequest')->default(0);

            $table->index([0 => 'xPerson']);
            $table->index([0 => 'xRequest']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Subscriptions');
    }
}
