<?php
namespace App\Libraries\EstimatedDays\Validators;

use App\Common\Functions;

use SimpleXMLElement;

use App\Http\Controllers\Response;

use ApiAuth;

class Usps {

    private $user_id;
    private $host;
    
    const DEFAULTS_INTERNATIONAL_MAX = [
        'Priority International' => 10,
        'First Class International' => 10,
        'Priority Express International' => 5
    ];

    const DEFAULTS_INTERNATIONAL_MIN = [
        'Priority International' => 6,
        'First Class International' => 6,
        'Priority Express International' => 3
    ];

    const DEFAULTS_DOMESTIC_MAX = [
        'First Class' => 5,
        'Priority Express' => 2,
        'Priority' => 3,
        'Cubic' => 3,
        'Parcel Select' => 8
    ];
    

    const DEFAULTS_DOMESTIC_MIN = [
        'First Class' => 2,
        'Priority Express' => 1,
        'Priority' => 1,
        'Cubic' => 1,
        'Parcel Select' => 2
    ];

    const MAIL_CLASS_TO_DOMESTIC = [
        'First Class' => 3,
        'Priority Express' => 1,
        'Priority' => 2,
        'Cubic' => 2,
        'Parcel Select' => 601
    ];
    

    const COMMITMENT_TO_DAYS = [
        '1-Day' =>  1,
        '2-Day' => 2,
    ];

	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
	}

    public function validateDays($shipment, $rates, $from_address, $to_address)
    {
        
        // generate response
        $response = new Response;

        // create xml request
        $xml_usps = new SimpleXMLElement("<SDCGetLocationsRequest USERID=\"$this->user_id\"/>");

        $xml_mail_class = $xml_usps->addChild('MailClass');
        $xml_mail_class[0] = '0';

        $xml_mail_zip_origin = $xml_usps->addChild('OriginZIP');
        $xml_mail_zip_origin[0] = $from_address->postal; 


        $xml_mail_zip_destination = $xml_usps->addChild('DestinationZIP');
        $xml_mail_zip_destination[0] = $to_address->postal;

        $xml_mail_shipdate = $xml_usps->addChild('AcceptDate');
        $xml_mail_shipdate[0] = date('m/d/Y', strtotime($shipment->ship_date));


        // intilize query
        $query = http_build_query(
            array(
                'API' => 'SDCGetLocations',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );

        $days_priority_express = null;
        $days_priority = null;
        $days_first_class = null;
        $days_parcel_select = null;

        $guarantee_priority_express = 0;
        $guarantee_priority = 0;
        $guarantee_first_class = 0;
        $guarantee_parcel_select = 0;

       // dd($xml_usps);
        try {

            if ($to_address->isUSDomestic())
            {
                // build url and get response
                $url = $this->host . '?' . $query;
                $usps_response = file_get_contents($url);

                // create response
                $xml_response = new SimpleXMLElement($usps_response);

                //dd($xml_response);

                // loop through children to find first address
                foreach($xml_response->children() as $key => $xml_value) {

                    // barcode set tracking a verification id
                    if ($key == 'Expedited') {
                        // loop throug the commitments 
                        foreach($xml_value->children() as $key_commitment => $xml_commitment) {
                            if ($key_commitment == 'Commitment') {

                                if (!isset(USPS::COMMITMENT_TO_DAYS[(string) $xml_commitment->CommitmentName])) continue;
                                $days = USPS::COMMITMENT_TO_DAYS[(string) $xml_commitment->CommitmentName];

                                if ((string) $xml_commitment->MailClass == '1') {
                                    if (!isset($days_priority_express)) $days_priority_express = $days;
                                    $days_priority_express = max($days, $days_priority_express);
                                    foreach($xml_commitment->children() as $key_location =>  $xml_location) {
                                        if ($key_location == 'Location') {
                                            if ((string) $xml_location->IsGuaranteed == 1) $guarantee_priority_express = 1;
                                        }
                                    }
                                }
                                else if ((string) $xml_commitment->MailClass = '2') {
                                    if (!isset($days_priority)) $days_priority = $days;
                                    $days_priority= max($days, $days_priority);
                                    foreach($xml_commitment->children() as $key_location =>  $xml_location) {
                                        if ($key_location == 'Location') {
                                            if ((string) $xml_location->IsGuaranteed == 2) $guarantee_priority = 1;
                                        }
                                    }
                                }
                            }
                        } 
                    }

                    // if scan_form image then we need to save image to s3 and return url
                    if ($key == 'NonExpedited') {
                        if (!isset($xml_value->SvcStdDays)) continue;
                        $days = (int) $xml_value->SvcStdDays;
                        if ((string) $xml_value->MailClass == '3') {
                            if (!isset($days_first_class)) $days_first_class = $days;
                            $days_first_class = max($days, $days_first_class);
                        }
                        if ((string) $xml_value->MailClass == '6') {
                            if (!isset($days_parcel_select)) $days_parcel_select = $days;
                            $days_parcel_select = max($days, $days_parcel_select);
                        }

                    }
                }

                if (!isset($days_priority_express)) $days_priority_express = Usps::DEFAULTS_DOMESTIC_MAX['Priority Express'];
                if (!isset($days_priority)) $days_priority = Usps::DEFAULTS_DOMESTIC_MAX['Priority'];
                if (!isset($days_first_class)) $days_first_class = Usps::DEFAULTS_DOMESTIC_MAX['First Class'];
                if (!isset($days_parcel_select)) $days_parcel_select = Usps::DEFAULTS_DOMESTIC_MAX['Parcel Select'];

                $days_map = [
                    'Priority' => $days_priority,
                    'Cubic' => $days_priority,
                    'First Class' => $days_first_class,
                    'Parcel Select' => $days_parcel_select,
                    'Priority Express' => $days_priority_express
                ];

                $guarantee_map = [
                    'Priority' => $guarantee_priority,
                    'Cubic' => $guarantee_priority,
                    'First Class' => $guarantee_first_class,
                    'Parcel Select' => $guarantee_parcel_select,
                    'Priority Express' => $guarantee_priority_express
                ];


                foreach ($rates as $rate) {
                    $days = $days_map[$rate->service];
                    $rate->delivery_days = $days;
                    $rate->delivery_date = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . $days . ' days', time()))));
                    $rate->delivery_guarantee = $guarantee_map[$rate->service];
                    $rate->delivery_range_min = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . Usps::DEFAULTS_DOMESTIC_MIN[$rate->service] . ' days', time()))));
                    $rate->delivery_range_max = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . Usps::DEFAULTS_DOMESTIC_MAX[$rate->service] . ' days', time()))));
                }

            }
            else {
                foreach ($rates as $rate) {
                    $days = Usps::DEFAULTS_INTERNATIONAL_MAX[$rate->service];
                    $rate->delivery_days = $days;
                    $rate->delivery_date = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . $days . ' days', time()))));
                    $rate->delivery_guarantee = 0;
                    $rate->delivery_range_min = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . Usps::DEFAULTS_INTERNATIONAL_MIN[$rate->service] . ' days', time()))));
                    $rate->delivery_range_max = Functions::convertTimeToMysql(strtotime(date('Y-m-d', strtotime('+' . Usps::DEFAULTS_INTERNATIONAL_MAX[$rate->service] . ' days', time()))));
                }
            }

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE');
        }
    }
}