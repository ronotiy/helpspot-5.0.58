<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPersonStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Person_Status', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xPersonStatus')->default(0); // Does not auto-increment
            $table->integer('xRequest')->default(0);
            $table->integer('dtGMTEntered')->default(0);
            $table->string('sPage', 255)->default('');
            $table->string('sDetails', 255)->default('');

            $table->unique('xPersonStatus');
            $table->index([0 => 'xRequest']);
            $table->index([0 => 'sPage'], null, 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Person_Status');
    }
}
