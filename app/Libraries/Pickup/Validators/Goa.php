<?php
namespace App\Libraries\Pickup\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateModel($pickup, $from_address, $labels, $scan_forms, $non_scan_form_labels) {

        $goa = new GoaClient;
        $response = new Response;
        
        $label_ids = [];
        foreach ($non_scan_form_labels as $label) {
            $label_ids[] = $label->verification_id;
        }

         
        $scan_form_ids = [];
        foreach ($scan_forms as $scan_form) {
            $scan_form_ids[] = $scan_form->verification_id;
        }

        $data = [
            'from_address_id' => $from_address->verification_id,
            'label_ids' => $label_ids,
            'scan_form_ids' => $scan_form_ids,
            'external_user_id' => $pickup->user_id,
            'package_location' => $pickup->package_location,
            'special_instructions' => $pickup->special_instructions,
            'date' => $pickup->date
        ];

        try {
            $goa_response = $goa->callPost('/restapi/pickup/schedule', $data);
            if ($goa_response->result == 'success')
            {
                $pickup->verified = 1;
                $pickup->verification_service = 'goa';
                $pickup->verification_id = $goa_response->data->model->id;
                $pickup->confirmation_number = $goa_response->data->model->confirmation_number;
                $pickup->date = $goa_response->data->model->date;
                $pickup->day_of_week = $goa_response->data->model->day_of_week;
                $pickup->carrier_route = $goa_response->data->model->carrier_route;
                $pickup->status = $goa_response->data->model->status;
                return $response->setSuccess();
            }
            return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}