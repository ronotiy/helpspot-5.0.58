<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddListItemColorsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_CustomFields', function (Blueprint $table) {
            $table->longText('listItemsColors')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_CustomFields', function (Blueprint $table) {
            $table->dropColumn('listItemsColors');
        });
    }
}
