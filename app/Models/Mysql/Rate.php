<?php

namespace App\Models\Mysql;

class Rate extends Base
{
    public $table = 'rates';

    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',
        'verified'
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'delivery_range_min' => 'datetime',
        'delivery_range_max' => 'datetime'
    ];
}
