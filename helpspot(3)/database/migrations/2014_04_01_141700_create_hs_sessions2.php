<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsSessions2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Sessions2', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('sesskey', 128)->default('');
            $table->dateTime('expiry');
            $table->string('expireref', 250)->default('')->nullable();
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->longText('sessdata')->nullable();

            $table->index([0 => 'expiry']);
            $table->index([0 => 'expireref'], null, 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Sessions2');
    }
}
