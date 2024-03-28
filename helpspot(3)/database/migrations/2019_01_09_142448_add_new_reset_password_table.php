<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewResetPasswordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop the original HS_Reset_Password table
        Schema::dropIfExists('HS_Reset_Password');
        Schema::create('HS_Reset_Password', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop our new password reset table, and re-create the old HS one
        Schema::dropIfExists('HS_Reset_Password');
        Schema::create('HS_Reset_Password', function (Blueprint $table) {
            $table->increments('xReset');
            $table->integer('dtCreatedOn')->default(0);
            $table->integer('xPerson')->default(0);
            $table->integer('xLogin')->default(0);
            $table->string('sToken', 60)->default('');
        });
    }
}
