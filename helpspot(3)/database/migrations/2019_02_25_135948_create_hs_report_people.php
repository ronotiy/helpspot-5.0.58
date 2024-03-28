<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsReportPeople extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Report_People', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xReport')->default(0);
            $table->integer('xPerson')->default(0);

            $table->index(array (  0 => 'xReport',));
            $table->index(array (  0 => 'xPerson',));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('HS_Report_People');
    }
}
