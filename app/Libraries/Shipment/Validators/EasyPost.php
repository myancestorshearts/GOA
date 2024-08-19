<?php
namespace App\Libraries\Shipment\Validators;

use App\Models\Mysql;
use GoaRestApi;
use App\Common\Functions;

class EasyPost {

    private $key;
    private $mode;
    
	function __construct() {
		$this->key = env('EASYPOST_KEY', '');
        $this->mode = env('EASYPOST_MODE', '');
	}

    public function validateShipment($shipment, $from_address, $to_address, $parcel) {
        \EasyPost\EasyPost::setApiKey($this->key);

        if ($from_address->verification_service != 'easypost') return false;
        if ($to_address->verification_service != 'easypost') return false;
        if ($parcel->verification_service != 'easypost') return false;

        try {
            $easy_shipment = \EasyPost\Shipment::create([
                'reference' => $shipment->reference,
                'to_address' => [
                    'id' => $to_address->verification_id
                ],
                'from_address' => [
                    'id' => $from_address->verification_id
                ],
                'parcel' => [
                    'id' => $parcel->verification_id
                ]
            ]);
            
            $easy_rates = $easy_shipment['rates'];

           // dd($easy_rates);
            $rates = [];
    
            foreach ($easy_rates as $easy_rate) {
                
                $rate = new Mysql\Rate;
                $rate->carrier = $easy_rate['carrier'];
                $rate->service = $easy_rate['service'];
                $rate->rate = $easy_rate['rate'];
                $rate->rate_retail = $easy_rate['retail_rate'];
                $rate->rate_list = $easy_rate['list_rate'];
                $rate->delivery_days = $easy_rate['delivery_days'];
                if (isset($easy_rate['delivery_date']) && $easy_rate['delivery_date'] != null) {
                    $rate->delivery_date = Functions::convertTimeToMysql(strtotime($easy_rate['delivery_date']));
                }
                $rate->delivery_guarentee = $easy_rate['delivery_date_guaranteed'];
                
                $rate->verified = 1;
                $rate->verification_service = 'easypost';
                $rate->verification_id = $easy_rate['id'];
                $rates[] = $rate;
            }
            
            if (count($rates) == 0) return false;

            $shipment->setSubModel('rates', $rates);
            $shipment->verified = 1;
            $shipment->verification_service = 'easypost';
            $shipment->verification_id = $easy_shipment['id'];
            
            return true;
        }
        catch (\EasyPost\Error $ex) {
            return false;
        }
    }
}