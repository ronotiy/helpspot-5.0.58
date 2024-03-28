<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGdprToMultiPortal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Multi_Portal', function (Blueprint $table) {
            $table->string('sPortalTerms', 255)->default('');
            $table->string('sPortalPrivacy', 255)->default('');
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
            $table->dropColumn('sPortalTerms');
            $table->dropColumn('sPortalPrivacy');
        });
    }
}
