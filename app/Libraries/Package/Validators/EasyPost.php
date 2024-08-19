<?php
namespace App\Libraries\Package\Validators;


use App\Http\Controllers\Response;

use App\Models;

class EasyPost {

    private $key;
    private $mode;
    
	function __construct() {
		$this->key = env('EASYPOST_KEY', '');
        $this->mode = env('EASYPOST_MODE', '');
	}

    public function validatePackage($parcel) {
        $response = new Response;

        $parcel->verified = 1;
        $parcel->verification_service = 'easypost';
        $parcel->verification_id = '';

        return $response->setSuccess();
    }
}