<?php

namespace App\Models\Mysql;

use App\Common\Functions;
use App\Common\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

use App\Mail;
use App\Common\Email;
use ApiAuth;
use App\Libraries;


use App\Http\Controllers\Response;

class User extends Base
{
    public $table = 'users';

    protected $hidden = [
        'password',
        'remember_token',
        'salt'
    ];

    // status constants
    public static $STATUS_PENDING = 'PENDING';
    public static $STATUS_DENIED = 'DENIED';
    public static $STATUS_APPROVED = 'APPROVED';

    // token keys
    private static $TOKEN_KEY_ACCESS = 'diwoqi-221-aw2-eurps';
    private static $TOKEN_KEY_REFRESH = 'wpdjdi-dmfdsjf-eurps';

    // referral program types
    public static $REFERRAL_PROGRAM_SEND_FIRST_LABEL = 'SEND_FIRST_LABEL';
    public static $REFERRAL_PROGRAM_COMMISSION = 'COMMISSION';

    // search users
    public static $search_parameters = [
        [
            'argument' => 'query',
            'columns' => ['email', 'name', 'company', 'phone'],
            'type' => 'SEARCH'
        ],
        [
            'argument' => 'status',
            'column' => 'status', 
            'type' => 'EQUAL'
        ]
    ];

    /**purpose
     *   construct a default user
     * args
     *   (none)
     * returns
     *   model called
     */
    public function __construct($args = []) {
		parent::__construct($args);
        $this->referral_program_type = User::$REFERRAL_PROGRAM_SEND_FIRST_LABEL;
        $this->referral_program = false;
        $this->referral_code = Functions::getUUID();
        $this->wallet_balance = 0;
        $this->status = User::$STATUS_PENDING;
	}

    /**purpose
     *   get wallet balance for user
     * args
     *   (none)
     * returns
     *   balance
     */
    public function getWalletBalance($connection = null) {
        // get wallet user id if there is a parent user then use their wallet instead
        $wallet_user_id = isset($this->parent_user_id) ? $this->parent_user_id : $this->id;
        return 
            isset($connection) ? 
                WalletTransaction::on($connection)->where([['user_id', '=', $wallet_user_id], ['failed', '=', 0], ['pending', '=', 0]])->sum('amount') :
                WalletTransaction::where([['user_id', '=', $wallet_user_id], ['failed', '=', 0], ['pending', '=', 0]])->sum('amount');
    }


    /**purpose
     *   set password
     * args
     *   password
     * returns
     *   valid
     */
    public function setPassword($password) {

        // check character limit 
        if (strlen($password) < 8) return false;
        
        // create salt 
        $this->salt = Functions::getRandomID(32);

        // hash password
        $password_with_salt = $password . $this->salt;
        $this->password = password_hash($password_with_salt, PASSWORD_BCRYPT);

        // return success
        return true;
    }   

    /**purpose
     *   check password
     * args
     *   password
     * returns
     *   valid (bool)
     */
    public function checkPassword($password) {
        $password_with_salt = $password . $this->salt;
        return password_verify($password_with_salt, $this->password);
    }

    /**purpose
     *   create token
     * args
     *   (none)
     * returns
     *   tokens
     */
    public function generateTokens($api_key = null) {
  
        $access_token = $this->generateAccessToken($api_key);
        $refresh_token = $this->generateRefreshToken($api_key);

        return [
            'access' => $access_token,
            'refresh' => $refresh_token
        ];
    }
    
    /**purpose
     *   generate a single token
     * args
     *   secret
     *   expire time
     * returns
     *   token
     */
    private function generateToken($key, $expires, $api_key) {

        // create payload
        $payload = [
            'id' => $this->id,
            'exp' => $expires,
        ];

        // check to see if api key exists
        if (isset($api_key)) {
            $payload['api_key_id'] = $api_key->id;
        }
        
        // generate token and return
        return [
            'token' => JWT::encode($payload, $key),
            'expires' => $expires
        ];
    }

    /**purpose
     *   generate a refersh token
     * args
     *   (none)
     * returns
     *   token
     */
    private function generateRefreshToken($api_key) {
        $expires = strtotime('+1 year', time());
        return $this->generateToken(User::$TOKEN_KEY_REFRESH, $expires, $api_key);
    }

    
    /**purpose
     *   generate a access token
     * args
     *   (none)
     * returns
     *   token
     */
    private function generateAccessToken($api_key) {
        $expires = strtotime('+1 day', time());
        return $this->generateToken(User::$TOKEN_KEY_ACCESS, $expires, $api_key);
    }

    /**purpose
     *   profile by access token
     * args
     *   token
     * returns
     *   profile
     */
    public static function findByAccessToken($token) {
        return User::findByToken($token, User::$TOKEN_KEY_ACCESS);
    }
    
    /**purpose
     *   profile by refresh token
     * args
     *   token
     * returns
     *   profile
     */
    public static function findByRefreshToken($token) {
        return User::findByToken($token, User::$TOKEN_KEY_REFRESH);
    }

