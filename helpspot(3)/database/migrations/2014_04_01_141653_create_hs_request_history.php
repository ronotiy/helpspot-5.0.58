<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequestHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_History', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xRequestHistory');
            $table->integer('xRequest')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('dtGMTChange')->default(0);
            $table->integer('xDocumentId')->default(0);
            $table->tinyInteger('fPublic')->default(0);
            $table->tinyInteger('fInitial')->default(0);
            $table->integer('iTimerSeconds')->default(0);
            $table->tinyInteger('fNoteIsHTML')->default(0);
            $table->integer('fMergedFromRequest')->default(0);
            $table->string('sRequestHistoryHash', 32)->default('');
            $table->longText('tLog');
            $table->longText('tNote');
            $table->longText('tEmailHeaders');

            $table->index([0 => 'xRequest']);
            //$table->index(array (  0 => 'tNote',), 'IndextNote'); // Needs FULLTEXT index
            $table->index([0 => 'dtGMTChange']);
            $table->index([0 => 'xPerson']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request_History');
    }
}
