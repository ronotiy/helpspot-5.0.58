<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSphCounter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * Tables acts a key:value pair
         * for HS_Requests and HS_Requests_History id storage
         * used so Sphinx delta indexes know where to begin
         * to index table data for the two delta indexes
         */
        Schema::create('HS_Sph_Counter', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('counter_key', 20);
            $table->integer('max_doc_id');

            $table->index([0 => 'counter_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Sph_Counter');
    }
}
