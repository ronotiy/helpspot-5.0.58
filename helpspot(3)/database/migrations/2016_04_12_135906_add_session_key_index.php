 <?php

 use Carbon\Carbon;
 use Illuminate\Database\Schema\Blueprint;
 use Illuminate\Database\Migrations\Migration;

 class AddSessionKeyIndex extends Migration
 {
     /**
      * Run the migrations.
      *
      * @return void
      */
     public function up()
     {
         Schema::table('HS_Sessions2', function (Blueprint $table) {
             // Delete expired sessions
             \DB::table('HS_Sessions2')->where('expiry', '<=', Carbon::now())->delete();

             // Give table sesskey index
             $table->index(['sesskey']);
         });
     }

     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::table('HS_Sessions2', function (Blueprint $table) {
             //
         });
     }
 }
