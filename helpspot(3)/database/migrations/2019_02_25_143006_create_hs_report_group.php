<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsReportGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Report_Group', function (Blueprint $table) {
            $table->engine = 'InnoDB';
			$table->integer('xReport')->default(0);
			$table->integer('xGroup')->default(0);

			$table->index(array (  0 => 'xReport',));
			$table->index(array (  0 => 'xGroup',));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('HS_Report_Group');
    }
}
