<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

class Square {

	private $application_id;
	private $access_token;
    private $application_secret;
    private $host = 'https://connect.squareup.com';
    private $api_host = 'https://connect.squareup.com';
    private $curl;
    private $details_map = [];
    private $goa_key = 'SQUARE';

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->application_id = env('SQAURE_APPLICATION_ID', '');
		$this->access_token = env('SQUARE_ACCESS_TOKEN', '');
		$this->application_secret = env('SQUARE_APPLICATION_SECRET', '');
	}

    /**purpose
     *   install store
    * args
     *   (token)
     * returns
     *   redirect_link
     */
    /*public function install($request) {
        
        // create response
        $response = new Response;

        // check required
        if (!$response->hasRequired($request, ['token'])) return $response->setFailure('Missing required fields');
        
        // $user
        $email = Functions::getRandomId(10) . '@fake.com';

        // get existing user
        $user = Mysql\User::where('email', '=', $email)->get()->first();

        // if user does not exist then create new user
        if (!isset($user)) {
            $user = new Mysql\User;
            $user->name = $this->goa_key . ' User';
            $user->email = $email;
            $user->phone = '5555555555';
            $user->password = 'blank';
            $user->save();     
        }

        // set api user
        ApiAuth::setUser($user);

        // get oauth state
        $oauth_state = Mysql\OauthState::generateState($this->goa_key, 'My ' . $this->goa_key . ' Store');

        // generate connect links
        $connect_link = $this->host . '/installer/install?' . 
        'token=' . $request->get('token') . 
        '&appId=' . $this->app_id . 
        '&redirectUrl=' . urlencode('https://' . $request->getHost() . '/api/oauth/install') . 
        '&state=' . urlencode($oauth_state->id);
  
        // set link in response
        $response->set('link', $connect_link);

        // return success response;
        return $response->setSuccess();
    }*/

    /**purpose
     *   connect store
     * args
     *   domain
     * returns
     *   link
     */
    public function connect($request) {

        // create response
        $response = new Response;

        // get oauth state
        $oauth_state = Mysql\OauthState::generateState($this->goa_key, $request->get('name'));

        // generate connect link
        $connect_link = $this->host . '/oauth2/authorize?' . 
        '&client_id=' . $this->application_id . 
        '&scope=' . urlencode('ORDERS_WRITE ORDERS_READ ITEMS_READ MERCHANT_PROFILE_READ') . 
        '&local=en-US' .
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

        // get code
        $code = $request->get('code');

        // create curl
        $curl = curl_init();

        // creaturl
		curl_setopt($curl, CURLOPT_URL, $this->host . '/oauth2/token');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = [
			'Content-Type: application/json',
			'Square-Version: 2022-02-16'
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		$data_string = json_encode(json_decode(json_encode([
            'client_id' => $this->application_id,
            'client_secret' => $this->application_secret,
            'code' => $code,
            'redirect_uri' => 'https://' . $request->getHost() . '/api/oauth/connect', 
            'grant_type' => 'authorization_code',
            'scopes' => [
                'ORDERS_WRITE',
                'ORDERS_READ',
                'ITEMS_READ',
                'MERCHANT_PROFILE_READ'
            ]
        ])));

		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string); 

		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);

        // check access token
        if (isset($server_decoded->access_token)) {

            // check if integration exists already
            $integration = Mysql\Integration::where([
                ['user_id', '=', $state->user_id],
                ['store_unique_key', '=', $server_decoded->merchant_id],
                ['store', '=', $this->goa_key]
            ])->limit(1)->get()->first();

            // create integration
            if (!isset($integration)) {
                $integration = new Mysql\Integration;
                $integration->user_id = $state->user_id;
                $integration->store_unique_key = $server_decoded->merchant_id;
                $integration->store = $this->goa_key;
            }

            // set integration information
            $integration->name = $state->name;
            $integration->active = 1;
            $integration->status = 'CONNECTED';
            $integration->save();
            
            // set tokens
            $tokens = Dynamo\Tokens::findOrCreate($integration->id);
            $tokens['ACCESS_TOKEN'] = encrypt($server_decoded->access_token);
            $tokens['REFRESH_TOKEN'] = encrypt($server_decoded->refresh_token);
            $tokens['ACCESS_EXPIRES'] = strtotime('-1 day', strtotime($server_decoded->expires_at));
            $tokens->updateItem();
        }
    }

    /**purpose
     *   refresh tokens from goa api
     * args
     *   (none)
     * returns
     *   token
     */
    private function getAccessToken($integration) {

        // find token from dynamo
        $tokens = Dynamo\Tokens::findOrCreate($integration->id);
        // get expire time
        $expire_time = $tokens['ACCESS_EXPIRES'];

        // if expired refresh token
        if ($expire_time < time()) {

            // create curl
            $curl = curl_init();

            // creaturl
            curl_setopt($curl, CURLOPT_URL, $this->host . '/oauth2/token');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                
            $headers = [
                'Content-Type: application/json'
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $data_string = json_encode(json_decode(json_encode([
                'client_id' => $this->application_id,
                'client_secret' => $this->application_secret,
                'refresh_token' => decrypt($tokens['REFRESH_TOKEN']),
                'grant_type' => 'refresh_token'
            ])));

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string); 

            $server_output = curl_exec($curl);
            $server_decoded = json_decode($server_output);
            
            if (isset($server_decoded->access_token)) {
                $tokens['ACCESS_TOKEN'] = encrypt($server_decoded->access_token);
                $tokens['REFRESH_TOKEN'] = encrypt($server_decoded->refresh_token);
                $tokens['ACCESS_EXPIRES'] = strtotime('-1 day', strtotime($server_decoded->expires_at));
                $tokens->updateItem();
            }

            curl_close($curl);
        }

        return decrypt($tokens['ACCESS_TOKEN']);
    }

	/**purpose
	 *   create curl for call
	 * args
	 *   api
	 * returns 
	 *   curl object ready for api call
	 */
	private function createCurl($integration, $api) {

        // get access token
        $access_token = $this->getAccessToken($integration);

        // build url
        $url = $this->api_host . $api;

        // init curl
        $this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        // set access token header
		$headers = [
            'Square-Version: 2022-02-16',
			'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
		];

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        return true;
    }

	/**purpose
	 *   call a post that has auth token
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

        $data_string = json_encode(json_decode(json_encode($data)));

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 

		$server_output = curl_exec($this->curl);

		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}

    /**purpose
	 *   call a put that has auth token
	 * args
	 *   api
	 *   data
	 * returns
	 *   decoded response
	 */
	public function callPut($integration, $api, $data) {

		$result = $this->createCurl($integration, $api);
        if (!$result) return false;
		
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');

        $data_string = json_encode(json_decode(json_encode($data)));
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string);

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

        $locations = $this->callGet($integration, '/v2/locations', []);

        $location_ids = [];
        foreach($locations->locations as $location)  {
            $location_ids[] = $location->id;
        }
        
        $weeks_ago = strtotime('-8 weeks', time());

        // get all orders for the  last month 
        $cursor = null;
        $total_orders = [];
        do {
                

            $args = [
                'limit' => 100,
                'location_ids' => $location_ids,
                'query' => [
                    'filter' => [
                        'date_time_filter' => [
                            'created_at' => [
                                'start_at' => date('Y-m-d\TH:i:s', $weeks_ago)
                            ]
                        ]
                    ]
                ]
            ];

            if (isset($cursor)) $args['cursor'] = $cursor;

            $data = $this->callPost($integration, '/v2/orders/search', $args);

            $cursor = isset($data->cursor) ? $data->cursor : null;
            

            if (!isset($data->orders)) continue;
            foreach ($data->orders as $integration_order) {

                // check to see if order is already in database
                $order = Mysql\Order::where([
                    ['reference', '=', $integration_order->reference_id],
                    ['store', '=', $this->goa_key],
                    ['store_id', '=', $integration_order->id]
                ])->limit(1)->get()->first();

                $is_shipped = $integration_order->state == 'COMPLETED';
                if (isset($order)) {                   
                    // check if order is marked fulfilled
                    if ($is_shipped) {

                        // find the order group
                        $order_group = Mysql\OrderGroup::find($order->order_group_id);
                        if (isset($order_group) && !Validator::validateBoolean($order_group->fulfilled)) {
                            $order_group->fulfilled = 1;
                            $order_group->save();
                        }
                    }
                    continue;
                }

                // make sure order is unfulfilled
                if ($is_shipped) continue;

                // get order products
                $order_products = [];
                foreach ($integration_order->line_items as $integration_line_item) {

                    $product_name = $integration_line_item->name;
                    if (isset($integration_line_item->variation_name) && !Functions::isEmpty($integration_line_item->variation_name)) $product_name .= ' - ' . $integration_line_item->variation_name;

                    $product_details = $this->getDetailsOfListing($integration, $integration_line_item->catalog_object_id);
                    // get weight of listing
                    $order_products[] = (object) [
                        'name' => $integration_line_item->name,
                        'sku' => $product_details['sku'],
                        'quantity' => $integration_line_item->quantity,
                        'weight' => 0,
                        'charged' => $integration_line_item->total_money->amount,
                        'store_id' => $integration_line_item->uid,
                        'created_at' => $integration_order->created_at
                    ];
                }

                // check to make sure 1 fulfillment and fulfilment line item application is all
                if (!isset($integration_order->fulfillments)) $integration->addFailedOrder($integration_order->reference_id, 'Fulfillments is not set - Items may not be marked as shippable.');
                if (count($integration_order->fulfillments) != 1)  $integration->addFailedOrder($integration_order->reference_id, 'There is not only 1 fulfillment. Currently not supported.');
                if ($integration_order->fulfillments[0]->line_item_application != 'ALL')  $integration->addFailedOrder($integration_order->reference_id, 'Fulfillment is not marked as ALL. Currently not supported.');
                $fulfillment_info = $integration_order->fulfillments[0];

                //make sure shipment details are set
                if (!isset($fulfillment_info->shipment_details)) $integration->addFailedOrder($integration_order->reference_id, 'Shipment details not set.');
                $shipment_details = $fulfillment_info->shipment_details;
                
                //make sure recipient is set
                if (!isset($shipment_details->recipient)) $integration->addFailedOrder($integration_order->reference_id, 'Recipient is not set.');
                $recipient_info = $shipment_details->recipient;
                
                //make sure recipient is set
                if (!isset($recipient_info->address)) $integration->addFailedOrder($integration_order->reference_id, 'Address is not set.');
                $address_info = $recipient_info->address;

                // get the email
                $email = isset($recipient_info->email_address) ? $recipient_info->email_address : '';

                // customer name
                $customer_name = $recipient_info->display_name;

                // address name
                $address_name = $customer_name;

                // build order group from order
                $order_group = (object) [
                    'name' => $customer_name,
                    'email' => $email,
                    'orders' => [
                        (object) [
                            'reference' => $integration_order->reference_id,
                            'order_products' => $order_products,
                            'store' => $this->goa_key,
                            'store_id' => $integration_order->id,
                            'integration_id' => $integration->id,
                            'created_at' => $integration_order->created_at
                        ]
                    ],
                    'address' => (object) [
                        'name' => $address_name,
                        'email' => $email,
                        'street_1' => $address_info->address_line_1,
                        'street_2' => isset($address_info->address_line_2) ? $address_info->address_line_2 : '',
                        'city' => $address_info->locality,
                        'state' => $address_info->administrative_district_level_1,
                        'postal' => $address_info->postal_code,
                        'country' => $address_info->country
                    ],
                    'created_at' => $integration_order->created_at
                ];

                // get user to create order group
                $user = Mysql\User::find($integration->user_id);

                // create order group
                $order_group_create_response = Mysql\OrderGroup::create($order_group, $user);

                // check order group create response
                if ($order_group_create_response->isFailure()) {
                    $integration->addFailedOrder($integration_order->reference_id, $order_group_create_response->message);
                }
                else { 
                    $integration->resolveFailedOrder($integration_order->reference_id);
                }
            }
        }
        while (isset($cursor));
    }

   

    /**purpose
     *   get the weight of a listing
     * args
     *   listing_id
     * returns 
     *   weight
     */
    private function getDetailsOfListing($integration, $product_id) {

        // get shop id
        if (isset($this->details_map[$product_id])) return $this->details_map[$product_id];

        // test listings
        $variant = $this->callGet($integration, '/v2/catalog/object/S6WA6TJJJXUFXJUMNUUYLMPO', [
        ]);

        // get sku of product
        $sku = '';
        if (isset($variant->object) &&
            isset($variant->object->item_variation_data) &&
            isset($variant->object->item_variation_data->sku)) {
            $sku = $variant->object->item_variation_data->sku;
        }

        // get weight of product
        /*
        $weight_oz = 0;
        if (isset($variant->variants) && 
            count($variant->variants) > 0 &&
            isset($variant->variants[0]->variant) && 
            isset($variant->variants[0]->variant->weight)) {
            $weight_oz = Mysql\Integration::convertWeightToOz($variant->variants[0]->variant->weight, 'lb');
        }*/
        
        // set map and return weight
        $this->details_map[$product_id] = [];
        $this->details_map[$product_id]['sku'] = $sku;
        
        return $this->details_map[$product_id];
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

        $line_items = [];
        
        $integration_order = $this->callGet($integration, '/v2/orders/' . $order->store_id, [
        ]);

        if (!isset($integration_order->order)) return;
        if (!isset($integration_order->order->fulfillments)) return;
        if (count($integration_order->order->fulfillments) != 1) return;
        $order_fulfillment = $integration_order->order->fulfillments[0];
        $result = $this->callPut($integration, '/v2/orders/' . $order->store_id, [
            'idempotency_key' => Functions::getRandomId(10),
            'order' => [
                'version' => $integration_order->order->version,
                'state' => 'COMPLETED',
                'fulfillments' => [
                    [
                        'uid' => $order_fulfillment->uid,
                        'state' => 'COMPLETED',
                        'shipment_details' => [
                            'carrier' => 'USPS',
                            'tracking_number' => $label->tracking,
                            'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $label->tracking . '&tABt=false'
                        ]
                    ]
                ]
            ]
        ]);
    }
}