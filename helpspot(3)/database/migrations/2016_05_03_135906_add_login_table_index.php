 <?php

 use Carbon\Carbon;
 use Illuminate\Database\Schema\Blueprint;
 use Illuminate\Database\Migrations\Migration;

 class AddLoginTableIndex extends Migration
 {
     /**
      * Run the migrations.
      *
      * @return void
      */
     public function up()
     {
         Schema::table('HS_Login_Attempts', function (Blueprint $table) {
             // Give table sesskey index
             $table->index(['dtDateAdded']);
         });
     }

     /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::table('HS_Login_Attempts', function (Blueprint $table) {
             //
         });
     }
 }
