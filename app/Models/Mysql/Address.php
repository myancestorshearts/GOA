<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Libraries;
use App\Common\Validator;
use App\Common\Functions;
use ApiAuth;

class Address extends Base
{
    public $table = 'addresses';
    
    // sets sub user so they have their own system for theses
    public CONST PERSONAL_ONLY = true;
    
    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id'
    ];
    
    public static $search_parameters = [
        [
            'argument' => 'saved',
            'column' => 'saved', 
            'type' => 'EQUAL',
            'default' => 1
        ],
        [
            'argument' => 'active',
            'column' => 'active',
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];

    /**purpose
     *   create an address
     * args
     *   name
     *   email
     *   phone
     *   company
     *   street_1
     *   street_2
     *   city
     *   postal
     *   state
     *   country
     *   saved 
     *   from 
     * returns
     *   address
     */
    public static function create($model_data, $user, $api_key_id = null, $validate = true, $skip_save = false) {

        // create response
        $response = new Response;

        // create address model
        $address = new Address;
        $address->user_id = $user->id;
        $address->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';
        $address->active = 1;
        $address->default = 0;
        $address->verified = 0;

        // validate country
        if (!isset($model_data->country)) return $response->setFailure('Invalid country', 'INVALID_PROPERTY', 'MISSING_COUNTRY');
        $validated_country = Validator::validateCountry($model_data->country);
        if (!isset($validated_country)) return $response->setFailure('Invalid country', 'INVALID_PROPERTY', 'INVALID_COUNTRY');
        $address->country = $validated_country;

        // validate postal
        if (!isset($model_data->postal)) return $response->setFailure('Invalid postal', 'INVALID_PROPERTY', 'MISSING_POSTAL');
        $validated_postal = Validator::validatePostalCode($model_data->postal, null, $validated_country);
        if (!isset($validated_postal)) return $response->setFailure('Invalid postal', 'INVALID_PROPERTY', 'INVALID_POSTAL');
        $address->postal = $validated_postal;

        // validate state
        if (!isset($model_data->state)) return $response->setFailure('Invalid state', 'INVALID_PROPERTY', 'MISSING_STATE');
        $validated_state = Validator::validateState($model_data->state, null, $validated_country);
        if (!isset($validated_state)) return $response->setFailure('Invalid state', 'INVALID_PROPERTY', 'INVALID_STATE');
        $address->state = $validated_state;

        // name
        if (!isset($model_data->name)) return $response->setFailure('Invalid name', 'INVALID_PROPERTY', 'MISSING_NAME');
        $validated_name = Validator::validateText($model_data->name, ['trim' => true]);
        if (!isset($validated_name)) return $response->setFailure('Invalid name', 'INVALID_PROPERTY', 'INVALID_NAME');
        $address->name = $validated_name;
        
        // company
        if (isset($model_data->company)) {
            $validated_company = Validator::validateText($model_data->company, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_company)) return $response->setFailure('Invalid company', 'INVALID_PROPERTY', 'INVALID_COMPANY');
            $address->company = $validated_company;
        }
        
        // email
        if (isset($model_data->email)) {
            $validated_email = Validator::validateEmail($model_data->email, ['clearable' => true]);
            if (!isset($validated_email)) return $response->setFailure('Invalid email', 'INVALID_PROPERTY', 'INVALID_EMAIL');
            $address->email = $validated_email;
        }

        // validate from address
        if (isset($model_data->from)) {
            $address->from = Validator::validateBoolean($model_data->from);
        }
        else $address->from = false;
        
        // phone
        $validated_phone = Validator::validatePhone(
            isset($model_data->phone) ? $model_data->phone : '', 
            ['clearable' => !$address->from]
        );
        if (!isset($validated_phone)) return $response->setFailure('Invalid phone', 'INVALID_PROPERTY', 'INVALID_PHONE');
        $address->phone = $validated_phone;
        
        // street
        if (!isset($model_data->street_1)) return $response->setFailure('Invalid street 1', 'INVALID_PROPERTY', 'MISSING_STREET_!');
        $validated_street_1 = Validator::validateText($model_data->street_1, ['trim' => true]);
        if (!isset($validated_street_1)) return $response->setFailure('Invalid street 1', 'INVALID_PROPERTY', 'INVALID_STREET_1');
        $address->street_1 = $validated_street_1;
        
        // street 2
        if (isset($model_data->street_2)) {
            $validated_street_2 = Validator::validateText($model_data->street_2, ['trim' => true, 'clearable' => true]);
            if (!isset($validated_street_2)) return $response->setFailure('Invalid street 2', 'INVALID_PROPERTY', 'INVALID_STREET_2');
            $address->street_2 = $validated_street_2;
        }
        else $address->street_2 = '';
        
        // city
        if (!isset($model_data->city)) return $response->setFailure('Invalid city', 'INVALID_PROPERTY', 'MISSING_CITY');
        $validated_city = Validator::validateText($model_data->city, ['trim' => true]);
        if (!isset($validated_city)) return $response->setFailure('Invalid city', 'INVALID_PROPERTY', 'INVALID_CITY');
        $address->city = $validated_city;

        // validate saved
        if (isset($model_data->saved)) {
            $address->saved = Validator::validateBoolean($model_data->saved);
            if (!$address->from) return $response->setFailure('Can only save from addresses', 'INVALID_PROPERTY', 'MISCONFIGURATION_SAVED');
        }
        else $address->saved = 0;

        // set default if no other address is already set as default
        if (Address::where([
            ['user_id', '=', $address->user_id],
            ['saved', '=', 1],
            ['default', '=', 1],
            ['active', '=', 1]
        ])->count() == 0) $address->default = 1;

        // we need to validate with a validation service now
        if ($validate) {
            $address_verifier = new Libraries\Address\AddressValidator;
            $address_response = $address_verifier->validateAddressModel($address);

            // if address failed to validate then return result why
            if ($address_response->isFailure()) return $address_response;
        }
        
        if (isset($model_data->reference)) $address->setSubModel('reference', $model_data->reference);
        if (isset($model_data->api_reference)) $address->setSubModel('api_reference', $model_data->api_reference);

        // set response
        $response->set('model', $address);

        // save address
        if (!$skip_save) $address->save();

        // return response
        return $response->setSuccess();
    }

    /**purpose
     *   deactivate model
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function deactivate() {

        // initialize response
        $response = new Response;

        // deactivate and save
        $this->active = 0;
        $this->default = 0;
        $this->save();

        // return response
        return $response->setSuccess();
    }


    public function hasPermissionSet() { 
        return $this->user_id == ApiAuth::user()->id;
    }

    public function set($request) {

        if (Validator::validateBoolean($request->get('default'))) {
            Address::where([
                ['user_id', '=', $this->user_id]
            ])->update(['default' => 0]);
            $this->default = true;
        }
        else $this->default = false;

        return parent::set($request);
    }

    /**
     * purpose
     *   determine if an address is us domestic
     * args
     *   (none)
     * returns
     *   (true/false)
     */
    public function isUSDomestic() {
        return ($this->country == 'US');
    }

    /**
     * purpose
     *   determines if an address requires customs
     * args
     *   (none)
     * returns
     *   (true/false)
     */
    public function requiresCustoms() {
        // requires customs if not us
        if ($this->country != 'US') return true;

        // requires customs if one of the following states
        $customs_states = ['AS', 'GU', 'MP', 'FM', 'MH', 'UM', 'AA', 'AE', 'AP'];
        if (in_array($this->state, $customs_states)) return true;
        
        // remaining states do not require customs
        return false;
    }

    /**purpose
     *   get a formatted address
     * args
     *   (none)
     * returns
     *   address formatted (string)
     */
    public function formatted() {
        $address_parts = [];
        if (!Functions::isEmpty($this->street_2)) {
            $address_parts[] = $this->street_1;
            $address_parts[] = $this->street_2 . ',';
        }
        else {
            $address_parts[] = $this->street_1 . ',';
        }

        $address_parts[] = $this->city . ',';
        $address_parts[] = $this->state; 
        $address_parts[] = $this->postal;

        return implode(' ', $address_parts);
    }

    /**purpose
     *   get street formatted
     * args
     *   (none)
     * returns
     *   address street formatted (string)
     */
    public function formattedStreet() {
        $address_parts = [];
        $address_parts[] = $this->street_1;
        if (!Functions::isEmpty($this->street_2)) {
            $address_parts[] = $this->street_2;
        }

        return implode(' ', $address_parts);
    }

    /**purpose
     *   get city state postal formatted
     * args
     *   (none)
     * returns
     *   address state postal formatted (string)
     */
    public function formattedCityStatePostal() {
        
        $address_parts = [];
        $address_parts[] = $this->city . ',';
        $address_parts[] = $this->state; 
        $address_parts[] = $this->postal;

        return implode(' ', $address_parts);
    }
}