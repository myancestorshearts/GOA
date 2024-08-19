<?php

namespace App\Models\Dynamo;

use App\Common\Validator;
use App\Common\Functions;

class RateDiscounts extends Base
{
    // set table
	protected $table = 'RateDiscounts';

    const SERVICE_USPS_FIRST_CLASS = 'first_class';
    const SERVICE_USPS_PRIORITY = 'priority';
    const SERVICE_USPS_PRIORITY_EXPRESS = 'priority_express';
    const SERVICE_USPS_PARCEL_SELECT = 'parcel_select';
    const SERVICE_USPS_CUBIC = 'cubic';

    const LOCAL_DOMESTIC = 'domestic';
    const LOCAL_INTERNATIONAL = 'international';
    const LOCAL_CANADA = 'canada';

    public static function findOrCreate($sk) {
        $model = parent::findOrCreate($sk);
        if (!isset($model->rates)) {
            $model->rates = [RateDiscounts::LOCAL_DOMESTIC => [], RateDiscounts::LOCAL_INTERNATIONAL => [], RateDiscounts::LOCAL_CANADA => []];
        }
        return $model;
    }

    
    /**purpose
     *   validate services array
     * args 
     *   services
     * returns
     *   response (null if failed)
     */
    public static function validateRates($rates) {
        foreach ($rates as $key => $type) {
            if (!in_array($key, ['international', 'domestic', 'canada'])) return false;
            foreach ($type as $carrier => $sub_rates) {
                if ($carrier != 'usps') return false;
                foreach ($sub_rates as $sub_rate => $value) {
                    if (!in_array($sub_rate, [
                        RateDiscounts::SERVICE_USPS_FIRST_CLASS,
                        RateDiscounts::SERVICE_USPS_PRIORITY,
                        RateDiscounts::SERVICE_USPS_PRIORITY_EXPRESS,
                        RateDiscounts::SERVICE_USPS_PARCEL_SELECT,
                        RateDiscounts::SERVICE_USPS_CUBIC
                    ])) return false;
                    if (!Functions::isEmpty(trim($value)) && !Validator::validateFloat(trim($value), ['min' => 0])) return false;
                }
            }
        }
        return true;
    }

    /**purpose
     *   calculate rate 
     * args
     *   cpp rate
     * returns 
     *   rate
     */
    public function calculateRate($local, $carrier, $service, $rate, $weight) {


        if ($carrier == 'usps' && !$this->validateUspsRates($local, $service, $weight)) return $rate;

        // check priority weight

        // check priority express wieght
        
        // check parcel select weight

        // check international priority express

        // check international priority 

        if (!isset($this->rates[$local])) return $rate;
        if (!isset($this->rates[$local][$carrier])) return $rate;
        if (!isset($this->rates[$local][$carrier][$service])) return $rate;
        return round($rate * (1 - ($this->rates[$local][$carrier][$service] / 100)), 2);
    }

    /**purpose
     *   check if we should apply usps rates
     * args
     *   local
     *   service
     *   weight
     * returns
     *   should apply rates
     */
    public function validateUspsRates($local, $service, $weight) {
        

        if ($local == RateDiscounts::LOCAL_DOMESTIC) {

            // priority has to be less than 20 lbs
            if ($service == RateDiscounts::SERVICE_USPS_PRIORITY && $weight >= (20 * 16)) return false;

            // priority express has to be less than 10 lbs
            if ($service == RateDiscounts::SERVICE_USPS_PRIORITY_EXPRESS && $weight >= (10 * 16)) return false;

            // parcel select has to be less than 20 lbs
            if ($service == RateDiscounts::SERVICE_USPS_PARCEL_SELECT && $weight >= (20 * 16)) return false;
        }
        else if ($local == RateDiscounts::LOCAL_INTERNATIONAL || $local == RateDiscounts::LOCAL_CANADA) {
            
            // priority has to be less than 20 lbs
            if ($service == RateDiscounts::SERVICE_USPS_PRIORITY && $weight >= (20 * 16)) return false;

            // priority express has to be less than 20 lbs
            if ($service == RateDiscounts::SERVICE_USPS_PRIORITY_EXPRESS && $weight >= (20 * 16)) return false;
        }

        return true;
    }
}