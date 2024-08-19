<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShipDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        DB::update("ALTER TABLE `scan_forms` CHANGE COLUMN `shipdate` `ship_date` DATE NOT NULL DEFAULT '2020-01-01' AFTER `from_address_id`");
        DB::update("ALTER TABLE `labels`
            CHANGE COLUMN `shipdate` `ship_date` DATE NOT NULL DEFAULT '2020-01-01' AFTER `return_address_id`,
            DROP INDEX `labels_shipdate_index`,
            ADD INDEX `labels_shipdate_index` (`ship_date`) USING BTREE");
        DB::update("ALTER TABLE `shipments`
            CHANGE COLUMN `shipdate` `ship_date` DATE NOT NULL DEFAULT '2020-01-01' AFTER `reference`");
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
