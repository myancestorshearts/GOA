<?php
namespace App\Libraries\Package\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;


use App\Http\Controllers\Response;

class Goa {
    
    public function validatePackage($package) {
        $goa = new GoaClient;
        $response = new Response;
        
        $data = [
            'carrier' => 'USPS',
            'type' => $package->type,
            'length' => $package->length,
            'width' => $package->width,
            'height' => $package->height
        ];

        try {
            $goa_response = $goa->callPost('/restapi/package/create', $data);
         
            if ($goa_response->result == Response::RESULT_SUCCESS)
            {
                $package->verified = 1;
                $package->verification_service = 'goa';
                $package->verification_id = $goa_response->data->model->id;
            
                return $response->setSuccess();
            }
            
            else return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}