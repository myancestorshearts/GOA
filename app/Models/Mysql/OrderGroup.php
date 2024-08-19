<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Common\Validator;
use App\Common\Functions;
use App\Integrations;

use App\Mail;
use App\Common\Email;

class OrderGroup extends Base
{
    public $table = 'order_groups';
    
    // sets sub user so they have their own system for theses
    public CONST SUB_USER_ALLOW = false;

    protected $hidden = [
    ];

    // search users
    public static $search_parameters = [
        [
            'argument' => 'query',
            'columns' => ['email', 'name', 'company', 'phone', 'orders.reference', 'order_products.name', 'order_products.sku'],
            'type' => 'SEARCH'
        ],
        [
            'argument' => 'fulfilled',
            'column' => 'fulfilled', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'active',
            'column' => 'active', 
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];

    public $address;
    public $model_pairs = [
        ['address', 'address_id', Address::class]
    ];

    public $orders;
    public $labels;
    public function getModelWherePairs()
    {
        return [
            ['orders', [['order_group_id', '=', $this->id]], Order::class],
            ['labels', [['order_group_id', '=', $this->id]], Label::class]
        ];
    }

    /**purpose
     *   override default search method
     */
    public static function search($models_query, $request) {

        $models_query->join('orders', 'orders.order_group_id', '=', 'order_groups.id')
            ->join('order_products', 'order_products.order_id', '=', 'orders.id');

        return parent::search($models_query, $request);
    }

    /**purpose
     *   create an address
     * args
     *   name (required)
     *   email (optional)
     *   phone (optional)
     *   company (optional)
     *   address (address model)
     *   orders (order model)
     * returns
     *   address
     */
    public static function create($order_group_data, $user) {

        // create response
        $response = new Response;

        // create address model
        $order_group = new OrderGroup;
        $order_group->user_id = $user->id;

        // name
        if (!isset($order_group_data->name)) return $response->setFailure('Invalid name');
        $validated_name = Validator::validateText($order_group_data->name, ['trim' => true]);
        if (!isset($validated_name)) return $response->setFailure('Invalid name');
        $order_group->name = $validated_name;
        
        // company
        if (isset($order_group_data->company)) {
            $validated_company = Validator::validateText($order_group_data->company, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_company)) return $response->setFailure('Invalid company');
            $order_group->company = $validated_company;
        }
        
        // email
        if (isset($order_group_data->email)) {
            $validated_email = Validator::validateEmail($order_group_data->email, ['clearable' => true]);
            if (!isset($validated_email)) return $response->setFailure('Invalid email');
            $order_group->email = $validated_email;
        }
        
        // phone
        if (isset($order_group_data->phone)) {
            $validated_phone = Validator::validatePhone($order_group_data->phone, ['clearable' => true]);
            if (!isset($validated_phone)) return $response->setFailure('Invalid phone');
            $order_group->phone = $validated_phone;
        }

        // created
        if (isset($order_group_data->created_at)) {
            $order_group->created_at = Functions::convertTimeToMysql(strtotime($order_group_data->created_at));
        }
        
        // check basic order product info
        if (!is_array($order_group_data->orders)) $response->setFailure('Orders must be an array');
        if (count($order_group_data->orders) == 0) $response->setFailure('Order group must have at least 1 order');
    
        // get all database added models incase of error we can clean up
        $all_models = [];
        
        
        // get address
		$address_response = Address::create((object) $order_group_data->address, $user);
		if ($address_response->isFailure()) return $address_response;
        $all_models[] = $address_response->get('model');
       
        // now that everything is validated lets start adding to database
        $address = $address_response->get('model');
        $order_group->address_id = $address->id;
        $order_group->setSubModel('address', $address);

        // save order group
        $order_group->save();
        $all_models[] = $order_group;
        $orders = [];

        // add order products
        foreach ($order_group_data->orders as $order_data) {
            $order_response = Order::create((object) $order_data, $order_group, $user);
            if ($order_response->isFailure()) return $order_response->cleanUp($all_models);
            $all_models[] = $order_response->get('model');
            $orders[] = $order_response->get('model');
        }

        // loop through and set commulative properties
        $commulate_properties = [
            'charged',
            'weight',
            'quantity'
        ];
        $commulate_values = [];
        foreach($orders as $order) {
            foreach ($commulate_properties as $property) {
                if (isset($order->{$property})) {
                    if (!isset($commulate_values[$property])) $commulate_values[$property] = 0;
                    $commulate_values[$property] += $order->{$property};
                }
            }
        }
        foreach($commulate_values as $key => $value) {
            $order_group->{$key} = $value;
        }
        $order_group->save();

        // set orders
        $order_group->setSubModel('orders', $orders);
        
        // set response
        $response->set('model', $order_group);

        // return response
        return $response->setSuccess();
    }

    /**purpose
     *   mark an order fulfilled
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function fulfill($user, $label = null) {
        $this->fulfilled = 1;
        $this->save();

        $orders = Order::where('order_group_id', '=', $this->id)->get();

        $fulfillment_label = $label;
        if (!isset($fulfillment_label)) {
            $fulfillment_label = Label::where('order_group_id', '=', $this->id)->limit(1)->get()->first();
        }
        
        // send fulfillment email
        if (isset($fulfillment_label)) $this->sendFulfillmentEmail($fulfillment_label);

        foreach ($orders as $order) {

            // if store is not set then skip
            if (Functions::isEmpty($order->integration_id)) continue;

            // generate new integration
            $integration = Integration::find($order->integration_id);

            // complete orders
            if (isset($integration)) $integration->completeOrder($order, $fulfillment_label);
        }
    }

    
    /**purpose
     *   send an email saying package is on its way with tracking number
     * args
     *   label
     * returns
     *   (none)
     */
    public function sendFulfillmentEmail($label) {
        $validated_email = Validator::validateEmail($this->email);
        if (isset($validated_email)) {
            $mailer = new Mail\Fulfillment($this, $label);
            Email::sendMailer($mailer);
        }
    }
}
