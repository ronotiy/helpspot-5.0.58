<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSRequestPasswordToMergeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Request_Merged', function (Blueprint $table) {
            $table->string('sRequestPassword', 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Request_Merged', function (Blueprint $table) {
            $table->dropColumn('sRequestPassword');
        });
    }
}
