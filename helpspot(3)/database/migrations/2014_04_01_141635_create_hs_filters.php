<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHsFilters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HS_Filters', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('xFilter');
            $table->integer('xPerson')->default(0);
            $table->integer('fType')->default(0);
            $table->integer('fShowCount')->default(0);
            $table->integer('fCustomerFriendlyRSS')->default(0);
            $table->integer('dtCachedCountAt')->default(0);
            $table->integer('iCachedCount')->default(0);
            $table->integer('fCacheNever')->default(0);
            $table->integer('fDisplayTop')->default(0);
            $table->string('sShortcut', 10)->default('');
            $table->string('sFilterName', 255)->default('');
            $table->string('sFilterView', 100)->default('');
            $table->longText('tFilterDef');

            $table->index([0 => 'xPerson']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('HS_Filters');
    }
}
