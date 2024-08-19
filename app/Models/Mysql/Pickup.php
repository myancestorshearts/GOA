<?php

namespace App\Models\Mysql;
use App\Http\Controllers\Response;
use App\Common\Validator;
use App\Common\Functions;
use App\Libraries;

use DB;

class Pickup extends Base
{
    const LOCATION_FRONT_DOOR = 'FRONT_DOOR';
    const LOCATION_BACK_DOOR = 'BACK_DOOR';
    const LOCATION_SIDE_DOOR = 'SIDE_DOOR';
    const LOCATION_KNOCK_ON_DOOR = 'KNOCK_ON_DOOR';
    const LOCATION_MAIL_ROOM = 'MAIL_ROOM';
    const LOCATION_OFFICE = 'OFFICE';
    const LOCATION_RECEPTION = 'RECEPTION';
    const LOCATION_IN_MAILBOX = 'IN_MAILBOX';
    const LOCATION_OTHER = 'OTHER';

    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_SCHEDULED = 'SCHEDULED';
    const STATUS_PENDING = 'PENDING';

    public $table = 'pickups';

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
        ]
    ];
    
    protected $casts = [
        'ship_date' => 'datetime'
    ];
    
    /**purpose
     *   create a label
     * args
     *   from_address_id (required)
     *   label_ids (optional)
     *   scan_form_ids (optional)
     *   package_location (required) (FRONT_DOOR, BACK_DOOR, SIDE_DOOR, KNOCK_ON_DOOR, MAIL_ROOM, OFFICE, RECEPTION, IN_MAILBOX, OTHER)
     *   special_instructions (optional)
     *   external_user_id (required for rest api)
     *   date (required)
     * returns
     *   label
     */
    public static function create($model_data, $user, $api_key_id = null) {
        
        // create response
        $response = new Response;
        
        // create address model
        $model = new Pickup;
        $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
        $model->created_user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';

        // validate from address id 
        if (!isset($model_data->from_address_id)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'MISSING_FROM_ADDRESS_ID');
        $from_address = Address::find($model_data->from_address_id);
        if (!isset($from_address)) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
        if ($from_address->user_id != $model->user_id) return $response->setFailure('Invalid from address id', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
        if (!Validator::validateBoolean($from_address->from)) return $response->setFailure('Invalid from address - address must be marked as a from address', 'INVALID_PROPERTY', 'INVALID_FROM_ADDRESS_ID');
        $model->from_address_id = $from_address->id;

        // external user id
        $model->external_user_id = null;
        if (isset($model_data->external_user_id)) {
            $validated_user_id = Validator::validateText($model_data->external_user_id, ['trim' => true, 'clearable' => false]);
            if (!isset($validated_user_id)) return $response->setFailure('Invalid external user id', 'INVALID_PROPERTY', 'INVALID_EXTERNAL_USER_ID');
            $model->external_user_id = $validated_user_id;
        }

        // referral program type
        if (!isset($model_data->package_location)) return $response->setFailure('Invalid package location', 'INVALID_PROPERTY', 'MISSING_PACKAGE_LOCATION');
        $validated_package = Validator::validateEnum($model_data->package_location, ['enums' => [
            Pickup::LOCATION_FRONT_DOOR,
            Pickup::LOCATION_BACK_DOOR,
            Pickup::LOCATION_SIDE_DOOR,
            Pickup::LOCATION_KNOCK_ON_DOOR,
            Pickup::LOCATION_MAIL_ROOM,
            Pickup::LOCATION_OFFICE,
            Pickup::LOCATION_RECEPTION,
            Pickup::LOCATION_IN_MAILBOX,
            Pickup::LOCATION_OTHER
        ]]);
        if (!isset($validated_package)) return $response->jsonFailure('Invalid package location', 'INVALID_PACKAGE_LOCATION', 'INVALID_PACKAGE_LOCATION');
        $model->package_location = $validated_package;

        // special instructions
        $model->special_instructions = isset($model_data->special_instructions) ? $model_data->special_instructions : '';

        // date
        if (!isset($model_data->date)) return $response->setFailure('Date is required', 'INVALID_PROPERTY', 'MISSING_DATE');
        $date = strtotime(date('Y-m-d', strtotime($model_data->date)));
        $morning = strtotime(date('Y-m-d', time()));
        if ($date < $morning) return $response->setFailure('Date must be in the future', 'INVALID_PROPERTY', 'INVALID_DATE');
        $model->date = date('Y-m-d', $date);

        // scan form labels
        $pickup_labels = [];
        $labels_with_no_scan_form = [];

        $model->count_label_total = 0;
        $model->count_label_individual = 0;
        $model->count_scan_form = 0;

        // get labels 
        if (isset($model_data->label_ids)) {
            $labels = Label::whereIn('id', $model_data->label_ids)->get();
            foreach ($labels as $label) {
                if (isset($label->scan_form_id)) return $response->setFailure('Label with id: ' . $label->id . ' is added to a scan form and must be added to a pickup via a scan form', 'INVALID_PROPERTY', 'LABEL_ADDED_TO_SCANFORM');
                $pickup_labels[] = $label;
                $labels_with_no_scan_form[] = $label;
            }
            $model->count_label_individual = count($labels);
        }

        // get scan forms 
        $scan_forms = [];
        if (isset($model_data->scan_form_ids)) {
            $scan_forms = ScanForm::whereIn('id', $model_data->scan_form_ids)->get();
            foreach ($scan_forms as $scan_form) {
                // check scan form is not already scheduled for pickup.
                if (isset($scan_form->pickup_id)) return $response->setFailure('Scan form with id: ' . $scan_form->id . ' already scheduled for pickup.', 'INVALID_PROPERTY', 'DUPLICATE_PICKUP');

                // check external_user_id
                if ($scan_form->external_user_id != $model->external_user_id) return $response->setFailure('Scan form with id: ' . $scan_form->id . ' is an invalid id. External user must be the same', 'INVALID_PROPERTY', 'EXTERNAL_USER_ID_MISMATCH');

                // check scan_form is users
                if ($scan_form->user_id != $model->user_id) return $response->setFailure('Scan form with id: ' . $scan_form->id . ' is an invalid id.', 'INVALID_PROPERTY', 'USER_ID_MISMATCH');
            
                // check address matches pickups
                if ($scan_form->from_address_id != $model->from_address_id) return $response->setFailure('Scan form with id: ' . $scan_form->id . ' has a different from address.', 'INVALID_PROPERTY', 'FROM_ADDRESS_ID_MISMATCH');
            }

            $labels = Label::whereIn('scan_form_id', $model_data->scan_form_ids)->get();
            foreach ($labels as $label) {
                $pickup_labels[] = $label;
            }
            $model->count_scan_form = count($scan_forms);
        }

        // check pickup labels to make sure there are some
        if (count($pickup_labels) == 0) return $response->setFailure('Must include some labels or some scan forms for pickup', 'NON_COMPLIANT', 'INVALID_LABEL_COUNT');

        // double check all labels are valid to be scanned
        foreach ($pickup_labels as $label) {
            
            if (Validator::validateBoolean($label->from_address_override)) return $response->setFailure('Label with id: ' . $label->id . ' has an overriden from address. Connot be added to pickup', 'NOT_COMPLIANT', 'FROM_ADDRESS_MISMATCH');

            // check external_user_id
            if ($label->external_user_id != $model->external_user_id) return $response->setFailure('Label with id: ' . $label->id . ' is an invalid id. External user must be the same', 'INVALID_PROPERTY', 'EXTERNAL_USER_ID_MISMATCH');

            // check label is users
            if ($label->user_id != $model->user_id) return $response->setFailure('Label with id: ' . $label->id . ' is an invalid id.', 'INVALID_PROPERTY', 'USER_ID_MISMATCH');
            
            // check label is not already scheduled for pickup.
            if (isset($label->pickup_id)) return $response->setFailure('Label with id: ' . $label->id . ' already scheduled for pickup.', 'INVALID_PROPERTY', 'DUPLICATE_PICKUP');

            // check address matches pickups
            if ($label->from_address_id != $model->from_address_id) return $response->setFailure('Label with id: ' . $label->id . ' has a different from address.', 'INVALID_PROPERTY', 'FROM_ADDRESS_MISMATCH');
        }

        // count label total
        $model->count_label_total = count($pickup_labels);

        // validate pickup
        $pickup_validator = new Libraries\Pickup\PickupValidator;
        $pickup_response = $pickup_validator->validateModel(
            $model, 
            $from_address, 
            $pickup_labels,
            $scan_forms, 
            $labels_with_no_scan_form
        );
        if ($pickup_response->isFailure()) return $pickup_response;

        // save model
        $model->save();

        // save labels and scan forms
        DB::transaction(function() use($model, $pickup_labels, $scan_forms) {
            foreach ($pickup_labels as $scan_form_label) {
                $scan_form_label->pickup_id = $model->id;
                $scan_form_label->save();
            }   
            foreach ($scan_forms as $scan_form) {
                $scan_form->pickup_id = $model->id;
                $scan_form->save();
            }
        });

        // set pickup and return
        $response->set('model', $model);

        // return successful response
        return $response->setSuccess();
    }

    /**purpose
     *   cancel a pickup
     * args
     *   (none)
     * returns 
     *   response
     */
    public function cancel() {

        $response = new Response;

        if ($this->status == 'CANCELLED') return $response->setFailure('Pickup already cancelled', 'ALREADY_CANCELLED', 'ALREADY_CANCELLED');

        if ($this->status == 'SCHEDULED') {
            // get pickup validator
            $pickup_cancel_validator = new Libraries\PickupCancel\Validator;
            $pickup_cancel_response = $pickup_cancel_validator->cancelModel($this);
            if ($pickup_cancel_response->isFailure()) return $pickup_cancel_response;
        }

        // update labels and scans forms
        Label::where('pickup_id', '=', $this->id)->update([
            'pickup_id' => null
        ]);
        ScanForm::where('pickup_id', '=', $this->id)->update([
            'pickup_id' => null
        ]);

        // save pickup as cancelled
        $this->status = 'CANCELLED';
        $this->save();
        
        // return cancel response
        return $response->setSuccess();;
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
    
    public static function getAvailableAddresses($user, $external_user_id = null) {

        // create from address ids
        $from_address_ids = [];

		// check from addresses labels
		$labels_query = Label::where([
            ['user_id', '=', $user->id],
            ['from_address_override', '=', 0],
            ['refunded', '=', 0],
            ['ship_date', '>', Functions::convertTimeToMysql(strtotime('-1 week', time()))]
        ])->whereRaw('scan_form_id is NULL')->whereRaw('pickup_id is NULL');

        if (isset($external_user_id)) $labels_query->where('external_user_id', '=', $external_user_id);
        else $labels_query->whereRaw('external_user_id IS NULL');

        $labels = $labels_query->select('id', 'from_address_id')->get();

        $available_options = [];
        foreach ($labels as $label) {
            $from_address_ids[] = $label->from_address_id;
        }

        // check from addresses by scan forms
        $scan_forms_query = ScanForm::where([
            ['user_id', '=', $user->id],
            ['ship_date', '>', Functions::convertTimeToMysql(strtotime('-1 week', time()))]
        ])->whereRaw('pickup_id is NULL');

        if (isset($external_user_id)) $scan_forms_query->where('external_user_id', '=', $external_user_id);
        else $scan_forms_query->whereRaw('external_user_id IS NULL');

        $scan_forms = $scan_forms_query->select('id', 'from_address_id')->get();
        foreach ($scan_forms as $scan_form) 
        {
            $from_address_ids[] = $scan_form->from_address_id;
        }

        $addresses = Address::whereIn('id', $from_address_ids)->get();
        return $addresses;
    }
}
