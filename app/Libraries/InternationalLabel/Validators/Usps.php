<?php
namespace App\Libraries\InternationalLabel\Validators;

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
    private $host;
    
    const COUNTRY_MAP = [
        'CA' => 'Canada'
    ];

    const SERVICE_PRIORITY = 'Priority International';
    const SERVICE_FIRST_CLASS = 'First Class International';
    const SERVICE_PRIORITY_EXPRESS = 'Priority Express International';

    const API_SERVICE_CALL = [
        Usps::SERVICE_PRIORITY_EXPRESS => 'eVSExpressMailIntl',
        Usps::SERVICE_PRIORITY  => 'eVSPriorityMailIntl',
        Usps::SERVICE_FIRST_CLASS => 'eVSFirstClassMailIntl'
    ];

	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
	}


    private function addImageParameters($xml_usps) {
        // add image parameters
        $xml_image_parameters = $xml_usps->addChild('ImageParameters');
        $xml_image_parameter = $xml_image_parameters->addChild('ImageParameter');
        $xml_image_parameter[0] = '4X6LABEL';
    }


    private function addFromAddress($xml_usps, $from_address) {
    
        $name_parts = explode(' ', $from_address->name);
        $first_name = '';
        $last_name = 'Sender';
        if (count($name_parts) > 1) {
            $last_name = array_pop($name_parts);
            $first_name = implode(' ', $name_parts);
        }
        else {
            $first_name = $name_parts[0];
        }

        $xml_first_name = $xml_usps->addChild('FromFirstName');
        $xml_first_name[0] = $first_name;
        
        $xml_last_name = $xml_usps->addChild('FromLastName');
        $xml_last_name[0] = $last_name;

        $xml_firm = $xml_usps->addChild('FromFirm');
        $xml_firm[0] = Functions::isEmpty($from_address->company) ? 'Company' : $from_address->company ;

        // set address
        $xml_address_1 = $xml_usps->addChild('FromAddress1');
        $xml_address_1[0] = $from_address->street_2;
        
        // set address 2
        $xml_address_2 = $xml_usps->addChild('FromAddress2');
        $xml_address_2[0] = $from_address->street_1;
        
        // set city
        $xml_city = $xml_usps->addChild('FromCity');
        $xml_city[0] = $from_address->city;

        // set state
        $xml_state = $xml_usps->addChild('FromState');
        $xml_state[0] = $from_address->state;
        
        // set postal
        $xml_zip5 = $xml_usps->addChild('FromZip5');
        $xml_zip5[0] = $from_address->postal;
        
        // ignore postal 4
        $xml_postal = $xml_usps->addChild('FromZip4');
        
        // set phone
        $xml_state = $xml_usps->addChild('FromPhone');
        $xml_state[0] = '8018857166';//$from_address->phone;
    }

    private function addSenderAddress($xml_usps, $from_address) {
    
        $name_parts = explode(' ', $from_address->name);
        $first_name = '';
        $last_name = 'Sender';
        if (count($name_parts) > 1) {
            $last_name = array_pop($name_parts);
            $first_name = implode(' ', $name_parts);
        }
        else {
            $first_name = $name_parts[0];
        }

        $xml_first_name = $xml_usps->addChild('SenderFirstName');
        $xml_first_name[0] = $first_name;
        
        $xml_last_name = $xml_usps->addChild('SenderLastName');
        $xml_last_name[0] = $last_name;

        $xml_firm = $xml_usps->addChild('SenderBusinessName');
        $xml_firm[0] = Functions::isEmpty($from_address->company) ? 'Company' : $from_address->company ;

        // set address
        $xml_address_1 = $xml_usps->addChild('SenderAddress1');
        $xml_address_1[0] = $from_address->street_1 . ' ' . $from_address->street_2;
        
        // set city
        $xml_city = $xml_usps->addChild('SenderCity');
        $xml_city[0] = $from_address->city;

        // set state
        $xml_state = $xml_usps->addChild('SenderState');
        $xml_state[0] = $from_address->state;
        
        // set postal
        $xml_zip5 = $xml_usps->addChild('SenderZip5');
        $xml_zip5[0] = $from_address->postal;
        
        // set phone
        $xml_state = $xml_usps->addChild('SenderPhone');
        $xml_state[0] = $from_address->phone;
    }

    
    private function addToAddress($xml_usps, $to_address) {
    
        $name_parts = explode(' ', $to_address->name);
        $first_name = '';
        $last_name = 'Receiver';
        if (count($name_parts) > 1) {
            $last_name = array_pop($name_parts);
            $first_name = implode(' ', $name_parts);
        }
        else {
            $first_name = $name_parts[0];
        }

        $xml_first_name = $xml_usps->addChild('ToFirstName');
        $xml_first_name[0] = $first_name;
        
        $xml_last_name = $xml_usps->addChild('ToLastName');
        $xml_last_name[0] = $last_name;

        $xml_firm = $xml_usps->addChild('ToFirm');
        $xml_firm[0] = Functions::isEmpty($to_address->company) ? 'Company' : $to_address->company ;

        // set address
        $xml_address_1 = $xml_usps->addChild('ToAddress1');
        $xml_address_1[0] = $to_address->street_2;
        
        // set address 2
        $xml_address_2 = $xml_usps->addChild('ToAddress2');
        $xml_address_2[0] = $to_address->street_1;
        
        // set city
        $xml_city = $xml_usps->addChild('ToCity');
        $xml_city[0] = $to_address->city;

        // set state
        $xml_province = $xml_usps->addChild('ToProvince');
        $xml_province[0] = $to_address->state;
        
        // set country
        $xml_country = $xml_usps->addChild('ToCountry');
        $xml_country[0] = Usps::COUNTRY_MAP[$to_address->country];
        
        // set postal
        $xml_postal = $xml_usps->addChild('ToPostalCode');
        $xml_postal[0] = $to_address->postal;

        // xml
        $xml_postal = $xml_usps->addChild('ToPOBoxFlag');
        $xml_postal[0] = 'N';
    }

    private function addRedirectAddress($xml_usps, $rate, $return_address) {

        if (isset($return_address)) {
            $xml_non_delivery = $xml_usps->addChild('NonDeliveryOption');
            $xml_non_delivery[0] = 'REDIRECT';

            $xml_name = $xml_usps->addChild('RedirectName');
            $xml_name[0] = $return_address->name;
            
            $xml_address = $xml_usps->addChild('RedirectAddress');
            $xml_address[0] = $return_address->street_1 . ' ' . $return_address->street_2;
            
            $xml_city = $xml_usps->addChild('RedirectCity');
            $xml_city[0] = $return_address->city;
            
            $xml_state = $xml_usps->addChild('RedirectState');
            $xml_state[0] = $return_address->state;
            
            $xml_postal = $xml_usps->addChild('RedirectZipCode');
            $xml_postal[0] = $return_address->postal;
        }
        else {
            $xml_non_delivery = $xml_usps->addChild('NonDeliveryOption');
            $xml_non_delivery[0] = 'RETURN';
        }
    }

    private function addShippingContents($xml_usps, $customs) {
        $xml_shipping_contents = $xml_usps->addChild('ShippingContents');

       

        foreach ($customs->items as $item) {

            $xml_item = $xml_shipping_contents->addChild('ItemDetail');

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
    }

    public function validateLabel($label, $shipment, $rate, $customs) {

        // create response
        $response = new Response;

        // get from address
        $from_address = Mysql\Address::find($shipment->from_address_id);
        if (!isset($from_address)) return $response->setFailure('Shipment has no linked from address', 'CORRUPT_SHIPMENT', 'INVALID_LINKED_FROM_ADDRESS');
        $to_address = Mysql\Address::find($shipment->to_address_id);
        if (!isset($to_address)) return $response->setFailure('Shipment has no linked to address', 'CORRUPT_SHIPMENT', 'INVALID_LINKED_TO_ADDRESS');
        $return_address = isset($shipment->return_address_id) ? Mysql\Address::find($shipment->return_address_id) : null;
    
        // get package
        $package = mysql\Package::find($shipment->package_id);
        if (!isset($package)) return $response->setFailure('Invalid package', 'INVALID_PACKAGE', 'INVALID_LINKED_PACKAGE');

        // check country
        if (!isset(Usps::COUNTRY_MAP[$to_address->country])) return $response->setFailure('Country not supported', 'NOT_SUPPORTED', 'NOT_SUPPORTED_COUNTRY');

        // get rate services
        $rate_services = Dynamo\Service::findOrCreate($rate->id);

        // create xml request
        $xml_usps = new SimpleXMLElement("<" . Usps::API_SERVICE_CALL[$rate->service] . "Request USERID=\"$this->user_id\"/>");

        // set revision
        $xml_revision = $xml_usps->addChild('Revision');
        $xml_revision[0] = 2;

        // set image parameters
        $this->addImageParameters($xml_usps);
        
        // add from address
        $this->addFromAddress($xml_usps, $from_address);

        // add to address
        $this->addToAddress($xml_usps, $to_address);

        // add non delivery option
        if ($rate->service != Usps::SERVICE_FIRST_CLASS) $this->addRedirectAddress($xml_usps, $rate, $return_address);

        // we need to add container here
        // priority container

        // add shipping contents
        $this->addShippingContents($xml_usps, $customs);
        
        // add postage value
        $xml_postage = $xml_usps->addChild('Postage'); 
        $xml_postage[0] = $rate->rate_list;

        // pounds
        $xml_pounds = $xml_usps->addChild('GrossPounds');
        $xml_pounds[0] = (int) floor($shipment->weight / 16);

        // ounces
        $xml_ounces = $xml_usps->addChild('GrossOunces');
        $xml_ounces[0] = (int) $shipment->weight % 16;

        // potential first class machineable


        // Content Type
        $xml_content_type = $xml_usps->addChild('ContentType');
        $xml_content_type[0] = $customs->content_type;

        // Content Type
        $xml_agreement = $xml_usps->addChild('Agreement');
        $xml_agreement[0] = 'Y';

        // comments
        $xml_comments = $xml_usps->addChild('Comments');
        $xml_comments[0] = $customs->comments;

        // invoice number 
        $xml_invoice = $xml_usps->addChild('InvoiceNumber');
        $xml_invoice[0] = substr($shipment->reference, 0, 30);

        // set image type
        $xml_image_type = $xml_usps->addChild('ImageType');
        $xml_image_type[0] = 'TIF';

        
        // add customer reference 
        $xml_reference = $xml_usps->addChild('CustomerRefNo');
        $xml_reference[0] = substr($shipment->reference, 0, 30);

        // set customer ref 2
        $xml_reference_2 = $xml_usps->addChild('CustomerRefNo2');
        $xml_reference_2[0] = substr($shipment->external_user_id, 0, 30);

        // set label date 
        $xml_ship_date = $xml_usps->addChild('LabelDate');
        $xml_ship_date[0] = date('m/d/Y', strtotime($label->ship_date));

        // set hold for manifest
        $xml_manifest_option = $xml_usps->addChild('HoldForManifest');
        $xml_manifest_option[0] = 'Y';


        // set price option
        if ($rate->service != Usps::SERVICE_FIRST_CLASS) {
            $xml_manifest_option = $xml_usps->addChild('PriceOptions');
            $xml_manifest_option[0] = 'COMMERCIAL PLUS';
        }


        // set length
        $xml_length = $xml_usps->addChild('Length');
        $xml_length[0] = (float) $package->length;

        // set width
        $xml_width = $xml_usps->addChild('Width');
        $xml_width[0] = (float) $package->width;
        
        // set height
        $xml_height = $xml_usps->addChild('Height');
        $xml_height[0] = (float) $package->height;

        // set rate indicator
        $xml_destination_rate_indicator = $xml_usps->addChild('DestinationRateIndicator');
        $xml_destination_rate_indicator[0] = 'N';
        

        // set Mid
        $xml_mid = $xml_usps->addChild('MID');
        $xml_mid[0] = '903100539';
        
        // set Crid
        $xml_crid = $xml_usps->addChild('CRID');
        $xml_crid[0] = '36902530';

        $this->addSenderAddress($xml_usps, $from_address);

        // intilize query
        $query = http_build_query(
            array(
                'API' => Usps::API_SERVICE_CALL[$rate->service],
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
                if ($key == 'Postage') {
                    $rate->rate = round((float) $xml_value, 2);
                    $rate->save();
                }

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