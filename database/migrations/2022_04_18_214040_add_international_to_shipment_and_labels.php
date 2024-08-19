<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternationalToShipmentAndLabels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('shipments', function (Blueprint $table) {
            //
            $table->boolean('international')->default(0)->after('customs');
        });
        
        Schema::table('labels', function (Blueprint $table) {
            //
            $table->boolean('international')->default(0)->after('customs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipment_and_labels', function (Blueprint $table) {
            //
        });
    }
}
