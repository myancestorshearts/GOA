<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('wallet_transactions', function (Blueprint $table) {
            //
            $table->uuid('label_correction_id')->after('label_id')->nullable()->index();
            $table->uuid('label_used_cancellation_id')->after('label_correction_id')->nullable()->index();
            $table->uuid('label_used_return_id')->after('label_used_cancellation_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
}
