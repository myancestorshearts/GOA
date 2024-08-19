<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('referrals', function(Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index('user');
            $table->uuid('referred_user_id')->index('referred');
            $table->string('email');
            $table->string('name');
            $table->boolean('purchased_first_label')->index('purchased');
            $table->string('label_link', 1024);
            $table->timestamps();
        });

        
        Schema::table('users', function (Blueprint $table) {
            //
            $table->uuid('referral_code')->after('status')->index('referral_code');
            $table->boolean('referral_program')->after('referral_code');
            $table->string('referral_program_type')->after('referral_program');
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
