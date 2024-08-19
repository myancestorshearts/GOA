<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use Shopify\Context;
use Shopify\Auth\FileSessionStorage;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

class Etsy {

	private $key;
	private $secret;
    private $host = 'https://www.etsy.com';
    private $api_host = 'https://api.etsy.com';
    private $curl;
    private $weight_map = [];

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->key = env('ETSY_KEY', '');
		$this->secret = env('ETSY_SECRET', '');
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
        return $response->setFailure('No install implemented for Etsy');
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

        // get oauth state
        $oauth_state = Mysql\OauthState::generateState('ETSY', $request->get('name'));

        // generate connect link
        $connect_link = $this->host . '/oauth/connect?' . 
        'response_type=code' . 
        '&redirect_uri=' . urlencode($_SERVER['HTTP_ORIGIN']) . '/api/oauth/connect' . 
        '&scope=address_r%20billing_r%20email_r%20listings_r%20transactions_r%20transactions_w' . 
        '&client_id=' . $this->key . 
        '&code_challenge=' . $oauth_state->challenge . 
        '&code_challenge_method=S256' . 
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

        $called_host = ($request->isSecure() ? 'https://' : 'http://') . $request->getHost();

        // get code
        $code = $request->get('code');

        // create curl
        $curl = curl_init();

