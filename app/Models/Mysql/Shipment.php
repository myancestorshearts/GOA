<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;
use App\Common\Validator;
use App\Common\Functions;
use App\Libraries;
use App\Models\Dynamo;
use DB;

class Shipment extends Base
{
    public $table = 'shipments';

    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',
        'verified'
    ];

    public $to_address;
    public $from_address;
    public $package;
    public $model_pairs = [
        ['to_address', 'to_address_id', Address::class],
        ['from_address', 'from_address_id', Address::class],
        ['package', 'package_id', Package::class]
    ];
    
    protected $casts = [
        'ship_date' => 'datetime'
    ];

    /**purpose
     *   rate shipment only that does not save 
     * args
     *   from_address_id (required) 
     *   to_postal (required)
     *   package (required)
     *   services (optional) (SIGNATURE, ADULT_SIGNATURE)
     * returns
     *   shipment
     */
    public static function createRateOnly($model_data, $api_user) {

        // create response
        $response = new Response;
        
        // create address model
        $model = new Shipment;
        $model->user_id = $api_user->id;

    
        // validate from address id 
        $from_address = null;
        if (isset($model_data->from_address_id)) {
            $from_address = Address::find($model_data->from_address_id);
            if (!isset($from_address)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
            if ($from_address->user_id != $model->user_id) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
            if (!Validator::validateBoolean($from_address->from)) return $response->setFailure('Invalid from address - address must be marked as a from address', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');    
        }
        else {
            // validate from postal
            if (!isset($model_data->from_postal)) return $response->setFailure('Invalid from postal', 'INVALID_PROPERTY', 'MISSING_FROM_POSTAL');
            $validated_postal = Validator::validatePostalCode($model_data->from_postal, null, 'US');
            if (!isset($validated_postal)) return $response->setFailure('Invalid from postal', 'INVALID_PROPERTY', 'INVALID_FROM_POSTAL');
            $from_address = new Address;
            $from_address->postal = $validated_postal;
            $from_address->country = 'US';
        }

        // validate country
        $country = isset($model_data->to_country) ? $model_data->to_country : 'US';
        $validated_country = Validator::validateCountry($country);
        if (!isset($validated_country)) return $response->setFailure('Invalid to postal', 'INVALID_PROPERTY', 'INVALID_COUNTRY');


        // validate to postal
        if (!isset($model_data->to_postal)) return $response->setFailure('Invalid to postal', 'INVALID_PROPERTY', 'MISSING_TO_POSTAL');
        $validated_postal = Validator::validatePostalCode($model_data->to_postal, null, $validated_country);
        if (!isset($validated_postal)) return $response->setFailure('Invalid to postal', 'INVALID_PROPERTY', 'INVALID_TO_POSTAL');
        $to_address = new Address;
        $to_address->postal = $validated_postal;
        $to_address->country = $validated_country;

        // validate package
        $package = null;
        $package_data = (object) [];
        if (isset($model_data->package)) $package_data = (object) $model_data->package;

        // get existing package or create a new one
        $package_id = null;
        if (isset($package_data->scalar)) $package_id = $package_data->scalar;
        else if (isset($package_data->id)) $package_id = $package_data->id;
        else if (isset($model_data->package_id)) $package_id = $model_data->package_id;
        if (isset($package_id)) {
            // get existing address
            $package = Package::find($package_id);
            if (!isset($package)) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY', 'INVALID_PACKAGE_ID');
            if ($package->user_id != $model->user_id) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY', 'INVALID_PACKAGE_ID');
        }
        else {
            // if no id is set then try to create address from the model provided
            $package_response = Package::createRateOnly($package_data);
            if ($package_response->isFailure()) return $package_response;
            $package = $package_response->get('model');
            $all_added_models[] = $package;
        }
        
        // check contents value
        if (isset($model_data->contents_value)) {
            $contents_value = (float) ($model_data->contents_value);
            if ($contents_value <= 0) return $response->cleanUp($all_added_models, 'Invalid contents value', 'INVALID_PROPERTY');  
            $model->contents_value = $contents_value; 
        }
    
        // validate services
        $services = [];
        if (isset($model_data->services)) {
            $services_result = Dynamo\Service::validateServices($model_data->services);
            if ($services_result->isFailure()) return $services_result;
            else $services = $services_result->get('services');

            
            foreach ($model_data->services as $service) {
                // if the service is INSURANCE then we need to make sure there is a contents_value
                if (Dynamo\Service::SERVICE_INSURANCE == $service) {
                    if (!isset($model->contents_value)) return $response->cleanUp($all_added_models, 'Contents value is required for insurance service', 'INVALID_PROPERTY', 'CONTENTS_VALUE_REQUIRED');   
                }
            }
        }
        
        // set model weight 
        $model->weight = (float) $model_data->weight;   
        // check weight min
        if ($model->weight < Shipment::TYPES[$package->type]['weight']['min']) 
            return $response->setFailure( 
                'Weight must be more than ' . Shipment::TYPES[$package->type]['weight']['min'], 
                'INVALID_PROPERTY',
                'INVALID_WEIGHT'
            );

        // check weight max
        if ($model->weight > Shipment::TYPES[$package->type]['weight']['max']) 
            return $response->setFailure( 
                'Weight must be less than ' . Shipment::TYPES[$package->type]['weight']['max'], 
                'INVALID_PROPERTY',
                'IVNALID_WEIGHT'
            );

        // validate shipment with validator
        $shipment_validator = $to_address->isUSDomestic() ? new Libraries\Shipment\ShipmentValidator : new Libraries\InternationalShipment\Validator;
        $shipment_response = $shipment_validator->validateShipmentModel($model, $from_address, $to_address, null, $package, $services);
        if ($shipment_response->isFailure()) return $shipment_response->cleanup($all_added_models);


        // deliveyr esimator
        $delivery_estimator = new Libraries\EstimatedDays\Validator;
        $delivery_estimator->validateDays($model, $model->rates, $from_address, $to_address);
        
        // return model
        $response->set('model', $model->getModel());

        // return response
        return $response->setSuccess();
    }

    /**purpose
     *   create a shipment
     * args
     *   from_address_id (required for all instances unless you have a from_address_override)
     *   from_address_override (optional) (allows from_address_id to not be required) (labels with from_address_override will not have be able to be added to scan forms and pickups)
     *   order_group_id (optional)
     *   to_address (required)
     *   package (required)
     *   reference (optional)
     *   services (optional) (SIGNATURE, ADULT_SIGNATURE)
     * returns
     *   shipment
     */
    public static function create($model_data, $user, $api_key_id = null) {

        // create response
        $response = new Response;

        // create address model
        $model = new Shipment;
        $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
        $model->created_user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';

        // reference
        $model->reference = '';
        if (isset($model_data->reference)) {
            $validated_reference = Validator::validateText($model_data->reference, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_reference)) return $response->setFailure('Invalid reference', 'INVALID_PROPERTY', 'INVALID_REFERENCE');
            $model->reference = $validated_reference;
        }

        // external user id
        $model->external_user_id = null;
        if (isset($model_data->external_user_id)) {
            $validated_user_id = Validator::validateText($model_data->external_user_id, ['trim' => true, 'clearable' => false]);
            if (!isset($validated_user_id)) return $response->setFailure('Invalid external user id', 'INVALID_PROPERTY', 'INVALID_EXTERNAL_USER_ID');
            $model->external_user_id = $validated_user_id;
        }

        // create all added models for database cleanup if something fails
        $all_added_models = [];

        // validate from address id
        $from_address = null; 
        if (isset($model_data->from_address_id)) {
            $from_address = Address::find($model_data->from_address_id);
            if (!isset($from_address)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
            if ($from_address->user_id != $model->created_user_id) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
            if (!Validator::validateBoolean($from_address->from)) return $response->setFailure('Invalid from address - address must be marked as a from address', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
            $model->from_address_id = $from_address->id;
        }
        else {
            if (!isset($model_data->from_address_override)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'MISSING_FROM_ADDRESS');

            // validate to address
            $from_address_data = (object) [];
            if (isset($model_data->from_address_override)) $from_address_data = (object) $model_data->from_address_override;
        
            // if no id is set then try to create address from the model provided
            $from_address_response = Address::create($from_address_data, $user, $api_key_id);
            if ($from_address_response->isFailure()) return $from_address_response;
            $from_address = $from_address_response->get('model');
            $all_added_models[] = $from_address;
            $model->from_address_id = $from_address->id;
            $model->from_address_override = 1;
        }
        

        // validate order group
        $to_address = null;
        if (isset($model_data->order_group_id)) {
            $order_group = OrderGroup::find($model_data->order_group_id);
            if (!isset($order_group)) return $response->setFailure('Invalid order group id', 'INVALID_PROPERTY', 'INVALID_ORDER_GROUP_ID');
            if ($order_group->user_id != $model->user_id) return $response->setFailure('Invalid order group id', 'INVALID_PROPERTY', 'INVALID_ORDER_GROUP_ID');
            

            // reference
            $references = [];
            $orders = Order::where('order_group_id', '=', $order_group->id)->get();
            foreach ($orders as $order) {
                $references[] = $order->reference;
            }

            $model->reference = implode(', ', $references);

            // set model
            $model->to_address_id = $order_group->address_id;
            $model->order_group_id = $order_group->id;

            // check to address exists
            $to_address = Address::find($order_group->address_id);
            if (!isset($to_address)) return $response->setFailure('Invalid to address', 'INVALID_PROPERTY', 'INVALID_TO_ADDRESS_ID');
        }
        else {
            // validate to address
            $to_address_data = (object) [];
            if (isset($model_data->to_address))  $to_address_data = (object) $model_data->to_address;

            // get existing address or create a new one
            $to_address_id = null;
            if (isset($to_address_data->scalar)) $to_address_id = $to_address_data->scalar;
            else if (isset($to_address_data->id)) $to_address_id = $to_address_data->id;
            else if (isset($model_data->to_address_id)) $to_address_id = $model_data->to_address_id;
        
            if (isset($to_address_id)) {
                // get existing address
                $to_address = Address::find($to_address_id);
                if (!isset($to_address)) return $response->setFailure('Invalid to address id', 'INVALID_PROPERTY', 'INVALID_TO_ADDRESS_ID');
                if ($to_address->user_id != $model->user_id) return $response->setFailure('Invalid to address id', 'INVALID_PROPERTY', 'INVALID_TO_ADDRESS_ID');
            }
            else {
                // if no id is set then try to create address from the model provided
                $address_response = Address::create($to_address_data, $user, $api_key_id);
                if ($address_response->isFailure()) return $address_response;
                $to_address = $address_response->get('model');
                $all_added_models[] = $to_address;
            }
            $model->to_address_id = $to_address->id;
        }

        // validate return address
        $return_address_data = null;
        if (isset($model_data->return_address))  $return_address_data = (object) $model_data->return_address;

        // get existing address or create a new one
        $return_address_id = null;
        if (isset($return_address_data->scalar)) $return_address_id = $return_address_data->scalar;
        else if (isset($return_address_data->id)) $return_address_id = $return_address_data->id;
        else if (isset($model_data->return_address_id)) $return_address_id = $model_data->return_address_id;
    
        $return_address = null;
        if (isset($return_address_id)) {
            // get existing address
            $return_address = Address::find($return_address_id);
            if (!isset($return_address)) return $response->setFailure('Invalid return address id', 'INVALID_PROPERTY', 'INVALID_RETURN_ADDRESS_ID');
            if ($return_address->user_id != $model->created_user_id) return $response->setFailure('Invalid return address id', 'INVALID_PROPERTY', 'INVALID_RETURN_ADDRESS_ID');
        }
        else if (isset($return_address_data)) {
            // if no id is set then try return create address from the model provided
            $address_response = Address::create($return_address_data, $user, $api_key_id);
            if ($address_response->isFailure()) return $address_response;
            $return_address = $address_response->get('model');
            $all_added_models[] = $return_address;
        }
        if (isset($return_address)) $model->return_address_id = $return_address->id;

        // validate package
        $package = null;
        $package_data = (object) [];
        if (isset($model_data->package)) $package_data = (object) $model_data->package;

        // get existing package or create a new one
        $package_id = null;
        if (isset($package_data->scalar)) $package_id = $package_data->scalar;
        else if (isset($package_data->id)) $package_id = $package_data->id;
        else if (isset($model_data->package_id)) $package_id = $model_data->package_id;
        if (isset($package_id)) {
            // get existing address
            $package = Package::find($package_id);
            if (!isset($package)) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY', 'INVALID_PACKAGE_ID');
            if ($package->user_id != $model->created_user_id) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY', 'INVALID_PACKAGE_ID');
        }
        else {
            // if no id is set then try to create address from the model provided
            $package_response = Package::create($package_data, $user, $api_key_id);
            if ($package_response->isFailure()) return $package_response;
            $package = $package_response->get('model');
            $all_added_models[] = $package;
        }
        $model->package_id = $package->id;

        // validate weight
        if (!isset($model_data->weight)) return $response->cleanUp($all_added_models, 'Invalid weight', 'INVALID_PROPERTY', 'MISSING_WEIGHT');
       
        // set model weight 
        $model->weight = (float) $model_data->weight;   
        // check weight min
        if ($model->weight < Shipment::TYPES[$package->type]['weight']['min']) 
            return $response->cleanUp(
                $all_added_models, 
                'Weight must be more than ' . Shipment::TYPES[$package->type]['weight']['min'], 
                'INVALID_PROPERTY',
                'INVALID_WEIGHT'
            );

        // check weight max
        if ($model->weight > Shipment::TYPES[$package->type]['weight']['max']) 
            return $response->cleanUp(
                $all_added_models, 
                'Weight must be less than ' . Shipment::TYPES[$package->type]['weight']['max'], 
                'INVALID_PROPERTY',
                'INVALID_WEIGHT'
            );


        // check contents value
        if (isset($model_data->contents_value)) {
            $contents_value = (float) ($model_data->contents_value);
            if ($contents_value <= 0) return $response->cleanUp($all_added_models, 'Invalid contents value', 'INVALID_PROPERTY', 'INVALID_CONTENTS_VALUE');  
            $model->contents_value = $contents_value; 
        }

        // validate services
        $services = [];
        if (isset($model_data->services)) {
            $services_result = Dynamo\Service::validateServices($model_data->services);
            if ($services_result->isFailure()) return $services_result;
            else $services = $services_result->get('services');

            foreach ($model_data->services as $service) {
                // if the service is INSURANCE then we need to make sure there is a contents_value
                if (Dynamo\Service::SERVICE_INSURANCE == $service) {
                    if (!isset($model->contents_value)) return $response->cleanUp($all_added_models, 'Contents value is required for insurance service', 'INVALID_PROPERTY', 'CONTENTS_VALUE_REQUIRED');   
                }
            }
        }

        // validate ship_date
        $time_adjusted = time();
        $ship_date = isset($model_data->ship_date) ? strtotime(date('Y-m-d', strtotime($model_data->ship_date))) : $time_adjusted;
        $morning = strtotime(date('Y-m-d', $time_adjusted));
        $future_7_days = strtotime('+7 Days', $morning);
        if ($ship_date < $morning) return $response->setFailure('Ship date cannot be a date in the past', 'INVALID_PROPERTY', 'SHIP_DATE_TOO_EARLY');
        if ($ship_date > $future_7_days) return $response->setFailure('Ship date cannot be greater than 7 days in the future', 'INVALID_PROPERTY', 'SHIP_DATE_EXCEEDED_MAX');
        $model->ship_date = date('Y-m-d', $ship_date);

        // check customs ands then add customs info
        $items = null;
        $customs_content_type = null;
        $customs_comments = null;
        if ($to_address->requiresCustoms()) {
            if (!isset($model_data->items)) return $response->setFailure('To address requires customs', 'MISSING_PROPERTY', 'MISSING_CUSTOMS_ITEMS');
            if (!isset($model_data->customs_content_type)) return $response->setFailure('To address requires customs content type', 'MISSING_PROPERTY', 'MISSING_CUSTOMS_CONTENT_TYPE');
            $customs_comments = isset($model_data->customs_comments) ? trim($model_data->customs_comments) : '';

            if (!is_array($model_data->items)) return $response->setFailure('Items must be an array of items', 'INVALID_PROPERTY', 'INVALID_CUSTOMS_ITEMS');
            $validate_items = Dynamo\Customs::validateItems($model_data->items);
            if ($validate_items->isFailure()) return $validate_items;
            else $items = $validate_items->get('models');

            $valid_content_type = Dynamo\Customs::isValidContentType($model_data->customs_content_type);
            if (!$valid_content_type) return $response->setFailure('Invalid customs content type', 'INVALID_PROPERTY', 'INVALID_CUSTOMS_CONTENT_TYPE');
            $customs_content_type = $model_data->customs_content_type;


            $model->customs = 1;
        }

        // validate shipment with validation service
        $shipment_validator = $to_address->isUSDomestic() ? new Libraries\Shipment\ShipmentValidator : new Libraries\InternationalShipment\Validator;
        $shipment_response = $shipment_validator->validateShipmentModel($model, $from_address, $to_address, $return_address, $package, $services);
        if ($shipment_response->isFailure()) return $shipment_response->cleanup($all_added_models);
        
        // deliveyr esimator
        $delivery_estimator = new Libraries\EstimatedDays\Validator;
        $delivery_estimator->validateDays($model, $model->rates, $from_address, $to_address);

        // return model
        $response->set('model', $model->getModel());

        // save shipment
        $model->save();

        // save rates 
        $rates = $shipment_response->get('rates');
        foreach($rates as $rate) {
            $rate->user_id = $user->id;
            $rate->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
            $rate->shipment_id = $model->id;
            $rate->save();

            // save services 
            $service = Dynamo\Service::findOrCreate($rate->id);
            $service->services = $rate->services;
            $service->updateItem();
        }
        $model->setSubModel('rates', $rates);

        // save items
        if (isset($items)) {
            $dynamo_items = Dynamo\Customs::findOrCreate($model->id);
            $dynamo_items->items = $items;
            $dynamo_items->content_type = $customs_content_type;
            $dynamo_items->comments = $customs_comments;
            $dynamo_items->updateItem();
        }

        // return success
        return $response->setSuccess();
    }

     /**purpose
     *   create a shipment
     * args
     *   from_address_id (required)
     *   order_group_ids (required)
     *   package (required)
     *   weight (required)
     *   services (optional) (SIGNATURE, ADULT_SIGNATURE)
     * returns
     *   shipment
     */
    public static function createMass($model_data, $user, $api_key_id = null) {

        // create response
        $response = new Response;

        // create address model
        $models = [];
        $all_added_models = [];
        // from address
        $model_from_address = null;
        if (!isset($model_data->from_address_id)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY');
        $model_from_address = Address::find($model_data->from_address_id);
        if (!isset($model_from_address)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY');
        if ($model_from_address->user_id != $user->id) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY');
        if (!Validator::validateBoolean($model_from_address->from)) return $response->setFailure('Invalid from address - address must be marked as a from address', 'INVALID_PROPERTY');
    
        // package
        $model_package = null;
        // get package id if that is set in the input api
        $package_data = (object) [];
        if (isset($model_data->package)) $package_data = (object) $model_data->package;
        $package_id = null;
        if (isset($package_data->scalar)) $package_id = $package_data->scalar;
        else if (isset($package_data->id)) $package_id = $package_data->id;
        if (isset($package_id)) {
            // get existing address
            $model_package = Package::find($package_id);
            if (!isset($model_package)) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY');
            if ($model_package->user_id != $user->id) return $response->setFailure('Invalid package id', 'INVALID_PROPERTY');
        }
        else {
            // if no id is set then try to create address from the model provided
            $package_response = Package::create($package_data, $user, $api_key_id);
            if ($package_response->isFailure()) return $package_response;
            $model_package = $package_response->get('model');
            $all_added_models[] = $model_package;
        }

        // validate weight
        if (!isset($model_data->weight)) return $response->cleanUp($all_added_models, 'Invalid weight', 'INVALID_PROPERTY');
        // set model weight 
        $model_weight = (float) $model_data->weight;
        // check weight min
        if ($model_weight < Shipment::TYPES[$model_package->type]['weight']['min']) 
            return $response->cleanUp(
                $all_added_models, 
                'Weight must be more than ' . Shipment::TYPES[$model_package->type]['weight']['min'], 
                'INVALID_PROPERTY'
            );

        // check weight max
        if ($model_weight > Shipment::TYPES[$model_package->type]['weight']['max']) 
            return $response->cleanUp(
                $all_added_models, 
                'Weight must be less than ' . Shipment::TYPES[$model_package->type]['weight']['max'], 
                'INVALID_PROPERTY'
            );

        // check contents value
        if (isset($model->contents_value)) {
            $contents_value = (float) ($model_data->contents_value);
            if ($contents_value <= 0) return $response->cleanUp($all_added_models, 'Invalid contents value', 'INVALID_PROPERTY');  
            $model->contents_value = $contents_value; 
        }
        
        // validate services
        $model_services = [];
        if (isset($model_data->services)) {
            $services_result = Dynamo\Service::validateServices($model_data->services);
            if ($services_result->isFailure())  return $response->cleanUp($all_added_models, $services_result->message, $services_result->error_code);
            else $model_services = $services_result->get('services');

            foreach ($model_data->services as $service) {
                // if the service is INSURANCE then we need to make sure there is a contents_value
                if (Dynamo\Service::SERVICE_INSURANCE == $service) {
                    if (!isset($model->contents_value)) return $response->cleanUp($all_added_models, 'Contents value is required for insurance service', 'INVALID_PROPERTY');   
                }
            }
        }

        // validate ship_date
        $time_adjusted = time();
        $model_ship_date = isset($model_data->ship_date) ? strtotime(date('Y-m-d', strtotime($model_data->ship_date))) : $time_adjusted;
        $morning = strtotime(date('Y-m-d', $time_adjusted));
        $future_7_days = strtotime('+7 Days', $morning);
        if ($model_ship_date < $morning)  return $response->cleanUp($all_added_models, 'Ship date cannot be a date in the past', 'INVALID_PROPERTY');
        if ($model_ship_date > $future_7_days)  return $response->cleanUp($all_added_models, 'Ship date cannot be greater than 7 days in the future', 'INVALID_PROPERTY');
        
        // validate return address
        $model_return_address = null;
        $return_address_data = null;
        if (isset($model_data->return_address))  $return_address_data = (object) $model_data->return_address;
        // get existing address or create a new one
        $return_address_id = null;
        if (isset($return_address_data->scalar)) $return_address_id = $return_address_data->scalar;
        else if (isset($return_address_data->id)) $return_address_id = $return_address_data->id;
        else if (isset($model_data->return_address_id)) $return_address_id = $model_data->return_address_id;
        if (isset($return_address_id)) {
            // get existing address
            $model_return_address = Address::find($return_address_id);
            if (!isset($model_return_address)) return $response->cleanUp($all_added_models, 'Invalid return address id', 'INVALID_PROPERTY');
            if ($model_return_address->user_id != $model->user_id)  return $response->cleanUp($all_added_models, 'Invalid return address id', 'INVALID_PROPERTY');
        }
        else if (isset($return_address_data)) {
            // if no id is set then try return create address from the model provided
            $address_response = Address::create($return_address_data, $user, $api_key_id);
            if ($address_response->isFailure()) return $response->cleanUp($all_added_models, $address_response->message . ' on from address', $address_response->error_code);
            $model_return_address = $address_response->get('model');
            $all_added_models[] = $model_return_address;
        }

        // get all order groups
        if (isset($model_data->order_group_ids)) {
            $order_groups = OrderGroup::whereIn('id', $model_data->order_group_ids)->get();
            if (count($order_groups) != count($model_data->order_group_ids)) return $response->cleanUp($all_added_models, 'One or more order group ids is invalid', 'INVALID_PROPERTY');

            // get all orders mapped by order group id
            $orders_mapped_by_group = [];
            $orders = Order::whereIn('order_group_id', $model_data->order_group_ids)->get();
            foreach($orders as $order) {
                if (!isset($orders_mapped_by_group[$order->order_group_id])) $orders_mapped_by_group[$order->order_group_id] = [];
                $orders_mapped_by_group[$order->order_group_id][] = $order;
            }   

            foreach($order_groups as $order_group) {
                
                // check signed in user
                if ($order_group->user_id != $user->id) return $response->cleanUp($all_added_models, 'One or more orders do not belong to signed in user', 'INVALID_PROPERTY');

                // check to make sure there is at least 1 order on the order group
                if (!isset($orders_mapped_by_group[$order_group->id])) return $response->cleanUp($all_added_models, 'One or more order group did not have an order attached', 'INVALID_PROPERTY'); 

                // check to address exists
                $to_address = Address::find($order_group->address_id);
                if (!isset($to_address)) return $response->cleanUp($all_added_models, 'One order group did not have an address attached', 'INVALID_PROPERTY'); 

                // create shipment and add to shipments
                $model = new Shipment;
                $model->id = Functions::getUUID();
                $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
                $model->created_user_id = $user->id;
                $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
                
                // reference
                $references = [];
                $orders = $orders_mapped_by_group[$order_group->id];
                foreach ($orders as $order) {
                    $references[] = $order->reference;
                }
                $model->reference = implode(', ', $references);

                $model->ship_date = Functions::convertTimeToMysql($model_ship_date);
                $model->from_address_id = $model_from_address->id;
                if(isset($model_return_address)) $model->return_address_id = $model_return_address->id;
                $model->to_address_id = $order_group->address_id;

                $model->package_id = $model_package->id;
                $model->weight = $model_weight;
                $model->order_group_id = $order_group->id;
            
                $models[] = $model;
            }
        }
        else if (isset($model_data->to_addresses)) {

            $addresses = [];
            foreach ($model_data->to_addresses as $to_address) {
                $address_response = Address::create((object) $to_address, $user, $api_key_id, false, true);

                if ($address_response->isFailure()) return $address_response;
                $addresses[] = $address_response->get('model');
            }

            // save shipment
            DB::transaction(function() use($addresses) {
                foreach($addresses as $address) {
                    $address->save();
                }
            });
            foreach($addresses as $address) {
                $all_added_models[] = $address;
            }

            foreach($addresses as $address) {
                $model = new Shipment;
                if (isset($address->reference)) $model->reference = $address->reference;
                if (isset($address->api_reference)) $model->setSubModel('api_reference', $address->api_reference);
                $model->id = Functions::getUUID();
                $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
                $model->created_user_id = $user->id;
                $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
                $model->ship_date = Functions::convertTimeToMysql($model_ship_date);
                $model->from_address_id = $model_from_address->id;
                if(isset($model_return_address)) $model->return_address_id = $model_return_address->id;
                $model->to_address_id = $address->id;
                $model->package_id = $model_package->id;
                $model->weight = $model_weight;
                
                $models[] = $model;
            }
        }
        else if (isset($model_data->to_address_ids)) {
            
            foreach($model_data->to_address_ids as $address_id) {
                $model = new Shipment;
                $model->id = Functions::getUUID();
                $model->user_id = $user->id;
                $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
                $model->ship_date = Functions::convertTimeToMysql($model_ship_date);
                $model->from_address_id = $model_from_address->id;
                if(isset($model_return_address)) $model->return_address_id = $model_return_address->id;
                $model->to_address_id = $address_id;
                $model->package_id = $model_package->id;
                $model->weight = $model_weight;
                
                $models[] = $model;
            }
        }
        else return $response->cleanUp($all_added_models, 'Invalid order group ids', 'INVALID_PROPERTY');
        
        // validate shipment with validation service
        $shipment_validator = new Libraries\Shipment\ShipmentValidator;
        $shipment_response = $shipment_validator->validateShipmentModelMass($models, $model_from_address, $model_return_address, $model_package, $model_services);
        if ($shipment_response->isFailure()) return $shipment_response->cleanup($all_added_models);
        

        $rates = $shipment_response->get('rates');
        
        $rates_by_shipment = [];
        foreach($rates as $rate) {
            if (!isset($rates_by_shipment[$rate->shipment_id])) $rates_by_shipment[$rate->shipment_id] = [];
            $rates_by_shipment[$rate->shipment_id][] = $rate;
        }

        foreach($models as $model) {
            if (isset($rates_by_shipment[$model->id])) $model->setSubModel('rates', $rates_by_shipment[$model->id]);
        }


        // return model
        $response->set('models', Shipment::getModels($models));

        
        Response::addTimer('Calling DB transaction');
        // save shipment
        DB::transaction(function() use($models, $rates, $user) {
            foreach($models as $model) {
                $model->save();
            }

            foreach($rates as $rate) {
                $rate->id = Functions::getUUID();
                $rate->user_id = $user->id;
                $rate->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
                $rate->save();
            }
        });
        
        Response::addTimer('Calling Dynamo Uploads');
        $services = [];
        foreach($rates as $rate) {
            // save services this needs to be set up as a single transaction call. 
            $service = Dynamo\Service::create($rate->id, false);
            $service->services = $rate->services;
            $services[] = $service;
        }
        Dynamo\Service::insertBatch($services);

        Response::addTimer('Completed');
        // return success
        return $response->setSuccess();
    }

      // different packages options and meta requiremenets
    const TYPES = [
        'UspsFlatRatePaddedEnvelope' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsFlatRateLegalEnvelope' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsSmallFlatRateEnvelope' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsFlatRateEnvelope' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'Parcel' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'SoftPack' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsSmallFlatRateBox' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsMediumFlatRateBoxTopLoading' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsMediumFlatRateBoxSideLoading' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsLargeFlatRateBox' => [
            'weight' => [
                'max' => 1120,
                'min' => .001
            ]
        ],
        'UspsRegionalRateBoxATopLoading' => [
            'weight' => [
                'max' => 240,
                'min' => .001
            ]
        ],
        'UspsRegionalRateBoxASideLoading' => [
            'weight' => [
                'max' => 240,
                'min' => .001
            ]
        ],
        'UspsRegionalRateBoxBTopLoading' => [
            'weight' => [
                'max' => 320,
                'min' => .001
            ]
        ],
        'UspsRegionalRateBoxBSideLoading' => [
            'weight' => [
                'max' => 320,
                'min' => .001
            ]
        ]
    ];
}