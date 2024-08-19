<?php

namespace App\Libraries\InternationalShipment;


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

    public function validateShipmentModel($shipment, $from_address, $to_address, $return_address, $package, $services) {
        $shipment->international = 1;
        return $this->verification_service->validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services);
    }
}