<?php 

namespace App\Libraries;
use App\Http\Controllers\Response;


class Pitney {

	private $key;
	private $secret;
	public $shipper_id;
	private $developer_id;
	private $endpoint;
	private $curl; 

	/**purpose
	 *   constructs class from environment variables
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function __construct() {
		$this->key = env('PITNEY_KEY', '');
		$this->secret = env('PITNEY_SECRET', '');
		$this->shipper_id = env('PITNEY_SHIPPER_ID', '');
		$this->developer_id = env('PITNEY_DEVELOPER_ID', '');
		$this->endpoint = env('PITNEY_ENDPOINT', '');
	}

	/**purpose
	 *   get a access token for pitney
	 * args
	 *   (none)
	 * returns
	 *   access_token
	 *   clientID
	 */
	private function getAccessToken() {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $this->endpoint . '/oauth/token');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Authorization: Basic ' . base64_encode($this->key . ':' . $this->secret),
			'Content-Type: application/x-www-form-urlencoded'
		];   
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($curl, CURLOPT_POST, true);                                     
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'client_credentials']));   
		
		$server_output = curl_exec($curl);
		$server_decoded = json_decode($server_output);

		curl_close($curl);
		return $server_decoded;
	}

	/**purpose
	 *   create curl for Pitney call includes access token
	 * args
	 *   api
	 * returns 
	 *   curl object ready for api call
	 */
	private function createCurl($api, $transaction_id) {

		$access_response = $this->getAccessToken();
		$this->curl = curl_init();

		curl_setopt($this->curl, CURLOPT_URL, $this->endpoint . '/shippingservices' . $api);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $access_response->access_token,
			'X-PB-UnifiedErrorStructure: true'
		];

		//X-PB-Shipper-Rate-Plan
		//X-PB-Integrator-CarrierId
		
		// set transaction id
		if (isset($transaction_id)) {
			$headers[] = 'X-PB-TransactionId: ' . $transaction_id;
		}

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }

	/**purpose
	 *   call a post for pitney that has auth token
	 * args
	 *   api
	 *   data
	 * returns
	 *   decoded response
	 */
	public function callPost($api, $data, $transaction_id = null) {

		$this->createCurl($api, $transaction_id);
		
		$data_string = json_encode(json_decode(json_encode($data)));
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 

		$server_output = curl_exec($this->curl);

		$server_decoded = json_decode($server_output);

		curl_close($this->curl);
		return $server_decoded;
	}


	

}