<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancellationsAndReturns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('label_used_returns', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('label_id')->index();
            $table->float('amount', 8, 2);
            $table->string('reference_id')->nullable();
            $table->timestamps();
        });
        
        Schema::create('label_used_cancellations', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('label_id')->index();
            $table->float('amount', 8, 2);
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
