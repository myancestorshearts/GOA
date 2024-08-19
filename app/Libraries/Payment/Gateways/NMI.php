<?php

namespace App\Libraries\Payment\Gateways;
use App\Http\Controllers\Response;
use App\Common\Functions;

class NMI {

	private $key;
	private $endpoint;

	private $curl; 

	function __construct() {
		$this->key = env('NMI_KEY', '');
		$this->endpoint = env('NMI_ENDPOINT', '');
	}

	private function createCurl($api, $data) {

		$this->curl = curl_init();
		
		curl_setopt($this->curl, CURLOPT_URL, $this->endpoint . $api);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data)); 
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	}

	private function callPost($api, $data) {
		$this->createCurl($api, $data);

		$server_output = curl_exec($this->curl);
		$server_decoded = $this->parseQS($server_output);
		curl_close($this->curl);
		return $server_decoded;
	}

	private function parseQS($query_string){

		$post_array = [];
		$query_array = explode('&', $query_string);
		for($i = 0; $i < count($query_array); $i++) {
			$element = explode('=', $query_array[$i]);
			$post_array[$element[0]] = htmlspecialchars(urldecode($element[1]));
		}
		return (object) $post_array;
	}

	public function saveCard($name, $card, $expiration_month, $expiration_year, $zipcode) {
		$api = '/api/transact.php';

		$data = [
			'customer_vault' => 'add_customer',
			'security_key' => $this->key,
			'ccnumber' => $card,
			'ccexp' => $expiration_month . $expiration_year,
			'zip' => $zipcode,
			'first_name' => $name
		];
		$save_response = $this->callPost($api, $data);	

		$response = new Response;
		if (isset($save_response->customer_vault_id)) {
			$response->set('token', $save_response->customer_vault_id);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure($save_response->responsetext);
		}		
	}

	
	public function saveACH($name, $routing, $account, $zipcode, $type) {
		$api = '/api/transact.php';
		$data = [
			'customer_vault' => 'add_customer',
			'security_key' => $this->key,
			'checkname' => $name,
			'checkaba' => $routing,
			'checkaccount' => $account,
			'account_holder_type' => $type == 'X' ? 'business' : 'personal',
			'account_type' => ($type == 'X' || $type == 'C') ? 'checking' : 'savings',
			'zip' => $zipcode
		];
		$save_response = $this->callPost($api, $data);	

		$response = new Response;
		if (isset($save_response->customer_vault_id)) {
			$response->set('token', $save_response->customer_vault_id);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure($save_response->responsetext);
		}
	}

	function process($transaction, $payment_method, $amount, $auto = false) {
		$api = '/api/transact.php';
		$data = [
			'customer_vault_id' => decrypt($payment_method->token),
			'security_key' => $this->key,
			'amount' => $amount,
			'currency' => 'USD',
			'initated_by' => $auto ? 'merchant' : 'customer'
		];

		$process_response = $this->callPost($api, $data);
		
		$response = new Response;
		
		if (isset($process_response->authcode) && !Functions::isEmpty($process_response->authcode)) {
			$response->set('auth_code', $process_response->authcode);
			$response->set('auth_reference', $process_response->transactionid);
			return $response->setSuccess();
		}
		else if (isset($process_response->responsetext) && $process_response->responsetext == 'APPROVED') 
		{
			$response->set('auth_code', '');
			$response->set('auth_reference', $process_response->transactionid);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure($process_response->responsetext);
		}		

	}
}
