<?php
namespace App\Libraries\ReturnLabel\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateReturn($return_label) {

        $goa = new GoaClient;
        $response = new Response;

        $label = Mysql\Label::find($return_label->label_id);
        if (!isset($label)) return $response->setFailure('Unable to find associated label', 'INTERNAL_ERROR');

        $data = [
            'label_id' => $label->verification_id,
            'external_user_id' => $label->user_id
        ];
        try {
            $goa_response = $goa->callPost('/restapi/label/return', $data);
            if ($goa_response->result == 'success')
            {
                $return_label->tracking = $goa_response->data->model->tracking;
                $return_label->url = $goa_response->data->model->url;
                $return_label->verified = 1;
                $return_label->rdc = $goa_response->data->model->rdc;
                $return_label->route = $goa_response->data->model->route;
                $return_label->zone = $goa_response->data->model->zone;
                $return_label->verification_service = 'usps';
                $return_label->verification_id = $goa_response->data->model->id;
            
                return $response->setSuccess();
            }
            else return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers ' . $ex->getMessage(), 'GOA_CONNECTION_FAILURE');
        }
    }
}