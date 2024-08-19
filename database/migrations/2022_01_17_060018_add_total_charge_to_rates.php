<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalChargeToRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::update("ALTER TABLE `rates` CHANGE COLUMN `rate_additional_services` `rate_services` DECIMAL(8,2) NOT NULL DEFAULT '0.00' AFTER `rate_list`");
        Schema::table('rates', function (Blueprint $table) {
            //
            $table->decimal('total_charge', 8, 2)->after('rate_services');
        });

        DB::update('UPDATE rates set total_charge = (rate_list + rate_services)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rates', function (Blueprint $table) {
            //
        });
    }
}
