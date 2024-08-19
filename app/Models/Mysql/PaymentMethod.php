<?php

namespace App\Models\Mysql;

class PaymentMethod extends Base
{
    public $table = 'payment_methods';
    
    // blocks sub users from accessing this api call
    public CONST SUB_USER_BLOCK = true;
}
