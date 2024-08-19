<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLabelCorrections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        
        Schema::table('label_corrections', function (Blueprint $table) {
            $table->uuid('user_id')->after('id')->index();
            $table->uuid('external_user_id')->after('user_id')->index()->nullable();
            $table->string('carrier')->after('weight')->index();
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
