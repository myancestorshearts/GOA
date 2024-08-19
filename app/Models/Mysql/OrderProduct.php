<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Common\Validator;
use App\Common\Functions;

class OrderProduct extends Base
{
    public $table = 'order_products';

    protected $hidden = [
    ];

    /**purpose
     *   create an address
     * args
     *   name (required)
     *   quantity (required)
     *   sku (optional)
     *   weight (optional)
     *   charged (optional)
     * returns
     *   address
     */
    public static function create($data, $order, $user) {

        // create response
        $response = new Response;

        // create address model
        $model = new OrderProduct;
        $model->user_id = $user->id;
        $model->order_id = $order->id;

        // name
        if (!isset($data->name)) return $response->setFailure('Invalid name');
        $validated_name = Validator::validateText($data->name, ['trim' => true]);
        if (!isset($validated_name)) return $response->setFailure('Invalid name');
        $model->name = $validated_name;

        // quantity
        if (!isset($data->quantity)) return $response->setFailure('Invalid quantity');
        $validated_quantity = Validator::validateFloat($data->quantity, ['min' => .01]);
        if (!isset($validated_quantity)) return $response->setFailure('Invalid quantity');
        $model->quantity = $validated_quantity;
        
        // sku
        if (isset($data->sku)) {
            $validated_sku = Validator::validateText($data->sku, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_sku)) return $response->setFailure('Invalid sku');
            $model->sku = $validated_sku;
        }

        // weight
        if (isset($data->weight)) {
            $validated_weight = Validator::validateFloat($data->weight, ['min' => 0]);
            if (!isset($validated_weight)) return $response->setFailure('Invalid weight');
            $model->weight = $validated_weight;
        }

        // charged
        if (isset($data->charged)) {
            $validated_charged = Validator::validateFloat($data->charged, ['min' => 0]);
            if (!isset($validated_charged)) return $response->setFailure('Invalid charged');
            $model->charged = $validated_charged;
        }
        
        // store id
        if (isset($data->store_id)) {
            $validated_store_id = Validator::validateText($data->store_id, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_store_id)) return $response->setFailure('Invalid reference');
            $model->store_id = $validated_store_id;
        }
        
        // created
        if (isset($data->created_at)) {
            $model->created_at = Functions::convertTimeToMysql(strtotime($data->created_at));
        }

        // save order group
        $model->save();

        // set response
        $response->set('model', $model);

        // return response
        return $response->setSuccess();
    }
}
