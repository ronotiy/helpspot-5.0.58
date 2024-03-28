<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsKbPages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_KB_Pages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPage');
            $table->integer('xChapter')->default(0);
            $table->string('sPageName', 255)->default('');
            $table->longText('tPage');
            $table->integer('xPersonCreator')->default(0);
            $table->integer('xPersonLastUpdate')->default(0);
            $table->integer('dtCreatedOn')->default(0);
            $table->integer('dtUpdatedOn')->default(0);
            $table->integer('iOrder')->default(0);
            $table->tinyInteger('fHidden')->default(0);
            $table->tinyInteger('fHighlight')->default(0);
            $table->integer('iHelpful')->default(0);
            $table->integer('iNotHelpful')->default(0);

            $table->index([0 => 'xChapter']);
            //$table->index(array (  0 => 'tPage',), 'IndextPage'); // Needs FULLTEXT index
            $table->index([0 => 'iHelpful']);
            $table->index([0 => 'iNotHelpful']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_KB_Pages');
    }
}
