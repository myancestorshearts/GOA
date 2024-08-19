<?php

namespace App\Libraries\PickupCancel;

use ApiAuth;
use App\Models\Mysql;
use App\Libraries;
use DB;

class Validator {

    private $verification_service;
    
	function __construct() {
        $this->verification_service = $this->getVerificationService();
	}

    private function getVerificationService($service = null) {

        $label_service = isset($service) ? $service : env('LABEL_SERVICE');
        switch($label_service) {
            case 'usps': 
                return new Validators\Usps;
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
    public function cancelModel($model) {
        return $this->verification_service->cancelModel($model);
    }
}