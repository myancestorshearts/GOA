<?php

namespace App\Models\Dynamo;
use App\Common\Validator;

use App\Http\Controllers\Response;

class Customs extends Base
{
    // set table
	protected $table = 'Customs';

    const TYPE_MERCHANDISE = 'MERCHANDISE';
    const TYPE_GIFT = 'GIFT';
    const TYPE_DOCUMENTS = 'DOCUMENTS';
    const TYPE_SAMPLE = 'SAMPLE';
    const TYPE_RETURN = 'RETURN';
    const TYPE_OTHER = 'OTHER';
    const TYPE_HUMANITARIAN = 'HUMANITARIAN';
    const TYPE_DANGEROUSGOODS = 'DANGEROUSGOODS';

    /**purpose
     *   validate services array
     * args 
     *   services
     * returns
     *   response (null if failed)
     */
    public static function validateItems($data_array) {
        // create response
        $response = new Response;

        // create validated services
        $validated_items = [];

        foreach ($data_array as $data) {

            $model_data = (object) $data;
            $model = [
                'name' => '',
                'quantity' => '',
                'weight' => '',
                'value' => '',
                'hs_tariff_number' => null,
                'content_type' => null,
                'comments' => '',
                'country_of_origin' => 'US'
            ];

            if (!isset($model_data->name)) return $response->setFailure('Item requires name', 'MISSING_PROPERTY');
            $validated_name = Validator::validateText($model_data->name, ['trim' => true]);
            if (!isset($validated_name)) return $response->setFailure('Invalid name', 'INVALID_PROPERTY');
            $model['name'] = $validated_name;
            
            // quantity
            if (!isset($model_data->quantity)) return $response->setFailure('Item requires quantity', 'MISSING_PROPERTY');
            $validated_quantity = Validator::validateFloat($model_data->quantity, ['min' => .01]);
            if (!isset($validated_quantity)) return $response->setFailure('Invalid quantity', 'INVALID_PROPERTY');
            $model['quantity'] = $validated_quantity;

            // value
            if (!isset($model_data->value)) return $response->setFailure('Item requires value', 'MISSING_PROPERTY');
            $validated_value = Validator::validateFloat($model_data->value, ['min' => .01]);
            if (!isset($validated_value)) return $response->setFailure('Invalid value', 'INVALID_PROPERTY');
            $model['value'] = $validated_value;

            // weight
            if (!isset($model_data->weight)) return $response->setFailure('Item requires weight', 'MISSING_PROPERTY');
            $validated_weight = Validator::validateFloat($model_data->weight, ['min' => .01]);
            if (!isset($validated_weight)) return $response->setFailure('Invalid weight', 'INVALID_PROPERTY');
            $model['weight'] = $validated_weight;

            // hs tariff number
            if (isset($model_data->hs_tariff_number)) {
                $validated_hs_tariff_number = Validator::validateText($model_data->hs_tariff_number, ['trim' => true]);
                if (!isset($validated_hs_tariff_number)) return $response->setFailure('Invalid hs tariff number', 'INVALID_PROPERTY');
                $model['hs_tariff_number'] = $validated_hs_tariff_number;
            }


            $validated_items[] = $model;
        }


        // set services 
        $response->set('models', $validated_items);

        // return success response
        return $response->setSuccess();
    }

    /**
     * purpose
     *   validate content type
     * args
     *   content type
     * returns
     *   valid or not
     */
    public static function isValidContentType($content_type) {

        return in_array($content_type, [
            Customs::TYPE_MERCHANDISE,
            Customs::TYPE_GIFT,
            Customs::TYPE_DOCUMENTS,
            Customs::TYPE_SAMPLE,
            Customs::TYPE_RETURN,
            Customs::TYPE_OTHER,
            Customs::TYPE_HUMANITARIAN,
            Customs::TYPE_DANGEROUSGOODS
        ]);
    }
}