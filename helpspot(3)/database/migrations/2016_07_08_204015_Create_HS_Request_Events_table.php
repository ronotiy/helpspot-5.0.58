<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHSRequestEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_Events', function (Blueprint $table) {
            $table->increments('xEvent');
            $table->integer('xRequestHistory')->unsigned();
            $table->integer('xRequest')->unsigned();
            $table->integer('xPerson');
            $table->string('sColumn');
            $table->integer('dtLogged');
            $table->integer('iSecondsInState')->default(0);
            $table->integer('iValue')->nullable(); // Integer
            $table->string('sValue')->nullable();  // String
            $table->string('dValue')->nullable();  // Decimal
            $table->string('sLabel');  // Human-readable value
            $table->string('sDescription')->nullable(); // Optional human-readable description of event

            // Regular Usage Indexes
            $table->index('xRequestHistory');
            // $table->index(['xRequestHistory', 'dtLogged']);

            // Reporting Indexes
            $table->index('xRequest');
            $table->index('xPerson');
            // $table->index('iValue');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request_Events');
    }
}
