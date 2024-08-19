<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiCallbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('api_callbacks', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->integer('user_id')->index();
            $table->string('type', 100)->index();
            $table->string('callback_url');
            $table->boolean('active')->index();
            $table->timestamps();
        });

        
        Schema::create('api_callback_instances', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->integer('user_id')->index();
            $table->uuid('api_callback_id')->index();
            $table->string('type')->index();
            $table->string('callback_url')->nullabel();
            $table->string('status')->index();
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
