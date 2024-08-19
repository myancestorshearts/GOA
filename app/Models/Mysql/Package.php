<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Libraries;
use App\Common\Validator;

class Package extends Base
{
    public $table = 'packages';
    
    // sets sub user so they have their own system for theses
    public CONST PERSONAL_ONLY = true;
    
    protected $hidden = [
        'user_id'
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
     *   create a parcel
     * args
     *   type
     *   length
     *   width
     *   height
     * returns
     *   parcel response
     */
    public static function createRateOnly($model_data) {

        // create response
        $response = new Response;

        // create address model
        $model = new Package;

        // validate type 
        if (!isset($model_data->type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY');
        $validated_type = Package::validateType($model_data->type);
        if (!isset($validated_type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY');
        $model->type = $validated_type;

        // validate each meta data
        $metas = ['length', 'width', 'height'];
        foreach($metas as $meta) {

            // validate meta value
            $validated_value = null;

            // if required then check the meta otherwise return default
            if (Package::requiresMeta($meta, $validated_type))
            {
                // check for valid meta
                if (!isset($model_data->{$meta})) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY');
                $validated_value = Package::validateMeta($meta, $model_data->{$meta}, $validated_type);
                if (!isset($validated_value)) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY');
                $model->{$meta} = $validated_value;
            }
            else $model->{$meta} = Package::getDefaultMeta($meta, $validated_type);
        }

        // return model
        $response->set('model', $model);

        // return success
        return $response->setSuccess();
    }


    /**purpose
     *   create a parcel
     * args
     *   type
     *   length
     *   width
     *   height
     * returns
     *   parcel response
     */
    public static function create($model_data, $user, $api_key_id = null) {

        // create response
        $response = new Response;

        // create address model
        $model = new Package;
        $model->user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';

        // validate type 
        if (!isset($model_data->type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY', 'MISSING_TYPE');
        $validated_type = Package::validateType($model_data->type);
        if (!isset($validated_type)) return $response->setFailure('Invalid type', 'INVALID_PROPERTY', 'INVALID_TYPE');
        $model->type = $validated_type;

        // validate each meta data
        $metas = ['length', 'width', 'height'];
        foreach($metas as $meta) {

            // validate meta value
            $validated_value = null;

            // if required then check the meta otherwise return default
            if (Package::requiresMeta($meta, $validated_type))
            {
                // check for valid meta
                if (!isset($model_data->{$meta})) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY', 'MISSING_' . strtoupper($meta));
                $validated_value = Package::validateMeta($meta, $model_data->{$meta}, $validated_type);
                if (!isset($validated_value)) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY', 'INVALID_' . strtoupper($meta));
                $model->{$meta} = $validated_value;
            }
            else $model->{$meta} = Package::getDefaultMeta($meta, $validated_type);
        }

        // validate saved
        if (isset($model_data->saved)) {
            $model->saved = Validator::validateBoolean($model_data->saved);

            // verify name
            if (!isset($model_data->name)) return $response->setFailure('Invalid name', 'INVALID_PROPERTY', 'MISSING_NAME');
            $validated_name = Validator::validateText($model_data->name, ['trim' => true]);
            if (!isset($validated_name)) return $response->setFailure('Invalid name', 'INVALID_PROPERTY', 'INVALID_NAME');
            $model->name = $validated_name;
            $model->active = 1;
        }
        else {
            $model->saved = 0;
            $model->name = '';
            $model->active = 0;
        }
        
        // validate model with validation service
        $package_validator = new Libraries\Package\PackageValidator;
        $package_response = $package_validator->validatePackageModel($model);
        if ($package_response->result == Response::RESULT_FAILURE) return $package_response;

        // return model
        $response->set('model', $model);

        // save model
        $model->save();

        // return success
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
        $this->save();

        // return response
        return $response->setSuccess();
    }

    /**purpose
     *   validate package
     * args
     *   package
     * returns 
     *   package (null if invalid)
     */
    private static function validateType($type) {
        $types = Package::TYPES;
        if (!isset($types[$type])) return null;
        return $type;
    }

    /**purpose
     *   check to see if meta is required
     * args
     *   meta_key
     *   package
     * returns
     *   true or false
     */
    private static function requiresMeta($meta_key, $type) {
        if (!isset(Package::TYPES[$type])) return null;
        $type_requirements = Package::TYPES[$type];
        if (!isset($type_requirements[$meta_key])) return null;
        $meta_requirements = $type_requirements[$meta_key];
        return $meta_requirements['required'];
    }

    /**purpose
     *   get default 
     * args
     *   meta_key
     *   package
     * returns
     *   value
     */
    private static function getDefaultMeta($meta_key, $type) {
        if (!isset(Package::TYPES[$type])) return null;
        $type_requirements = Package::TYPES[$type];
        if (!isset($type_requirements[$meta_key])) return null;
        $meta_requirements = $type_requirements[$meta_key];
        return $meta_requirements['default'];
    }

    /**purpose
     *   verify meta value
     * args
     *   meta_key
     *   value
     *   package
     * returns
     *   value (null if invalid)
     */
    private static function validateMeta($meta_key, $value, $type) {
        // get type requirements
        if (!isset(Package::TYPES[$type])) return null;
        $type_requirements = Package::TYPES[$type];
        if (!isset($type_requirements[$meta_key])) return null;
        $meta_requirements = $type_requirements[$meta_key];
        
        $value_floated = round((float) $value, 3);

        // validate
        if ($value_floated > $meta_requirements['max'] || $value_floated < $meta_requirements['min']) return null;
        return $value_floated;
    }


    // different packages options and meta requiremenets
    const TYPES = [
        'UspsFlatRatePaddedEnvelope' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 12.5
            ],
            'width' => [
                'required' => false,
                'default' => .5
            ],
            'height' => [
                'required' => false,
                'default' => 9.5
            ]
        ],
        'UspsFlatRateLegalEnvelope' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 15
            ],
            'width' => [
                'required' => false,
                'default' => .5
            ],
            'height' => [
                'required' => false,
                'default' => 9.5
            ]
        ],
        'UspsSmallFlatRateEnvelope' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 10
            ],
            'width' => [
                'required' => false,
                'default' => .5
            ],
            'height' => [
                'required' => false,
                'default' => 6
            ]
        ],
        'UspsFlatRateEnvelope' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 12.5
            ],
            'width' => [
                'required' => false,
                'default' => .5
            ],
            'height' => [
                'required' => false,
                'default' => 9.5
            ]  
        ],
        'Parcel' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => true,
                //'max' => 22,
                'max' => 10000,
                'min' => 0.001
            ],
            'width' => [
                'required' => true,
                //'max' => 18, 
                'max' => 10000, 
                'min' => 0.001
            ],
            'height' => [
                'required' => true,
               // 'max' => 15,
                'max' => 10000,
                'min' => 0.001
            ]
        ],
        'SoftPack' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => true,
                //'max' => 19,
                'max' => 10000,
                'min' => 5
            ],
            'width' => [
                'required' => true,
                //'max' => 19, 
                'max' => 10000,
                'min' => 2
            ],
            'height' => [
                'required' => true,
                'max' => 10000, 
                'min' => .5
            ]
        ],
        'UspsSmallFlatRateBox' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 8.62
            ],
            'width' => [
                'required' => false,
                'default' => 5.37
            ],
            'height' => [
                'required' => false,
                'default' => 1.62
            ]
        ],
        'UspsMediumFlatRateBoxTopLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 11
            ],
            'width' => [
                'required' => false,
                'default' => 8.5
            ],
            'height' => [
                'required' => false,
                'default' => 5.5
            ]
        ],
        'UspsMediumFlatRateBoxSideLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 13.6
            ],
            'width' => [
                'required' => false,
                'default' => 11.8
            ],
            'height' => [
                'required' => false,
                'default' => 3.3
            ]
        ],
        'UspsLargeFlatRateBox' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 12
            ],
            'width' => [
                'required' => false,
                'default' => 12
            ],
            'height' => [
                'required' => false,
                'default' => 5.5
            ]
        ],
        'UspsRegionalRateBoxATopLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 10
            ],
            'width' => [
                'required' => false,
                'default' => 7
            ],
            'height' => [
                'required' => false,
                'default' => 4.75
            ]
        ],
        'UspsRegionalRateBoxASideLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 10.9
            ],
            'width' => [
                'required' => false,
                'default' => 2.3
            ],
            'height' => [
                'required' => false,
                'default' => 12.81
            ]
        ],
        'UspsRegionalRateBoxBTopLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 12
            ],
            'width' => [
                'required' => false,
                'default' => 10.25
            ],
            'height' => [
                'required' => false,
                'default' => 5
            ]
        ],
        'UspsRegionalRateBoxBSideLoading' => [
            'carriers' => ['USPS'],
            'length' => [
                'required' => false,
                'default' => 14.3
            ],
            'width' => [
                'required' => false,
                'default' => 2.82
            ],
            'height' => [
                'required' => false,
                'default' => 15.82
            ]
        ]
    ];

}
