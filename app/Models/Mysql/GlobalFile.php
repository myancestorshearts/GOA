<?php

namespace App\Models\Mysql;

use Auth;

class GlobalFile extends Base
{
    protected $connection = 'goa';
    public $table = 'global_files';

}
