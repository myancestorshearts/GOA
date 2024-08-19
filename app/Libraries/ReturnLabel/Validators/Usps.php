<?php
namespace App\Libraries\ReturnLabel\Validators;

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
        $this->user_id = ApiAuth::user()->usps_webtools_returns;
	}

   /* private function addReturnAddress($xml_usps, $address) {
        
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
    }*/

    private function addAddress($xml_usps, $prefix, $address) {
        
        if ($prefix == 'Customer') {
            // set name
            $xml_name = $xml_usps->addChild($prefix . 'FirstName');
            $xml_name[0] = $address->name;
            
            $xml_name = $xml_usps->addChild($prefix . 'LastName');
            $xml_name[0] = $address->name;
        }
        
        // set name
        $xml_company = $xml_usps->addChild($prefix . 'Firm');
        $xml_company[0] = Functions::isEmpty($address->company) ? 'Firm' : $address->company;
        
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
        $xml_zip4[0] = $address->postal_sub;
        
        // set name

        if ($prefix == 'Retailer') {
            $xml_phone = $xml_usps->addChild($prefix . 'Phone');
            $xml_phone[0] = $address->phone;
        }
    }

    public function addImageParameters($xml_usps) {

        // set image paramters
        $xml_image_parameters = $xml_usps->addChild('ImageParameters');
        
        // set imagie paramter
        $xml_image_parameter = $xml_image_parameters->addChild('ImageType');
        $xml_image_parameter[0] = 'TIF';
    }
    


    public function validateReturn($return_label) {

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
        if (!isset($return_address)) return $response->setFailure('Invalid return address', 'INVALID_RETURN_ADDRESS', 'INVALID_RETURN_ADDRESS');

        // create xml request
        $xml_usps = new SimpleXMLElement("<USPSReturnsLabelRequest USERID=\"$this->user_id\"/>");
        
        // set revision
        $xml_revision = $xml_usps->addChild('Revision');

        // add image parameters
        $this->addImageParameters($xml_usps);

        // set to address
        $this->addAddress($xml_usps, 'Customer', $customer_address);

        $xml_allow_non_clenased = $xml_usps->addChild('AllowNonCleansedOriginAddr');
        $xml_allow_non_clenased[0] = 'true';
        
        // set from address
        $this->addAddress($xml_usps, 'Retailer', $return_address);

        // set weight
        $xml_weight = $xml_usps->addChild('WeightInOunces');
        $xml_weight[0] = (int) $return_label->weight;

        // add express mail options
        $xml_service = $xml_usps->addChild('ServiceType');
        $xml_service[0] = strtoupper($return_label->service);

        $xml_postage_type = $xml_usps->addChild('ReturnsPostageType');
        $xml_postage_type[0] = '5';

        $xml_machineable = $xml_usps->addChild('Machinable');
        $xml_machineable[0] = 'true';

        // set Mid
        $xml_crid = $xml_usps->addChild('MailOwnerMID');
        $xml_crid[0] = '903100539';

        // add customer reference 
        $xml_reference = $xml_usps->addChild('CustomerRefNo');
        $xml_reference[0] = substr($return_label->reference, 0, 30);

        // set customer ref 2
        $xml_reference_2 = $xml_usps->addChild('CustomerRefNo2');
        $xml_reference_2[0] = substr(isset($return_label->external_user_id) ? $return_label->external_user_id : ApiAuth::user()->id, 0, 30);
        
        // set sender name
        $xml_sender_name = $xml_usps->addChild('SenderName');
        $xml_sender_name[0] = $return_address->name;
        
        // set sender email
        $xml_sender_email = $xml_usps->addChild('SenderEmail');
        $xml_sender_email[0] = $return_address->email;
        
        // set recipient name
        $xml_recipient_name = $xml_usps->addChild('RecipientName');
        $xml_recipient_name[0] = $return_address->name;

        //dd($xml_usps);
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
            //dd($xml_response);

           // dd($xml_response);

            $error_message = null;
            
            // loop through children to find first address
            foreach($xml_response->children() as $key => $xml_value) {

                // set rdc
                if ($key == 'RDC') {
                    $return_label->rdc = (string) $xml_value;
                }

                // set route
                if ($key == 'CarrierRoute') {
                    $return_label->route = (string) $xml_value;
                }

                // set zone
                if ($key == 'Zone') {
                    $return_label->zone = (string) $xml_value;
                }
                
                // barcode set tracking a verification id
                if ($key == 'BarcodeNumber') {
                    $return_label->verification_id = (string) $xml_value;
                    $return_label->tracking = (string) $xml_value;
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
                    $return_label->url = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $file_location;
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

            if (!isset($return_label->tracking)) return $response->setFailure('Unable to generate label', 'GENERATION_ERROR', 'USPS_VALIDATION_ERROR');
        
            $return_label->verified = 1;
            $return_label->verification_service = 'usps';

            return $response->setSuccess();
        }
        catch (\EasyPost\Error $ex) {
            return $response->setFailure('Error connecting to usps servers', 'USPS_CONNECTION_FAILURE', 'USPS_CONNECTION_FAILURE');
        }
    }

   /* private function addCustoms($xml_usps, $customs) {


        $xml_contents = $xml_usps->addChild('ShippingContents');



        foreach ($customs->items as $item) {

            $xml_item = $xml_contents->addChild('ItemDetail');

            $xml_description = $xml_item->addChild('Description');
            $xml_description[0] = $item['name'];


            $xml_quantity = $xml_item->addChild('Quantity');
            $xml_quantity[0] = $item['quantity'];

            $xml_value = $xml_item->addChild('Value');
            $xml_value[0] = $item['value'];

            $xml_ounces = $xml_item->addChild('NetPounds');
            $xml_ounces[0] = 0;

            $xml_ounces = $xml_item->addChild('NetOunces');
            $xml_ounces[0] = $item['weight'];

            $xml_tariff = $xml_item->addChild('HSTariffNumber');
            $xml_tariff[0] = $item['hs_tariff_number'];

            $xml_country = $xml_item->addChild('CountryOfOrigin');
            $xml_country[0] = $item['country_of_origin'];
        }

        $xml_content_type = $xml_usps->addChild('CustomsContentType');
        $xml_content_type[0] = $customs->content_type;
        
        $xml_comments = $xml_usps->addChild('ContentComments');
        $xml_comments[0] = $customs->comments;
    }*/

}