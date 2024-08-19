<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConvertIdsToUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update("ALTER TABLE `addresses` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NOT NULL DEFAULT '' AFTER `user_id`;");
        DB::update("ALTER TABLE `api_keys` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`;");
        DB::update("ALTER TABLE `labels` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NOT NULL DEFAULT '' AFTER `user_id`, 
        CHANGE COLUMN `shipment_id` `shipment_id` CHAR(36) NOT NULL DEFAULT '' AFTER `api_key_id`, 
        CHANGE COLUMN `rate_id` `rate_id` CHAR(36) NULL DEFAULT '' AFTER `shipment_id`;");
        DB::update("ALTER TABLE `parcels` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NOT NULL DEFAULT '' AFTER `user_id`;");
        DB::update("ALTER TABLE `payment_methods` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`;");
        DB::update("ALTER TABLE `rates` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NOT NULL DEFAULT '' AFTER `user_id`, 
        CHANGE COLUMN `shipment_id` `shipment_id` CHAR(36) NOT NULL DEFAULT '' AFTER `api_key_id`;");
        DB::update("ALTER TABLE `shipments` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NOT NULL DEFAULT '' AFTER `user_id`, 
        CHANGE COLUMN `from_address_id` `from_address_id` CHAR(36) NOT NULL DEFAULT '' AFTER `reference`, 
        CHANGE COLUMN `to_address_id` `to_address_id` CHAR(36) NOT NULL DEFAULT '' AFTER `from_address_id`, 
        CHANGE COLUMN `parcel_id` `parcel_id` CHAR(36) NOT NULL DEFAULT '' AFTER `to_address_id`, 
        CHANGE COLUMN `label_id` `label_id` CHAR(36) NULL DEFAULT '' AFTER `parcel_id`;");
        DB::update("ALTER TABLE `users` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST;");
        DB::update("ALTER TABLE `wallet_transactions` CHANGE COLUMN `id` `id` CHAR(36) NOT NULL DEFAULT '' FIRST, 
        CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`, 
        CHANGE COLUMN `api_key_id` `api_key_id` CHAR(36) NULL DEFAULT '' AFTER `user_id`, 
        CHANGE COLUMN `payment_method_id` `payment_method_id` CHAR(36) NULL DEFAULT '' AFTER `type`, 
        CHANGE COLUMN `label_id` `label_id` CHAR(36) NULL DEFAULT '' AFTER `payment_method_id`;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uuid', function (Blueprint $table) {
            //
        });
    }
}
