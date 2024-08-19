<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickupsAndScanForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() 
    {
        //
        
        Schema::create('scan_forms', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('from_address_id')->index();
            $table->string('carrier', 100)->index();
            $table->integer('label_count');
            $table->string('url');
            $table->timestamps();
        });
        
        Schema::create('pickups', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('from_address_id')->index('address_id');
            $table->string('carrier', 100)->index();
            $table->datetime('date')->index();
            $table->timestamps();
        });

        Schema::table('labels', function(Blueprint $table)
        {
            $table->uuid('scan_form_id')->after('rate_id')->nullable()->index();
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
