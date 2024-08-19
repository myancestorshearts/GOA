<?php

namespace App\Libraries\Shipment;

use ApiAuth;
use App\Models\Mysql;
use App\Libraries;

class ShipmentValidator {

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

    public function validateShipmentModel($shipment, $from_address, $to_address, $return_address, $package, $services) {
        return $this->verification_service->validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services);
    }
    
    public function validateShipmentModelMass($shipment, $from_address, $return_address, $package, $services) {
        return $this->verification_service->validateShipmentMass($shipment, $from_address, $return_address, $package, $services);
    }

}