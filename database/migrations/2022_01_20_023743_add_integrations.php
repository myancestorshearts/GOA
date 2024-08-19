<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntegrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('integrations', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('name', 100)->index();
            $table->string('store_unique_key', 100)->index();
            $table->string('store', 100)->index();
            $table->string('status');
            $table->datetime('refreshed_at')->nullable()->index();
            $table->boolean('active')->index();
            $table->timestamps();
        });

        Schema::table('integration_failed_orders', function (Blueprint $table) {
            $table->uuid('integration_id')->after('user_id')->index();
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
