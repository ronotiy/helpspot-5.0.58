<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Documents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xDocumentId');
            $table->string('sFilename', 255)->default('');
            $table->string('sFileMimeType', 255)->default('');
            $table->string('sCID', 255)->default('');
            $table->longBinary('blobFile')->nullable();
            $table->string('sFileLocation', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Documents');
    }
}
