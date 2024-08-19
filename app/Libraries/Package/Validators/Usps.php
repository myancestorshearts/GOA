<?php
namespace App\Libraries\Package\Validators;

use App\Models;

use App\Http\Controllers\Response;

class Usps {
    public function validatePackage($parcel) {
        $response = new Response;

        $parcel->verified = 1;
        $parcel->verification_service = 'usps';
        $parcel->verification_id = '';

        return $response->setSuccess();
    }
}