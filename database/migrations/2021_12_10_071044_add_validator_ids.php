<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidatorIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        

           
        Schema::table('scan_forms', function(Blueprint $table)
        {
            $table->string('barcode', 100)->after('url')->index();
            $table->boolean('verified')->after('barcode');
            $table->string('verification_service', 100)->nullable()->after('verified')->index();
            $table->string('verification_id')->nullable()->after('verification_service'); 
        });
        
        Schema::table('pickups', function(Blueprint $table)
        {   
            $table->boolean('verified')->after('date');
            $table->string('verification_service', 100)->nullable()->after('verified')->index();
            $table->string('verification_id')->nullable()->after('verification_service'); 
        });

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
