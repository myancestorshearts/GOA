<?php
namespace App\Libraries\ScanForm\Validators;


use App\Libraries\Goa as GoaClient;

use Exception;

use App\Models\Mysql;

use App\Http\Controllers\Response;

class Goa {


    public function validateScanForm($scan_form, $from_address, $labels) {

        $goa = new GoaClient;
        $response = new Response;
        
        $label_ids = [];
        foreach ($labels as $label) {
            $label_ids[] = $label->verification_id;
        }

        $data = [
            'from_address_id' => $from_address->verification_id,
            'label_ids' => $label_ids,
            'external_user_id' => $scan_form->user_id,
            'ship_date' => $scan_form->ship_date
        ];

        try {
            $goa_response = $goa->callPost('/restapi/scanform/create', $data);
            if ($goa_response->result == 'success')
            {
                $scan_form->verified = 1;
                $scan_form->verification_service = 'goa';
                $scan_form->verification_id = $goa_response->data->model->id;
                $scan_form->barcode = $goa_response->data->model->barcode;
                $scan_form->url = $goa_response->data->model->url;
                return $response->setSuccess();
            }
            return $response->setFailure($goa_response->message, $goa_response->error_code);
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to goa servers', 'GOA_CONNECTION_FAILURE');
        }
    }
}