<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchaseRateIdAndTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //      
        Schema::table('labels', function (Blueprint $table) {
            $table->integer('rate_id')->after('shipment_id')->nullable();
            $table->string('tracking')->after('rate_id')->nullable();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->after('amount')->nullable()->index();
            $table->decimal('profit', 8, 2)->after('cost')->nullable()->index();
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
