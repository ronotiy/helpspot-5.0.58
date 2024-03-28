<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FCanTransferRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Permission_Groups', function (Blueprint $table) {
            $table->unsignedTinyInteger('fCanTransferRequests')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Permission_Groups', function (Blueprint $table) {
            $table->dropColumn('fCanTransferRequests');
        });
    }
}
