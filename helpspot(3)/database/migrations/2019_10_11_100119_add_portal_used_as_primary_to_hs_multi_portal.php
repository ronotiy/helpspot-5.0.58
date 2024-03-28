<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPortalUsedAsPrimaryToHsMultiPortal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Multi_Portal', function (Blueprint $table) {
            $table->unsignedTinyInteger('fIsPrimaryPortal')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Multi_Portal', function (Blueprint $table) {
            $table->dropColumn('fIsPrimaryPortal');
        });
    }
}
