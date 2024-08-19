<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('labels', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('api_key_id')->index();

            $table->integer('shipment_id')->index();
            $table->string('url');

            $table->boolean('verified');
            $table->string('verification_service', 100)->index()->nullable();
            $table->string('verification_id')->nullable(); 
            $table->timestamps();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->integer('label_id')->after('parcel_id')->nullable();
        });
        
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->integer('label_id')->after('payment_method_id')->nullable()->index();
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
