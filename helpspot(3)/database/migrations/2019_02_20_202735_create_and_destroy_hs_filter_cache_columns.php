<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAndDestroyHsFilterCacheColumns extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Filters', function (Blueprint $table) {
            $table->unsignedInteger('iCachedMinutes')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Filters', function (Blueprint $table) {
            //
        });
    }
}
