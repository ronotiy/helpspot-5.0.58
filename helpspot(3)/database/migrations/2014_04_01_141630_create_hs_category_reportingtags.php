<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsCategoryReportingtags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Category_ReportingTags', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xReportingTag');
            $table->integer('xCategory')->default(0);
            $table->string('sReportingTag', 255)->default('');
            $table->integer('iOrder')->default(0);

            $table->index([0 => 'xCategory']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Category_ReportingTags');
    }
}
