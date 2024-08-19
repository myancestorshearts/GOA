<?php
namespace App\Libraries\Label\Validators;

use App\Models\Mysql;

use App\Libraries\Pitney as PitneyClient;
use App\Common\Functions;
use App\Http\Controllers\Response;

use Exception;

use Aws\S3\S3Client;

class Pitney {

    const SERVICE_MAP = [
        'First Class' => 'FCM',
        'Priority Express' => 'EM',
        'Parcel Select' => 'PRCLSEL',
        'Priority' => 'PM'
    ];

    const PACKAGE_MAP = [
        'SmallFlatRateEnvelope' => 'FRE',
        'FlatRateLegalEnvelope' => 'LGLFRENV',
        'FlatRatePaddedEnvelope' => 'PFRENV',
        'FlatRateEnvelope' => 'FRE',
        'Parcel' => 'PKG',
        'SoftPack' => 'SOFTPACK',
        'SmallFlatRateBox' => 'SFRB',
        'MediumFlatRateBox' => 'FRB',
        'LargeFlatRateBox' => 'LFRB',
        'RegionalRateBoxA' => 'RBA',
        'RegionalRateBoxB' => 'RBB'
    ];

    public function validateLabel($label, $shipment, $rate) {

        $response = new Response;

        // initialize pitney client
        $pitney_client = new PitneyClient;

        // get from address 
        $from_address = mysql\Address::find($shipment->from_address_id);
        if (!isset($from_address)) return false;

        // get to address
        $to_address = mysql\Address::find($shipment->to_address_id);
        if (!isset($to_address)) return false;



        // set from address
        $pitney_from_address = [
            'name' => $from_address->name,
            'postalCode' => $from_address->postal,
            'countryCode' => $from_address->country,
            'addressLines' => [
                $from_address->street_1
            ],
            'cityTown' => $from_address->city,
            'stateProvince' => $from_address->state
        ];

        // set to address
        $pitney_to_address = [
            'name' => $to_address->name,
            'postalCode' => $to_address->postal,
            'countryCode' => $to_address->country,
            'addressLines' => [
                $to_address->street_1
            ],
            'cityTown' => $to_address->city,
            'stateProvince' => $to_address->state
        ];

        // set parcel
        $pitney_parcel = [
            'weight' => [
                'unitOfMeasurement' => 'OZ',
                'weight' => (float) $parcel->weight
            ],
            'dimension' => [
                'unitOfMeasurement' => 'IN',
                'length' => (float) $parcel->length,
                'width' => (float) $parcel->width,
                'height' => (float) $parcel->height
            ]
        ];

        // set rates
        $rates = [
            [
                'carrier' => 'USPS',
                'parcelType' => Pitney::PACKAGE_MAP[$parcel->package],
                'serviceId' => Pitney::SERVICE_MAP[$rate->service],
                'specialServices' => [
                    [
                        'specialServiceId' => 'DelCon',
                        'inputParameters' => []
                    ]
                ]
            ]
        ];

        //set shipment options
        $shipment_options = [
            [
                'name' => 'SHIPPER_ID',
                'value' => $pitney_client->shipper_id
            ]
        ];

        // set document
        $document = [
            [
                'size' => 'DOC_4X6',
                'fileFormat' => 'PNG',
                'contentType' => 'BASE64',
                'type' => 'SHIPPING_LABEL'
            ]
        ];

        // create data object
        $pitney_data = [
            'fromAddress' => $pitney_from_address,
            'toAddress' => $pitney_to_address,
            'parcel' => $pitney_parcel,
            'rates' => $rates,
            'documents' => $document,
            'shipmentOptions' => $shipment_options
        ];

        // attempt to purchase label
        try {

            $transaction_id = Functions::getRandomID(25);

            $response = $pitney_client->callPost('/v1/shipments', $pitney_data, $transaction_id);


            if (!isset($response->documents)) return false;

            foreach ($response->documents as $document) {
                foreach ($document->pages as $page) {
                    // create new image
                    $file_location = 'goa-pitney-' . Functions::getUUID() . '.png';

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
                        'Body'       => base64_decode($page->contents)
                    ));

                    // clear image for garbage collection
                    $label->url = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $file_location;
                    $label->tracking = $response->parcelTrackingNumber;
                    $label->verified = 1;
                    $label->verification_service = 'pitney';
                    $label->verification_id = $transaction_id;

                    return $response->setSuccess();
                }

            }

        }
        catch (Exception $ex) {
            return $response->isFailure();
        }



    }
}