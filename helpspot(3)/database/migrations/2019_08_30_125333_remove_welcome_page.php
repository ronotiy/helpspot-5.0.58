<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveWelcomePage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') == 'sqlsrv') {
            \HS\Database\SqlSrv::drop('HS_Person', 'fShowWelcome');
        } else {
            Schema::table('HS_Person', function (Blueprint $table) {
                $table->dropColumn('fShowWelcome');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
