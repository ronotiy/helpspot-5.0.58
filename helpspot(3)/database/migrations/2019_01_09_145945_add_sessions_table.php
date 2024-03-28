<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete any existing session tables (old hs2 and hs3-4 tables)
        Schema::dropIfExists('HS_Sessions2');
        Schema::dropIfExists('HS_Sessions');
        Schema::create('HS_Sessions', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Delete our new sessions table
        Schema::dropIfExists('HS_Sessions');

        // Re-create helpspot's old sessions table
        Schema::create('HS_Sessions2', function (Blueprint $table) {
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
}
