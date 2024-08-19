<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixUserIdOnApiCallbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        DB::update("ALTER TABLE `api_callbacks` CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`;");
        DB::update("ALTER TABLE `api_callback_instances` CHANGE COLUMN `user_id` `user_id` CHAR(36) NOT NULL DEFAULT '' AFTER `id`;");
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
