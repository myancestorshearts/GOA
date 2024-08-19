<?php
namespace App\Libraries\Address\Validators;

use App\Models;

use App\Http\Controllers\Response;

use SimpleXMLElement;

use ApiAuth;

class Usps {

    private $user_id;
    private $host;
    
	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
	}

    public function validateAddress($address) {
        $response = new Response;
        try {
            
            // create xml request
            $xml_usps = new SimpleXMLElement("<AddressValidateRequest USERID=\"$this->user_id\"/>");

            // set revision
            $xml_revision = $xml_usps->addChild('Revision');
            $xml_revision[0] = 1;

            // create address 
            $xml_address = $xml_usps->addChild('Address');
            $xml_address->addAttribute('ID', '0');
            
            // street 1
            $xml_address_1 = $xml_address->addChild('Address1');
            $xml_address_1[0] = $address->street_2;
            
            // street 2
            $xml_address_2 = $xml_address->addChild('Address2');
            $xml_address_2[0] = $address->street_1;

            // city
            $xml_city = $xml_address->addChild('City');
            $xml_city[0] = $address->city;
            
            // state
            $xml_state = $xml_address->addChild('State');
            $xml_state[0] = $address->state;

            // postal
            $xml_postal = $xml_address->addChild('Zip5');
            $xml_postal[0] = $address->postal;
            
            // postal
            $xml_postal = $xml_address->addChild('Zip4');


            // intilize query
            $query = http_build_query(
                array(
                    'API' => 'Verify',
                    'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
                )
            );
           // dd($xml_usps);
            // build url and get response
            $url = $this->host . '?' . $query;
            $get_response = file_get_contents($url);

            // create response
            $xml_response = new SimpleXMLElement($get_response);
         //   dd($xml_response);
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_item) {

                // if key is address then address validated
                if ($key == 'Address') {

                    // set address to verified
                    $address->verified = 1;
                    $address->verification_service = 'usps';
                    $address->verification_id = $address->id;

                    // loop through values and set address values
                    foreach ($xml_item->children() as $address_property => $address_value)
                    {
                        if ($address_property == 'Address2') $address->street_1 = (string) $address_value;
                        else if ($address_property == 'Address1') $address->street_2 = (string) $address_value;
                        else if ($address_property == 'City') $address->city = (string) $address_value;
                        else if ($address_property == 'State') $address->state = (string) $address_value;
                        else if ($address_property == 'Zip5') $address->postal = (string) $address_value;
                        else if ($address_property == 'Zip4') $address->postal_sub = (string) $address_value;
                        else if ($address_property == 'Error') $address->verified = 0;
                    }
                    
                    // return success response
                    return $response->setSuccess();
                }
            }
            
            return $response->setFailure('Invalid address', 'INVALID_ADDRESS', 'USPS_VALIDATION_ERROR');
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}