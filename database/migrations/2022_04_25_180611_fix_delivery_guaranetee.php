<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixDeliveryGuaranetee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        
        Schema::table('rates', function (Blueprint $table) {
            $table->boolean('delivery_guarantee')->after('delivery_guarentee')->default(0);
        });
        Schema::table('labels', function (Blueprint $table) {
            $table->integer('delivery_days')->after('service')->nullable();
            $table->datetime('delivery_date')->after('delivery_days')->nullable();
            $table->boolean('delivery_guarantee')->after('delivery_date')->default(0);
        });
        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn('delivery_guarentee');
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
