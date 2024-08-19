<?php
namespace App\Libraries\Address\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Http\Controllers\Response;

class Goa {


    public function validateAddress($address) {

        $response = new Response;

        $goa = new GoaClient;
        
        $data = [
            'name' => $address->name,
            'company' => $address->company,
            'email' => $address->email,
            'phone' => $address->phone,
            'street_1' => $address->street_1,
            'street_2' => $address->street_2,
            'city' => $address->city,
            'state' => $address->state,
            'postal' => $address->postal,
            'country' => $address->country,
            'from' => $address->from
        ];

        try {

            $goa_response = $goa->callPost('/restapi/address/create', $data);

            if ($goa_response->result == Response::RESULT_SUCCESS) {
                $address->street_1 = $goa_response->data->model->street_1;
                $address->street_2 = $goa_response->data->model->street_2;
                $address->city = $goa_response->data->model->city;
                $address->postal = $goa_response->data->model->postal;
                $address->verified = $goa_response->data->model->verified;
                $address->verification_service = 'goa';
                $address->verification_id = $goa_response->data->model->id;
                
                return $response->setSuccess();
            }

            return $response->setFailure('Invalid address', 'INVALID_ADDRESS');
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}