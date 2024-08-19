<?php 

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class RestApi extends Facade {

    protected static function getFacadeAccessor() { return 'restapi'; } 

}