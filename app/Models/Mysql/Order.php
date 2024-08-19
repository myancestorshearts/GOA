<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Common\Validator;
use App\Common\Functions;


class Order extends Base
{
    public $table = 'orders';

    protected $hidden = [
    ];

    public $order_products;
    public function getModelWherePairs()
    {
        return [
            ['order_products', [['order_id', '=', $this->id]], OrderProduct::class]
        ];
    }

    /**purpose
     *   create an address
     * args
     *   name (required)
     *   email (optional)
     *   phone (optional)
     *   company (optional)
     *   order_products (order product models)
     *   ignore_duplicates (optional) (default false)
     * returns
     *   address
     */
    public static function create($data, $order_group, $user) {

        // create response
        $response = new Response;

        // create address model
        $model = new Order;
        $model->user_id = $user->id;
        $model->order_group_id = $order_group->id;

        // check basic order product info
        if (!is_array($data->order_products)) return $response->setFailure('Order products must be an array');
        if (count($data->order_products) == 0) return $response->setFailure('Order must have at least 1 product');

        // reference
        if (isset($data->reference)) {
            
            // check to see if there are duplicates
            if (isset($data->ignore_duplicates) && Validator::validateBoolean($data->ignore_duplicates)) {
                if (Order::join('order_groups', 'order_groups.id', '=', 'orders.order_group_id')->where([
                    ['orders.user_id', '=', $user->id],
                    ['orders.reference', '=', $data->reference],
                    ['order_groups.active', '=', 1]
                ])->count() > 0) return $response->setFailure('Duplicate order', 'DUPLICATE');
            }

            $validated_reference = Validator::validateText($data->reference, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_reference)) return $response->setFailure('Invalid reference');
            $model->reference = $validated_reference;
        }

        // store
        if (isset($data->store)) {
            $validated_store = Validator::validateText($data->store, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_store)) return $response->setFailure('Invalid reference');
            $model->store = $validated_store;
        }

        // store id
        if (isset($data->store_id)) {
            $validated_store_id = Validator::validateText($data->store_id, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_store_id)) return $response->setFailure('Invalid reference');
            $model->store_id = $validated_store_id;
        }
        
        // integration id
        if (isset($data->integration_id)) {
            $integration = Integration::find($data->integration_id);
            if (!isset($integration)) return $response->setFailure('Invalid integration id');
            $model->integration_id = $integration->id;
        }

        // created
        if (isset($data->created_at)) {
            $model->created_at = Functions::convertTimeToMysql(strtotime($data->created_at));
        }

        // save order group
        $model->save();

        // gather all models in case of failure
        $all_models = [$model];
        $order_products = [];

        // add order products
        foreach ($data->order_products as $order_product_data) {
            $order_product_response = OrderProduct::create((object) $order_product_data, $model, $user);
            if ($order_product_response->isFailure()) return $order_product_response->cleanUp($all_models);
            $all_models[] = $order_product_response->get('model');
            $order_products[] = $order_product_response->get('model');
        }

        // loop through and set commulative properties
        $commulate_properties = [
            'charged',
            'weight',
            'quantity'
        ];
        $commulate_values = [];
        foreach($order_products as $order_product) {
            foreach ($commulate_properties as $property) {
                if (isset($order_product->{$property})) {
                    if (!isset($commulate_values[$property])) $commulate_values[$property] = 0;                    
                    $commulate_values[$property] += $order_product->{$property};
                }
            }
        }
        foreach($commulate_values as $key => $value) {
            $model->{$key} = $value;
        }
        $model->save();
        
        // set order product sub model
        $model->setSubModel('order_products', $order_products);

        // set response
        $response->set('model', $model);

        // return response
        return $response->setSuccess();
    }

    

}
