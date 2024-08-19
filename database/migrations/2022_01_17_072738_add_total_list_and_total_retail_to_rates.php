<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalListAndTotalRetailToRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rates', function (Blueprint $table) {
            //
            $table->decimal('total_list', 8, 2)->after('total_charge');
            $table->decimal('total_retail', 8, 2)->after('total_list');
        });

        DB::update('UPDATE rates set total_list = (rate_list + rate_services)');
        DB::update('UPDATE rates set total_retail = (rate_retail + rate_services)');
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
