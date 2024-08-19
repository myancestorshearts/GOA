<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        
        Schema::create('payment_methods', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('type', 50)->index();
            $table->string('token');
            $table->string('name');
            $table->string('last_four');
            $table->string('expiration_month')->nullable();
            $table->string('expiration_year')->nullable();
            $table->string('zipcode');
            $table->boolean('auto_pay')->index();
            $table->decimal('threshold', 8, 2);
            $table->decimal('reload', 8, 2)->index();
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
