<?php

namespace App\Libraries\Label;

use ApiAuth;
use App\Models\Mysql;

class LabelValidator {

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
                return new Validators\Pitney;
            case 'usps': 
                return new Validators\Usps;
            default: 
                return new Validators\EasyPost;
        }
    }

    
    public function validateReturnLabelModel($return_label) { 
        return $this->verification_service->validateReturnLabel($return_label);
    }

    public function validateLabelModel($label, $shipment, $rate, $customs) {
        return $this->verification_service->validateLabel($label, $shipment, $rate, $customs);
    }



}