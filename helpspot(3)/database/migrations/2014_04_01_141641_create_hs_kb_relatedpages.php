<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsKbRelatedpages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_KB_RelatedPages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xPage')->default(0);
            $table->integer('xRelatedPage')->default(0);

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
        Schema::drop('HS_KB_RelatedPages');
    }
}
