<?php
namespace App\Libraries\ScanForm\Validators;

use App\Models\Mysql;
use App\Common\Functions;

use SimpleXMLElement;

use Imagick;
use ImagickPixel;

use Storage;

use Aws\S3\S3Client;

use App\Http\Controllers\Response;

use ApiAuth;

class Usps {

    private $user_id;
    private $host;
    
	function __construct() {
		$this->host = env('USPS_HOST', '');
        $this->user_id = ApiAuth::user()->usps_webtools_evs;
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
    
    }

    public function setTime($xml_usps, $time) {

        // set xml date
        $xml_date = $xml_usps->addChild('MailDate');
        $xml_date[0] = date('Ymd', $time);

        // set xml time
        $xml_time = $xml_usps->addChild('MailTime');
        $xml_time[0] = date('His', $time);
    }

    private function addLabel($xml_shipment, $label) {
        
        // add package detail
        $xml_package = $xml_shipment->addChild('PackageDetail');

        // add barcode
        $xml_barcode = $xml_package->addChild('PkgBarcode');
        $xml_barcode[0] = $label->tracking;
    }

    public function validateScanForm($scan_form, $from_address, $labels) {
        
        // generate response
        $response = new Response;

        // create xml request
        $xml_usps = new SimpleXMLElement("<SCANRequest USERID=\"$this->user_id\"/>");

        // set height
        $xml_option = $xml_usps->addChild('Option');


        // set from address
        $this->addAddress($xml_usps, 'From', $from_address);

        // set Crid
        $xml_shipment = $xml_usps->addChild('Shipment');
        foreach ($labels as $label) {
            // add label to shipment
            $this->addLabel($xml_shipment, $label);
        }

        //$manifext = $xml_usps->addChild('CloseManifest');
        //$manifext[0] = 'ALL';
        
        $this->setTime($xml_usps, strtotime($scan_form->ship_date));

        // set height
        $xml_image_type = $xml_usps->addChild('ImageType');
        $xml_image_type[0] = 'TIF';
        

        // intilize query
        $query = http_build_query(
            array(
                'API' => 'SCAN',
                'XML' => explode("\n", $xml_usps->asXML(), 2)[1],
            )
        );

        //dd($xml_usps);
        try {

            // build url and get response
            $url = $this->host . '?' . $query;
            $usps_response = file_get_contents($url);

            // create response
            $xml_response = new SimpleXMLElement($usps_response);

            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                // barcode set tracking a verification id
                if ($key == 'SCANFormNumber') {
                    $scan_form->verification_id = (string) $xml_value;
                    $scan_form->barcode = (string) $xml_value;
                }

                // if scan_form image then we need to save image to s3 and return url
                if ($key == 'SCANFormImage') {
                    $image = new Imagick();
                    $image->readImageBlob(base64_decode($xml_value));
                    $image->setImageFormat('jpg');
                    $image_data = $image->getImageBlob();
                    $file_location = 'goa-scan-form-' . Functions::getUUID() . '.jpg';

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
                    $scan_form->url = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $file_location;
                }
                
                if ($key == 'Description') {
                    $error_message = (string) $xml_value;
                    return $response->setFailure($error_message, 'USPS_VALIDATION_ERROR', 'USPS_VALIDATION_ERROR');
                }
            }
        
            $scan_form->verified = 1;
            $scan_form->verification_service = 'usps';
            
            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }
}