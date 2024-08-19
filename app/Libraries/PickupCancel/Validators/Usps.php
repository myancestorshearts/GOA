<?php
namespace App\Libraries\PickupCancel\Validators;

use App\Models\Mysql;
use App\Common\Functions;

use SimpleXMLElement;

use Imagick;
use ImagickPixel;

use Storage;

use Aws\S3\S3Client;

use ApiAuth;


use App\Http\Controllers\Response;

class Usps {

    private $user_id;
    private $host;
    
	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
	}

    
    const PACKAGE_LOCATION_MAP = [
        'FRONT_DOOR' => 'Front Door',
        'BACK_DOOR' => 'Back Door',
        'SIDE_DOOR' => 'Side Door',
        'KNOCK_ON_DOOR' => 'Knock on Door/Ring Bell',
        'MAIL_ROOM' => 'Mail Room',
        'OFFICE' => 'Office',
        'RECEPTION' => 'Reception',
        'IN_MAILBOX' => 'In/At Mailbox',
        'OTHER' => 'Other'
    ];

    const SERVICE_MAP = [
        'First Class' => 'FirstClass',
        'Priority Express' => 'PriorityMailExpress',
        'Parcel Select' => 'ExpressMail',
        'Cubic' => 'PriorityMail',
        'Priority' => 'PriorityMail'
    ];

    private function addAddress($xml_usps, $address) {
        

        // set name
        $xml_company = $xml_usps->addChild('FirmName');
        $xml_company[0] = $address->company;
        
        // set name
        $xml_address_1 = $xml_usps->addChild('SuiteOrApt');
        $xml_address_1[0] = $address->street_2;
        
        // set name
        $xml_address_2 = $xml_usps->addChild('Address2');
        $xml_address_2[0] = $address->street_1;
        
        // set ubanization to empty
        $xml_urbanization = $xml_usps->addChild('Urbanization');

        // set name
        $xml_city = $xml_usps->addChild('City');
        $xml_city[0] = $address->city;
        
        // set name
        $xml_state = $xml_usps->addChild('State');
        $xml_state[0] = $address->state;
        
        // set name
        $xml_postal = $xml_usps->addChild('ZIP5');
        $xml_postal[0] = $address->postal;
        
        // set name
        $xml_zip4 = $xml_usps->addChild('ZIP4');

    }

    public function cancelModel($pickup) {

        // create response
        $response = new Response;

        // get from address
        $from_address = Mysql\Address::find($pickup->from_address_id);
        if (!isset($from_address)) return $response->setFailure('Unable to link from address', 'INVALID_FROM_ADDRESS', 'INVALID_FROM_ADDRESS');

        // create xml request
        $xml_usps = new SimpleXMLElement("<CarrierPickupCancelRequest USERID=\"$this->user_id\"/>");

        // set from addresss
        $this->addAddress($xml_usps, $from_address);


        $xml_weight = $xml_usps->addChild('ConfirmationNumber');
        $xml_weight[0] = $pickup->verification_id;

        // intilize query
        $query = http_build_query(
            array(
                'API' => 'CarrierPickupCancel',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );

        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create usps_response
            $xml_response = new SimpleXMLElement($usps_response);
           
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {
                if ($key == 'Description') {
                    return $response->setFailure((string) $xml_value, 'USPS_VALIDATION_ERROR', 'USPS_VALIDATION_ERROR');
                }
            }

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->jsonFailure('Cannot connect to USPS', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}