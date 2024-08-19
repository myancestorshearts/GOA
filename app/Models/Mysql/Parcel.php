<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;

use App\Libraries;
use App\Common\Validator;

class Parcel extends Base
{
    public $table = 'parcels';

    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',
        'verified'
    ];

    /**purpose
     *   create a parcel
     * args
     *   package
     *   length
     *   width
     *   height
     *   weight
     * returns
     *   parcel response
     */
    public static function create($model_data, $user, $api_key_id = null) {

        // create response
        $response = new Response;

        // create address model
        $parcel = new Parcel;
        $parcel->user_id = $user->id;
        $parcel->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';

        // validate package 
        if (!isset($model_data->package)) return $response->setFailure('Invalid package', 'INVALID_PROPERTY');
        $validated_package = Parcel::validatePackage($model_data->package);
        if (!isset($validated_package)) return $response->setFailure('Invalid package', 'INVALID_PROPERTY');
        $parcel->package = $validated_package;

        // validate each meta data
        $metas = ['length', 'width', 'height', 'weight'];
        foreach($metas as $meta) {

            // validate meta value
            $validated_value = null;

            // if required then check the meta otherwise return default
            if (Parcel::requiresMeta($meta, $validated_package))
            {
                // check for valid meta
                if (!isset($model_data->{$meta})) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY');
                $validated_value = Parcel::validateMeta($meta, $model_data->{$meta}, $validated_package);
                if (!isset($validated_value)) return $response->setFailure('Invalid ' . $meta, 'INVALID_PROPERTY');
                $parcel->{$meta} = $validated_value;
            }
            else $parcel->{$meta} = Parcel::getDefaultMeta($meta, $validated_package);
        }

        // validate parcel with validation service
        $parcel_validator = new Libraries\Parcel\ParcelValidator;
        $parcel_response = $parcel_validator->validateParcelModel($parcel);
        if ($parcel_response->result == Response::RESULT_FAILURE) return $parcel_response;
       
        // return model
        $response->set('model', $parcel);

        // save parcel
        $parcel->save();

        // return success
        return $response->setSuccess();
    }

    /**purpose
     *   validate package
     * args
     *   package
     * returns 
     *   package (null if invalid)
     */
    private static function validatePackage($package) {
        $packages = Parcel::OPTIONS;
        if (!isset($packages[$package])) return null;
        return $package;
    }

    /**purpose
     *   check to see if meta is required
     * args
     *   meta_key
     *   package
     * returns
     *   true or false
     */
    private static function requiresMeta($meta_key, $package) {
        if (!isset(Parcel::OPTIONS[$package])) return null;
        $package_requirements = Parcel::OPTIONS[$package];
        if (!isset($package_requirements[$meta_key])) return null;
        $meta_requirements = $package_requirements[$meta_key];
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
    private static function getDefaultMeta($meta_key, $package) {
        if (!isset(Parcel::OPTIONS[$package])) return null;
        $package_requirements = Parcel::OPTIONS[$package];
        if (!isset($package_requirements[$meta_key])) return null;
        $meta_requirements = $package_requirements[$meta_key];
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
    private static function validateMeta($meta_key, $value, $package) {
        // get package requirements
        if (!isset(Parcel::OPTIONS[$package])) return null;
        $package_requirements = Parcel::OPTIONS[$package];
        if (!isset($package_requirements[$meta_key])) return null;
        $meta_requirements = $package_requirements[$meta_key];
        
        $value_floated = round((float) $value, 3);

        // validate
        if ($value_floated > $meta_requirements['max'] || $value_floated < $meta_requirements['min']) return null;
        return $value_floated;
    }

    // different packages options and meta requiremenets
    const OPTIONS = [
        'FlatRatePaddedEnvelope' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'FlatRateLegalEnvelope' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'SmallFlatRateEnvelope' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'FlatRateEnvelope' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]  
        ],
        'Parcel' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => true,
                'max' => 22,
                'min' => 0.001
            ],
            'width' => [
                'required' => true,
                'max' => 18, 
                'min' => 0.001
            ],
            'height' => [
                'required' => true,
                'max' => 15,
                'min' => 0.001
            ]
        ],
        'SoftPack' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => true,
                'max' => 19,
                'min' => 5
            ],
            'width' => [
                'required' => true,
                'max' => 19, 
                'min' => 2
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'SmallFlatRateBox' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'MediumFlatRateBox' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'LargeFlatRateBox' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 1120,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'RegionalRateBoxA' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 240,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false,
                'default' => 1
            ]
        ],
        'RegionalRateBoxB' => [
            'carriers' => ['USPS'],
            'weight' => [
                'required' => true,
                'max' => 320,
                'min' => .001
            ],
            'length' => [
                'required' => false,
                'default' => 1
            ],
            'width' => [
                'required' => false,
                'default' => 1
            ],
            'height' => [
                'required' => false
            ]
        ]
    ];
}
