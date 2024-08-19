<?php
namespace App\Libraries\InternationalLabel\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateLabel($label, $shipment, $rate, $customs) {

        $goa = new GoaClient;
        $response = new Response;
        
        $data = [
            'shipment_id' => $shipment->verification_id,
            'rate_id' => $rate->verification_id
        ];

        try {
            $goa_response = $goa->callPost('/restapi/label/purchase', $data);
            if ($goa_response->result == 'success')
            {
                $label->tracking = $goa_response->data->model->tracking;
                $label->url = $goa_response->data->model->url;
                $label->verified = 1;
                $label->rdc = $goa_response->data->model->rdc;
                $label->route = $goa_response->data->model->route;
                $label->verification_service = 'usps';
                $label->verification_id = $goa_response->data->model->id;
            
                $cost = $goa_response->data->wallet_transaction->amount * -1;
                $response->set('cost', $cost);
                
                return $response->setSuccess();
            }
            else return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers ' . $ex->getMessage(), 'GOA_CONNECTION_FAILURE');
        }
    }
}