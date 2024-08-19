<?php
namespace App\Libraries\InternationalRefund\Validators;

use App\Models;
use App\Common\Functions;

use SimpleXMLElement;

use App\Http\Controllers\Response;

use ApiAuth;

class Usps {

    private $user_id;
    private $host;
    
	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
	}

    public function validateRefundModel($label) {

        $response = new Response;


        // create xml request
        $xml_usps = new SimpleXMLElement("<eVSCancelRequest USERID=\"$this->user_id\"/>");
        

        // set sender name
        $xml_barcode = $xml_usps->addChild('BarcodeNumber');
        $xml_barcode[0] = $label->tracking;

        // intilize query
        $query = http_build_query(
            array(
                'API' => 'eVSCancel',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );
        
        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create usps_response
            $xml_response = new SimpleXMLElement($usps_response);


            $error_message = null;
            $status = 'Not Cancelled';
            
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                // set rdc
                if ($key == 'Status') {
                    $status = (string) $xml_value;
                }

                // set route
                if ($key == 'Reason') {
                    $error_message = (string) $xml_value;
                }

                // description
                if ($key == 'Description') {
                    $error_message = (string) $xml_value;
                    return $response->setFailure($error_message, 'GENERATION_ERROR');
                }
            }

            if ($status != 'Cancelled') return $response->setFailure('Unable to cancel - ' . $error_message, 'GENERATION_ERROR');

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE');
        }

        return $response->setSuccess();
    }
}