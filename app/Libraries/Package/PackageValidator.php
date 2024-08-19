<?php

namespace App\Libraries\Package;

use ApiAuth;
use App\Models\Mysql;

class PackageValidator {

    private $verification_service;
    
	function __construct() {
        $this->verification_service = $this->getVerificationService();
	}

    private function getVerificationService($service = null) {

        $label_service = isset($service) ? $service : env('LABEL_SERVICE');
        switch($label_service) {
            case 'goa': 
                return new Validators\Goa;
            case 'pitney': 
                return new Validators\Usps;
            case 'usps': 
                return new Validators\Usps;
            default: 
                return new Validators\EasyPost;
        }
    }

    public function validatePackageModel($package) {
        return $this->verification_service->validatePackage($package);
    }

    /*
    public function validatePackage($carrier, $package, $length, $width, $height, $weight) {    
        
        // add validations here as well if we want to use this in a different place in the platform.

        $Package = new Mysql\Package;
        $parcel->user_id = ApiAuth::user()->id;
        $parcel->api_key_id = ApiAuth::apiKeyId();
        $parcel->package = $package;
        $parcel->carrier = $carrier;
        $parcel->length = $length;
        $parcel->width = $width;
        $parcel->height = $height;
        $parcel->weight = $weight;

        $result = $this->verification_service->validateParcel($parcel);
        if (!$result) return null;
        
        $parcel->save();

        return $parcel;
    }

    public function validateCarrier($carrier) {
        if (!isset(Mysql\Parcel::OPTIONS[$carrier])) return null;
        return $carrier;
    }

    public function validatePackage($package, $carrier) {
        if (!isset(Mysql\Parcel::OPTIONS[$carrier])) return null;
        $carrier_packages = Mysql\Parcel::OPTIONS[$carrier];
        if (!isset($carrier_packages[$package])) return null;
        return $package;
    }

    public function requiresMeta($meta_key, $package, $carrier) {
        // get package requirements
        if (!isset(Mysql\Parcel::OPTIONS[$carrier])) return null;
        $carrier_packages = Mysql\Parcel::OPTIONS[$carrier];
        if (!isset($carrier_packages[$package])) return null;
        $package_requirements = $carrier_packages[$package];
        if (!isset($package_requirements[$meta_key])) return null;
        $meta_requirements = $package_requirements[$meta_key];
        return $meta_requirements['required'];
    }

    public function validateMeta($meta_key, $value, $package, $carrier) {
        // get package requirements
        if (!isset(Mysql\Parcel::OPTIONS[$carrier])) return null;
        $carrier_packages = Mysql\Parcel::OPTIONS[$carrier];
        if (!isset($carrier_packages[$package])) return null;
        $package_requirements = $carrier_packages[$package];
        if (!isset($package_requirements[$meta_key])) return null;
        $meta_requirements = $package_requirements[$meta_key];
        
        $value_floated = round((float) $value, 3);

        // validate
        if ($value_floated > $meta_requirements['max'] || $value_floated < $meta_requirements['min']) return null;
        return $value_floated;
    }*/
}