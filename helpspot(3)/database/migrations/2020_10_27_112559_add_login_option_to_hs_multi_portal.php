<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLoginOptionToHsMultiPortal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Multi_Portal', function (Blueprint $table) {
            $table->unsignedTinyInteger('fRequireAuth')->default(0);
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
            $table->dropColumn('fRequireAuth');
        });
    }
}
