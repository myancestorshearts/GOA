<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

use ZipArchive;

class WooCommerce {


    
    /**purpose
     *   connect shopify store
     * args
     *   domain
     * returns
     *   integration
     */
    public function connect($request) {

        // create response
        $response = new Response;
        if (!$response->hasRequired($request, ['domain'])) return $response->setFailure('Missing required fields');

        $user = ApiAuth::user();

        // check if integration exists already
        $integration = Mysql\Integration::where([
            ['user_id', '=', $user->id],
            ['store_unique_key', '=', $request->get('domain')],
            ['store', '=', Integration::TYPE_WOOCOMMERCE]
        ])->limit(1)->get()->first();

        // create integration
        if (!isset($integration)) {
            $integration = new Mysql\Integration;
            $integration->user_id = $user->id;
            $integration->store_unique_key = $request->get('domain');
            $integration->store = Integration::TYPE_WOOCOMMERCE;
        }

        // set integration information
        $integration->name = $request->get('name');
        $integration->active = 1;
        $integration->status = 'CONNECTED';
        $integration->save();

        $response->set('model', $integration);

        // return success response;
        return $response->setSuccess();
    }   

    
	/**purpose
	 *   download an integration file
	 * args
	 *   $integration
	 * returns
	 *   downloaded file
	 */
	function download($integration) {

        $response = new Response;
		// create temp filename
		$filename = tempnam('/tmp', 'export.zip');


        $file_contents = file_get_contents(__DIR__ . '/WooCommercePlugin.php');

        $file_contents_filtered = str_replace('{COMPANY_NAME}', env('APP_NAME'), $file_contents);
        $file_contents_filtered = str_replace('{COMPANY_URL}', env('APP_URL'), $file_contents_filtered);

        $auth_key = base64_encode(env('APP_GOAKEY') . ':' . $integration->id);
        $file_contents_filtered = str_replace('{AUTHORIZATION_KEY}', $auth_key, $file_contents_filtered);
        
        $response->set('contents', $file_contents_filtered);

        return $response->setSuccess();

	}

    
	/**purpose
	 *   create curl for Shopify call
	 * args
	 *   api
	 * returns 
	 *   curl object ready for api call
	 */
	private function createCurl($integration, $api) {


        // get shopify shop
        $domain = $integration->store_unique_key;

        // build url
        $url = $domain . $api;

        // init curl
        $this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        // set access token header
		$headers = [
			'Authorization: ' . base64_encode(env('APP_GOAKEY') . ':' . $integration->id)
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
            $response = $this->callGet($integration, '/wp-json/goa/v1/orders', [
                'start' => date('Y-m-d\TH:i:s', strtotime('-8 weeks', time())),
                'end' => date('Y-m-d\TH:i:s', $created_max),
                'take' => 250
            ]);

            if ($response->result != 'success') return;
            
            if (!isset($response->data->orders)) return;


            $filtered_orders = [];

            foreach ($response->data->orders as $incoming_order) {
                if ($incoming_order->type == 'ORDER') {
                    $filtered_orders[] = $incoming_order;
                }
            }

            $total_count = count($filtered_orders);

            foreach ($filtered_orders as $incoming_order) {

                // since id
                $created_max = min(strtotime($incoming_order->created->date), $created_max) - 1;

                // check to see if order is already in database
                $order = Mysql\Order::where([
                    ['reference', '=', $incoming_order->reference],
                    ['store', '=', Integration::TYPE_WOOCOMMERCE],
                    ['store_id', '=', $incoming_order->reference]
                ])->limit(1)->get()->first();

                if (isset($order)) {
                    
                    // check if order is marked completed
                    if ($incoming_order->status == 'completed') {
                        // find the order group
                        $order_group = Mysql\OrderGroup::find($order->order_group_id);
                        if (isset($order_group) && !Validator::validateBoolean($order_group->fulfilled)) {
                            $order_group->fulfilled = 1;
                            $order_group->save();
                        }
                    }
                    else {
                        // find the order group
                        $order_group = Mysql\OrderGroup::find($order->order_group_id);
                        if (isset($order_group) && Validator::validateBoolean($order_group->fulfilled)) {
                            // get user to create order group
                            $user = Mysql\User::find($integration->user_id);
                            $order_group->fulfill($user);
                        }
                    }
                    continue;
                }

                // make sure order is unfulfilled
                if ($incoming_order->status == 'completed') {
                    $integration->resolveFailedOrder($incoming_order->reference);
                }

                // get order products
                $order_products = [];
                $index = 0;
                foreach ($incoming_order->items as $incoming_line_item) {
                    $order_products[] = (object) [
                        'name' => $incoming_line_item->product_name,
                        'sku' => $incoming_line_item->sku,
                        'quantity' => $incoming_line_item->quantity,
                        'store_id' => $index++,
                        'created_at' => $incoming_order->created->date
                    ];
                }

                // get company name
                $company_name = '';

                // get phone
                $phone = '';

                // get phone on address
                $address_phone = '';

                // get the email
                $email = isset($incoming_order->customer_email) ? $incoming_order->customer_email : '';

                // address name
                $address_name = $incoming_order->customer_full_name;

                // build order group from order
                $order_group = (object) [
                    'name' => $incoming_order->customer_full_name,
                    'email' => $email,
                    'company' => $company_name, 
                    'phone' => $phone,
                    'orders' => [
                        (object) [
                            'reference' => $incoming_order->reference,
                            'order_products' => $order_products,
                            'store' => Integration::TYPE_WOOCOMMERCE,
                            'store_id' => $incoming_order->reference,
                            'integration_id' => $integration->id,
                            'created_at' => $incoming_order->created->date
                        ]
                    ],
                    'address' => (object) [
                        'name' => $address_name,
                        'company' => $company_name,
                        'email' => $email,
                        'phone' => $address_phone,
                        'street_1' => $incoming_order->street_1,
                        'street_2' => $incoming_order->street_2,
                        'city' => $incoming_order->city,
                        'state' => $incoming_order->state,
                        'postal' => $incoming_order->postal,
                        'country' => $incoming_order->country
                    ],
                    'created_at' => $incoming_order->created->date
                ];

                // get user to create order group
                $user = Mysql\User::find($integration->user_id);

                // create order group
                $order_group_create_response = Mysql\OrderGroup::create($order_group, $user);

                // check order group create response
                if ($order_group_create_response->isFailure()) {
                    $integration->addFailedOrder($incoming_order->reference, $order_group_create_response->message);
                }
                else { 
                    $integration->resolveFailedOrder($incoming_order->reference);
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
      

        $data = [
            'id' => $order->store_id,
        ];

        /*
        'id' => [
            'location_id' => $location_id,
            'api_version' => '2021-10',
           
            'tracking_number' => $label->tracking,
            'tracking_company' => 'USPS',
            'notify_customer' => true,
            'service' => 'manual',
            'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $label->tracking . '&tABt=false'
        ]*/


        $result = $this->callPost($integration, '/wp-json/goa/v1/order/complete', $data);
    }
}