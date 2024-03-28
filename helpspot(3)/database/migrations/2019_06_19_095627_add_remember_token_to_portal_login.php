<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRememberTokenToPortalLogin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Portal_Login', function (Blueprint $table) {
            $table->string('sRememberToken', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Portal_Login', function (Blueprint $table) {
            $table->dropColumn('sRememberToken');
        });
    }
}
