<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiKeysTypeProcessingFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        DB::update('ALTER TABLE `wallet_transactions` CHANGE COLUMN `balance` `balance` DECIMAL(8,2) NULL AFTER `amount`;');
        
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->integer('api_key_id')->after('user_id')->nullable()->index(); 
            $table->string('type', 100)->after('api_key_id')->nullable()->index(); 
            $table->string('payment_method_id', 100)->after('type')->nullable()->index(); 
            $table->boolean('failed')->after('balance')->index();
            $table->string('failed_message')->after('failed');
        });

        
        Schema::create('api_keys', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('name', 100)->index();
            $table->string('key', 100)->index();
            $table->string('password');
            $table->boolean('active')->index();
            $table->timestamps();
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
