<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSavedPackagesAndAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        
        Schema::create('packages', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('user');
            $table->string('name', 100)->index('name');
            $table->string('type');
            $table->decimal('length', 8, 3);
            $table->decimal('width', 8, 3);
            $table->decimal('height', 8, 3);
            $table->boolean('saved')->index();
            $table->boolean('active')->index();
            $table->timestamps();
        });

        
        Schema::table('addresses', function (Blueprint $table) {
            $table->boolean('default')->after('country')->index();
            $table->boolean('saved')->after('default')->index();
            $table->boolean('active')->after('saved')->index();
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
