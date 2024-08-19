<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FromAddressOverride extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        
        Schema::table('shipments', function (Blueprint $table) {
            //
            $table->boolean('from_address_override')->after('from_address_id')->default(0)->index();
        });
        
        Schema::table('labels', function (Blueprint $table) {
            //
            $table->boolean('from_address_override')->after('from_address_id')->default(0)->index();
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
