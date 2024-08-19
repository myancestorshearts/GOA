<?php

namespace App\Models\Mysql;

use Auth;

class ApiKey extends Base
{
    public $table = 'api_keys';

    // blocks sub users from accessing this api call
    public CONST SUB_USER_BLOCK = true;

    public static function getModels($collection, $ignore_classes = [], $request = null)
    {
        $models = parent::getModels($collection, $ignore_classes);
        $count = count($models);
        for ($i = 0; $i < $count; $i++) {

            $models[$i]->password = decrypt($models[$i]->password);
        }
        return $models;
    }

    // api key parameters
    public static $search_parameters = [
        [
            'argument' => 'active',
            'column' => 'active', 
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];
}
