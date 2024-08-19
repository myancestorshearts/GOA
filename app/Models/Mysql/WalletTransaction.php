<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;


use App\Common\Validator;
use App\Common\Functions;


class WalletTransaction extends Base
{
    public $table = 'wallet_transactions';

    protected $hidden = [
        'cost',
        'profit',
        'profit_calculated',
        'auth_code',
        'auth_reference',
        'user_id',
        'api_key_id'
    ];
    
    public static $search_parameters = [
        [
            'argument' => 'failed',
            'column' => 'failed', 
            'type' => 'EQUAL',
            'default' => 0 
        ],
        [
            'argument' => 'pending',
            'column' => 'pending',
            'type' => 'EQUAL',
            'default' => 0
        ],
        [
            'argument' => 'created_after',
            'column' => 'created_at',
            'type' => 'GREATER'
        ],
        [
            'argument' => 'created_before',
            'column' => 'created_at',
            'type' => 'LESSER'
        ],
        [
            'argument' => 'type',
            'column' => 'type',
            'type' => 'EQUAL'
        ]
    ];

    public $label;
    public $user;
    public $model_pairs = [
        ['label', 'label_id', Label::class],
        ['user', 'user_id', User::class]
    ];


    /**purpose
     *   approve a pending transaction
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function approve() {

        // create response
        $response = new Response;

        // check to make sure the wallet transaction is still pending
        if (!Validator::validateBoolean($this->pending)) return $response->setFailure('Transaction is already approved');

        // check to make sure transaction did not fail
        if (Validator::validateBoolean($this->failed)) return $response->setFailure('Cannot approve a failed transaction');
        
        $user = User::find($this->user_id);
        if (!isset($user)) return $response->setFailure('Could not locate user');

        // get new user balance
        $new_balance = $user->getWalletBalance() + $this->amount;

        // save wallet transaction
        $this->pending = 0;
        $this->finalized_at = Functions::convertTimeToMysql(time());
        $this->balance = $new_balance;
        $this->save();

        // save user with new balance
        $user->wallet_balance = $new_balance;
        $user->save();

        return $response->setSuccess();

    }


    // generic admin search method
    public static function adminSearch($models_query, $request) {
        return parent::adminSearch($models_query, $request);
    }

}
