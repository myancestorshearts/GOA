<?php
namespace App\Libraries\Refund\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateRefundModel($label) {

        $goa = new GoaClient;
        $response = new Response;
        
        $data = [
            'label_id' => $label->verification_id
        ];

        try {
            $goa_response = $goa->callPost('/restapi/label/refund', $data);
            if ($goa_response->result == 'success')
            {
                return $response->setSuccess();
            }
            else return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers ' . $ex->getMessage(), 'GOA_CONNECTION_FAILURE');
        }
    }
}