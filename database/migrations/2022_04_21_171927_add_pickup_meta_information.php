<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickupMetaInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('pickups', function (Blueprint $table) {
            $table->integer('count_label_total')->after('carrier');
            $table->integer('count_label_individual')->after('count_label_total');
            $table->integer('count_scan_form')->after('count_label_individual');
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
