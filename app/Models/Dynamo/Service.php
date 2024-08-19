<?php

namespace App\Models\Dynamo;
use App\Common\Validator;


use App\Http\Controllers\Response;

class Service extends Base
{
    // set table
	protected $table = 'RateServices';

    const SERVICE_SIGNATURE = 'SIGNATURE';
    const SERVICE_ADULT_SIGNATURE = 'ADULT_SIGNATURE';
    const SERVICE_INSURANCE = 'INSURANCE';

    const INVALID_COMBINATIONS = [
        [
            Service::SERVICE_SIGNATURE,
            Service::SERVICE_ADULT_SIGNATURE
        ]
    ];

    /**purpose
     *   validate services array
     * args 
     *   services
     * returns
     *   response (null if failed)
     */
    public static function validateServices($services) {

        // create response
        $response = new Response;

        // create validated services
        $validated_services = [];

        // make sure services is an array
        if (!is_array($services)) return $response->setFailure('Services must be an array', 'INVALID_PROPERTY');

        // loop through services to validate each one
        foreach ($services as $service) {

            $validated_service = Validator::validateEnum($service, ['enums' => [
                Service::SERVICE_SIGNATURE,
                Service::SERVICE_ADULT_SIGNATURE,
                Service::SERVICE_INSURANCE
            ]]);

            if (!isset($validated_service)) return $response->setFailure('Invalid service: ' . $service, 'INVALID_PROPERTY');
            $validated_services[] = $validated_service;
        }

        // check services that cannot be combined
        foreach (Service::INVALID_COMBINATIONS as $invalid_combination) {
            $found_match = false;
            foreach ($invalid_combination as $combination_service) {
                if (in_array($combination_service, $validated_services)) {
                    if ($found_match) return $response->setFailure('Services cannot be combined: ' . implode(', ', $invalid_combination), 'INVALID_PROPERTY');
                    $found_match = true;
                }
            }
        }

        // set services 
        $response->set('services', $validated_services);

        // return success response
        return $response->setSuccess();
    }
}