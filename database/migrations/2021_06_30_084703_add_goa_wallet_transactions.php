<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoaWalletTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('wallet_transactions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->decimal('amount', 8, 2)->index();
            $table->decimal('balance', 8, 2)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 8, 2)->after('password')->index(); 
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
