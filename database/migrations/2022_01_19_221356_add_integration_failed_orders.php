<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntegrationFailedOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        Schema::create('integration_failed_orders', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->string('reference', 100)->index();
            $table->string('error_message', 1024);
            $table->boolean('resolved')->index();
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
