<?php 

namespace App\Integrations;
use App\Http\Controllers\Response;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Common\Functions;
use App\Common\Validator;

use ApiAuth;

class ShipHero {


    const SHIPPING_METHOD_USPS_PRIORITY = 'USPS_PRIORITY';
    const SHIPPING_METHOD_USPS_PRIORITY_EXPRESS = 'USPS_PRIORITY_EXPRESS';
    const SHIPPING_METHOD_USPS_FIRST_CLASS = 'USPS_FIRST_CLASS';
    const SHIPPING_METHOD_USPS_PARCEL_SELECT = 'USPS_PARCEL_SELECT';

    const RATE_MAP = [
        ShipHero::SHIPPING_METHOD_USPS_PRIORITY => 'Priority',
        ShipHero::SHIPPING_METHOD_USPS_PRIORITY_EXPRESS => 'Priority Express',
        ShipHero::SHIPPING_METHOD_USPS_FIRST_CLASS => 'First Class',
        ShipHero::SHIPPING_METHOD_USPS_PARCEL_SELECT => 'Parcel Select'
    ];

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
        if (!$response->hasRequired($request, ['account_id'])) return $response->setFailure('Missing required fields');

        $user = ApiAuth::user();

        // check if integration exists already
        $integration = Mysql\Integration::where([
            ['user_id', '=', $user->id],
            ['store_unique_key', '=', $request->get('account_id')],
            ['store', '=', Integration::TYPE_SHIPHERO]
        ])->limit(1)->get()->first();

        // create integration
        if (!isset($integration)) {
            $integration = new Mysql\Integration;
            $integration->user_id = $user->id;
            $integration->store_unique_key = $request->get('account_id');
            $integration->store = Integration::TYPE_SHIPHERO;
        }

        // set integration information
        $integration->name = $request->get('name');
        $integration->active = 1;
        $integration->status = 'CONNECTED';
        $integration->save();


        // set response model
        $response->set('model', $integration);

        // send email to client specifing the things they need to do in order to get it to work on shipheros side

