<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiKeyToScanFormsAndPickups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
           
        Schema::table('scan_forms', function(Blueprint $table)
        {
            $table->uuid('user_id')->after('id')->index();
            $table->uuid('api_key_id')->after('user_id')->index();
        });
        
        Schema::table('pickups', function(Blueprint $table)
        {   
            $table->uuid('user_id')->after('id')->index();
            $table->uuid('api_key_id')->after('user_id')->index();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scan_forms_and_pickups', function (Blueprint $table) {
            //
        });
    }
}
