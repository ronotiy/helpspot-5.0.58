<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSearchQueries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Search_Queries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            // TODO: Make sure this is populated with new search
            $table->increments('xSearch');
            $table->integer('dtGMTPerformed')->default(0);
            $table->string('sSearch', 255)->default('');
            $table->string('sFromPage', 255)->default('');
            $table->string('sSearchType', 255)->default('');
            $table->integer('iResultCount')->default(0);
            $table->integer('xPortal')->default(0);

            $table->index([0 => 'dtGMTPerformed']);
            $table->index([0 => 'iResultCount']);
            $table->index([0 => 'sFromPage'], null, 10);
            $table->index([0 => 'sSearch'], null, 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Search_Queries');
    }
}
