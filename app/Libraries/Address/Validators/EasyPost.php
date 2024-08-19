<?php
namespace App\Libraries\Address\Validators;

use App\Models;

use App\Http\Controllers\Response;

class EasyPost {

    private $key;
    private $mode;
    
	function __construct() {
		$this->key = env('EASYPOST_KEY', '');
        $this->mode = env('EASYPOST_MODE', '');
	}

    public function validateAddress($address) {
        \EasyPost\EasyPost::setApiKey($this->key);

        try {
            $easy_address = \EasyPost\Address::create_and_verify([
                'mode' => $this->mode,
                'name' => $address->name,
                'company' => $address->company,
                'email' => $address->email,
                'phone' => $address->phone,
                'street1' => $address->street_1,
                'street2' => $address->street_2,
                'city' => $address->city,
                'state' => $address->state,
                'zip' => $address->postal,
                'country' => $address->country
            ]);
        
            $address->street_1 = $easy_address['street1'];
            $address->street_2 = $easy_address['street2'];
            $address->city = $easy_address['city'];
            $address->postal = $easy_address['zip'];
            $address->verified = 1;
            $address->verification_service = 'easypost';
            $address->verification_id = $easy_address['id'];
            
            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Easy Post connection error', 'EASYPOST_CONNECTION_FAILURE');
        }
    }
}