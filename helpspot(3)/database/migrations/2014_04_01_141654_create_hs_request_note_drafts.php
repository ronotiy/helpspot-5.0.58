<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequestNoteDrafts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_Note_Drafts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xDraft');
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('dtGMTSaved')->default(0);
            $table->longText('tNote');

            $table->index([0 => 'xRequest',  1 => 'xPerson']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request_Note_Drafts');
    }
}
