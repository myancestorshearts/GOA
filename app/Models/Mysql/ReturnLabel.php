<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;
use App\Common\Functions;
use App\Libraries;
use App\Common\Validator;
use Aws\S3\S3Client;
use App\Models\Dynamo;

use ApiAuth;

class ReturnLabel extends Base
{
    public $table = 'return_labels';
    
    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',   
        'verified'
    ];

    
    /**purpose
     *   create a label
     * args
     *   order_group_id (optional)
     *   shipment_id (required)
     *   rate_id (required)
     *   ship_date (optional) (default - current day)
     * returns
     *   label
     */
    public static function create($model_data, $user, $api_key_id = null) {
        
        // create response
        $response = new Response;

        // create address model
        $model = new ReturnLabel;
        $model->user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
        
        // get label
        if (!isset($model_data->label_id)) return $response->setFailure('Missing label id', 'MISSING_REQUIRED_FIELDS', 'MISSING_LABEL_ID');
        $label = Label::find($model_data->label_id);
        if (!isset($label)) return $response->setFailure('Invalid label', 'INVALID_PROPERTY', 'INVALID_LABEL_ID');
        if ($label->user_id != $user->id) return $response->setFailure('Invalid label', 'INVALID_PROPERTY', 'INVALID_LABEL_ID');
        $model->label_id = $label->id;

        // customer address
        $shipment = Shipment::find($label->shipment_id);
        if (!isset($shipment)) return $response->setFailure('Invalid shipment', 'INVALID_PROPERTY', 'INVALID_SHIPMENT_ID');
        $model->customer_address_id = $shipment->to_address_id;

        // return address
        $model->return_address_id = isset($label->return_address_id) ? $label->return_address_id : $label->from_address_id;
        
        // reference 
        $model->reference = $shipment->reference;

        // service
        $model->service = $label->weight < 16 ? 'First Class' : 'Priority';
        
        // weight
        $model->weight = $label->weight;

        // external user id
        $model->external_user_id = $label->external_user_id;

        // validate shipment with validation service
        $return_label_validator = new Libraries\ReturnLabel\Validator;
        $return_label_response = $return_label_validator->validateReturnModel($model);
        if ($return_label_response->isFailure()) return $return_label_response;

        // save model
        $model->save();

        // set model
        $response->set('model', $model);
        return $response->setSuccess();
    }
}