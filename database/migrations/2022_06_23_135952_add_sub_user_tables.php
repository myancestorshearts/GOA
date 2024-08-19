<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubUserTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('active')->after('first_time_login')->index()->default(1);
            $table->uuid('parent_user_id')->after('id')->index()->nullable();
        });
        
        Schema::table('shipments', function (Blueprint $table) {
            $table->uuid('created_user_id')->after('user_id')->index()->nullable();
        });
        
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->uuid('created_user_id')->after('user_id')->index()->nullable();
        });
        
        Schema::table('scan_forms', function (Blueprint $table) {
            $table->uuid('created_user_id')->after('user_id')->index()->nullable();
        });
        
        Schema::table('pickups', function (Blueprint $table) {
            $table->uuid('created_user_id')->after('user_id')->index()->nullable();
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
