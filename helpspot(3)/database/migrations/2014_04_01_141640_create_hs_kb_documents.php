<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsKbDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_KB_Documents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xDocumentId');
            $table->integer('xPage')->default(0);
            $table->tinyInteger('fDownload')->default(1);
            $table->string('sFilename', 255)->default('');
            $table->string('sFileMimeType', 40)->default('');
            $table->longBinary('blobFile')->nullable();

            $table->index([0 => 'xPage']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_KB_Documents');
    }
}
