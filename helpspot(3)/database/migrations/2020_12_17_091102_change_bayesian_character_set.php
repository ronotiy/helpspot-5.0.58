<?php

use Illuminate\Database\Migrations\Migration;

class ChangeBayesianCharacterSet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') == 'mysql') {
            $sql = "ALTER TABLE `HS_Bayesian_Corpus` CHANGE `sWord` `sWord` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_bin  NOT NULL  DEFAULT '';";
            DB::statement($sql);
            $sql = "ALTER TABLE `HS_Portal_Bayesian_Corpus` CHANGE `sWord` `sWord` VARCHAR(100)  CHARACTER SET utf8mb4  COLLATE utf8mb4_bin  NOT NULL  DEFAULT '';";
            DB::statement($sql);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
