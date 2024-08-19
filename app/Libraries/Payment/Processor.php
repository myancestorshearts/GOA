<?php

namespace App\Libraries\Payment;

use GoaRestApi;
use App\Models;

class Processor {

 
    private $gateway;
    
	function __construct() {
        $this->gateway = $this->getVerificationService();
	}

    private function getVerificationService() {
        
        $service = env('GATEWAY');

        switch($service) {
            case 'nmi': 
                return new Gateways\NMI;
            default: 
                return new Gateways\Chase;
        }
    }

    /**purpose
     *   save a card
     * args
     *   name
     *   card
     *   expiration_month
     *   expiration_year
     *   zipcode
     * returns
     *   payment method
     */
	public function saveCard($name, $card, $expiration_month, $expiration_year, $zipcode) {
        return $this->gateway->saveCard($name, $card, $expiration_month, $expiration_year, $zipcode);
    }
    
    /**purpose
     *   save a card
     * args
     *   name
     *   routing
     *   account
     *   zipcode
     *   type
     * returns
     *   payment method
     */
	public function saveACH($name, $routing, $account, $zipcode, $type) {
        return $this->gateway->saveACH($name, $routing, $account, $zipcode, $type);
    }

    /**purpose
     *   save a card
     * args
     *   transaction
     *   payment_method
     *   amount
     * returns
     *   payment method
     */
	function process($transaction, $payment_method, $amount, $auto = false) {
        return $this->gateway->process($transaction, $payment_method, $amount, $auto);
    }
}