        // creaturl
		curl_setopt($curl, CURLOPT_URL, $this->api_host . '/v3/public/oauth/token');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($curl, CURLOPT_POST, true);                                     
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $this->key,
            'redirect_uri' => $called_host . '/api/oauth/connect',
            'code' => $code,
            'code_verifier' => $state->verifier
        ]));   
		
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);
        
        // check access token
        if (isset($server_decoded->access_token)) {

            // get shop uniqueu id
            $shop_unique_id = $this->getShopUniqueId($server_decoded->access_token);
            if (!isset($shop_unique_id)) return null;

            // check if integration exists already
            $integration = Mysql\Integration::where([
                ['user_id', '=', $state->user_id],
                ['store_unique_key', '=', $shop_unique_id],
                ['store', '=', 'ETSY']
            ])->limit(1)->get()->first();

            // create integration
            if (!isset($integration)) {
                $integration = new Mysql\Integration;
                $integration->user_id = $state->user_id;
                $integration->store_unique_key = $shop_unique_id;
                $integration->store = 'ETSY';
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
            $tokens['ACCESS_EXPIRES'] = time() + $server_decoded->expires_in;
            $tokens->updateItem();
        }
    }

    /**purpose
     *   get shop unique id
     * args
     *   access_token
     * returns
     *   unique_id
     */
    private function getShopUniqueId($access_token) {

        // init curl
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->api_host . '/v3/application/user/addresses');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // set access token header
        $headers = [
            'x-api-key: ' . $this->key,
            'authorization: Bearer ' . $access_token
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        $server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);

		curl_close($curl);

        if (isset($server_decoded->results) && count($server_decoded->results) > 0) {
            return $server_decoded->results[0]->user_id;
        }

        return null;
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
            curl_setopt($curl, CURLOPT_URL, $this->api_host . '/v3/public/oauth/token');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($curl, CURLOPT_POST, true);                                     
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->key,
                'refresh_token' => decrypt($tokens['REFRESH_TOKEN'])
            ])); 

            $server_output = curl_exec($curl);
            $server_decoded = json_decode($server_output);
            
            if (isset($server_decoded->access_token)) {
                $tokens['ACCESS_TOKEN'] = encrypt($server_decoded->access_token);
                $tokens['REFRESH_TOKEN'] = encrypt($server_decoded->refresh_token);
                $tokens['ACCESS_EXPIRES'] = time() + $server_decoded->expires_in;
                $tokens->updateItem();
            }

            curl_close($curl);
        }

        return decrypt($tokens['ACCESS_TOKEN']);
    }

	/**purpose
	 *   create curl for Shopify call
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
			'x-api-key: ' . $this->key,
            'authorization: Bearer ' . $access_token
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

        // get shop id
        $shop = $this->callGet($integration, '/v3/application/users/' . $integration->store_unique_key . '/shops', []);
        if (!isset($shop) || !isset($shop->shop_id)) return;

        // get all orders for the  last month 
        $offset = 0;
        $max_offset = 0;

        do {
                
            $four_weeks_ago = strtotime('-4 weeks', time());
            $data = $this->callGet($integration, '/v3/application/shops/' . $shop->shop_id . '/receipts', [
                'min_created' => $four_weeks_ago,
                'limit' => 100,
                'offset' => $offset
            ]);


            if (!isset($data->results)) return;

            $max_offset = $data->count;

            foreach ($data->results as $integration_order) {

                // check to see if order is already in database
                $order = Mysql\Order::where([
                    ['reference', '=', $integration_order->receipt_id],
                    ['store', '=', 'ETSY'],
                    ['store_id', '=', $integration_order->receipt_id]
                ])->limit(1)->get()->first();

                if (isset($order)) {                   
                    // check if order is marked fulfilled
                    if (Validator::validateBoolean($integration_order->is_shipped)) {
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
                if (Validator::validateBoolean($integration_order->is_shipped)) continue;

                // get order products
                $order_products = [];
                foreach ($integration_order->transactions as $integration_line_item) {

                    // get weight of listing
                    $order_products[] = (object) [
                        'name' => $integration_line_item->title,
                        'sku' => $integration_line_item->sku,
                        'quantity' => $integration_line_item->quantity,
                        'weight' => $this->getWeightOfListing($integration, $integration_line_item->listing_id),
                        'charged' => $integration_line_item->price->amount / $integration_line_item->price->divisor,
                        'store_id' => $integration_line_item->transaction_id
                    ];
                }

                // get the email
                $email = isset($integration_order->buyer_email) ? $integration_order->buyer_email : '';

                // customer name
                $customer_name = $integration_order->name;

                // address name
                $address_name = $customer_name;

                // build order group from order
                $order_group = (object) [
                    'name' => $customer_name,
                    'email' => $email,
                    'orders' => [
                        (object) [
                            'reference' => $integration_order->receipt_id,
                            'order_products' => $order_products,
                            'store' => 'ETSY',
                            'store_id' => $integration_order->receipt_id,
                            'integration_id' => $integration->id
                        ]
                    ],
                    'address' => (object) [
                        'name' => $address_name,
                        'email' => $email,
                        'street_1' => $integration_order->first_line,
                        'street_2' => $integration_order->second_line,
                        'city' => $integration_order->city,
                        'state' => $integration_order->state,
                        'postal' => $integration_order->zip,
                        'country' => $integration_order->country_iso
                    ]
                ];
                
                // get user to create order group
                $user = Mysql\User::find($integration->user_id);

                // create order group
                $order_group_create_response = Mysql\OrderGroup::create($order_group, $user);

                // check order group create response
                if ($order_group_create_response->isFailure()) {
                    $integration->addFailedOrder($integration_order->name, $order_group_create_response->message);
                }
                else { 
                    $integration->resolveFailedOrder($integration_order->name);
                }
            }

            $offset += 90;
        }
        while ($offset <= $max_offset);
    }

    /**purpose
     *   get the weight of a listing
     * args
     *   listing_id
     * returns 
     *   weight
     */
    private function getWeightOfListing($integration, $listing_id) {
        
        // get shop id
        if (isset($this->weight_map[$listing_id])) return $this->weight_map[$listing_id];

        // test listings
        $listing = $this->callGet($integration, '/v3/application/listings/' . $listing_id, []);

        // get weight of listing
        $weight_oz = 0;
        if (isset($listing->item_weight) && isset($listing->item_weight_unit)) {
            $weight_oz = Mysql\Integration::convertWeightToOz($listing->item_weight, $listing->item_weight_unit);
        } 
        
        // set map and return weight
        $this->weight_map[$listing_id] = $weight_oz;
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

        // get shop id
        $shop = $this->callGet($integration, '/v3/application/users/' . $integration->store_unique_key . '/shops', []);
        if (!isset($shop) || !isset($shop->shop_id)) return;
        $rate = Mysql\Rate::find($label->rate_id);

        // get shop id
        $result = $this->callPost($integration, '/v3/application/shops/' . $shop->shop_id . '/receipts/' . $order->store_id . '/tracking', [
            'tracking_code' =>  $label->tracking,
            'carrier_name' => 'USPS'
        ]);
    }
}