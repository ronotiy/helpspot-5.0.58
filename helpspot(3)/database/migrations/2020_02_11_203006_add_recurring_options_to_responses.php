<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRecurringOptionsToResponses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Responses', function (Blueprint $table) {
            $table->tinyInteger('fRecurringRequest')->default(0);
            $table->string('fSendEvery', 255)->nullable();
            $table->string('fSendDay', 255)->nullable();
            $table->string('fSendTime', 255)->nullable();
            $table->dateTime('dtSendsAt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Responses', function (Blueprint $table) {
            //
        });
    }
}
