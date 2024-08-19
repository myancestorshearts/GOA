<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickupIdToLablesAndShipments extends Migration
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
            $table->uuid('pickup_id')->after('api_key_id')->nullable()->index();
        });
        Schema::table('labels', function(Blueprint $table)
        {
            $table->uuid('pickup_id')->after('scan_form_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lables_and_shipments', function (Blueprint $table) {
            //
        });
    }
}
