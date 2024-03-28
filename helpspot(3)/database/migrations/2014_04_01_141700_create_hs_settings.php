<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('sSetting', 50)->default('');
            $table->longText('tValue')->nullable();

            $table->unique('sSetting');
            $table->index([0 => 'sSetting'], null, 20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Settings');
    }
}
