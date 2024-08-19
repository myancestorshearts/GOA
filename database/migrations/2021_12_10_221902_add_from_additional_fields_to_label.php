<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFromAdditionalFieldsToLabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('labels', function (Blueprint $table) {
            $table->uuid('from_address_id')->after('order_group_id')->index();
            $table->decimal('weight', 8, 2)->after('from_address_id');
            $table->string('service')->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('additional_fields_to_label', function (Blueprint $table) {
            //
        });
    }
}
