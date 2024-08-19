<?php

namespace App\Libraries\ScanForm;

use ApiAuth;
use App\Models\Mysql;
use App\Libraries;

class ScanFormValidator {

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
     *   validate a scan form model
     * args
     *   scan_form
     *   from_address
     *   labels
     * returns
     *   result response
     */
    public function validateScanFormModel($scan_form, $from_address, $labels) {
        return $this->verification_service->validateScanForm($scan_form, $from_address, $labels);
    }

    /**purpose
     *   validate a scan form 
     * args
     *   from_address
     *   labels
     * returns
     *   scan_form
     */
   /* public function validateScanForm($from_address, $labels) {

        // add validations here as well if we want to use this in a different place in the platform.
        $scan_form = new Mysql\ScanForm;
        $scan_form->user_id = ApiAuth::user()->id;
        $scan_form->api_key_id = ApiAuth::apiKeyId();

        $scan_form->from_address_id = $from_address->id;
        $count = count($labels);
        $scan_form->label_count = $count;

        // get all shipment ids associated with labels
        $shipment_ids = [];
        foreach ($labels as $label) {
            $shipment_ids[] = $label->shipment_id;
        }

        // get all shipments associated with ids
        $shipments = Mysql\Base::getModelsMapped(Mysql\Shipment::whereIn('id', $shipment_ids)->get());
        
        // check all labels
        foreach ($labels as $label) {
            // check label is users
            if ($label->user_id != $scan_form->user_id) return null;

            // check label is users
            if (isset($scan_form->scan_form_id)) return null;

            // get shipment
            if (!isset($shipments[$label->shipment_id])) return null;
            
            // get shipment
            $shipment = $shipments[$label->shipment_id];

            // check address matches scan forms
            if ($shipment->from_address_id != $scan_form->from_address_id) return null;

        }

        $result = $this->verification_service->validateScanForm($scan_form, $from_address, $labels);
        if (!$result) return null;
    
    

        return $scan_form;
    }*/
}