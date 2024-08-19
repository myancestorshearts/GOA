<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Mysql;

class AddItemCountToOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_groups', function (Blueprint $table) {
            $table->decimal('quantity', 8, 2)->after('weight')->index();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('quantity', 8, 2)->after('weight')->index();
        });

        $order_products = Mysql\OrderProduct::all();

        $orders = Mysql\Order::all();
        $order_groups = Mysql\OrderGroup::all();
        foreach($orders as $order) {
            $quantity = 0;
            foreach($order_products as $order_product) {
                if ($order_product->order_id == $order->id) {
                    $quantity += $order_product->quantity;
                }
            }

            $order->quantity = $quantity;
        }

        foreach($order_groups as $order_group) {
            $quantity = 0;
            foreach($orders as $order) {
                if ($order->order_group_id == $order_group->id) {
                    $quantity += $order->quantity;
                }
            }
            $order_group->quantity = $quantity;
        }


        DB::transaction(function() use ($orders, $order_groups) {
            foreach($orders as $order) {
                $order->save();
            }
            foreach($order_groups as $order_group) {
                $order_group->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_groups', function (Blueprint $table) {
            //
        });
    }
}
