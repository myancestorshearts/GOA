<?php
namespace App\Libraries\Pickup\Validators;

use App\Models\Mysql;
use App\Common\Functions;
use App\Libraries\PickupAvailability\PickupAvailabilityValidator;

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
        'Priority' => 'PriorityMail',
        'Priority International' => 'International',
        'First Class International' => 'International',
        'Priority Express International' => 'International'
    ];

    private function addAddress($xml_usps, $address) {
        
        // set name
        $xml_name = $xml_usps->addChild('FirstName');
        $xml_name[0] = $address->name;

        // set name
        $xml_last_name = $xml_usps->addChild('LastName');
        $xml_last_name[0] = '-';

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

        // set name
        $xml_phone = $xml_usps->addChild('Phone');
        $xml_phone[0] = $address->phone;

        // set name
        $xml_extension = $xml_usps->addChild('Extension');

    }
    
    public function setTime($xml_usps, $time) {

        // set xml date
        $xml_date = $xml_usps->addChild('Date');
        $xml_date[0] = date('Y-m-d\TH:i:s\Z', $time);
    }

    public function validateModel($pickup, $from_address, $labels, $scan_forms, $non_scan_form_labels) {

        // create response
        $response = new Response;

        // check availability 
        $pickup_availability_validator = new PickupAvailabilityValidator();
        $availability_response = $pickup_availability_validator->validatePickupAvailability($from_address, strtotime($pickup->date));
        
        // check for availability failure
        if ($availability_response->isFailure()) return $availability_response;

        // check availability
        $availability = $availability_response->get('availability');
        if (strtotime($pickup->date) != strtotime($availability['date'])) return $response->setFailure('Date not available', 'INVALID_DATE', 'INVALID_DATE');

        if (strtotime($pickup->date) - time() > 24 * 60 * 60) {
            $pickup->status = 'PENDING';
            $pickup->carrier = 'usps';
            $pickup->verification_service = 'usps';
            $pickup->verified = 1;
            $pickup->verification_id = '';
            $pickup->confirmation_number = '';
            $pickup->carrier_route = $availability['carrier_route'];
            $pickup->day_of_week = $availability['day_of_week'];
            return $response->setSuccess();
        }

        
        // create xml request
        $xml_usps = new SimpleXMLElement("<CarrierPickupScheduleRequest USERID=\"$this->user_id\"/>");

        // set from addresss
        $this->addAddress($xml_usps, $from_address);

        $labels_by_service = [];
        $estimated_weight = 0;
        foreach($labels as $label) {
            $service = USPS::SERVICE_MAP[$label->service];
            if (!isset($labels_by_service[$service])) $labels_by_service[$service] = 0;
            $labels_by_service[$service]++;
            $estimated_weight += $label->weight;
        }

        foreach($labels_by_service as $key => $count) {
            $xml_package = $xml_usps->addChild('Package');
            $xml_service_type = $xml_package->addChild('ServiceType');
            $xml_service_type[0] = $key;
            $xml_count = $xml_package->addChild('Count');
            $xml_count[0] = $count;
        }

        $xml_weight = $xml_usps->addChild('EstimatedWeight');
        $xml_weight[0] = (int) max(1, ($estimated_weight / 16));

        // set package location
        $xml_package_location = $xml_usps->addChild('PackageLocation');
        $xml_package_location[0] = USPS::PACKAGE_LOCATION_MAP[$pickup->package_location];
        
        $xml_special_instructions  = $xml_usps->addChild('SpecialInstructions');
        $xml_special_instructions[0] = $pickup->special_instructions;

        //dd($xml_usps);
        // intilize query
        $query = http_build_query(
            array(
                'API' => 'CarrierPickupSchedule',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );

        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create usps_response
            $xml_response = new SimpleXMLElement($usps_response);
           
            $error_message = 'Failed to schedule pickup';

            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                if ($key == 'DayOfWeek') {
                    $pickup->day_of_week = (string) $xml_value;
                }
                if ($key == 'Date') {
                    $pickup->date = Functions::convertTimeToMysql(strtotime((string) $xml_value));
                }
                if ($key == 'CarrierRoute') {
                    $pickup->carrier_route = (string) $xml_value;
                }
                if ($key == 'ConfirmationNumber') {
                    $pickup->confirmation_number = (string) $xml_value;
                }
                if ($key == 'Description') {
                    $error_message = (string) $xml_value;
                }
            }
            $pickup->status = 'SCHEDULED';

            if (!isset($pickup['day_of_week'])) return $response->setFailure($error_message, 'USPS_ERROR', 'USPS_VALIDATION_ERROR');
        
            $pickup->carrier = 'usps';
            $pickup->verification_service = 'usps';
            $pickup->verified = 1;
            $pickup->verification_id = $pickup->confirmation_number;

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->jsonFailure('Cannot connect to USPS', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}