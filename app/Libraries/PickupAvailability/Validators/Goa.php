<?php
namespace App\Libraries\PickupAvailability\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Common\Functions;

class Goa {


    public function validatePickupAvailability($from_address, $time) {

        $goa = new GoaClient;
        $response = new Response;
        
    
        $data = [
            'from_address_id' => $from_address->verification_id,
            'date' => Functions::convertTimeToMysql($time)
        ];
        try {
            $goa_response = $goa->callGet('/restapi/pickup/availability', $data);
            if ($goa_response->result == 'success')
            {
                $response->set('availability', $goa_response->data->model);
                return $response->setSuccess();
            }
            return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}