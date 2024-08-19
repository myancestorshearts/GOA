<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('shipments', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('api_key_id')->index();
            $table->string('reference')->default('');
            $table->integer('from_address_id')->index();
            $table->integer('to_address_id')->index();
            $table->integer('parcel_id')->index();
            $table->timestamps();
        });

        
        Schema::create('rates', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->integer('api_key_id')->index();

            $table->integer('shipment_id')->index();
            $table->string('carrier');
            $table->string('service');
            $table->decimal('rate', 8, 2);
            $table->decimal('rate_retail', 8, 2);
            $table->decimal('rate_list', 8, 2);
            $table->integer('delivery_days')->nullable();
            $table->datetime('delivery_date')->nullable();
            $table->boolean('delivery_guarentee');

            $table->boolean('verified');
            $table->string('verification_service', 100)->index()->nullable();
            $table->string('verification_id')->nullable(); 
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
