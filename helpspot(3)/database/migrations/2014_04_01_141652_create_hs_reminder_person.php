<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsReminderPerson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Reminder_Person', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xReminder')->default(0);
            $table->integer('xPerson')->default(0);

            $table->index([0 => 'xPerson']);
            $table->index([0 => 'xReminder']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Reminder_Person');
    }
}
