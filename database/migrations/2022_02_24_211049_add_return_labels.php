<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReturnLabels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('return_labels', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('api_key_id')->index();

            $table->uuid('label_id')->index();
            $table->uuid('customer_address_id')->index();
            $table->uuid('return_address_id')->index();
            $table->string('reference');
            $table->string('service');
            $table->decimal('weight', 8, 3);
            $table->string('url');
            $table->boolean('used')->index()->default(0);

            $table->string('external_user_id')->index()->nullable()->default('null');

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
