<?php

namespace App\Libraries\Payment\Gateways;
use App\Http\Controllers\Response;


class Chase {

	private $username;
	private $password;
	private $merchant;
	private $endpoint;

	private $curl; 


	function __construct() {
		$this->username = env('CHASE_USERNAME', '');
		$this->password = env('CHASE_PASSWORD', '');
		$this->merchant = env('CHASE_MERCHANT', '');
		$this->endpoint = env('CHASE_ENDPOINT', '');
	}

	private function createCurl($api) {

		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->endpoint . $api);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

		$headers = [
			'Content-Type: application/json',
			'OrbitalConnectionUsername: ' . $this->username,
			'OrbitalConnectionPassword: ' . $this->password,
			'MerchantID: ' . $this->merchant
		];

		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
	}

	private function callPost($api, $data) {
		$this->createCurl($api);
		$data_string = json_encode(json_decode(json_encode($data)));
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data_string); 

		$server_output = curl_exec($this->curl);
		$server_decoded = json_decode($server_output);
		curl_close($this->curl);
		return $server_decoded;
	}

	public function saveCard($name, $card, $expiration_month, $expiration_year, $zipcode) {
		$api = '/gwapi/v3/gateway/profile/';
		$data = [
			'version'=> '4.3',
			'merchant'=> [
				'bin'=> '000001',
				'terminalID' => '001'
			],
			'order'=> [
				'customerProfileOrderOverideInd'=> 'NO',
				'customerProfileFromOrderInd'=> 'A',
				'orderDefaultAmount'=> '1'
			],
			'paymentInstrument'=> [
				'customerAccountType'=> 'CC',
				'card'=> [
					'ccAccountNum'=> $card,
					'ccExp'=> '20' . $expiration_year . $expiration_month
				]
			],
			'profile'=> [
				'customerName'=> $name,
				//'customerAddress1'=> '123 Main St',
				//'customerCity'=> 'Tampa',
				//'customerState'=> 'FL',
				'customerZIP'=> $zipcode,
				//'customerEmail'=> 'email@email.com',
				//'customerPhone'=> '1234561234',
				'customerCountryCode'=> 'US'
			]
		];

		$chase_response = $this->callPost($api, $data);	

		$response = new Response;
		if (isset($chase_response->profile)) {
			$response->set('token', $chase_response->profile->customerRefNum);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure($chase_response->procStatusMessage);
		}		
	}

	
	public function saveACH($name, $routing, $account, $zipcode, $type) {
		$api = '/gwapi/v3/gateway/profile/';
		$data = [
			'version'=> '4.3',
			'merchant'=> [
				'bin'=> '000001'
			],
			'order'=> [
				'customerProfileOrderOverideInd'=> 'NO',
				'customerProfileFromOrderInd'=> 'A',
				'orderDefaultAmount'=> '1'
			],
			'paymentInstrument'=> [
				'customerAccountType'=> 'EC',
				'ecp'=> [
					'ecpCheckRT'=> $routing,
					'ecpCheckDDA'=> $account,
					'ecpBankAcctType' => $type
				]
			],
			'profile'=> [
				'customerName'=> $name,
				//'customerAddress1'=> '123 Main St',
				//'customerCity'=> 'Tampa',
				//'customerState'=> 'FL',
				'customerZIP'=> $zipcode,
				//'customerEmail'=> 'email@email.com',
				//'customerPhone'=> '1234561234',
				'customerCountryCode'=> 'US'
			]
		];
		$chase_response = $this->callPost($api, $data);
		$response = new Response;
		if (isset($chase_response->profile)) {
			$response->set('token', $chase_response->profile->customerRefNum);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure($chase_response->procStatusMessage);
		}		
	}

	function process($transaction, $payment_method, $amount, $auto = false) {
		$api = '/gwapi/v4/gateway/payments/';
		$data = [
			'version'=> '4.3',
			'transType' => 'AC',
			'merchant'=> [
				'bin'=> '000001',
				'terminalID' => '001'
			],
			'order'=> [
				'orderID'=> 'goa',
				'comments'=> 'Order Refill',
				'amount' => (int) round($amount * 100, 0),
				'industryType' => 'EC'
			],
			'paymentInstrument'=> [
				'useProfile' => [
					'useCustomerRefNum'=> decrypt($payment_method->token)
				]
			]
		];
		
		$chase_response = $this->callPost($api, $data);

		$response = new Response;

		if (isset($chase_response->order) && $chase_response->order->status->approvalStatus == '1') {
			$response->set('auth_code', $chase_response->order->status->authorizationCode);
			$response->set('auth_reference', $chase_response->order->txRefNum);
			return $response->setSuccess();
		}
		else {
			return $response->setFailure(isset($chase_response->order) ? $chase_response->order->status->procStatusMessage : $chase_response->procStatusMessage);
		}		

	}
}
