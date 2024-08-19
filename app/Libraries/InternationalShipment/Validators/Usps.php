<?php
namespace App\Libraries\InternationalShipment\Validators;

use App\Models\Mysql;
use App\Models\Dynamo;
use App\Common\Functions;
use App\Common\Validator;

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

    const COUNTRY_MAP = [
        'CA' => 'Canada'
    ];

    const SERVICE_GXG = 'USPS_GXG';
    const SERVICE_PRIORITY = 'USPS_PRIORITY';
    const SERVICE_FIRST_CLASS = 'USPS_FIRST_CLASS';
    const SERVICE_PRIORITY_EXPRESS = 'USPS_PRIORITY_EXPRESS';

    const SERVICE_MAP = [
        USPS::SERVICE_GXG => 'GXG International',
        USPS::SERVICE_PRIORITY => 'Priority International',
        USPS::SERVICE_FIRST_CLASS => 'First Class International',
        USPS::SERVICE_PRIORITY_EXPRESS => 'Priority Express International'
    ];
    
    const RATE_DISCOUNT_MAP = [
        'Priority International' => 'priority',
        'First Class International' => 'first_class',
        'Priority Express International' => 'priority_express'
    ];

    const ADDITIONAL_SERVICE_MAP = [
        'SIGNATURE' => 182,
        'ADULT_SIGNATURE' => 182
        //'SIGNATURE_ELECTRONIC' => 156
    ];
    
    private function addPackage($xml_usps, $id, $shipment, $package, $from_address, $to_address) {

        
        // create package 
        $xml_package = $xml_usps->addChild('Package');
        $xml_package->addAttribute('ID', $id);

        // pounds
        $xml_ounces = $xml_package->addChild('Pounds');
        $xml_ounces[0] = (int) floor($shipment->weight / 16);

        // ounces
        $xml_ounces = $xml_package->addChild('Ounces');
        $xml_ounces[0] = (int) $shipment->weight % 16;

        // machineable
        $xml_machineable = $xml_package->addChild('Machinable');
        $xml_machineable[0] = 'true';

        // set mail type to all for now
        $xml_mail_type = $xml_package->addChild('MailType');
        $xml_mail_type[0] = 'ALL';

        // set insurance amount
        $xml_value = $xml_package->addChild('ValueOfContents');
        $xml_value[0] = 0;

        // set country
        $xml_country = $xml_package->addChild('Country');
        $xml_country[0] = Usps::COUNTRY_MAP[$to_address->country];

        // service
        $xml_container = $xml_package->addChild('Container');
        $xml_container[0] = 'VARIABLE';
        
        // width
        $xml_width = $xml_package->addChild('Width');
        $xml_width[0] = round($package->width, 2);

        // length
        $xml_length = $xml_package->addChild('Length');
        $xml_length[0] = round($package->length, 2);

        // height
        $xml_height = $xml_package->addChild('Height');
        $xml_height[0] = round($package->height, 2);

        // zip origination
        $xml_zip_origination = $xml_package->addChild('OriginZip');
        $xml_zip_origination[0] = Validator::validatePostalCode($from_address->postal);

        // commercial
        $xml_commercial = $xml_package->addChild('CommercialFlag');
        $xml_commercial[0] = 'Y';
        
        // commercial plus
        $xml_commercial_plus = $xml_package->addChild('CommercialPlusFlag');
        $xml_commercial_plus[0] = 'Y';

        // postal acceptance time
        $xml_post_acceptance_time = $xml_package->addChild('AcceptanceDateTime');
        $xml_post_acceptance_time[0] = date('Y-m-d\TH:i:s', time());
        
        // postal destination
        $xml_postal_destination = $xml_package->addChild('DestinationPostalCode');
        $xml_postal_destination[0] = $to_address->postal;
        
       /* $extra_services = $xml_package->addChild('ExtraServices');
        $extra_service = $extra_services->addChild('ExtraService');
        $extra_service[0] = 109;*/

        // special services 
        //$special_services = $xml_package->addChild('SpecialServices');

        // special services - signature
       // $signature = $special_services->addChild('SpecialService');
        //$signature[0] = 182;
    }
   
    public function validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services) {

        $response = new Response;

        $rate_discounts = Dynamo\RateDiscounts::findOrCreate($shipment->user_id);
        // check country
        if (!isset(Usps::COUNTRY_MAP[$to_address->country])) return $response->setFailure('Country not supported', 'NOT_SUPPORTED', 'COUNTRY_NOT_SUPPORTED');
        
        // create xml request
        $xml_usps = new SimpleXMLElement("<IntlRateV2Request USERID=\"$this->user_id\"/>");

        // set revision
        $xml_revision = $xml_usps->addChild('Revision');
        $xml_revision[0] = 2;

        $this->addPackage($xml_usps, 'rate', $shipment, $package, $from_address, $to_address);

        try {

            // intilize query
            $query = http_build_query(
                array(
                    'API' => 'IntlRateV2',
                    'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
                )
            );

            // build url and get response
            $url = $this->host . '?' . $query;
            
            $usps_response = file_get_contents($url);

            // create response
            $xml_response = new SimpleXMLElement($usps_response);

            //dd($xml_response);
            
            // intialize rates
            $rates = [];
            $error_message = 'No rates found';

            //
            $priority_retail = null;

            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_package) {

                // if key is address then address validated
                if ($key == 'Package') {
                    $package_id = $xml_package->attributes()['ID'];
                    

                    // loop through values and set address values
                    foreach ($xml_package->children() as $package_key => $xml_service)
                    {
                        if ($package_key == 'Service')  {

                            $service = null;
                            $rate_cpp = null;
                            $rate_retail = null;
                            $additional_services = [];
                            $total_service_charges = 0;


                            foreach($xml_service->children() as $service_key => $service_element) {
                                if ($service_key == 'SvcDescription') {
                                    if ((string) $service_element == 'Priority Mail Express International&lt;sup&gt;&#8482;&lt;/sup&gt;') $service = USPS::SERVICE_PRIORITY_EXPRESS;
                                    else if ((string) $service_element == 'Priority Mail International&lt;sup&gt;&#174;&lt;/sup&gt;') $service = USPS::SERVICE_PRIORITY;
                                    else if ((string) $service_element == 'First-Class Package International Service&lt;sup&gt;&#8482;&lt;/sup&gt;') $service = USPS::SERVICE_FIRST_CLASS;
                                }

                                if ($service_key == 'Postage') {
                                    $rate_cpp = isset($rate_cpp) ? min($rate_cpp, (float) $service_element) : (float) $service_element;
                                    $rate_retail = (float) $service_element;
                                }

                                if ($service_key == 'CommercialPostage') {
                                    $rate_cpp = isset($rate_cpp) ? min($rate_cpp, (float) $service_element) : (float) $service_element;
                                }
                                
                                if ($service_key == 'CommercialPlusPostage') {
                                    $rate_cpp = isset($rate_cpp) ? min($rate_cpp, (float) $service_element) : (float) $service_element;
                                }

                                if ($service_key == 'ExtraServices') {
                                    foreach ($service_element as $additional_service) {
                                        foreach ($services as $requested_service) {

                                            $additional_service_id = Usps::ADDITIONAL_SERVICE_MAP[$requested_service];

                                            if ((int) $additional_service->ServiceID[0] == $additional_service_id) {
                                                
                                                $matched_service_rate = round((float) $additional_service->PriceOnline[0], 2);

                                                $additional_services[] = [
                                                    'service' => $requested_service,
                                                    'rate' => $matched_service_rate
                                                ];
    
                                                $total_service_charges += $matched_service_rate;
                                            }
                                        }
                                    }
                                }
                            }


                            if (!isset($service)) continue;

                            $rate = new Mysql\Rate;
                            $rate->carrier = 'USPS';
                            $rate->service = Usps::SERVICE_MAP[$service];

                            $rate->rate = (string) $rate_discounts->calculateRate(
                                ($to_address->country == 'CA' ? 'canada' : 'international'), 
                                'usps', 
                                USPS::RATE_DISCOUNT_MAP[$rate->service], 
                                (float) $rate_cpp,
                                $shipment->weight
                            );

                            $rate->setSubModel('services', $additional_services);
                            $rate->rate_services = (string) $total_service_charges;

                            $rate->rate_list = (string) $rate_cpp;
                            $rate->rate_retail = (string) $rate_retail;
                            $rate->total_charge = (string) ($rate->rate_services + $rate->rate);
                            $rate->total_retail = (string) $rate_retail;
                            $rate->total_list = (string) $rate_cpp;

                            $rate->delivery_guarantee = 0;

                            $rate->verified = 1;
                            $rate->verification_service = 'usps';
                            $rate->verification_id = '';

                            $rates[] = $rate;
                        }

                        if ($package_key == 'Error') {
                            // loop through values and set address values
                            foreach ($xml_postage->children() as $error_key => $error_element)
                            {
                                if ($error_key == 'Description') $error_message = (string) $error_element;
                            }
                        }
                    }                
                }
            }

            if (count($rates) == 0) return $response->setFailure($error_message, 'USPS_ERROR', 'USPS_VALIDATION_ERROR');

            $response->set('rates', $rates);

            $shipment->setSubModel('rates', $rates);
            $shipment->verified = 1;
            $shipment->verification_service = 'usps';
            $shipment->verification_id = '';

            return $response->setSuccess();
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}
