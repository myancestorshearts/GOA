<?php

namespace App\Libraries\ReturnLabel;

use ApiAuth;
use App\Models\Mysql;

class Validator {

    private $verification_service;
    
	function __construct() {
        $this->verification_service = $this->getVerificationService();
	}

    private function getVerificationService($service = null) {

        $label_service = isset($service) ? $service : env('LABEL_SERVICE');

        switch($label_service) {
            case 'goa': 
                return new Validators\Goa;
            case 'usps':
                return new Validators\Usps;
            default: 
                return new Validators\EasyPost;
        }
    }

    public function validateReturnModel($return_label) {
        return $this->verification_service->validateReturn($return_label);
    }

}