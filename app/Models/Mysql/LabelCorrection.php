<?php

namespace App\Models\Mysql;

use Auth;
use App\Common\Functions;

class LabelCorrection extends Base
{
    public $table = 'label_corrections';
    
    // search users
    public static $search_parameters = [
        [
            'argument' => 'query_search',
            'column' => ['external_user_id'], 
            'type' => 'SEARCH'
        ]
    ];

    
    public $label;
    public $model_pairs = [
        ['label', 'label_id', Label::class]
    ];



    public function addTransaction($connection = null) {
        


        // set user
        $user = isset($connection) ? User::on($connection)->find($this->user_id) : User::find($this->user_id);


        // create wallet transaction
        $new_wallet_transaction = new WalletTransaction;
        if (isset($connection)) $new_wallet_transaction->setConnection($connection);
        $new_wallet_transaction->user_id = $this->user_id;
        $new_wallet_transaction->created_user_id = null;
		$new_wallet_transaction->api_key_id = 'INTERNAL';
        $new_wallet_transaction->type = 'Correction';
        $new_wallet_transaction->label_id = $this->label_id;
        $new_wallet_transaction->label_correction_id = $this->id;
		$new_wallet_transaction->amount = (string) (($this->amount) * -1);
		$new_wallet_transaction->cost = $this->amount;
        $new_wallet_transaction->profit = 0;
        $new_wallet_transaction->profit_calculated = 1;
        $new_wallet_transaction->pending = 0;
        $new_wallet_transaction->processing_fee = 0;
        $new_wallet_transaction->failed = 0;
        $new_wallet_transaction->failed_message = '';
        $new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());

		$new_balance = $user->getWalletBalance($connection) - $this->amount;
		$new_wallet_transaction->balance = (string) round($new_balance, 2);

        $new_wallet_transaction->save();

        $user->wallet_balance = (string) round($new_balance, 2);
        $user->save();

        $this->wallet_transaction_id = $new_wallet_transaction->id;
        $this->save();

    }
}
