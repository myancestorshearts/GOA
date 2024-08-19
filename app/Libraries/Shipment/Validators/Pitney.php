<?php
namespace App\Libraries\Shipment\Validators;

use App\Models\Mysql;
use GoaRestApi;
use App\Common\Functions;
use App\Http\Controllers\Response;

use App\Libraries\Pitney as PitneyClient;

use Exception;

class Pitney {
    
    const SERVICE_MAP = [
        'FCM' => 'First Class',
        'EM' => 'Priority Express',
        'PRCLSEL' => 'Parcel Select',
        /*'CUBIC' => 'Cubic',*/
        'PM' => 'Priority'
    ];

    const PACKAGE_MAP = [
        'SmallFlatRateEnvelope' => 'FRE',
        'FlatRateLegalEnvelope' => 'LGLFRENV',
        'FlatRatePaddedEnvelope' => 'PFRENV',
        'FlatRateEnvelope' => 'FRE',
        'Parcel' => 'PKG',
        'SoftPack' => 'SOFTPACK',
        'SmallFlatRateBox' => 'SFRB',
        'MediumFlatRateBox' => 'FRB',
        'LargeFlatRateBox' => 'LFRB',
        'RegionalRateBoxA' => 'RBA',
        'RegionalRateBoxB' => 'RBB'
    ];

    public function validateShipment($shipment, $from_address, $to_address, $package) {
        
        //initialize response
        $goa_response = new Response;

        // initialize pitney client
        $pitney_client = new PitneyClient;

        // set from address
        $from_address = [
            'postalCode' => $from_address->postal,
            'countryCode' => $from_address->country,
            'addressLines' => [
                $from_address->street_1
            ]
        ];

        // set to address
        $to_address = [
            'name' => $to_address->name,
            'postalCode' => $to_address->postal,
            'countryCode' => $to_address->country,
            'addressLines' => [
                $to_address->street_1
            ],
            'cityTown' => $to_address->city,
            'stateProvince' => $to_address->state
        ];

        // set parcel
        $pitney_parcel = [
            'weight' => [
                'unitOfMeasurement' => 'OZ',
                'weight' => (float) $shipment->weight
            ],
            'dimension' => [
                'unitOfMeasurement' => 'IN',
                'length' => (float) $package->length,
                'width' => (float) $package->width,
                'height' => (float) $package->height
            ]
        ];

        // set rates
        $rates = [
            [
                'carrier' => 'USPS',
                'parcelType' => Pitney::PACKAGE_MAP[$package->type]
            ]
        ];

        //set shipment options
        $shipment_options = [
            [
                'name' => 'SHIPPER_ID',
                'value' => $pitney_client->shipper_id
            ]
        ];

        // create data to pass to api
        $data = [
            'fromAddress' => $from_address,
            'toAddress' => $to_address,
            'parcel' => $pitney_parcel,
            'rates' => $rates,
            'shipmentOptions' => $shipment_options
        ];

        // request response
        try {
            $response = $pitney_client->callPost('/v1/rates', $data);

            if (!isset($response->rates) && count($response->rates) == 0) return false;
        }
        catch (Exception $ex) {
            return false;
        }

        // loop through rates and create them. 
        $rates = [];
        foreach (Pitney::SERVICE_MAP as $pitney_service => $goa_service) {

            // set null retail and cpp pricing
            $retail_price = null;
            $com_plus_price = null;

            // loop through available options and get each service retail and commercial
            foreach($response->rates as $rate) {
                if ($rate->serviceId == $pitney_service) {
                    if ($rate->rateTypeId == 'RETAIL') {
                        $retail_price = $rate->totalCarrierCharge;
                        if (!isset($com_plus_price)) $com_plus_price = $rate->totalCarrierCharge; 
                    }
                    else if ($rate->rateTypeId == 'COMMERCIAL_BASE' || $rate->rateTypeId == 'COMMERCIAL') {
                        if (!isset($retail_price)) $retail_price = $rate->totalCarrierCharge;
                        if (!isset($com_plus_price)) $com_plus_price = $rate->totalCarrierCharge; 
                    }
                    else if ($rate->rateTypeId == 'COMMERCIAL_PLUS') {
                        if (!isset($retail_price)) $retail_price = $rate->totalCarrierCharge;
                        $com_plus_price = $rate->totalCarrierCharge; 
                    }
                }
            }

            // if the prices are set then we create a rate
            if (isset($retail_price) && isset($com_plus_price))  {

                $rate = new Mysql\Rate;
                $rate->carrier = 'usps';
                $rate->service = $goa_service;
                $rate->rate = $com_plus_price;
                $rate->rate_retail = $retail_price;
                $rate->rate_list = $com_plus_price;
                $rate->total_charge = $rate->rate;
                $rate->rate_services = 0;
                $rate->total_retail = $rate->rate_retail;
                $rate->total_list = $rate->rate_list;

                $rate->delivery_guarentee = false;
                
                $rate->verified = 1;
                $rate->verification_service = 'pitney';
                $rate->verification_id = '';
                $rates[] = $rate;
            }
        }
        
        if (count($rates) == 0) return false;

        $goa_response->set('rates', $rates);
        $shipment->setSubModel('rates', $rates);
        $shipment->verified = 1;
        $shipment->verification_service = 'pitney';
        $shipment->verification_id = '';
        
        return $goa_response->setSuccess();
    }
}