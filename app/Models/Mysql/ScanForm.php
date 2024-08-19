<?php

namespace App\Models\Mysql;
use App\Http\Controllers\Response;

use App\Common\Validator;
use App\Common\Functions;
use App\Libraries;

use DB;

class ScanForm extends Base
{
    public $table = 'scan_forms';

    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',
        'verified'
    ];

    public $from_address;
    public $model_pairs = [
        ['from_address', 'from_address_id', Address::class]
    ];

    
    // search users
    public static $search_parameters = [
        [
            'argument' => 'external_user_id',
            'column' => 'external_user_id', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'pickup_id',
            'column' => 'pickup_id', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'from_address_id',
            'column' => 'from_address_id', 
            'type' => 'EQUAL'
        ]
    ];

    
    protected $casts = [
        'ship_date' => 'datetime'
    ];

    /**purpose
     *   create a shipment
     * args
     *   from_address_id (required)
     *   to_address (required)
     *   package (required)
     *   reference (optional)
     * returns
     *   shipment
     */
    public static function create($model_data, $user, $api_key_id = null, $external_user_id = null) {

        // create response
        $response = new Response;
        
        // create scan form model
        $model = new ScanForm;
        $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
        $model->created_user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
        $model->external_user_id = $external_user_id;

        // check from address
        if (!isset($model_data->from_address_id)) return $response->setFailure('Missing from address id', 'MISSING_REQUIRED_FIELDS', 'MISSING_FROM_ADDRESS_ID');
        $from_address = Address::find($model_data->from_address_id);
        if (!isset($from_address)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
        if ($from_address->user_id != $model->user_id) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
        if (!Validator::validateBoolean($from_address->from)) return $response->setFailure('Invalid from address - address must be marked as a from address', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRES');
        $model->from_address_id = $from_address->id;

        // check label_ids 
        if (!isset($model_data->label_ids)) return $response->setFailure('Missing label_ids', 'MISSING_REQUIRED_FIELDS', 'MISSING_LABEL_IDS');
        $label_ids = $model_data->label_ids;
        if (count($label_ids) <= 1) return $response->setFailure('Scan forms must have more than one label', 'INVALID_COUNT', 'INVALID_LABEL_COUNT');
        $labels = Label::whereIn('id', $label_ids)->get();
        if (count($label_ids) != count($labels)) return $response->setFailure('Labels found count does not match id count', 'INVALID_LABEL_ID', 'INVALID_LABEL_ID');
        
        // get ship_date
        if (!isset($model_data->ship_date)) return $response->setFailure('Missing ship date', 'MISSING_REQUIRED_FIELDS', 'MISSING_SHIP_DATE');
        $model->ship_date = $model_data->ship_date;

        // iterate through labels to see if any have been added to the scan form
        $verification_service = null;
        foreach ($labels as $label) {
            // make sure the label was not overriden by from address
            if (Validator::validateBoolean($label->from_address_override)) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' had from address overriden. Overriden from addresses cannot be added to scan forms', 
                    'NOT_COMPLIANT',
                    'NOT_COMPLAINT'
                );
            }

            // check ship_date
            if ($label->ship_date != $model->ship_date) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' does not have a matching ship date', 
                    'INVALID_PROPERTY',
                    'SHIP_DATE_MISMATCH'
                );
            }

            // check external user id
            if ($label->external_user_id != $external_user_id) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' does not have matching external user id', 
                    'INVALID_PROPERTY',
                    'EXTERNAL_USER_ID_MISMATCH'
                );
            }

            // check user id
            if ($label->user_id != $model->user_id) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' is invalid', 
                    'INVALID_PROPERTY',
                    'INVALID_LABEL_ID'
                );
            }

            // check from address id
            if ($label->from_address_id != $model->from_address_id) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' has a different from address linked', 
                    'INVALID_ADDRESS',
                    'FROM_ADDRESS_MISMATCH'
                );
            }

            // check existing scan form
            if (isset($label->scan_form_id)) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' has already been added to a scan form', 
                    'INVALID_COUNT',
                    'DUPLICATE_SCAN_FORM'
                );
            }

            // check existing refunded pending 
            if (Validator::validateBoolean($label->refunded) ||
                Validator::validateBoolean($label->refund_pending)
            ) {
                return $response->setFailure(
                    'Label with tracking: ' . $label->tracking . ' has been marked cancelled and cannot be added to a scan form.', 
                    'LABEL_REFUNDED',
                    'LABEL_REFUNDED'
                );
            }

            // check verification service
            if (!isset($verification_service)) $verification_service = $label->verification_service;
            if ($label->verification_service != $verification_service) {
                return $response->setFailure(
                    'All labels must have the same carrier', 
                    'CARRIER_MIXED',
                    'CARRIER_MISMATCH'
                );
            }
        }

        // set carrier
        $model->carrier = $verification_service;
        $model->label_count = count($labels);

        // validate scan form with validation service
        $validator = new Libraries\ScanForm\ScanFormValidator;
        $validator = $validator->validateScanFormModel($model, $from_address, $labels);
        if ($validator->isFailure()) return $validator;

        // save scan form
        $model->save();

        // set labels
        foreach ($labels as $label) {
            $label->scan_form_id = $model->id;
        }

        // save labels to database
        DB::transaction(function() use ($labels) 
        {
            foreach ($labels as $label) {
                $label->save();
            }
        });

        // set response model
        $response->set('model', $model);

        // return successful response
        return $response->setSuccess();
    }

    /**purpose
     *   get all the available options
     * args
     *   (none)
     * returns
     *   array (
     *     date,
     *     from_address
     *   )
     */
    public static function getAvailableOptions($user, $external_user_id = null) {
        
		// check from addresses
		$labels_query = Label::where([
            ['user_id', '=', $user->id],
            ['from_address_override', '=', 0],
            ['refunded', '=', 0],
            ['ship_date', '>', Functions::convertTimeToMysql(strtotime('-1 week', time()))]
        ])->whereRaw('scan_form_id is NULL AND pickup_id IS NULL');

        if (isset($external_user_id)) $labels_query->where('external_user_id', '=', $external_user_id);
        else $labels_query->whereRaw('external_user_id IS NULL');

        $labels = $labels_query->select('id', 'from_address_id', 'ship_date')->get();

        $available_options = [];
        foreach ($labels as $label) {
            if (!isset($available_options[$label->from_address_id])) $available_options[$label->from_address_id] = [];
            if (!isset($available_options[$label->from_address_id][(string) $label->ship_date])) $available_options[$label->from_address_id][(string) $label->ship_date] = [];
            $available_options[$label->from_address_id][(string) $label->ship_date][] = $label->id;
        }

        /*
		// create id array
		$address_ids = [];
		foreach ($labels as $label) {
			$address_ids[] = $label->from_address_id;
		}

        // get addresses
        $addresses = Address::whereIn('id', $address_ids)->get();
        
        // initialize available options
        $available_options = [];

        // loop through adddresses and get dates
        foreach ($addresses as $address) {
        
            // get dates associated with from address
            $dates = [];
            foreach ($labels as $label) {
                if ($label->from_address_id == $address->id) {
                    $dates[] = $label->ship_date;
                }
            }
            
        }
*/
        // return available options
        return $available_options;
    }
}
