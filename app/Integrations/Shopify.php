<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

class Shopify {

	private $key;
	private $secret;
    private $curl;

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->key = env('SHOPIFY_KEY', '');
		$this->secret = env('SHOPIFY_SECRET', '');
	}

    /**purpose
     *   install shopify store
     * args
     *   shop
     * returns
     *   redirect_link
     */
    public function install($request) {
        
        // create response
        $response = new Response;

        // check required
        if (!$response->hasRequired($request, ['shop'])) return $response->setFailure('Missing required fields');

        // $user
        $email = $request->get('shop') . '@fake.com';

        // get existing user
        $user = Mysql\User::where('email', '=', $email)->get()->first();

        // if user does not exist then create new user
        if (!isset($user)) {
            $user = new Mysql\User;
            $user->name = 'Shopify User';
            $user->email = $email;
            $user->phone = '5555555555';
            $user->password = 'blank';
            $user->save();     
        }

        // set api user
        ApiAuth::setUser($user);

        // get oauth state
        $oauth_state = Mysql\OauthState::generateState('SHOPIFY', $request->get('shop'));

        // generate connect link
        $connect_link = 'https://' . $request->get('shop') . '/admin/oauth/authorize?client_id=' . urlencode($this->key) . 
        '&scope=' . urlencode('write_orders,read_orders,read_products') . 
        '&redirect_uri=' . ($request->isSecure() ? 'https://' : 'http://') . $request->getHost() . '/api/oauth/install' . 
        '&state=' . urlencode($oauth_state->id);
      
        // set link in response
        $response->set('link', $connect_link);

        // return success response;
        return $response->setSuccess();
    }

    /**purpose
     *   connect shopify store
     * args
     *   domain
     * returns
     *   link
     */
    public function connect($request) {

        // create response
        $response = new Response;
        if (!$response->hasRequired($request, ['shop'])) return $response->setFailure('Missing required fields');

        // get oauth state
        $oauth_state = Mysql\OauthState::generateState('SHOPIFY', $request->get('name'));

        // generate connect link
        $connect_link = 'https://' . $request->get('shop') . '.myshopify.com/admin/oauth/authorize?client_id=' . urlencode($this->key) . 
        '&scope=' . urlencode('write_orders,read_orders,read_products') . 
        '&redirect_uri=' . urlencode($_SERVER['HTTP_ORIGIN']) . '/api/oauth/connect' . 
        '&state=' . urlencode($oauth_state->id);
      
        // set link in response
        $response->set('link', $connect_link);

        // return success response;
        return $response->setSuccess();
    }   

    
    /**purpose
     *   confirm connection
     * args
     *   user_id
     *   request
     * returns
     *   none
     */
    public function confirmConnection($state, $request) {

        // get shop
        $shop = $request->get('shop');

        // get code
        $code = $request->get('code');

        // create curl
        $curl = curl_init();

        // creaturl
		curl_setopt($curl, CURLOPT_URL, 'https://' . $shop . '/admin/oauth/access_token');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($curl, CURLOPT_POST, true);                                     
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $this->key,
            'client_secret' => $this->secret,
            'code' => $code
        ]));   
		
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);
        
        // check access token
        if (isset($server_decoded->access_token)) {
            
            // check if integration exists already
            $integration = Mysql\Integration::where([
                ['user_id', '=', $state->user_id],
                ['store_unique_key', '=', $shop],
                ['store', '=', 'SHOPIFY']
            ])->limit(1)->get()->first();

            // create integration
            if (!isset($integration)) {
                $integration = new Mysql\Integration;
                $integration->user_id = $state->user_id;
                $integration->store_unique_key = $shop;
                $integration->store = 'SHOPIFY';
            }

            // set integration information
            $integration->name = $state->name;
            $integration->active = 1;
            $integration->status = 'CONNECTED';
            $integration->save();
            
            // set tokens
            $tokens = Dynamo\Tokens::findOrCreate($integration->id);
            $tokens['ACCESS_TOKEN'] = encrypt($server_decoded->access_token);
            $tokens->updateItem();
        }
    }

	/**purpose
	 *   create curl for Shopify call
	 * args
	 *   api
	 * returns 
	 *   curl object ready for api call
	 */
	private function createCurl($integration, $api) {
        // find token from dynamo
        $token = Dynamo\Tokens::findOrCreate($integration->id);

        // get shopify info
        if (!isset($token['ACCESS_TOKEN'])) return false;
        $shopify_access_token = $token['ACCESS_TOKEN'];

        // get shopify shop
        $shop = $integration->store_unique_key;
        $access_token = decrypt($shopify_access_token);

        // build url
        $url = 'https://' . $shop . $api;

        // init curl
        $this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        // set access token header
		$headers = [
			'X-Shopify-Access-Token: ' . $access_token
		];

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        return true;
    }

	/**purpose
	 *   call a post for pitney that has auth token
	 * args
	 *   api
	 *   data
	 * returns
	 *   decoded response
	 */
	public function callPost($integration, $api, $data) {

		$result = $this->createCurl($integration, $api);
        if (!$result) return false;
		
		curl_setopt($this->curl, CURLOPT_POST, 1);

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data)); 

		$server_output = curl_exec($this->curl);

		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}

    /**purpose
     *   call a get for shopify
     * args
     *   api
     *   data
     * returns
     *   decoded response
     */
    public function callGet($integration, $api, $data) {
        
        $data_string = http_build_query($data);
        $result = $this->createCurl($integration, $api . '?' . $data_string);
        if (!$result) return null;
        
        $server_output = curl_exec($this->curl);

		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
    }

    /**purpose
     *   sync orders 
     * args
     *   api
     * returns
     *   (none)
     */
    public function syncOrders($integration) {

        // get all orders for the  last month 
        $created_max = time();
        $total_count = 0;

        do {
            // call api
            $data = $this->callGet($integration, '/admin/api/2021-10/orders.json', [
                'created_at_min' => date('Y-m-d\TH:i:s', strtotime('-8 weeks', time())),
                'created_at_max' => date('Y-m-d\TH:i:s', $created_max),
                'limit' => 250,
                'status' => 'any'
            ]);
            
            if (!isset($data->orders)) return;

            $total_count = count($data->orders);

            foreach ($data->orders as $shopify_order) {

                // since id
                $created_max = min(strtotime($shopify_order->created_at), $created_max) - 1;

                // check to see if order is already in database
                $order = Mysql\Order::where([
                    ['reference', '=', $shopify_order->name],
                    ['store', '=', 'SHOPIFY'],
                    ['store_id', '=', $shopify_order->id]
                ])->limit(1)->get()->first();

                if (isset($order)) {
                    
                    // check if order is marked fulfilled
                    if ($shopify_order->fulfillment_status == 'fulfilled') {
                        // find the order group
                        $order_group = Mysql\OrderGroup::find($order->order_group_id);
                        if (isset($order_group) && !Validator::validateBoolean($order_group->fulfilled)) {
                            $order_group->fulfilled = 1;
                            $order_group->save();
                        }
                    }
                    else {
                        
                        // find the order group
                        /*$order_group = Mysql\OrderGroup::find($order->order_group_id);
                        if (isset($order_group) && Validator::validateBoolean($order_group->fulfilled)) {
                            
                            // get user to create order group
                            //$user = Mysql\User::find($integration->user_id);
                            //$order_group->fulfill($user);
                        }*/
                    }
                    continue;
                }

                // make sure order is unfulfilled
                if ($shopify_order->fulfillment_status == 'fulfilled') {
                    $integration->resolveFailedOrder($shopify_order->name);
                    continue;
                }

                // get shopify customer
                $shopify_customer = isset($shopify_order->customer) ? $shopify_order->customer : null;

                // get shipping address
                $shopify_shipping_address = isset($shopify_order->shipping_address) ? $shopify_order->shipping_address : null;

                // check to make sure customer and address are set - if one of these are not set then the order is not supported in goa software yet 
                if (!isset($shopify_customer) || !isset($shopify_shipping_address)) {
                    
                    // get missing parts
                    $missing_parts = [];
                    if (!isset($shopify_customer)) $missing_parts[] = 'customer';
                    if (!isset($shopify_shipping_address)) $missing_parts[] = 'shipping address';

                    // create error message
                    $error_message = 'Shopify order is missing ' . implode(' and ', $missing_parts) . '.';

                    // create failur    e
                    $integration->addFailedOrder($shopify_order->name, $error_message);
                    continue;
                }

                // get customer name parts
                $shopify_name_parts = [];
                if (isset($shopify_customer->first_name)) $shopify_name_parts[] = $shopify_customer->first_name;
                if (isset($shopify_customer->last_name)) $shopify_name_parts[] = $shopify_customer->last_name;

                // get order products
                $order_products = [];
                foreach ($shopify_order->line_items as $shopify_line_item) {
                    $order_products[] = (object) [
                        'name' => $shopify_line_item->name,
                        'sku' => $shopify_line_item->sku,
                        'quantity' => $shopify_line_item->fulfillable_quantity,
                        'weight' => round($shopify_line_item->grams * 0.035274, 2),
                        'charged' => $shopify_line_item->price,
                        'store_id' => $shopify_line_item->id,
                        'created_at' => Functions::convertTimeToMysql(strtotime($shopify_order->created_at))
                    ];
                }

                // get company name
                $company_name = '';
                if (isset($shopify_shipping_address)) {
                    $company_name = isset($shopify_shipping_address->company) ? $shopify_shipping_address->company : $company_name;
                }

                // get phone
                $phone = isset($shopify_customer->phone) ? str_replace('+1', '', $shopify_customer->phone) : '';
                $phone = (
                    Functions::isEmpty($phone) && 
                    isset($shopify_shipping_address) && 
                    isset($shopify_shipping_address->phone)
                ) ? str_replace('+1', '', $shopify_shipping_address->phone) : $phone;

                // get phone on address
                $address_phone = (
                    isset($shopify_shipping_address) &&
                    isset($shopify_shipping_address->phone)
                ) ? str_replace('+1', '', $shopify_shipping_address->phone) : '';

                // get the email
                $email = isset($shopify_customer->email) ? $shopify_customer->email : '';

                // customer name
                $customer_name = implode(' ', $shopify_name_parts);

                // address name
                $address_name = isset($shopify_shipping_address->name) ? $shopify_shipping_address->name : implode(' ', $shopify_name_parts);

                // build order group from order
                $order_group = (object) [
                    'name' => $customer_name,
                    'email' => $email,
                    'company' => $company_name, 
                    'phone' => $phone,
                    'orders' => [
                        (object) [
                            'reference' => $shopify_order->name,
                            'order_products' => $order_products,
                            'store' => 'SHOPIFY',
                            'store_id' => $shopify_order->id,
                            'integration_id' => $integration->id,
                            'created_at' => Functions::convertTimeToMysql(strtotime($shopify_order->created_at))
                        ]
                    ],
                    'address' => (object) [
                        'name' => $address_name,
                        'company' => $company_name,
                        'email' => $email,
                        'phone' => $address_phone,
                        'street_1' => $shopify_shipping_address->address1,
                        'street_2' => $shopify_shipping_address->address2,
                        'city' => $shopify_shipping_address->city,
                        'state' => $shopify_shipping_address->province_code,
                        'postal' => $shopify_shipping_address->zip,
                        'country' => $shopify_shipping_address->country_code
                    ],
                    'created_at' => Functions::convertTimeToMysql(strtotime($shopify_order->created_at))
                ];
                
                // get user to create order group
                $user = Mysql\User::find($integration->user_id);

                // create order group
                $order_group_create_response = Mysql\OrderGroup::create($order_group, $user);

                // check order group create response
                if ($order_group_create_response->isFailure()) {
                    $integration->addFailedOrder($shopify_order->name, $order_group_create_response->message);
                }
                else { 
                    $integration->resolveFailedOrder($shopify_order->name);
                }
            }
        }
        while ($total_count != 0);

    }

    /**purpose
     *   complete order
     * args
     *   user
     *   order
     * returns
     *   (none)
     */
    public function completeOrder($integration, $order, $label) {
        $locations_result = $this->callGet($integration, '/admin/api/2021-10/locations.json', [
            'active' => true
        ]);
        $location_id = null;
        
        if (count($locations_result->locations) > 0) $location_id = $locations_result->locations[0]->id;
        else return;

        $data = [
            'fulfillment' => [
                'location_id' => $location_id,
                'api_version' => '2021-10',
                'order_id' => $order->store_id,
                'tracking_number' => $label->tracking,
                'tracking_company' => 'USPS',
                'notify_customer' => true,
                'service' => 'manual',
                'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $label->tracking . '&tABt=false'
            ]
        ];

        $order_products = Mysql\OrderProduct::where('order_id', '=', $order->id)->get();

        $result = $this->callPost($integration, '/admin/api/2021-10/orders/' . $order->store_id . '/fulfillments.json', $data);
    }
}