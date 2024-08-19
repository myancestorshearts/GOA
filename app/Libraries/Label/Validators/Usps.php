<?php
namespace App\Libraries\Label\Validators;

use App\Models\Mysql;
use App\Common\Functions;

use SimpleXMLElement;

use Imagick;
use ImagickPixel;

use Storage;

use Aws\S3\S3Client;

use App\Http\Controllers\Response;

use ApiAuth;

use App\Models\Dynamo;

class Usps {

    private $user_id;
    private $return_user_id;
    private $host;
    
	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
        $this->return_user_id = ApiAuth::user()->usps_webtools_returns;
	}

    const SERVICE_MAP = [
        'First Class' => 'First Class',
        'Priority Express' => 'Priority Express',
        'Parcel Select' => 'Parcel Select Ground',
        'Cubic' => 'Priority Mail Cubic',
        'Priority' => 'Priority'
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

    const EXTRA_SERVICE_MAP = [
        'First Class' => [
            'SIGNATURE' => 156,
            'ADULT_SIGNATURE' => 119,
            'INSURANCE' => 100
        ],
        'Priority Express' => [
            'SIGNATURE' => 156,
            'ADULT_SIGNATURE' => 119,
            'INSURANCE' => 101
        ],
        'Parcel Select' => [
            'SIGNATURE' => 156,
            'ADULT_SIGNATURE' => 119,
            'INSURANCE' => 100
        ],
        'Cubic' => [
            'SIGNATURE' => 156,
            'ADULT_SIGNATURE' => 119,
            'INSURANCE' => 125
        ],
        'Priority' => [
            'SIGNATURE' => 156,
            'ADULT_SIGNATURE' => 119,
            'INSURANCE' => 125
        ]
    ];

    private function addReturnAddress($xml_usps, $address) {
        
        // set address
        $xml_address_1 = $xml_usps->addChild('AltReturnAddress1');
        $xml_address_1[0] = $address->street_2;
        
        // set address 2
        $xml_address_2 = $xml_usps->addChild('AltReturnAddress2');
        $xml_address_2[0] = $address->street_1;
        
        // set city
        $xml_city = $xml_usps->addChild('AltReturnAddress3');
        $xml_city[0] = $address->city;
        
        // set state
        $xml_state = $xml_usps->addChild('AltReturnAddress4');
        $xml_state[0] = $address->state;
        
        // set postal
        $xml_postal = $xml_usps->addChild('AltReturnAddress5');
        $xml_postal[0] = $address->postal;
        
        // set postal 4
        $xml_zip4 = $xml_usps->addChild('AltReturnAddress6');
        
        // set country
        $xml_phone = $xml_usps->addChild('AltReturnCountry');
        $xml_phone[0] = 'United States';
    }

    private function addAddress($xml_usps, $prefix, $address) {
        
        // set name
        $xml_name = $xml_usps->addChild($prefix . 'Name');
        $xml_name[0] = $address->name;
        
        // set name
        $xml_company = $xml_usps->addChild($prefix . 'Firm');
        $xml_company[0] = $address->company;
        
        // set name
        $xml_address_1 = $xml_usps->addChild($prefix . 'Address1');
        $xml_address_1[0] = $address->street_2;
        
        // set name
        $xml_address_2 = $xml_usps->addChild($prefix . 'Address2');
        $xml_address_2[0] = $address->street_1;
        
        // set name
        $xml_city = $xml_usps->addChild($prefix . 'City');
        $xml_city[0] = $address->city;
        
        // set name
        $xml_state = $xml_usps->addChild($prefix . 'State');
        $xml_state[0] = $address->state;
        
        // set name
        $xml_postal = $xml_usps->addChild($prefix . 'Zip5');
        $xml_postal[0] = $address->postal;
        
        // set name
        $xml_zip4 = $xml_usps->addChild($prefix . 'Zip4');
        
        // set name
        $xml_phone = $xml_usps->addChild($prefix . 'Phone');
        $xml_phone[0] = substr($address->phone, -10);

    }

    public function addPackageInfo($xml_usps, $package, $shipment, $rate) {

        // set container
        $xml_container = $xml_usps->addChild('Container');

        // cubic parcel
        if (($package->length * $package->height * $package->width) / (12 * 12 * 12) < .5 && 
            $shipment->weight < 320 && 
            $rate->service == 'Cubic') {
                $xml_container[0] = 'CUBIC PARCELS';
        }
        // cubic softpack
        else if ((float) $package->width <= 18 &&
            (float) $package->length <= 18 && 
            (float) $package->weight < 320 && 
            $rate->service == 'Cubic') {
                $xml_container[0] = 'CUBIC SOFT PACK';
        }
        else $xml_container[0] = USPS::PACKAGE_MAP[$package->type];
        
        // set width
        $xml_width = $xml_usps->addChild('Width');
        $xml_width[0] = (float) $package->width;
        
        // set length
        $xml_length = $xml_usps->addChild('Length');
        $xml_length[0] = (float) $package->length;
        
        // set height
        $xml_height = $xml_usps->addChild('Height');
        $xml_height[0] = (float) $package->height;
        
        // set machinable
        $xml_machinable = $xml_usps->addChild('Machinable');
        $xml_machinable[0] = 'true';

        // price options for cubic
        if ($rate->service == 'Cubic') {
            $xml_price_options = $xml_usps->addChild('PriceOptions');
            $xml_price_options[0] = 'Commercial Plus';
        }

        // insured amount
        $xml_insured = $xml_usps->addChild('InsuredAmount');
        $xml_insured[0] = (float) $shipment->contents_value;

    }

    public function addRateInfo($xml_usps, $rate) {

        // set service type
        $xml_service_type = $xml_usps->addChild('ServiceType');
        $xml_service_type[0] = strtoupper(Usps::SERVICE_MAP[$rate->service]);
    }

    public function addImageReturnParameters($xml_usps) {

        // set image paramters
        $xml_image_parameters = $xml_usps->addChild('ImageParameters');
        
        // set imagie paramter
        $xml_image_parameter = $xml_image_parameters->addChild('ImageType');
        $xml_image_parameter[0] = 'TIF';
    }

    public function addImageParameters($xml_usps, $to_address) {

        // set image paramters
        $xml_image_parameters = $xml_usps->addChild('ImageParameters');
        
        // set imagie paramter
        $xml_image_parameter = $xml_image_parameters->addChild('ImageParameter');
        $xml_image_parameter[0] = $to_address->requiresCustoms() ? '4X6LABEL' : 'BARCODE ONLY';
    }

    public function setTime($xml_usps, $time) {

        // set xml date
        $xml_date = $xml_usps->addChild('ShipDate');
        $xml_date[0] = date('Y/m/d', $time);

        // set xml time
        //$xml_time = $xml_usps->addChild('MailTime');
        //$xml_time[0] = date('His', $time);
    }

    public function addExtraServices($xml_usps, $rate_services, $rate) {
        // special services 
        $extra_services = $xml_usps->addChild('ExtraServices');

        if (isset($rate_services->services)) {
            foreach ($rate_services->services as $rate_service) {
                $usps_service_id = USPS::EXTRA_SERVICE_MAP[$rate->service][$rate_service['service']];
                
                // update signature for express
                if ($rate_service['service'] == 'SIGNATURE' && $rate->service == 'Priority Express') continue;

                $extra_service = $extra_services->addChild('ExtraService');
                $extra_service[0] = $usps_service_id;
            }
        }
    }


    
    public function addExpressMailOptions($xml_usps, $rate_services) {
        // special services 
        $express_options = $xml_usps->addChild('ExpressMailOptions');

        // requires signature
        $requires_signature = false;
        if (isset($rate_services->services)) {
            foreach ($rate_services->services as $rate_service) {

                // update signature for express
                if ($rate_service['service'] == 'SIGNATURE' || 
                    $rate_service['service'] == 'ADULT_SIGNATURE' ||
                    $rate_service['service'] == 'SIGNATURE_ELECTRONIC') $requires_signature = true;
            }
        }

        $signature_option = $express_options->addChild('WaiverOfSignature');
        $signature_option[0] = $requires_signature ? 'False' : 'True';
    }


    public function validateReturnLabel($return_label) {

        // create response
        $response = new Response;
/*
        $return_label->url = "https://goasolutions-labels.s3.us-west-2.amazonaws.com/return-image.PNG";
        $return_label->verified = 1;
        $return_label->verification_service = 'usps';
        $return_label->verification_id =  '';
        return $response->setSuccess();*/


        // get to address
        $customer_address = mysql\Address::find($return_label->customer_address_id);
        if (!isset($customer_address)) return $response->setFailure('Invalid customer address', 'INVALID_CUSTOMER_ADDRESS', 'INVALID_CUSTOMER_ADDRESS');
        
        // get return address
        $return_address = mysql\Address::find($return_label->return_address_id);
        if (!isset($return_address)) return $response->setFailure('Invalid return address', 'INVALID_RETURN_ADDRESS');

        // create xml request
        $xml_usps = new SimpleXMLElement("<USPSReturnsLabelRequest USERID=\"$this->return_user_id\"/>");
        
        // set revision
        $xml_revision = $xml_usps->addChild('Revision');

        // add image parameters
        $this->addImageReturnParameters($xml_usps);

        // set to address
        $this->addAddress($xml_usps, 'Customer', $customer_address);
        
        // set from address
        $this->addAddress($xml_usps, 'Retailer', $return_address);


        // set weight
        $xml_weight = $xml_usps->addChild('WeightInOunces');
        $xml_weight[0] = (int) $return_label->weight;


        // add express mail options
        $xml_service = $xml_usps->addChild('ServiceType');
        $xml_service[0] = strtoupper($return_label->service);

        // set Mid
        $xml_crid = $xml_usps->addChild('MailOwnerMID');
        $xml_crid[0] = '903100539';

        // add customer reference 
        $xml_reference = $xml_usps->addChild('CustomerRefNo');
        $xml_reference[0] = $return_label->reference;

        // set customer ref 2
        $xml_reference_2 = $xml_usps->addChild('CustomerRefNo2');
        $xml_reference_2[0] = $return_label->external_user_id;
        
        // set sender name
        $xml_sender_name = $xml_usps->addChild('SenderName');
        $xml_sender_name[0] = $return_address->name;
        
        // set sender email
        $xml_sender_email = $xml_usps->addChild('SenderEmail');
        $xml_sender_email[0] = $return_address->email;
        
        // set recipient name
        $xml_recipient_name = $xml_usps->addChild('RecipientName');
        $xml_recipient_name[0] = $return_address->name;
        

        if (isset($return_address)) {
            $this->addReturnAddress($xml_usps, $return_address);
        }

        dd($xml_usps);
        // intilize query
        $query = http_build_query(
            array(
                'API' => 'USPSReturnsLabel',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );
        
        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create usps_response
            $xml_response = new SimpleXMLElement($usps_response);
            dd($xml_response);

           // dd($xml_response);

            $error_message = null;
            
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                // set rdc
                if ($key == 'RDC') {
                    $label->rdc = (string) $xml_value;
                }

                // set route
                if ($key == 'CarrierRoute') {
                    $label->route = (string) $xml_value;
                }
                
                // barcode set tracking a verification id
                if ($key == 'BarcodeNumber') {
                    $label->verification_id = (string) $xml_value;
                    $label->tracking = (string) $xml_value;
                }

                // if label image then we need to save image to s3 and return url
                if ($key == 'LabelImage') {
                    $image = new Imagick();
                    $image->readImageBlob(base64_decode($xml_value));
                    $image->setImageFormat('jpg');
                    $image_data = $image->getImageBlob();
                    $file_location = 'goa-label-' . Functions::getUUID() . '.jpg';

                    // create s3 client and put object to s3
                    $s3_client = S3Client::factory(array(
                        'credentials' => array(
                            'key'    => env('AWS_ACCESS_KEY_ID'),
                            'secret' => env('AWS_SECRET_ACCESS_KEY')
                        ),
                        'region' => env('AWS_DEFAULT_REGION'),
                        'version' => 'latest'
                    ));

                    // save object to s3 storage
                    $s3_client->putObject(array(
                        'Bucket'     => env('AWS_BUCKET'),
                        'Key'        => $file_location,
                        'Body'       => $image_data
                    ));

                    // clear image for garbage collection
                    $image->clear();
                    $label->url = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $file_location;
                }
 
                // postage 
                /*if ($key == 'Postage') {
                    $rate->rate = round((float) $xml_value, 2);
                    $rate->save();
                }*/

                // description
                if ($key == 'Description') {
                    $error_message = (string) $xml_value;
                    return $response->setFailure($error_message, 'GENERATION_ERROR');
                }
            }

            if (!isset($label->tracking)) return $response->setFailure('Unable to generate label', 'GENERATION_ERROR');
        
            $label->verified = 1;
            $label->verification_service = 'usps';

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE');
        }
    }

    private function addCustoms($xml_usps, $customs) {


        $xml_contents = $xml_usps->addChild('ShippingContents');



        foreach ($customs->items as $item) {

            $xml_item = $xml_contents->addChild('ItemDetail');

            $xml_description = $xml_item->addChild('Description');
            $xml_description[0] = $item['name'];


            $xml_quantity = $xml_item->addChild('Quantity');
            $xml_quantity[0] = $item['quantity'];

            $xml_value = $xml_item->addChild('Value');
            $xml_value[0] = $item['value'];

            $xml_lbs = $xml_item->addChild('NetPounds');
            $xml_lbs[0] = (int) floor($item['weight'] / 16);

            $xml_ounces = $xml_item->addChild('NetOunces');
            $xml_ounces[0] = (int) $item['weight'] % 16;

            $xml_tariff = $xml_item->addChild('HSTariffNumber');
            $xml_tariff[0] = $item['hs_tariff_number'];

            $xml_country = $xml_item->addChild('CountryOfOrigin');
            $xml_country[0] = $item['country_of_origin'];
        }

        $xml_content_type = $xml_usps->addChild('CustomsContentType');
        $xml_content_type[0] = $customs->content_type;
        
        $xml_comments = $xml_usps->addChild('ContentComments');
        $xml_comments[0] = $customs->comments;
    }

    public function validateLabel($label, $shipment, $rate, $customs) {


        // get rate services
        $rate_services = Dynamo\Service::findOrCreate($rate->id);

        // create response
        $response = new Response;

        // get from address 
        $from_address = mysql\Address::find($shipment->from_address_id);
        if (!isset($from_address)) return $response->setFailure('Invalid from address', 'INVALID_FROM_ADDRESS', 'INVALID_LINKED_FROM_ADDRESS');

        // get to address
        $to_address = mysql\Address::find($shipment->to_address_id);
        if (!isset($to_address)) return $response->setFailure('Invalid to address', 'INVALID_TO_ADDRESS', 'INVALID_LINKED_TO_ADDRESS');
        
        // get return address
        $return_address = isset($shipment->return_address_id) ? mysql\Address::find($shipment->return_address_id) : null;

        // get package
        $package = mysql\Package::find($shipment->package_id);
        if (!isset($package)) return $response->setFailure('Invalid package', 'INVALID_PACKAGE', 'INVALID_LINKED_PACKAGE');

        // create xml request
        $xml_usps = new SimpleXMLElement("<eVSRequest USERID=\"$this->user_id\"/>");
        
        // set revision
        $xml_revision = $xml_usps->addChild('Revision');
        $xml_revision[0] = 1;

        // add image parameters
        $this->addImageParameters($xml_usps, $to_address);

        // set from address
        $this->addAddress($xml_usps, 'From', $from_address);

        
        $xml_address_allow = $xml_usps->addChild('AllowNonCleansedOriginAddr');
        $xml_address_allow[0] = 'TRUE';

        // set to address
        $this->addAddress($xml_usps, 'To', $to_address);


        $xml_address_allow = $xml_usps->addChild('AllowNonCleansedDestAddr');
        $xml_address_allow[0] = 'TRUE';

        // set weight
        $xml_weight = $xml_usps->addChild('WeightInOunces');
        $xml_weight[0] = (int) $shipment->weight;

        // set rate info
        $this->addRateInfo($xml_usps, $rate);

        // set package info
        $this->addPackageInfo($xml_usps, $package, $shipment, $rate);

        // add time
        //$this->setTime($xml_usps, time());

        // add express mail options
        if ($rate->service == 'Priority Express') $this->addExpressMailOptions($xml_usps, $rate_services);

        // set ship date 
        $xml_ship_date = $xml_usps->addChild('ShipDate');
        $xml_ship_date[0] = date('m/d/Y', strtotime($label->ship_date));
        
        // add customer reference 
        $xml_reference = $xml_usps->addChild('CustomerRefNo');
        $xml_reference[0] = substr($shipment->reference, 0, 30);

        // set customer ref 2
        $xml_reference_2 = $xml_usps->addChild('CustomerRefNo2');
        $xml_reference_2[0] = substr($shipment->external_user_id, 0, 30);
        
        // add extra services
        $this->addExtraServices($xml_usps, $rate_services, $rate);
        
        // set Crid
        $xml_crid = $xml_usps->addChild('CRID');
        $xml_crid[0] = '36902530';
        
        // set Mid
        $xml_crid = $xml_usps->addChild('MID');
        $xml_crid[0] = '903100539';
        
        // set sender name
        $xml_sender_name = $xml_usps->addChild('SenderName');
        $xml_sender_name[0] = $from_address->name;
        
        // set sender email
        $xml_sender_email = $xml_usps->addChild('SenderEMail');
        $xml_sender_email[0] = $from_address->email;
        
        // set recipient name
        $xml_recipient_name = $xml_usps->addChild('RecipientName');
        $xml_recipient_name[0] = $to_address->name;
        
        // set reciipient option
        $xml_recipient_option = $xml_usps->addChild('ReceiptOption');
        $xml_recipient_option[0] = 'None';

        // set height
        $xml_image_type = $xml_usps->addChild('ImageType');
        $xml_image_type[0] = 'TIF';

        // set hold for manifest
        $xml_manifest_option = $xml_usps->addChild('HoldForManifest');
        $xml_manifest_option[0] = 'Y';

        // print customer ref number
        $xml_print_customer_ref = $xml_usps->addChild('PrintCustomerRefNo');
        $xml_print_customer_ref[0] = 'True';

        if (isset($return_address)) {
            $this->addReturnAddress($xml_usps, $return_address);
        }

        // add Custom Information
        if ($to_address->requiresCustoms()) $this->addCustoms($xml_usps, $customs);

        //dd($xml_usps);
        // intilize query
        $query = http_build_query(
            array(
                'API' => 'eVS',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );
        
        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create usps_response
            $xml_response = new SimpleXMLElement($usps_response);

            //dd($xml_response);

            $error_message = null;
            
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                // set rdc
                if ($key == 'RDC') {
                    $label->rdc = (string) $xml_value;
                }

                // set route
                if ($key == 'CarrierRoute') {
                    $label->route = (string) $xml_value;
                }
                
                // barcode set tracking a verification id
                if ($key == 'BarcodeNumber') {
                    $label->verification_id = (string) $xml_value;
                    $label->tracking = (string) $xml_value;
                }

                // if label image then we need to save image to s3 and return url
                if ($key == 'LabelImage') {
                    $image = new Imagick();
                    $image->readImageBlob(base64_decode($xml_value));
                    $image->setImageFormat('jpg');
                    $image_data = $image->getImageBlob();
                    $file_location = 'goa-label-' . Functions::getUUID() . '.jpg';

                    // create s3 client and put object to s3
                    $s3_client = S3Client::factory(array(
                        'credentials' => array(
                            'key'    => env('AWS_ACCESS_KEY_ID'),
                            'secret' => env('AWS_SECRET_ACCESS_KEY')
                        ),
                        'region' => env('AWS_DEFAULT_REGION'),
                        'version' => 'latest'
                    ));

                    // save object to s3 storage
                    $s3_client->putObject(array(
                        'Bucket'     => env('AWS_BUCKET'),
                        'Key'        => $file_location,
                        'Body'       => $image_data
                    ));

                    // clear image for garbage collection
                    $image->clear();
                    $label->url = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $file_location;
                }
 
                // postage 
                /*if ($key == 'Postage') {
                    $rate->rate = round((float) $xml_value, 2);
                    $rate->save();
                }*/

                // description
                if ($key == 'Description') {
                    $error_message = (string) $xml_value;
                    return $response->setFailure($error_message, 'GENERATION_ERROR', 'USPS_VALIDATION_ERROR');
                }
            }

            if (!isset($label->tracking)) return $response->setFailure('Unable to generate label', 'GENERATION_ERROR', 'USPS_VALIDATION_ERROR');
        
            $label->verified = 1;
            $label->verification_service = 'usps';

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}