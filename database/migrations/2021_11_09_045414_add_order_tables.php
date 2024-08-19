<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('order_groups', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('user');
            $table->uuid('address_id')->nullable();
            $table->string('email')->default('')->nullable();
            $table->string('name', 100)->default('')->nullable()->index('name');
            $table->string('company')->default('')->nullable();
            $table->string('phone')->default('')->nullable();
            $table->decimal('charged', 8, 3)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->uuid('parcel_id')->nullable();
            $table->uuid('suggested_parcel_id')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('user');
            $table->uuid('order_group_id')->index('order_group');
            $table->string('reference', 100)->index('reference')->nullable();
            $table->decimal('charged', 8, 3)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('store', 255)->nullable();
            $table->string('store_id', 255)->nullable();
            $table->timestamps();
        });
        
        Schema::create('order_products', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('user');
            $table->uuid('order_id')->index('order');
            $table->string('name', 100)->index('name');
            $table->string('sku', 100)->index('sku')->nullable();
            $table->float('quantity', 8, 2);
            $table->decimal('charged', 8, 3)->nullable();
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('store_id', 255)->nullable();
            $table->timestamps();
        });

        Schema::table('labels', function(Blueprint $table)
        {
            $table->uuid('order_group_id')->nullable()->after('shipment_id')->index('order_group');
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
