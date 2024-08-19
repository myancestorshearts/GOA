<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

class Wix {

	private $app_id;
	private $secret;
    private $host = 'https://www.wix.com';
    private $api_host = 'https://www.wixapis.com';
    private $curl;
    private $weight_map = [];
    private $goa_key = 'WIX';

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->app_id = env('WIX_APP_ID', '');
		$this->secret = env('WIX_SECRET', '');
	}

    /**purpose
     *   install store
     * args
     *   (token)
     * returns
     *   redirect_link
     */
    public function install($request) {
        
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
    }

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
        $connect_link = $this->host . '/installer/install?' . 
        '&appId=' . $this->app_id . 
        '&redirectUrl=' . urlencode('https://' . $request->getHost() . '/api/oauth/connect') . 
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
		curl_setopt($curl, CURLOPT_URL, $this->host . '/oauth/access');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = [
			'Content-Type: application/json'
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		$data_string = json_encode(json_decode(json_encode([
            'grant_type' => 'authorization_code',
            'client_id' => $this->app_id,
            'code' => $code,
            'client_secret' => $this->secret
        ])));

		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string); 

 
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);




        // check access token
        if (isset($server_decoded->access_token)) {

            // finalize install
            curl_setopt($curl, CURLOPT_URL, $this->host . '/installer/token-received');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $headers = [
                'Authorization: ' . $server_decoded->access_token
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            // get shop uniqueu id
            $shop_unique_id = $request->get('instanceId');;

            // check if integration exists already
            $integration = Mysql\Integration::where([
                ['user_id', '=', $state->user_id],
                ['store_unique_key', '=', $shop_unique_id],
                ['store', '=', $this->goa_key]
            ])->limit(1)->get()->first();

            // create integration
            if (!isset($integration)) {
                $integration = new Mysql\Integration;
                $integration->user_id = $state->user_id;
                $integration->store_unique_key = $shop_unique_id;
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
            $tokens['ACCESS_EXPIRES'] = time() + 120;
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
            curl_setopt($curl, CURLOPT_URL, $this->host . '/oauth/access');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                
            $headers = [
                'Content-Type: application/json'
            ];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $data_string = json_encode(json_decode(json_encode([
                'grant_type' => 'refresh_token',
                'client_id' => $this->app_id,
                'refresh_token' => decrypt($tokens['REFRESH_TOKEN']),
                'client_secret' => $this->secret
            ])));

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string); 

            $server_output = curl_exec($curl);
            $server_decoded = json_decode($server_output);
            
            if (isset($server_decoded->access_token)) {
                $tokens['ACCESS_TOKEN'] = encrypt($server_decoded->access_token);
                $tokens['REFRESH_TOKEN'] = encrypt($server_decoded->refresh_token);
                $tokens['ACCESS_EXPIRES'] = time() + 120;
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

        // find token from dynamo
        $token = Dynamo\Tokens::findOrCreate($integration->id);

        // get info
        if (!isset($token['ACCESS_TOKEN'])) return false;
        $access_token = $token['ACCESS_TOKEN'];

        // get shop
        $access_token = decrypt($access_token);

        // build url
        $url = $this->api_host . $api;

        // init curl
        $this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        // set access token header
		$headers = [
			'Content-Type: application/json',
            'authorization: ' . $access_token
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

        $data_string = json_encode(json_decode(json_encode($data)));

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 

		$server_output = curl_exec($this->curl);

		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}

    /**purpose
     *   call a get for store
     * args
     *   api
     *   data
     * returns
     *   decoded response
     */
    public function callGet($integration, $api, $data) {

        $result = $this->createCurl($integration, $api);

        $data_string = json_encode(json_decode(json_encode($data)));

		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 
        
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

        // get shop id
        $data = $this->callPost($integration, '/stores/v2/orders/query', [
            'query' => [
                'paging' => [
                    'limit' => 100
                ]
            ]
        ]);

        if (!isset($data->orders)) return;

        foreach ($data->orders as $integration_order) {

            // check to see if order is already in database
            $order = Mysql\Order::where([
                ['reference', '=', $integration_order->number],
                ['store', '=', $this->goa_key],
                ['store_id', '=', $integration_order->id]
            ])->limit(1)->get()->first();

            if (isset($order)) {
                
                // check if order is marked fulfilled
                if ($integration_order->fulfillmentStatus == 'FULFILLED') {
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
            if ($integration_order->fulfillmentStatus == 'FULFILLED') continue;

            // get customer
            $integration_customer = isset($integration_order->buyerInfo) ? $integration_order->buyerInfo : null;

            // get shipping address
            $integration_info = isset($integration_order->shippingInfo) ? $integration_order->shippingInfo : null;

            
            // get shipping details
            $ingration_details = (isset($integration_info) && isset($integration_info->shipmentDetails)) ? $integration_info->shipmentDetails : null;
            // get shipping details
            $integration_address = (isset($ingration_details) && isset($ingration_details->address)) ? $ingration_details->address : null;

            // check to make sure customer and address are set - if one of these are not set then the order is not supported in goa software yet 
            if (!isset($integration_customer) || !isset($integration_address)) {
                
                // get missing parts
                $missing_parts = [];
                if (!isset($integration_customer)) $missing_parts[] = 'customer';
                if (!isset($integration_address)) $missing_parts[] = 'shipping address';

                // create error message
                $error_message = 'Wix order is missing ' . implode(' and ', $missing_parts) . '.';

                // create failure
                $integration->addFailedOrder($integration_order->number, $error_message);
                continue;
            }

            // get customer name parts
            $name_parts = [];
            if (isset($integration_customer->firstName)) $name_parts[] = $integration_customer->firstName;
            if (isset($integration_customer->lastName)) $name_parts[] = $integration_customer->lastName;

            // get order products
            $order_products = [];
            foreach ($integration_order->lineItems as $integration_line_item) {

                $order_products[] = (object) [
                    'name' => $integration_line_item->name,
                    'sku' => $integration_line_item->sku,
                    'quantity' => $integration_line_item->quantity,
                    'weight' => $this->getWeightOfListing($integration, $integration_line_item->productId, $integration_line_item->variantId),
                    'charged' => $integration_line_item->price,
                    'store_id' => $integration_line_item->index
                ];
            }

            // get phone
            $phone = isset($integration_customer->phone) ? str_replace('+1', '', $integration_customer->phone) : '';
            $phone = (
                Functions::isEmpty($phone) && 
                isset($integration_address) && 
                isset($integration_address->phone)
            ) ? str_replace('+1', '', $integration_address->phone) : $phone;

            // get phone on address
            $address_phone = (
                isset($integration_address) &&
                isset($integration_address->phone)
            ) ? str_replace('+1', '', $integration_address->phone) : '';

            // get the email
            $email = isset($integration_customer->email) ? $integration_customer->email : '';

            // customer name
            $customer_name = implode(' ', $name_parts);

            // address name
            $address_name = implode(' ', $name_parts);

            if (isset($integration_address->fullName)) {
                $address_name_parts = [];
                if (isset($integration_address->fullName->firstName)) $address_name_parts[] = $integration_address->fullName->firstName;
                if (isset($integration_address->fullName->lastName)) $address_name_parts[] = $integration_address->fullName->lastName;
                $address_name = implode(' ', $address_name_parts);
            }

            $address_state_parts = explode('-', $integration_address->subdivision);
            $address_state = count($address_state_parts) == 2 ? $address_state_parts[1] : '';

            // build order group from order
            $order_group = (object) [
                'name' => $customer_name,
                'email' => $email,
                'phone' => $phone,
                'orders' => [
                    (object) [
                        'reference' => $integration_order->number,
                        'order_products' => $order_products,
                        'store' => $this->goa_key,
                        'store_id' => $integration_order->id,
                        'integration_id' => $integration->id
                    ]
                ],
                'address' => (object) [
                    'name' => $address_name,
                    'email' => $email,
                    'phone' => $address_phone,
                    'street_1' => $integration_address->addressLine1,
                    'city' => $integration_address->city,
                    'state' => $address_state,
                    'postal' => $integration_address->zipCode,
                    'country' => $integration_address->country
                ]
            ];
            
            // get user to create order group
            $user = Mysql\User::find($integration->user_id);

            // create order group
            $order_group_create_response = Mysql\OrderGroup::create($order_group, $user);

            // check order group create response
            if ($order_group_create_response->isFailure()) {
                $integration->addFailedOrder($integration_order->number, $order_group_create_response->message);
            }
            else { 
                $integration->resolveFailedOrder($integration_order->number);
            }
        }
    }

    /**purpose
     *   get the weight of a listing
     * args
     *   listing_id
     * returns 
     *   weight
     */
    private function getWeightOfListing($integration, $product_id, $variant_id) {

        // get shop id
        if (isset($this->weight_map[$product_id])) return $this->weight_map[$product_id];

        // test listings
        $variant = $this->callGet($integration, '/stores/v1/products/' . $product_id . '/variants/query', [
            'variantIds' => [$variant_id]
        ]);

        // get weight of product
        $weight_oz = 0;
        if (isset($variant->variants) && 
            count($variant->variants) > 0 &&
            isset($variant->variants[0]->variant) && 
            isset($variant->variants[0]->variant->weight)) {
            $weight_oz = Mysql\Integration::convertWeightToOz($variant->variants[0]->variant->weight, 'lb');
        } 
        
        // set map and return weight
        $this->weight_map[$product_id] = $weight_oz;
        return $weight_oz;
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
        
        $order_products = Mysql\OrderProduct::where('order_id', '=', $order->id)->get();
        
        foreach ($order_products as $order_product) {
            $line_items[] = [
                'index' => $order_product->store_id,
                'quantity' => $order_product->quantity
            ];
        }

        $data = [
            'fulfillment' => [
                'lineItems' => $line_items,
                'trackingInfo' => [
                    'shippingProvider' => 'USPS',
                    'trackingNumber' => $label->tracking,
                    'trackingLink' => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $label->tracking . '&tABt=false'
                ]
            ]
        ];

        $result = $this->callPost($integration, '/stores/v2/orders/' . $order->store_id . '/fulfillments', $data);
    }
}