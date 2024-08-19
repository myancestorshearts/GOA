<?php

namespace App\Libraries\PickupAvailability;

use ApiAuth;
use App\Models\Mysql;
use App\Libraries;
use DB;

class PickupAvailabilityValidator {

    private $verification_service;
    
	function __construct() {
        $this->verification_service = $this->getVerificationService();
	}

    private function getVerificationService($service = null) {

        $label_service = isset($service) ? $service : env('LABEL_SERVICE');
        switch($label_service) {
            case 'usps': 
                return new Validators\Usps;
            case 'goa': 
                return new Validators\Goa;
            default: 
                //return new Validators\EasyPost;
        }
    }

    /**purpose
     *   validate a scan form 
     * args
     *   from_address
     *   labels
     * returns
     *   scan_form
     */
    public function validatePickupAvailability($from_address, $time) {
        return $this->verification_service->validatePickupAvailability($from_address, $time);
    }
}