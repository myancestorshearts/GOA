<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalUserIdToScanForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
        Schema::table('scan_forms', function (Blueprint $table) {
            $table->string('external_user_id', 100)->after('from_address_id')->nullable()->default(null);
        });
        
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('external_user_id', 100)->after('from_address_id')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scan_forms', function (Blueprint $table) {
            //
        });
    }
}
