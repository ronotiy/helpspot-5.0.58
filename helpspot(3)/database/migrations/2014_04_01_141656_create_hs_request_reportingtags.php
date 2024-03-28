<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsRequestReportingtags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Request_ReportingTags', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('xRequest')->default(0);
            $table->integer('xReportingTag')->default(0);

            $table->index([0 => 'xRequest']);
            $table->index([0 => 'xReportingTag']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Request_ReportingTags');
    }
}
