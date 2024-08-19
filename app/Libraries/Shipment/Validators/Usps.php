<?php
namespace App\Libraries\Shipment\Validators;

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

    private function addPackage($xml_usps, $id, $service, $container, $from_address, $to_address, $shipment, $package, $id_prefix = 'id') {

        // create package 
        $xml_package = $xml_usps->addChild('Package');
        $xml_package->addAttribute('ID', $id_prefix . '_' . $id);

        // service
        $xml_service = $xml_package->addChild('Service');
        $xml_service[0] = $service;
        
        // first class package type
        if ($id == 'FIRST') {
            $xml_first_class_mail_type = $xml_package->addChild('FirstClassMailType');
            $xml_first_class_mail_type[0] = 'PACKAGE SERVICE';
        }

        // zip origination
        $xml_zip_origination = $xml_package->addChild('ZipOrigination');
        $xml_zip_origination[0] = $from_address->postal;

        // zip destination
        $xml_zip_desitnation = $xml_package->addChild('ZipDestination');
        $xml_zip_desitnation[0] = Validator::validatePostalCode($to_address->postal);


        // pounds
        $xml_ounces = $xml_package->addChild('Pounds');
        $xml_ounces[0] = (int) floor($shipment->weight / 16);

        // ounces
        $xml_ounces = $xml_package->addChild('Ounces');
        $xml_ounces[0] = (int) $shipment->weight % 16;

        // container
        $xml_container = $xml_package->addChild('Container');
        $xml_container[0] = $container;

        // width
        $xml_width = $xml_package->addChild('Width');
        $xml_width[0] = round($package->width, 2);

        // length
        $xml_length = $xml_package->addChild('Length');
        $xml_length[0] = round($package->length, 2);

        // height
        $xml_height = $xml_package->addChild('Height');
        $xml_height[0] = round($package->height, 2);

        // value 
        if ($shipment->contents_value > 0) {
            $xml_value = $xml_package->addChild('Value');
            $xml_value[0] = round($shipment->contents_value, 2);
        }

        // special services 
        //$special_services = $xml_package->addChild('SpecialServices');

        // special services - signature
       // $signature = $special_services->addChild('SpecialService');
        //$signature[0] = 182;

        // machineable
        $xml_machineable = $xml_package->addChild('Machinable');
        $xml_machineable[0] = 'true';

    }

    const SERVICE_MAP = [
        'FIRST' => 'First Class',
        'EXPRESS' => 'Priority Express',
        'PARCELSELECT' => 'Parcel Select',
        'CUBIC' => 'Cubic',
        'PRIORITY' => 'Priority'
    ];

    const RATE_DISCOUNT_MAP = [
        'First Class' => 'first_class',
        'Priority Express' => 'priority_express',
        'Priority' => 'priority',
        'Cubic' => 'cubic',
        'Parcel Select' => 'parcel_select'
    ];

    const PACKAGE_MAP = [
        'UspsSmallFlatRateEnvelope' => 'SM FLAT RATE ENVELOPE',
        'UspsFlatRateLegalEnvelope' => 'LEGAL FLAT RATE ENVELOPE',
        'UspsFlatRatePaddedEnvelope' => 'PADDED FLAT RATE ENVELOPE',
        'UspsFlatRateEnvelope' => 'FLAT RATE ENVELOPE',
        'Parcel' => 'VARIABLE',
        'SoftPack' => 'VARIABLE',
        'UspsSmallFlatRateBox' => 'SM FLAT RATE BOX',
        'UspsMediumFlatRateBoxTopLoading' => 'MD FLAT RATE BOX',
        'UspsMediumFlatRateBoxSideLoading' => 'MD FLAT RATE BOX',
        'UspsLargeFlatRateBox' => 'LG FLAT RATE BOX',
        'UspsRegionalRateBoxATopLoading' => 'REGIONALRATEBOXA',
        'UspsRegionalRateBoxASideLoading' => 'REGIONALRATEBOXA',
        'UspsRegionalRateBoxBTopLoading' => 'REGIONALRATEBOXB',
        'UspsRegionalRateBoxBSideLoading' => 'REGIONALRATEBOXB'
    ];

    const ADDITIONAL_SERVICE_MAP = [
        'SIGNATURE' => [156],
        'ADULT_SIGNATURE' => [119],
        'INSURANCE' => [100, 101, 125]
        //'SIGNATURE_ELECTRONIC' => 156
    ];

    public function validateShipment($shipment, $from_address, $to_address, $return_address, $package, $services) {

        $rate_discounts = Dynamo\RateDiscounts::findOrCreate($shipment->user_id);

        $response = new Response;

        try {
            
            // create xml request
            $xml_usps = new SimpleXMLElement("<RateV4Request USERID=\"$this->user_id\"/>");

            // set revision
            $xml_revision = $xml_usps->addChild('Revision');
            $xml_revision[0] = 2;

            // get usps package type 
            $package_type = USPS::PACKAGE_MAP[$package->type];

            // add first class if less than 16 oz
            if ($shipment->weight < 16) {
                $this->addPackage($xml_usps, 'FIRST', 'First Class Commercial', $package_type, $from_address, $to_address, $shipment, $package);
            }
            
            // always add 
            $this->addPackage($xml_usps, 'EXPRESS', 'Priority Mail Express CPP', $package_type, $from_address, $to_address, $shipment, $package);
            $this->addPackage($xml_usps, 'PARCELSELECT', 'Parcel Select Ground', $package_type, $from_address, $to_address, $shipment, $package);

            // cubic parcel
            if (($package->length * $package->height * $package->width) / (12 * 12 * 12) < .5 && 
                 $shipment->weight <= 320 && 
                 $package->type == 'Parcel') {
                $this->addPackage($xml_usps, 'CUBIC', 'Priority Mail Cubic', 'CUBIC PARCELS', $from_address, $to_address, $shipment, $package);
            }

            // cubic softpack
            if ((float) $package->width <= 18 &&
                (float) $package->length <= 18 && 
                (float) $package->weight <= 320 && 
                $package->type == 'SoftPack') {
                $this->addPackage($xml_usps, 'CUBIC', 'Priority Mail Cubic', 'CUBIC SOFT PACK', $from_address, $to_address, $shipment, $package);
            }

            // add priority to be rated
            $this->addPackage($xml_usps, 'PRIORITY', 'Priority Cpp', $package_type, $from_address, $to_address, $shipment, $package);

            // intilize query
            $query = http_build_query(
                array(
                    'API' => 'RateV4',
                    'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
                )
            );
        
            // build url and get response
            $url = $this->host . '?' . $query;

            
            $usps_response = file_get_contents($url);

            // create response
            $xml_response = new SimpleXMLElement($usps_response);

            
            // intialize rates
            $rates = [];
            $error_message = 'No rates found';

            //
            $priority_retail = null;

            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_package) {


                // add error in error message
                if ($key == 'Description') $error_message = (string) $xml_package;
                
                // add source in failure description
                if ($key == 'Source') $error_message .= ' - ' . (string) $xml_package;

                // if key is address then address validated
                if ($key == 'Package') {

                    $package_id = explode('_', $xml_package->attributes()['ID'])[1];

                    // loop through values and set address values
                    foreach ($xml_package->children() as $package_key => $xml_postage)
                    {
                        if ($package_key == 'Postage')  {

                            $rate = new Mysql\Rate;
                            $rate->carrier = 'USPS';
                            $rate->service = Usps::SERVICE_MAP[(string) $package_id];

                            if (in_array($package->type, [
                                'UspsRegionalRateBoxATopLoading',
                                'UspsRegionalRateBoxASideLoading',
                                'UspsRegionalRateBoxBTopLoading',
                                'UspsRegionalRateBoxBSideLoading'
                            ]) && $rate->service != 'Priority') continue;

                            $rate->rate = 0;

                            foreach ($xml_postage->children() as $rate_key => $rate_value) {
                                if ($rate_key == 'Rate') $rate->rate_retail = (string) round((float) $rate_value, 2);
                                if ($rate_key == 'Rate' || $rate_key == 'CommercialRate' || $rate_key == 'CommercialPlusRate') $rate->rate_list = (string) round((float) $rate_value, 2);
                           
                                if ($rate_key == 'SpecialServices') {
                                    $additional_services = [];
                                    $total_service_charges = 0;
                                    foreach ($services as $additional_service) {
                                        $additional_service_ids = Usps::ADDITIONAL_SERVICE_MAP[$additional_service];
                                        $matched_service_rate = 0;
                                        foreach ($rate_value as $special_service) {
                                            $matched_service = false;
                                            $service_rate = 0;
                                            foreach ($special_service->children() as $special_service_key => $special_service_value) {
                                                foreach($additional_service_ids as $additional_service_id) {
                                                    if ($special_service_key == 'ServiceID' && (int) $special_service_value == $additional_service_id) $matched_service = true;
                                                }
                                                if ($special_service_key == 'Price') $service_rate = round((float) $special_service_value, 2); 
                                            }
                                            if ($matched_service) {
                                                $matched_service_rate = $service_rate;
                                            }
                                        }
                                        
                                        $additional_services[] = [
                                            'service' => $additional_service,
                                            'rate' => (string) $matched_service_rate
                                        ];

                                        $total_service_charges += $matched_service_rate;
                                    }

                                    $rate->setSubModel('services', $additional_services);
                                    $rate->rate_services = (string) $total_service_charges;
                                }
                            }

                            if ($rate->service == 'Priority') {
                                $priority_retail = $rate->rate_retail;
                            }

                            // we can add a priority check here if we want to give them discounts or what not. 
                            $rate->rate = (string) $rate_discounts->calculateRate('domestic', 'usps', USPS::RATE_DISCOUNT_MAP[$rate->service], (float) $rate->rate_list, $shipment->weight);
                            
                            // set total charge
                            $rate->total_charge = (string) ($rate->rate_services + $rate->rate);

                            $rate->total_retail = (string) ($rate->rate_retail + $rate->rate_services);
                            $rate->total_list = (string) ($rate->rate_list + $rate->rate_services);

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

            // loop through rates and find cubic set retail to priority retail
            foreach ($rates as $rate) {
                if ($rate->service == 'Cubic') {
                    $rate->rate_retail = $priority_retail;
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

    
    public function validateShipmentMass($shipments, $from_address, $return_address, $package, $services) {
        $response = new Response;

        $rate_discounts = Dynamo\RateDiscounts::findOrCreate($shipments[0]->user_id);

        
        $to_address_ids = [];
        foreach($shipments as $shipment) {
            $to_address_ids[] = $shipment->to_address_id;
        }

        $to_addresses_mapped = Mysql\Address::getModelsMapped(Mysql\Address::whereIn('id', $to_address_ids)->get());
          
        try {

            $package_details = [];

            // get usps package type 
            $package_type = USPS::PACKAGE_MAP[$package->type];
            
            foreach($shipments as $shipment) {

                if (!isset($to_addresses_mapped[$shipment->to_address_id]))  return $response->setFailure('Unable to link address to a shipment', 'SHIPMENT_ERROR', 'SHIPMENT_INTERNAL_ERROR');
                $to_address = $to_addresses_mapped[$shipment->to_address_id];

                // add first class if less than 16 oz
                if ($shipment->weight < 16) {
                    $package_details[] = [
                        'service' => 'FIRST',
                        'service_usps' => 'First Class Commercial',
                        'package_type' => $package_type,
                        'to_address' => $to_address,
                        'shipment' => $shipment,
                    ];
                }
                
                // always add 
                
                $package_details[] = [
                    'service' => 'EXPRESS',
                    'service_usps' => 'Priority Mail Express CPP',
                    'package_type' => $package_type,
                    'to_address' => $to_address,
                    'shipment' => $shipment
                ];
                
                $package_details[] = [
                    'service' => 'PARCELSELECT',
                    'service_usps' => 'Parcel Select Ground',
                    'package_type' => $package_type,
                    'to_address' => $to_address,
                    'shipment' => $shipment
                ];

                // cubic parcel
                if (($package->length * $package->height * $package->width) / (12 * 12 * 12) < .5 && 
                    $shipment->weight <= 320 && 
                    $package->type == 'Parcel') {
                    $package_details[] = [
                        'service' => 'CUBIC',
                        'service_usps' => 'Priority Mail Cubic',
                        'package_type' => 'CUBIC PARCELS',
                        'to_address' => $to_address,
                        'shipment' => $shipment
                    ];
                }

                // cubic softpack
                if ((float) $package->width <= 18 &&
                    (float) $package->length <= 18 && 
                    (float) $package->weight <= 320 && 
                    $package->type == 'SoftPack') {
                    $package_details[] = [
                        'service' => 'CUBIC',
                        'service_usps' => 'Priority Mail Cubic',
                        'package_type' => 'CUBIC SOFT PACK',
                        'to_address' => $to_address,
                        'shipment' => $shipment
                    ];
                }

                $package_details[] = [
                    'service' => 'PRIORITY',
                    'service_usps' => 'Priority Cpp',
                    'package_type' => $package_type,
                    'to_address' => $to_address,
                    'shipment' => $shipment
                ];
            }
                
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->host);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            // intialize rates
            $priority_retail = [];
            $rates = [];
            $error_message = 'No rates found';

            Response::addTimer('Before calling usps');

            do {
                // create xml request
                $xml_usps = new SimpleXMLElement("<RateV4Request USERID=\"$this->user_id\"/>");

                // set revision
                $xml_revision = $xml_usps->addChild('Revision');
                $xml_revision[0] = 2;

                for ($i = 0; $i < 25 && count($package_details) > 0; $i++) {
                    $package_detail = array_pop($package_details);
                    $this->addPackage(
                        $xml_usps, 
                        $package_detail['service'], 
                        $package_detail['service_usps'], 
                        $package_detail['package_type'], 
                        $from_address, 
                        $package_detail['to_address'], 
                        $package_detail['shipment'],
                        $package,
                        $package_detail['shipment']->id
                    );
                }

                // creaturl
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(
                    array(
                        'API' => 'RateV4',
                        'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
                    )
                ));   
                
                Response::addTimer('calling usps', $xml_usps);
                $usps_response = curl_exec($curl);

                Response::addTimer('finished usps', $usps_response);
                // create response
                $xml_response = new SimpleXMLElement($usps_response);


                // loop through children to find first address
                foreach($xml_response->children() as $key => $xml_package) {

                    // add error in error message
                    if ($key == 'Description') $error_message = (string) $xml_package;
                    
                    // add source in failure description
                    if ($key == 'Source') $error_message .= ' - ' . (string) $xml_package;

                    // if key is address then address validated
                    if ($key == 'Package') {

                        $package_id = $xml_package->attributes()['ID'];
                        
                        $package_id_parts = explode('_', $package_id);
                        $package_id_service = $package_id_parts[1];
                        $package_id_shipment_id = $package_id_parts[0];

                        // loop through values and set address values
                        foreach ($xml_package->children() as $package_key => $xml_postage)
                        {
                            if ($package_key == 'Postage')  {

                                $rate = new Mysql\Rate;
                                $rate->carrier = 'USPS';
                                $rate->service = Usps::SERVICE_MAP[(string) $package_id_service];

                                $rate->rate = 0;

                                foreach ($xml_postage->children() as $rate_key => $rate_value) {
                                    if ($rate_key == 'Rate') $rate->rate_retail = (string) round((float) $rate_value, 2);
                                    if ($rate_key == 'Rate' || $rate_key == 'CommercialRate' || $rate_key == 'CommercialPlusRate') $rate->rate_list = (string) round((float) $rate_value, 2);
                            
                                    if ($rate_key == 'SpecialServices') {
                                        $additional_services = [];
                                        $total_service_charges = 0;
                                        foreach ($services as $additional_service) {
                                            $additional_service_ids = Usps::ADDITIONAL_SERVICE_MAP[$additional_service];
                                            $matched_service_rate = 0;
                                            foreach ($rate_value as $special_service) {
                                                $matched_service = false;
                                                $service_rate = 0;
                                                foreach ($special_service->children() as $special_service_key => $special_service_value) {
                                                    foreach($additional_service_ids as $additional_service_id) {
                                                        if ($special_service_key == 'ServiceID' && (int) $special_service_value == $additional_service_id) $matched_service = true;
                                                    }
                                                    if ($special_service_key == 'Price') $service_rate = round((float) $special_service_value, 2); 
                                                }
                                                if ($matched_service) {
                                                    $matched_service_rate = $service_rate;
                                                }
                                            }
                                            
                                            $additional_services[] = [
                                                'service' => $additional_service,
                                                'rate' => (string) $matched_service_rate
                                            ];

                                            $total_service_charges += $matched_service_rate;
                                        }

                                        $rate->setSubModel('services', $additional_services);
                                        $rate->rate_services = (string) $total_service_charges;
                                    }
                                }

                                if ($rate->service == 'Priority') {
                                    $priority_retail[$package_id_shipment_id] = $rate->rate_retail;
                                }

                                $internal_shipment = null;
                                foreach ($shipments as $shipment) {
                                    if ($shipment->id == $package_id_shipment_id) {
                                        $internal_shipment = $shipment;
                                    }
                                }
                                
                                // we can add a priority check here if we want to give them discounts or what not. 
                                $rate->rate = (string) $rate_discounts->calculateRate('domestic', 'usps', Goa::RATE_DISCOUNT_MAP[$rate->service], (float) $rate->rate_list, $internal_shipment->weight);
                            
                                $rate->shipment_id = $package_id_shipment_id;

                                // set total charge
                                $rate->total_charge = (string) ($rate->rate_services + $rate->rate);

                                $rate->total_retail = (string) ($rate->rate_retail + $rate->rate_services);
                                $rate->total_list = (string) ($rate->rate_list + $rate->rate_services);

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
            }
            while(count($package_details) > 0);
            
            curl_close($curl);

            Response::addTimer('looping shipments done with usps');
            // loop through rates and find cubic set retail to priority retail
            foreach ($shipments as $shipment) {
                
                foreach ($rates as $rate) {
                    if ($rate->service == 'Cubic' && $rate->shipment_id == $shipment->id && isset($priority_retail[$shipment->id])) {
                        $rate->rate_retail = $priority_retail[$shipment->id];
                    }
                }

                $shipment->verified = 1;
                $shipment->verification_service = 'usps';
                $shipment->verification_id = '';
                
            }

            if (count($rates) == 0) return $response->setFailure($error_message, 'USPS_ERROR');

            $response->set('rates', $rates);

            return $response->setSuccess();
        }
        catch (Exception $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE');
        }
    }
}