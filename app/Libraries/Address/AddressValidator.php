<?php

namespace App\Libraries\Address;

use ApiAuth;
use App\Models\Mysql;

use App\Http\Controllers\Response;

class AddressValidator {

    private $verification_service;
    
	function __construct() {
        $this->verification_service = $this->getVerificationService();
	}


    private function getVerificationService($service = null) {

        $label_service = isset($service) ? $service : env('LABEL_SERVICE');
        switch($label_service) {
            case 'goa': 
                return new Validators\Goa;
            case 'pitney': 
                return new Validators\Usps;
            case 'usps': 
                return new Validators\Usps;
            default: 
                return new Validators\EasyPost;
        }
    }

    public function validateAddressModel($address) {
        return $this->verification_service->validateAddress($address);
    }

    /*
    public function validateAddress($name, $company, $email, $phone, $street_1, $street_2, $city, $state, $postal, $country) {    
    
        $address = new Mysql\Address;
        $address->user_id = ApiAuth::user()->id;
        $address->api_key_id = ApiAuth::apiKeyId();
        $address->name = $name;
        $address->company = $company;
        $address->email = $email;
        $address->phone = $phone;
        $address->street_1 = $street_1;
        $address->street_2 = $street_2;
        $address->city = $city;
        $address->state = $state;
        $address->postal = $postal;
        $address->country = $country;
        $address->default = 0;
        $address->saved = 0;
        $address->active = 0;

        $verification_response = $this->verification_service->validateAddress($address);
        if ($verification_response->result == Response::RESULT_FAILURE) return null;
        
        $address->save();

        return $address;
    }*/
}