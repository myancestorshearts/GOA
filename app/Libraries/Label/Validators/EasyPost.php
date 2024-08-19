<?php
namespace App\Libraries\Label\Validators;

use App\Common\Functions;

class EasyPost {

    private $key;
    private $mode;
    
	function __construct() {
		$this->key = env('EASYPOST_KEY', '');
        $this->mode = env('EASYPOST_MODE', '');
	}

    public function validateLabel($label, $shipment, $rate) {
        \EasyPost\EasyPost::setApiKey($this->key);

        if ($shipment->verification_service != 'easypost') return false;
        if ($rate->verification_service != 'easypost') return false;

        try {
            $easy_shipment = \EasyPost\Shipment::retrieve($shipment->verification_id);
            
            $matched_rate = null;
            foreach ($easy_shipment->rates as $easy_rate) {
                if ($easy_rate->id == $rate->verification_id) $matched_rate = $easy_rate;
            }

            if (!isset($matched_rate)) return false;
            if (round($matched_rate['list_rate'], 2) != round($rate->rate_list, 2)) return false;
    
            $easy_shipment->buy(array(
                'rate'      => $matched_rate,
                'insurance' => 100
            ));

            if (!isset($easy_shipment['postage_label'])) return false;

            $easy_postage = $easy_shipment['postage_label'];

        
            $label->tracking = $easy_shipment['tracking_code'];
            $label->url = $easy_postage['label_url'];
            $label->verified = 1;
            $label->verification_service = 'easypost';
            $label->verification_id = $easy_postage['id'];
            
            return true;
        }
        catch (\EasyPost\Error $ex) {
            return false;
        }
    }
}