        // return success response;
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
	 *   purchase a label
	 * args
	 *   $integration
	 *   $request
	 * returns
	 *   label info for integration
	 */
    public function purchase($integration, $request) {

        // create a shiphero response
        $response = new ShipHeroResponse;

        
        // check user
        $user = Mysql\User::find($integration->user_id);
        if (!isset($user)) {
            $response->code = 500;
            $response->error = 'Invalid connection';
            return $response;
        }
        ApiAuth::setUser($user);

        // check to make sure integration is still valid
        if (!Validator::validateBoolean($integration->active)) {
            $response->code = 500;
            $response->error = 'Invalid connection';
            return $response;
        }


        // validate call is from shiphero

        // first validate the shipping method method
        $validated_shipping_method = Validator::validateEnum($request->get('shipping_method'), ['enums' => [
			ShipHero::SHIPPING_METHOD_USPS_PRIORITY,
			ShipHero::SHIPPING_METHOD_USPS_PRIORITY_EXPRESS,
			ShipHero::SHIPPING_METHOD_USPS_FIRST_CLASS,
			ShipHero::SHIPPING_METHOD_USPS_PARCEL_SELECT
		]]);
        if (!isset($validated_shipping_method)) {
            $response->code = 500;
            $response->error = 'Invalid shipping method';
            return $response;
        }

        // check the account id
        if ($request->get('account_id') != $integration->store_unique_key) {
            $response->code = 500;
            $response->error = 'Invalid account id';
            return $response;
        }

        // from address
        $from_address = $request->get('from_address');
        if (!isset($from_address)) {
            $response->code = 500;
            $response->error = 'Invalid from address';
            return $response;
        }
    
        // to address
        $to_address = $request->get('to_address');
        if (!isset($to_address)) {
            $response->code = 500;
            $response->error = 'Invalid to address';
            return $response;
        }

        // validate packages
        $packages = $request->get('packages');
        if (!isset($packages)) {
            $response->code = 500;
            $response->error = 'Invalid packages';
            return $response;
        }
        if (!is_array($packages)) {
            $response->code = 500;
            $response->error = 'Invalid packages - must be an array';
            return $response;
        }
        
        // create shipment
        $internal_shipments = [];
        foreach($packages as $package) {
            $shipment_args = (object) [
                'from_address_override' => [
                    'name' => isset($from_address['name']) ? $from_address['name'] : '',
                    'company' => isset($from_address['company_name']) ? $from_address['company_name'] : null,
                    'street_1' => isset($from_address['address_1']) ? $from_address['address_1'] : '',
                    'street_2' => isset($from_address['address_2']) ? $from_address['address_2'] : '',
                    'email' => isset($from_address['email']) ? $from_address['email'] : null,
                    'city' => isset($from_address['city']) ? $from_address['city'] : '',
                    'state' => isset($from_address['state']) ? $from_address['state'] : '',
                    'postal' => isset($from_address['zip']) ? $from_address['zip'] : '',
                    'country' => isset($from_address['country']) ? $from_address['country'] : ''
                ],
                'to_address' => (object) [
                    'name' => isset($to_address['name']) ? $to_address['name'] : '',
                    'company' => isset($to_address['company_name']) ? $to_address['company_name'] : null,
                    'street_1' => isset($to_address['address_1']) ? $to_address['address_1'] : '',
                    'street_2' => isset($to_address['address_2']) ? $to_address['address_2'] : '',
                    'email' => isset($to_address['email']) ? $to_address['email'] : null,
                    'city' => isset($to_address['city']) ? $to_address['city'] : '',
                    'state' => isset($to_address['state']) ? $to_address['state'] : '',
                    'postal' => isset($to_address['zip']) ? $to_address['zip'] : '',
                    'country' => isset($to_address['country']) ? $to_address['country'] : ''
                ],
                'weight' => isset($package['weight_in_oz']) ? $package['weight_in_oz'] : '',
                'package' => (object) [
                    'type' => 'Parcel',
                    'width' => isset($package['width']) ? $package['width'] : '',
                    'height' => isset($package['height']) ? $package['height'] : '',
                    'length' => isset($package['length']) ? $package['length'] : ''
                ],
                'reference' => $request->get('order_number'),
                'services' => [],
                'items' => [],
                'customs_content_type' => '',
                'customs_comments' => ''
            ];

            // check line items
            if (isset($package['line_items'])) {

                $line_items = $package['line_items'];
                if (is_array($line_items)) {
                    foreach($line_items as $line_item) {
                        if (isset($line_item['ignore_on_customs'])) {
                            if (Validator::validateBoolean($line_item['ignore_on_customs'])) continue;
                        }

                        $shipment_args->items[] = (object) [
                            'name' => isset($line_item['product_name']) ? $line_item['product_name'] : '',
                            'quantity' => isset($line_item['quantity']) ? $line_item['quantity'] : '',
                            'value' => isset($line_item['price']) ? $line_item['price'] : '',
                            'weight' => isset($line_item['weight']) ? $line_item['weight'] : '',
                            'hs_tarrif_number' => isset($line_item['tariff_code']) ? $line_item['tariff_code'] : ''
                        ];
                    }
                }
            }

            $internal_shipment = Mysql\Shipment::create((object) $shipment_args, ApiAuth::user());

            if ($internal_shipment->result == Response::RESULT_FAILURE) {
                $response->code = 500;
                $response->error = $internal_shipment->message;
                return $response;
            }

            $internal_shipments[] = $internal_shipment;

        }

        // find correct rate

        $rates = [];
        foreach ($internal_shipments as $internal_shipment) {

            $matched_rate = null;
            foreach($internal_shipment->get('model')->rates as $rate) {
                if ($rate->carrier == 'USPS' && $rate->service == ShipHero::RATE_MAP[$validated_shipping_method]) {
                    $matched_rate = $rate;
                }
            }

            // check for cubic
            if ($validated_shipping_method == ShipHero::SHIPPING_METHOD_USPS_PRIORITY) {
                foreach($internal_shipment->get('model')->rates as $rate) {
                    if ($rate->carrier == 'USPS' && $rate->service == 'Cubic') {
                        $matched_rate = $rate;
                    }
                }
            }

            if (!isset($matched_rate)) {
                $response->code = 500;
                $response->error = 'Could not find a rate that matches dimensions or weight';
                return $response;
            }

            $rates[$internal_shipment->get('model')->id] = $matched_rate;
        }
        
        // purchase shipment
        $labels = [];
        foreach($rates as $shipment_id => $rate) {
            
            $label_response = Mysql\Label::create((object) [
                'shipment_id' => $shipment_id,
                'rate_id' => $rate->id,
                'file_type' => Mysql\Label::FILE_TYPE_PNG
            ], $user);
            
            if ($label_response->result == Response::RESULT_FAILURE) {

                // cancel any labels that were just purchased during request previously /***** IMPORTANT***** */

                $response->code = 500;
                $response->error = 'Failed to purchase label';
                return $response;
            }

            $label = $label_response->get('model');
            $labels[] = $label;
        }


        $external_labels = [];

        foreach ($labels as $label) {
            $external_labels[] = [
                'tracking_number' => $label->tracking,
                'tracking_url' => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=2&text28777=&tLabels=' . $label->tracking,
                'label' => $label->label_url,
                'shipping_carrier' => 'usps'
            ];
        }

        $response->packages = $external_labels;


        return $response;
    }


}


class ShipHeroResponse {
    public $code = 200;
    public $error = '';
    public $packages = [];

    public function json() {
        return response()->json($this);
    }
}