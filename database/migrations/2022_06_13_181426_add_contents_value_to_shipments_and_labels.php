<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContentsValueToShipmentsAndLabels extends Migration
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
            $table->decimal('contents_value', 8, 2)->after('weight')->default(0);
        });
        
        Schema::table('labels', function (Blueprint $table) {
            //
            $table->decimal('contents_value', 8, 2)->after('weight')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipments_and_labels', function (Blueprint $table) {
            //
        });
    }
}
