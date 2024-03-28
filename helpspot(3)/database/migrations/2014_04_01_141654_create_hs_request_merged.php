<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequestMerged extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_Merged', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xMergedRequest')->default(0);
            $table->integer('xRequest')->default(0);

            $table->unique('xMergedRequest');
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
        Schema::drop('HS_Request_Merged');
    }
}
