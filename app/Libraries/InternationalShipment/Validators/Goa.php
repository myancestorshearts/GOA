<?php
namespace App\Libraries\InternationalShipment\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services) {

        $goa = new GoaClient;
        $response = new Response;
        
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
            'package_id' => $package->verification_id,
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

            $goa_response = $goa->callPost('/restapi/shipment/create', $data);

            if ($goa_response->result == 'success')
            {
                $rates = [];
            
                foreach ($goa_response->data->model->rates as $goa_rate) {
                    
                    $rate = new Mysql\Rate;
                    $rate->carrier = $goa_rate->carrier;
                    $rate->service = $goa_rate->service;
                    $rate->rate = $goa_rate->rate;
                    $rate->rate_retail = $goa_rate->rate_retail;
                    $rate->rate_list = $goa_rate->rate_list;
                    $rate->rate_services = $goa_rate->rate_services;
                    $rate->total_charge = $goa_rate->total_charge;
                    $rate->total_list = $goa_rate->total_list;
                    $rate->total_retail = $goa_rate->total_retail;
                    if (isset($goa_rate->delivery_days)) $rate->delivery_days = $goa_rate->delivery_days;
                    if (isset($goa_rate->delivery_date)) $rate->delivery_date = $goa_rate->delivery_date;
                    if (isset($goa_rate->delivery_guarentee)) $rate->delivery_guarentee = $goa_rate->delivery_guarentee;
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
}