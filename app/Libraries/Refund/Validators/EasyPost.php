<?php
namespace App\Libraries\Refund\Validators;

use App\Models;
use GoaRestApi;
use App\Common\Functions;

class EasyPost {

    private $key;
    private $mode;
    
	function __construct() {
		$this->key = env('EASYPOST_KEY', '');
        $this->mode = env('EASYPOST_MODE', '');
	}

    public function validateRefund($label) {

        \EasyPost\EasyPost::setApiKey($this->key);

        $shipment = Models\Shipment::find($label->shipment_id);
        if (!isset($shipment)) return null;

        if ($shipment->verification_service != 'easypost') return false;
        if ($label->verification_service != 'easypost') return false;
        
        try {
            $easy_shipment = \EasyPost\Shipment::retrieve($shipment->verification_id);
            $easy_shipment->refund();

            if (!isset($easy_shipment['refund_status'])) return false;
            
            return true;
        }
        catch (\EasyPost\Error $ex) {
            return false;
        }
    }
}