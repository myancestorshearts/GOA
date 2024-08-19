<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrackingAndCorrectionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        
        Schema::create('label_corrections', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('label_id')->index();
            $table->float('amount', 8, 2);
            $table->float('width', 8, 2);
            $table->float('height', 8, 2);
            $table->float('length', 8, 2);
            $table->float('weight', 8, 2);
            $table->string('service');
            $table->string('reference_id')->nullable();
            $table->timestamps();
        });
        
        Schema::create('label_trackings', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('label_id')->index();
            $table->string('status');
            $table->string('reference_id')->nullable();
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
