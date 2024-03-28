<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPersonPhotos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Person_Photos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPersonPhotoId');
            $table->integer('xPerson')->default(0);
            $table->string('sDescription', 255)->default('');
            $table->string('sFilename', 255)->default('');
            $table->string('sFileMimeType', 50)->default('');
            $table->string('sSeries', 20)->default('');
            $table->longBinary('blobPhoto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Person_Photos');
    }
}