    /**purpose
     *   profile by token
     * args
     *   token
     * returns
     *   profile
     */
    private static function findByToken($token, $key) {

        // decode claims
        try {
            $claims = JWT::decode($token, $key, array('HS256'));
        }
        catch (UnexpectedValueException $ex) {
            return null;
        }
        catch (SignatureInvalidException $ex) {
            return null;
        }

        // check claims api key
        if (isset($claims->api_key_id)) {

            // check if api key is still active
            $api_key = ApiKey::find($claims->api_key_id);
            if (!isset($api_key)) return null;
            if (!Validator::validateBoolean($api_key->active)) return null;

            // set api key id in the static auth
            ApiAuth::setApiKeyId($claims->api_key_id);
        }

        // if expired we should return null
        if (time() > $claims->exp) return null;

        // return proflie
        return User::find($claims->id);
    }

    
    /**purpose
     *   send verification email
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function sendVerificationEmail() {
        $mailer = new Mail\EmailVerification($this);
        Email::sendMailer($mailer);
    }

    /**purpose
     *   send admins a new user email
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function sendAdminNewUser() {
        $mailer = new Mail\AdminNewUser($this);
        Email::sendMailer($mailer);
    }
    
    /**purpose
     *   send password request email
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function sendPasswordRequest() {
        $mailer = new Mail\PasswordReset($this);
        Email::sendMailer($mailer);
    }

    /**purpose
     *   invites someone to join. 
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function sendReferralInvite($referred_name, $referred_email) {
        $mailer = new Mail\ReferralInvite($this, $referred_name, $referred_email);
        Email::sendMailer($mailer);
    }

    /**purpose
     *   invites user to have access to account
     * args
     *   parent_user (required)
     * returns
     *   (none)
     */
    public function sendSubUserCreated($parent_user) {
        $mailer = new Mail\SubUserCreated($this, $parent_user);
        Email::sendMailer($mailer);
    }

    
    /**purpose
     *   approve user
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function approveUser() {
        $this->status = User::$STATUS_APPROVED;
        $this->save();
        $mailer = new Mail\ClientApproved($this);
        Email::sendMailer($mailer);
    }
    


    /**purpose
     *   refill a wallet with a payment method
     * args
     *   payment_method
     * return
     *   refill_response
     */
    public function refillWallet($payment_method, $amount) {
        
        $response = new Response;

        // get total amount
        $processing_fee = (env('APP_ENV') != 'sandbox' && $payment_method->type == 'cc') ? round($amount * (env('GATEWAY_CC_FEE', .03)), 2) : 0;
		$charge_amount = $amount + $processing_fee;
        
		// create new wallet transaction
		$new_wallet_transaction = new WalletTransaction;
		$new_wallet_transaction->user_id = $this->id;
		$new_wallet_transaction->amount = $amount;
		$new_wallet_transaction->failed = 1;
		$new_wallet_transaction->failed_message = 'Unknown Error';
		$new_wallet_transaction->type = 'Refill';
		if (env('APP_ENV') != 'sandbox') $new_wallet_transaction->payment_method_id = $payment_method->id;
		$new_wallet_transaction->processing_fee = $processing_fee;
		$new_wallet_transaction->pending = 1;
		$new_wallet_transaction->save();

        if (env('APP_ENV') != 'sandbox') {
            $processor = new Libraries\Payment\Processor;
            $processor_response = $processor->process($new_wallet_transaction, $payment_method, $charge_amount);

            // check for successful response
            if ($processor_response->result == 'failure') {
                $new_wallet_transaction->failed_message = $processor_response->message;
                $new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());
                $new_wallet_transaction->pending = 0;
                $new_wallet_transaction->save();
                return $processor_response->setFailure($processor_response->message . ' - You can try deleting and adding your payment method again');
            }
            
            $new_wallet_transaction->auth_code = $processor_response->get('auth_code');
            $new_wallet_transaction->auth_reference = $processor_response->get('auth_reference');
        }

		// mark wallet transaction success
		$new_wallet_transaction->failed = 0;
		$new_wallet_transaction->failed_message = '';

		// cc or ach_auto immedietly finalizes
		if (env('APP_ENV') == 'sandbox' || $payment_method->type == 'cc'  || Validator::validateBoolean($this->ach_auto)) {
			$new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());
			$new_balance = $this->getWalletBalance() + $amount;
			$new_wallet_transaction->balance = $new_balance;
			$new_wallet_transaction->pending = 0;
			$this->wallet_balance = $new_balance;
			$this->save();
		}

		// save fineal version of new wallet transaction
		$new_wallet_transaction->save();

		// set wallet transaction in response
		$response->set('wallet_transaction', $new_wallet_transaction);

		// return successful response 
		return $response->setSuccess();
    }
    

    /**purpose
     *   check for auto refill
     * args
     *   (none)
     * returns
     *   (none)
     */
    public function checkAutoRefill() {

        $response = new Response;
        $wallet_balance = $this->getWalletBalance();

        // if sandbox then we should auto refill up to 1000
        if (env('APP_ENV') == 'sandbox') {
            if ($wallet_balance < 100) {
                return $this->refillWallet(null, 1000);
            }
        }
        else { 
            // check ach thresholds
            $payment_method_ach = PaymentMethod::where([
                ['user_id', '=', $this->id],
                ['type', '=', 'ach'],
                ['active', '=', 1]
            ])->limit(1)->get()->first();
            if (isset($payment_method_ach)) {
                if (Validator::validateBoolean($payment_method_ach->auto_pay)) {
                    // check to see if there is a pending transaction
                    if (WalletTransaction::where([
                        ['user_id', '=', $this->id],
                        ['type', '=', 'Refill'],
                        ['pending', '=', 1],
                        ['failed', '=', 0]
                    ])->count() > 0) return $response->setFailure();

                    if ($wallet_balance < $payment_method_ach->threshold) return $this->refillWallet($payment_method_ach, $payment_method_ach->reload);
                }
            }

            // check cc thresholds
            $payment_method_cc = PaymentMethod::where([
                ['user_id', '=', $this->id],
                ['type', '=', 'cc'],
                ['active', '=', 1]
            ])->limit(1)->get()->first();
            if (isset($payment_method_cc)) {
                if (Validator::validateBoolean($payment_method_cc->auto_pay)) {
                    if ($wallet_balance < $payment_method_cc->threshold) return $this->refillWallet($payment_method_cc, $payment_method_cc->reload);
                }
            }
        }
        return $response->setFailure();
    }


}
