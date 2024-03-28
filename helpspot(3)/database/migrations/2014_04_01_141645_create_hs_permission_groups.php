<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsPermissionGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Permission_Groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xGroup');
            $table->string('sGroup', 255)->default('');
            $table->tinyInteger('fModuleReports')->default(0);
            $table->tinyInteger('fModuleKbPriv')->default(0);
            $table->tinyInteger('fModuleForumsPriv')->default(0);
            $table->tinyInteger('fViewInbox')->default(0);
            $table->tinyInteger('fCanBatchRespond')->default(0);
            $table->tinyInteger('fCanMerge')->default(0);
            $table->tinyInteger('fCanViewOwnReqsOnly')->default(0);
            $table->tinyInteger('fLimitedToAssignedCats')->default(0);
            $table->tinyInteger('fCanAdvancedSearch')->default(0);
            $table->tinyInteger('fCanManageSpam')->default(0);
            $table->tinyInteger('fCanManageTrash')->default(0);
            $table->tinyInteger('fCanManageKB')->default(0);
            $table->tinyInteger('fCanManageForum')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Permission_Groups');
    }
}
