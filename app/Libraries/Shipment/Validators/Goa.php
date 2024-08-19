<?php
namespace App\Libraries\Shipment\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;
use App\Models\Dynamo;

use App\Http\Controllers\Response;

class Goa {

    const RATE_DISCOUNT_MAP = [
        'First Class' => 'first_class',
        'Priority Express' => 'priority_express',
        'Priority' => 'priority',
        'Cubic' => 'cubic',
        'Parcel Select' => 'parcel_select'
    ];

    public function validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services) {

        $goa = new GoaClient;
        $response = new Response;
        
        $rate_discounts = Dynamo\RateDiscounts::findOrCreate($shipment->user_id);
        
        $data = [
            'ref' => $shipment->ref,
            'from_address_id' => $from_address->verification_id,
            'to_address' => [
                'name' => $to_address->name,
                'company' => $to_address->company,
                'email' => $to_address->email,
                'phone' => $to_address->phone,
                'street_1' => $to_address->street_1,
                'street_2' => $to_address->street_2,
                'city' => $to_address->city,
                'postal' => $to_address->postal,
                'state' => $to_address->state,
                'country' => $to_address->country
            ],
            'package' => $package->verification_id,
            'weight' => $shipment->weight,
            'services' => $services,
            'ship_date' => $shipment->ship_date,
            'external_user_id' => $shipment->user_id,
            'contents_value' => $shipment->contents_value
        ];

        if (isset($return_address)) {
            $data['return_address'] = [
                'name' => $return_address->name,
                'company' => $return_address->company,
                'email' => $return_address->email,
                'phone' => $return_address->phone,
                'street_1' => $return_address->street_1,
                'street_2' => $return_address->street_2,
                'city' => $return_address->city,
                'postal' => $return_address->postal,
                'state' => $return_address->state,
                'country' => $return_address->country
            ];
        }
        

        try {

            $goa_response = $goa->callPost('/restapi/shipment/create', $data);

            if ($goa_response->result == 'success')
            {
                $rates = [];
            
                foreach ($goa_response->data->model->rates as $goa_rate) {
                    
                    $rate = new Mysql\Rate;
                    $rate->carrier = $goa_rate->carrier;
                    $rate->service = $goa_rate->service;
                    
                    $rate->rate = $rate_discounts->calculateRate('domestic', 'usps', Goa::RATE_DISCOUNT_MAP[$goa_rate->service], (float) $goa_rate->rate_list, $shipment->weight);
                    $rate->rate_retail = $goa_rate->rate_retail;
                    $rate->rate_list = $goa_rate->rate_list;
                    $rate->rate_services = $goa_rate->rate_services;
                    $rate->total_charge = $rate->rate + $rate->rate_services;
                    $rate->total_list = $goa_rate->total_list;
                    $rate->total_retail = $goa_rate->total_retail;
                    if (isset($goa_rate->delivery_days)) $rate->delivery_days = $goa_rate->delivery_days;
                    if (isset($goa_rate->delivery_date)) $rate->delivery_date = $goa_rate->delivery_date;
                    if (isset($goa_rate->delivery_guarantee)) $rate->delivery_guarantee = $goa_rate->delivery_guarantee;
                    $rate->verified = 1;
                    $rate->verification_service = 'usps';
                    $rate->verification_id = $goa_rate->id;

                    $rates[] = $rate;
                }

                $response->set('rates', $rates);
                $shipment->setSubModel('rates', $rates);
                $shipment->verified = 1;
                $shipment->verification_service = 'goa';
                $shipment->verification_id = $goa_response->data->model->id;
            
                return $response->setSuccess();
            }
            return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }


    public function validateShipmentMass($shipments, $from_address, $return_address, $package, $services) {
    
        $goa = new GoaClient;
        $response = new Response;
        
        $rate_discounts = Dynamo\RateDiscounts::findOrCreate($shipments[0]->user_id);
        
        $to_address_ids = [];
        foreach($shipments as $shipment) {
            $to_address_ids[] = $shipment->to_address_id;
        }    
        $to_addresses_mapped = Mysql\Address::getModelsMapped(Mysql\Address::whereIn('id', $to_address_ids)->get());
        

        $data_addresses = [];
        foreach ($to_addresses_mapped as $id => $to_address) {

            // get references
            $reference = '';
            $api_reference = '';

            foreach($shipments as $shipment) 
            {
                if ($shipment->to_address_id == $to_address->id) {
                    $reference = $shipment->reference;
                    $api_reference = $shipment->id;
                }
            }

            $data_addresses[] = [
                'reference' => $reference,
                'api_reference' => $api_reference,
                'name' => $to_address->name,
                'company' => $to_address->company,
                'email' => $to_address->email,
                'phone' => $to_address->phone,
                'street_1' => $to_address->street_1,
                'street_2' => $to_address->street_2,
                'city' => $to_address->city,
                'postal' => $to_address->postal,
                'state' => $to_address->state,
                'country' => $to_address->country
            ];
        }

        $data = [
            'ref' => $shipment->ref,
            'from_address_id' => $from_address->verification_id,
            'to_addresses' => $data_addresses,
            'package' => [
                'name' => $package->name,
                'type' => $package->type,
                'length' => $package->length,
                'width' => $package->width,
                'height' => $package->height
            ],
            'weight' => $shipment->weight,
            'services' => $services,
            'ship_date' => $shipment->ship_date,
            'external_user_id' => $shipment->user_id
        ];


        if (isset($return_address)) {
            $data['return_address'] = [
                'name' => $return_address->name,
                'company' => $return_address->company,
                'email' => $return_address->email,
                'phone' => $return_address->phone,
                'street_1' => $return_address->street_1,
                'street_2' => $return_address->street_2,
                'city' => $return_address->city,
                'postal' => $return_address->postal,
                'state' => $return_address->state,
                'country' => $return_address->country
            ];
        }

        try {

            $goa_response = $goa->callPost('/restapi/shipment/rate/mass', $data);
            if ($goa_response->result == 'success')
            {

                $rates = [];
            
                foreach ($goa_response->data->models as $goa_shipment) {
                    foreach ($goa_shipment->rates as $goa_rate) {
                        
                        $rate = new Mysql\Rate;
                        $rate->carrier = $goa_rate->carrier;
                        $rate->service = $goa_rate->service;
                        $rate->shipment_id = $goa_shipment->api_reference;
                        
                        $rate->rate = $rate_discounts->calculateRate('domestic', 'usps', Goa::RATE_DISCOUNT_MAP[$goa_rate->service], (float) $goa_rate->rate_list, $goa_shipment->weight);
                        $rate->rate_retail = $goa_rate->rate_retail;
                        $rate->rate_list = $goa_rate->rate_list;
                        $rate->rate_services = $goa_rate->rate_services;
                        $rate->total_charge = $rate->rate + $rate->rate_services;
                        $rate->total_list = $goa_rate->total_list;
                        $rate->total_retail = $goa_rate->total_retail;
                        if (isset($goa_rate->delivery_days)) $rate->delivery_days = $goa_rate->delivery_days;
                        if (isset($goa_rate->delivery_date)) $rate->delivery_date = $goa_rate->delivery_date;
                        if (isset($goa_rate->delivery_guarantee)) $rate->delivery_guarantee = $goa_rate->delivery_guarantee;
                        if (isset($goa_rate->delivery_range_min)) $rate->delivery_range_min = $goa_rate->delivery_range_min;
                        if (isset($goa_rate->delivery_range_max)) $rate->delivery_range_max = $goa_rate->delivery_range_max;
                        $rate->verified = 1;
                        $rate->verification_service = 'usps';
                        $rate->verification_id = $goa_rate->id;

                        $rates[] = $rate;
                    }

                    // loop through rates and find cubic set retail to priority retail
                    foreach ($shipments as $shipment) {
                        if ($shipment->id == $goa_shipment->api_reference) {
                            $shipment->verified = 1;
                            $shipment->verification_service = 'goa';
                            $shipment->verification_id = $goa_shipment->id;
                        }
                    }
                }
    
                $response->set('rates', $rates);
                return $response->setSuccess();
            }
            return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}