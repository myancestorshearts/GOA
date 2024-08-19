<?php

namespace App\Libraries\InternationalRefund;

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
                return new Validators\Goa;
        }
    }

    public function validateRefundModel($label) { 
        return $this->verification_service->validateRefundModel($label);
    }
}