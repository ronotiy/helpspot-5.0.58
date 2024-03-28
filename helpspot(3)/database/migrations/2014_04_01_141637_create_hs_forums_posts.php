<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsForumsPosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Forums_Posts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xPostId');
            $table->integer('xTopicId')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('dtGMTPosted')->default(0);
            $table->tinyInteger('fSpam')->default(0);
            $table->integer('iSpamProbability')->default(0);
            $table->tinyInteger('fEmailUpdate')->default(0);
            $table->string('sName', 50)->default('');
            $table->string('sIP', 20)->default('');
            $table->string('sOS', 20)->default('');
            $table->string('sEmail', 255)->default('');
            $table->string('sURL', 255)->default('');
            $table->string('sBrowser', 100)->default('');
            $table->longText('tPost');

            $table->index([0 => 'xTopicId']);
            //$table->index(array (  0 => 'tPost',), 'IndextPost'); // Needs FULLTEXT index
            $table->index([0 => 'fSpam']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Forums_Posts');
    }
}
