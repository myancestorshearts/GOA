<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use Redirect;
use App\Http\Controllers\Response;
use Illuminate\Http\Request;
use Hash;

use App\Libraries;

use App\Models\Dynamo;
use App\Models\Mysql;


use App\Common\Validator;
use App\Common\Functions;

use App\Integrations;

use ApiAuth;

use DB;

class ApiController extends Controller {

	/**purpose
	 *   register a user
	 * args
	 *   user_id (optional) (only exists if user is installing app from store)
	 *   first_name (required)
	 *   last_name (required)
	 *   email (required)
	 *   password (required)
	 *   company (optional)
	 *   phone (required)
	 *   code (optional)
 	 * returns
	 *   user
	 */
	public function doRegister(Request $request) {

        // create response
        $response = new Response;

        // validate requests has all required arguments
        if (!$response->hasRequired($request, ['name', 'email', 'phone', 'password'])) return $response->jsonFailure('Missing required fields');

        // validate various fields to make sure they are valid
        $validated_name = Validator::validateText($request->get('name'), ['trim' => true]);
        if (!isset($validated_name)) return $response->jsonFailure('Invalid name', 'INVALID_ARGS');

        $validated_company = Validator::validateText($request->get('company', ''), ['trim' => true, 'clearable' => true]);
        if (!isset($validated_company)) return $response->jsonFailure('Invalid company name', 'INVALID_ARGS');
        
        $validated_email = Validator::validateEmail($request->get('email', ''));
        if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');
        
        $validated_phone = Validator::validatePhone($request->get('phone', ''));
        if (!isset($validated_phone)) return $response->jsonFailure('Invalid phone', 'INVALID_ARGS');

		// check to see if user exists with email already
		if (Mysql\User::where('email', '=', $validated_email)->count() > 0) return $response->jsonFailure('User already exists with email', 'DUPLICATE_USER');

        // create initial user and set defaults
		$user = null;
		if ($request->has('user_id')) {
			$user = Mysql\User::find($request->get('user_id'));
			if (!isset($user)) return $response->jsonFailure('Invalid user id');
			if ($user->password != 'blank') return $response->jsonFailure('Invalid user id');
		}
		
		// create user if not store install
		if (!isset($user)) $user = new Mysql\User;

		// set credentials
		$user->name = $validated_name;
		$user->company = $validated_company;
		$user->email = $validated_email;
		$user->phone = $validated_phone;
		
		// set password
		if (!$user->setPassword($request->get('password'))) return $response->jsonFailure('Password does not meet requirements. Password must be at least 8 characters long', 'PASSWORD_FAILED_REQUIREMENTS');

		// save the user
		$user->save();
	
		// get referrer and set referrer 
		if ($request->has('code')) {

			// get referral user
			$referral_user = Mysql\User::where('referral_code', '=', $request->get('code'))->limit(1)->get()->first();

			// check to make sure referral user has referral program enabled
			if (isset($referral_user) && Validator::validateBoolean($referral_user->referral_program)) {
				
				// create referral
				$referral = new Mysql\Referral;
				$referral->user_id = $referral_user->id;
				$referral->referred_user_id = $user->id;
				$referral->email = $user->email;
				$referral->name = $user->name;
				$referral->purchased_first_label = false;
				$referral->label_link = '';

				// insert referral into database
				$referral->save();
			}
		}

		// send email 
		$user->sendVerificationEmail();

		// send admin new user
		$user->sendAdminNewUser();

		// set user in response
		$response->set('user', $user);
    
		// return response
        return $response->jsonSuccess();
	}

	/**purpose
	 *   login user and get tokens
	 * args
	 *   email (required)
	 *   password (required)
	 * returns
	 *   user
	 *   token 
	 */
	public function doLogin(Request $request) {
        

        // create response
		$response = new Response;

        // check for arguments
		if (!$response->hasRequired($request, ['email', 'password'])) return $response->jsonFailure('Missing required fields');

		// validate email
        $validated_email = Validator::validateEmail($request->get('email', ''));
        if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');

        // get user by email
        $user = Mysql\User::where('email', '=', $validated_email)->limit(1)->get()->first();
		if (!isset($user)) return $response->jsonFailure('Invalid credentials');

		// check password
		if (!$user->checkPassword($request->get('password'))) return $response->jsonFailure('Invalid credentials');

		// check to make sure email is verified
		if (!Validator::validateBoolean($user->verified)) return $response->jsonFailure('Email is not verified - Must verify before you can log in');

		// check to make sure email is verified
		if (!Validator::validateBoolean($user->active)) return $response->jsonFailure('Invalid credentials');

		// create access token
		$tokens = $user->generateTokens();

		// set response
		$response->set('tokens', $tokens);
		$response->set('user', $user);

        // return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   get search
	 * args
	 *   class (required)
	 *   include_meta (optional) (includes "take, page, total_count, page_count" in response)
	 *   order_by (sorts results by this)
	 * returns
	 *   models
	 *   take
	 *   page
	 *   total_count
	 *   page_count
	 */
	public function getSearch(Request $request) {

		// create response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['class', 'take', 'page'])) return $response->jsonFailure('Missing required fields');

		// get user from api auth
		$user = ApiAuth::user();

		// get class from class key
		$class = Mysql\Base::getClassFromClassKey($request->get('class'));
		if (!isset($class)) return $response->jsonFailure('Invalid class key');

        // get take and page
        $take = (int) min($request->get('take'), 1000);
        $page = (int) $request->get('page');

		// validate page
		if ($page <= 0) return $response->jsonFailure('Invalid page');

		// create a temp model to get the table
		$model_temp = new $class;

		// check if they are a sub user and block
		if (isset($user->parent_user_id) && $class::SUB_USER_BLOCK) return $response->jsonFailure('Not authorized');

        // instantiate the query class. 
		$models_query = null;
		if (isset($user->parent_user_id) && !$class::PERSONAL_ONLY) {
			if (!$class::SUB_USER_ALLOW) {
				$models_query = $class::where($model_temp->table . '.user_id', '=', $user->parent_user_id);
			}
			else $models_query = $class::where($model_temp->table . '.created_user_id', '=', $user->id);
		}
		else {
			$models_query = $class::where($model_temp->table . '.user_id', '=', $user->id);
		}

        // apply the search
        $apply_search_result = Mysql\Base::applyFilters($request, $class, $models_query);
        if ($apply_search_result !== true) return $response->jsonFailure($apply_search_result);

        // generate meta information if include_meta is true
        if (Validator::validateBoolean($request->get('include_meta',  false)))
        {
			$count_query = clone ($models_query);
        	$count = count($count_query->select(DB::raw('count(*) as total'))->pluck('total')->all());
	        $response->set('total_count', $count);
	        $response->set('page_count', ceil($count / $take));
        	$response->set('take', $take);
        	$response->set('page', $page);
        }
		
		// order by arguments 
        Mysql\Base::applyOrderBy($request, $class, $models_query);

        // get models and set them in response
        $models = $class::getModels($models_query->take($take)->offset(($page - 1) * $take)->get(), $request);
        $response->set('models', $models);

		// return successful response
		return $response->jsonSuccess();
	}

	
	/**purpose 
	 *   verify email
	 * args
	 *   key (required)
	 * returns
	 *   result
	 */
	public function doVerifyEmail(Request $request)
	{
		// create response
		$response = new Response;

		// verify has required arguments
		if (!$response->hasRequired($request, ['key'])) return $response->jsonFailure('Missing required fields');

		// decrypt key 
		$email = decrypt($request->get('key'));
		
		// get user
		$user = Mysql\User::where('email', '=', $email)->limit(1)->get()->first();
		if (!isset($user)) return $response->jsonFailure('User not found');

		// set email is verified
		$user->verified = true;

		// save user
		$user->save();

		// return successful repsonse
		return $response->jsonSuccess();
	}

	/**purpose
	 *   request a forgotton password
	 * args
	 *   email (required)
	 * returns
	 *   result
	 */
	public function doPasswordRequest(Request $request) {
		// create response
		$response = new Response;

		// check to make sure required fields are set
		if (!$response->hasRequired($request, ['email'])) return $response->jsonFailure('Missing required fields');

		// validate email
        $validated_email = Validator::validateEmail($request->get('email', ''));
        if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');

		// check to make sure account exists
		$user = Mysql\User::where('email', '=', $validated_email)->limit(1)->get()->first();
		if (!isset($user)) return $response->jsonFailure('No user found associated with email.');

		// send reset request
		$user->sendPasswordRequest();
		
		// return successful repsonse
		return $response->jsonSuccess();
	}

	/**purpose
	 *   set password
	 * args
	 *   key (required)
	 *   password (required)
	 * returns
	 *   (none)
	 */
	public function doPasswordSet(Request $request) {

		// create response
		$response = new Response;

		// check key 
		if (!$response->hasRequired($request, ['key', 'password'])) return $response->jsonFailure('Missing required fields');

		// decrypted key
		$decrypted_key = decrypt($request->get('key'));

		// get email and expire
		$decrypted_parts = explode('#', $decrypted_key);
		if (count($decrypted_parts) != 2) return $rseponse->jsonFailure('Invalid key');
		$email = $decrypted_parts[0];
		$time = $decrypted_parts[1];

		// check time 
		if ($time < time()) return $response->jsonFailure('Password reset expired.  Please request another password reset');

		// get user from email
		$user = Mysql\User::where('email', '=', $email)->limit(1)->get()->first();
		$user->verified = 1;
		if (!isset($user)) return $response->jsonFailure('Invalid key');

		// check password that it meets requirements
		$password_result = $user->setPassword($request->get('password'));
		if (!$password_result) return $response->jsonFailure('Password did not meet requirements');

		// save user
		$user->save();

		// return successful repsonse
		return $response->jsonSuccess();
	}



	/**purpose
	 *   refresh tokens
	 * args
	 *   (header) authorization
	 * returns
	 *   tokens
	 */
	public function doTokenRefresh(Request $request) {
		// create response
		$response = new Response;

		// get user
		$user = ApiAuth::user();

		// create access token
		$tokens = $user->generateTokens();

		// set response
		$response->set('tokens', $tokens);
		
		// return successful repsonse
		return $response->jsonSuccess();
	}


	/**purpose
	 *   refill wallet
	 * args
	 *   type (required)
	 *   amount (required)
	 *   processing_fee (required if type = cc)
	 * returns
	 *   wallet_transaction
	 */
	public function doWalletRefill(Request $request) {

		// create response
		$response = new Response;

		// get user
		$user = ApiAuth::user();

		// validate required fields
		if (!$response->hasRequired($request , ['amount'])) return $response->jsonFailure('Missing required fields');

		// validate amount is valid
		$amount = (float) round($request->get('amount'), 2);
		if ($amount <= 0) return $response->jsonFailure('Invalid amount');

		// validate type is valid
		$type = $request->get('type', '');
		if ($type != 'ach' && $type != 'cc' && env('APP_ENV') != 'sandbox') return $response->jsonFailure('Invalid type');

		// get payment method 
		if (env('APP_ENV') != 'sandbox') {
			$payment_method = Mysql\PaymentMethod::where([
				['user_id', '=', $user->id],
				['type', '=', $type],
				['active', '=', 1]
			])->limit(1)->get()->first();
			if (!isset($payment_method)) return $response->jsonFailure('Invalid payment method');
		}

		// get total amount
		$charge_amount = ($type == 'cc') ? round($amount * (1 + env('GATEWAY_CC_FEE', .03)), 2) : round($amount, 2);
		$expected_charge = round((float) $request->get('processing_fee', 0), 2) + $amount;

		// check expected charge
		if ($expected_charge != $charge_amount) return $response->jsonFailure('Expected charge different. Cannot process transaction - Contact Support');

		// create new wallet transaction
		$new_wallet_transaction = new Mysql\WalletTransaction;
		$new_wallet_transaction->user_id = $user->id;
		$new_wallet_transaction->amount = $amount;
		$new_wallet_transaction->failed = 1;
		$new_wallet_transaction->failed_message = 'Unknown Error';
		$new_wallet_transaction->type = 'Refill';
		if (env('APP_ENV') != 'sandbox') $new_wallet_transaction->payment_method_id = $payment_method->id;
		$new_wallet_transaction->processing_fee = round((float) $request->get('processing_fee', 0), 2);
		$new_wallet_transaction->pending = 1;
		$new_wallet_transaction->save();

		// charge with chase
		if (env('APP_ENV') != 'sandbox') {
			$processor = new Libraries\Payment\Processor;
			$processor_response = $processor->process($new_wallet_transaction, $payment_method, $charge_amount);

			// check fo successful chase response
			if ($processor_response->result == 'failure') {
				$new_wallet_transaction->failed_message = $processor_response->message;
				$new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());
				$new_wallet_transaction->pending = 0;
				$new_wallet_transaction->save();
				return $processor_response->jsonFailure($processor_response->message . ' - You can try deleting and adding your payment method again');
			}
			
			$new_wallet_transaction->auth_code = $processor_response->get('auth_code');
			$new_wallet_transaction->auth_reference = $processor_response->get('auth_reference');
		}

		// mark wallet transaction success
		$new_wallet_transaction->failed = 0;
		$new_wallet_transaction->failed_message = '';

		// cc or ach_auto immedietly finalizes
		if ($type == 'cc' || env('APP_ENV') == 'sandbox' || Validator::validateBoolean($user->ach_auto)) {
			$new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());
			$new_balance = $user->getWalletBalance() + $amount;
			$new_wallet_transaction->balance = $new_balance;
			$new_wallet_transaction->pending = 0;
			$user->wallet_balance = $new_balance;
			$user->save();
		}

		// save fineal version of new wallet transaction
		$new_wallet_transaction->save();

		// set wallet transaction in response
		$response->set('wallet_transaction', $new_wallet_transaction);

		// return successful response 
		return $response->jsonSuccess();
	}

	/**purpose
	 *   add a payment method
	 * args
	 *   type (required) (ach, cc)
	 *   zipcode (required)
	 *   name (required)
	 *   account (required if ach)
	 *   routing (required if ach)
	 *   account_type (required if ach) (C - Personal Checking, S - Personal Savings, X - Business Checking)
	 *   card (required for cc)
	 *   expiration_month (required for cc)
	 *   expiration_year (required for cc)
	 *   security (required for cc)
	 * returns
	 *   payment_method
	 */
	public function doPaymentMethodAdd(Request $request) {

		// create response
		$response = new Response;

		// get validated user
		$user = ApiAuth::user();

		// validate type
		$type = $request->get('type', '');
		if ($type != 'ach' && $type != 'cc') return $response->jsonFailure('Invalid type');

		// create payment method
		$payment_method = new Mysql\PaymentMethod;
		$payment_method->user_id = $user->id;
		$payment_method->type = $type;
		$payment_method->zipcode = $request->get('zipcode');
		$payment_method->name = $request->get('name');
		$payment_method->auto_pay = 0;
		$payment_method->active = 1;

		// initialize chase payment processor
		$processor = new Libraries\Payment\Processor;

		// each type has different requirements
		if ($type == 'ach') {
			// check ach required args
			if (!$response->hasRequired($request, ['account', 'routing', 'zipcode', 'name', 'account_type'])) return $response->jsonFailure('Missing required fields');

			// save ach with chase
			$processor_response = $processor->saveACH(
				$request->get('name'),
				$request->get('routing'),
				$request->get('account'),
				$request->get('zipcode'),
				$request->get('account_type')
			);
			// check for failure
			if ($processor_response->result == 'failure') return $processor_response->json();
			
			// finalize payment method
			$payment_method->token = encrypt($processor_response->get('token'));
			$payment_method->expiration_month = '';
			$payment_method->expiration_year = '';
			$payment_method->threshold = 50;
			$payment_method->reload = 250;
			$payment_method->last_four = substr($request->get('account'), -4);

			$payment_method->save();
		}
		else if ($type == 'cc') {

			// check cc required args
			if (!$response->hasRequired($request, ['card', 'expiration_month', 'expiration_year', 'name', 'security', 'zipcode'])) return $response->jsonFailure('Missing required fields');
			
			// save cc with chase
			$processor_response = $processor->saveCard(
				$request->get('name'),
				$request->get('card'),
				$request->get('expiration_month'),
				$request->get('expiration_year'),
				$request->get('zipcode')
			);
			
			// check for failure
			if ($processor_response->result == 'failure') return $processor_response->json();

			// finalize payment method 
			$payment_method->token = encrypt($processor_response->get('token'));
			$payment_method->expiration_month = $request->expiration_month;
			$payment_method->expiration_year = '20' . $request->expiration_year;
			$payment_method->threshold = 25;
			$payment_method->reload = 100;
			$payment_method->last_four = substr($request->get('card'), -4);
			$payment_method->save();
		}
		
		// delete any pre existing payment methods
		Mysql\PaymentMethod::where([
			['user_id', '=', $user->id],
			['type', '=', $type],
			['active', '=', 1],
			['id', '!=', $payment_method->id]
		])->update([
			'active' => 0
		]);

		// set response data
		$response->set('payment_method', $payment_method);

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   get verified user
	 * args
	 *   (none)
	 * returns
	 *   user
	 */
	public function getUser(Request $request) {

		// initialize response
		$response = new Response;
		
		// set response
		$response->set('user', ApiAuth::user());

		// returns successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   for user to set basic client data
	 * args
	 *   name (optional)
	 *   email (optional)
	 *   phone (optional)
	 *   company (optional)
	 * returns
	 *   user
	 */
	public function doUserSet(Request $request) {

		// initialize response
		$response = new Response;

		// get user
		$user = ApiAuth::user();

		// validate and set name
		if ($request->has('name')) {
			$validated_name = Validator::validateText($request->get('name'), ['trim' => true]);
			if (!isset($validated_name)) return $response->jsonFailure('Invalid name', 'INVALID_ARGS');
			$user->name = $validated_name;
		}

		// validate and set company
		if ($request->has('company')) {
			$validated_company = Validator::validateText($request->get('company', ''), ['trim' => true, 'clearable' => true]);
			if (!isset($validated_company)) return $response->jsonFailure('Invalid company name', 'INVALID_ARGS');
			$user->company = $validated_company;
		}

		// validate and set email
		if ($request->has('email')) {
			$validated_email = Validator::validateEmail($request->get('email', ''));
			if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');
			$user->email = $validated_email;
		}

		// validate and set phone
		if ($request->has('phone')) {
			$validated_phone = Validator::validatePhone($request->get('phone', ''), ['clearable' => true]);
			if (!isset($validated_phone)) return $response->jsonFailure('Invalid phone', 'INVALID_ARGS');
			$user->phone = $validated_phone;
		}

		// save user
		$user->save();

		// set response
		$response->set('user', $user);

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   allow logged in user to set password
	 * args
	 *   current_password (required)
	 *   new_password (required)
	 * returns
	 *   (none)
	 */
	public function doUserPasswordSet(Request $request) {

		// initialize response
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();

		// current password
		if (!$response->hasRequired($request, ['current_password', 'new_password'])) return $response->jsonFailure('Missing required fields');

		// check existing password
		if (!$user->checkPassword($request->get('current_password'))) $response->jsonFailure('Invalid password');

		// set new password
		$user->setPassword($request->get('new_password'));
		
		// save user
		$user->save();

		// return successful response
		return $response->jsonSuccess();
	}
	
	/**purpose 
	 *   delete payment method
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	*/
	public function doPaymentMethodDelete(Request $request) {

		// initialize response
		$response = new Response;

		// get user 
		$user = ApiAuth::user();

		// check to see if payment method exists
		$payment_method = Mysql\PaymentMethod::find($request->get('id'));
		if (!isset($payment_method)) return $response->jsonFailure('Invalid id');

		// check to make sure payment method belongs to logged in user
		if ($payment_method->user_id != $user->id) return $response->jsonFailure('Not authorized');

		// delete payment method
		$payment_method->active = 0;
		$payment_method->save();

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   get payment method
	 * args
	 *   type (required)
	 * returns
	 *   payment_method
	 */
	public function getPaymentMethod(Request $request) {

		// initialize repsponse
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();

		// check type
		$type = $request->get('type', '');
		if ($type != 'ach' && $type != 'cc') return $response->jsonFailure('Invalid type');

		// get payment method
		$payment_method = Mysql\PaymentMethod::where([
			['user_id', '=', $user->id],
			['type', '=', $type],
			['active', '=', 1]
		])->limit(1)->get()->first();
		if (isset($payment_method)) $response->set('payment_method', $payment_method);

		// returns successful response
		return $response->jsonSuccess();
	}

	/**purpose 
	 *   get payment methods
	 * args
	 *   (none)
	 * returns
	 *   payment_methods
	 */
	public function getPaymentMethods(Request $request) {
		
		// initialize repsponse
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();
		
		// get payment method
		$payment_methods = Mysql\PaymentMethod::where([
			['user_id', '=', $user->id],
			['active', '=', 1]
		])->limit(2)->get();

		// set payment methods in response
		$response->set('payment_methods', $payment_methods);

		// return payment methods
		return $response->jsonSuccess();
	}

	// purpose
	//   set a payment method
	// args
	//   id (required)
	//   threshold (optional)
	//   reload (optional)
	//   auto_pay (optional)
	// returns
	//   payment_method
	public function doPaymentMethodSet(Request $request) {
		
		// initialize response
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();
		
		// get payment method
		$payment_method = Mysql\PaymentMethod::find($request->get('id'));

		// check to make sure payment method belongs to logged in user
		if ($payment_method->user_id != $user->id) return $response->jsonFailure('Not authorized');
		
		// check threshold and set
		if ($request->has('threshold'))  {
			$threshold = (float) $request->get('threshold');
			if ($threshold < 25) return $response->jsonFailure('Threshold must be 25 or greater');
			$payment_method->threshold = round($threshold, 2);
		}

		// check realod and set
		if ($request->has('reload'))  {
			$reload = (float) $request->get('reload');
			if ($reload < 1) return $response->jsonFailure('Refill must be 100 or greater');
			$payment_method->reload = round($reload, 2);
		}
		
		// double check threshold
	//if ($payment_method->threshold >= $payment_method->reload) return $response->jsonFailure('Refill must be greater than threshold');

		// set autopay
		$payment_method->auto_pay = Validator::validateBoolean($request->get('auto_pay', $payment_method->auto_pay));

		// save and return
		$payment_method->save();
		
		// set response
		$response->set('payment_method', $payment_method);

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   add an api key
	 * args
	 *   name (required)
	 * returns
	 *   api_key
	 */
	public function doApiKeyAdd(Request $request) {

		// initialize response
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();

		// check for name on api key
		if (!$response->hasRequired($request, ['name'])) return $response->jsonFailure('Missing required fields');

		// check name
		$name = trim($request->get('name'));
		if (Functions::isEmpty($name)) return $resposne->jsonFailure('Invalid name');

		// add api key
		$api_key = new Mysql\ApiKey;
		$api_key->user_id = $user->id;
		$api_key->name = $name;
		$api_key->active = 1;
		$api_key->key = Functions::getRandomID();
		$api_key->password = encrypt(Functions::getRandomID());
		$api_key->save();
		
		// set api key in response
		$response->set('api_key', $api_key->getModel($request));

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   delete an api key
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	public function doApiKeyDelete(Request $request) {

		// create response
		$response = new Response;

		// check for name on api key
		if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

		// get api key
		$api_key = Mysql\ApiKey::find($request->get('id'));
		if (!isset($api_key)) return $response->jsonFailure('Invalid id', 'INVALID_ID');
		
		// check to make usre it is linked to you
		$user = ApiAuth::user();
		if ($user->id != $api_key->user_id) return $response->jsonFailure('Invalid id', 'INVALID_ID');

		// set as deactive
		$api_key->active = 0;
		$api_key->save();

		// return successful response
		return $response->jsonSuccess();

	}

	/**purpose
	 *   get user preferences
	 * args
	 *   (none)
	 * returns
	 *   preferences
	 */
	public function getUserPreferences(Request $request) {

		// initiate response
		$response = new Response;

		// get verified user
		$user = ApiAuth::user();

		// get preferences
		$preferences = Dynamo\Preferences::findOrCreate($user->id);

		// set response
		$response->set('preferences', $preferences);

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   set user preference
	 * args
	 *   key (key of preference)
	 *   value (value) 
	 * returns
	 *   (none)
	 */
	public function doUserPreferenceSet(Request $request) {
		
		// initiate response
		$response = new Response;

		// check required fields
		if (!$response->hasRequired($request, ['key', 'value'])) return $response->jsonFailure('Missing required fields');

		// check key
		$key = trim($request->get('key'));
		if ($key == '') return $response->jsonFailure('Invalid key');
		
		// check value
		$value = trim($request->get('value'));
		if ($value == '') return $response->jsonFailure('Invalid value');

		// get verified user 
		$user = ApiAuth::user();

		// get preferences
		$preferences = Dynamo\Preferences::findOrCreate($user->id);
		
		// set key preference
		$preferences->{$request->get('key')} = $request->get('value');
		
		// push changes to database
		$preferences->updateItem();

		// return successful response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   add an order
	 * args
	 *   name (required)
	 *   email (optional)
	 *   company (optional)
	 *   phone (optional)
	 *   address (optional)
	 *   reference (optional)
	 *   address (required) (address model)
	 *   order_products: array (
	 *     name (required)
	 *     sku (optional)
	 *     quantity (required)
	 *     length (optional)
	 *     width (optional)
	 *     height (optional)
	 *     weight (optional)
	 *   )
	 * returns
	 *   order_group
	 */
	public function doOrderGroupAdd(Request $request) {
		
		// initialize response 
		$response = new Response;

		// check required fields
		if (!$response->hasRequired($request, ['name'])) return $response->jsonFailure('Missing required fields');

		// create address
		$order_group_response = Mysql\OrderGroup::create((object) $request->all(), ApiAuth::user());
		return $order_group_response->json();

	}
	
	/**purpose 
	 *   delete an order group
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	public function doOrderGroupDelete(Request $request) {
		
		// initialize response 
		$response = new Response;

		// get order group
		$order_group = Mysql\OrderGroup::find($request->get('id'));
		if (!isset($order_group)) return $response->jsonFailure('Invalid id');

		// set order group
		$order_group->active = 0;
		$order_group->save();

		// return response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   add a saved package
	 * args
	 *   name (required) 
	 *   type (required)
	 *   length (required)
	 *   width (required)
	 *   height (required)
	 *   saved (optional)
	 * returns
	 *   package
	 */
	public function doPackageAdd(Request $request) {
		
		// initialize
		$response = new Response;

		// check required fields
		if (!$response->hasRequired($request, ['name', 'type', 'length', 'width', 'height'])) return $response->jsonFailure('Missing required fields');

		// create package
		$package = Mysql\Package::create((object) $request->all(), ApiAuth::user());
		return $package->json();

	}

	/**purpose
	 *   deactivate a model
	 * args
	 *   class (required)
	 *   id (required)
	 * returns
	 *   (none)
	 */
	public function doDeactivate(Request $request) {

		// initialize response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['class', 'id'])) return $response->jsonFailure('Missing required fields');

		// get user from api auth
		$user = ApiAuth::user();

		// get class from class key
		$class = Mysql\Base::getClassFromClassKey($request->get('class'));
		if (!isset($class)) return $response->jsonFailure('Invalid class key');

		// find model
		$model = $class::find($request->get('id'));
		if (!isset($model)) return $response->jsonFailure('Invalid id');
		
		// check user 
		if (!isset($model->user_id)) return $response->jsonFailure('Not authorized');
		if ($model->user_id != $user->id && $model->user_id != $user->parent_user_id) return $response->jsonFailure('Not authorized');

		// find model 
		$deactivate_result = $model->deactivate();

		// return response
		return $deactivate_result->json();
	}

	/**purpose
	 *   generic set method
	 * args
	 *   class (required)
	 *   id (required)
	 * returns
	 *   (none)
	 */
	public function doSet(Request $request) {
		
		// initialize response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['class', 'id'])) return $response->jsonFailure('Missing required fields');

		// get user from api auth
		$user = ApiAuth::user();

		// get class from class key
		$class = Mysql\Base::getClassFromClassKey($request->get('class'));
		if (!isset($class)) return $response->jsonFailure('Invalid class key');

		// find model
		$model = $class::find($request->get('id'));
		if (!isset($model)) return $response->jsonFailure('Invalid id');


		// check to see if model has permissions
		if (!$model->hasPermissionSet()) return $response->jsonFailure('Not authorized');

		// set model
		$result = $model->set($request);

		// return response
		return $result->jsonSuccess();
	}

	/**purpose
	 *   add a saved address
	 * args
	 *   name (required) 
	 *   phone (optional)
	 *   company (optional)
	 *   email (optional)
	 *   street_1 (required)
	 *   street_2 (optional)
	 *   city (required)
	 *   state (required)
	 *   postal (required)
	 *   country (required)
	 *   saved (optional)
	 * returns
	 *   address
	 */
	public function doAddressAdd(Request $request) {
		
		// initialize
		$response = new Response;

		// check required fields
		if (!$response->hasRequired($request, ['name', 'street_1', 'city', 'state', 'postal', 'country'])) return $response->jsonFailure('Missing required fields');

		// create package
		$package = Mysql\Address::create((object) $request->all(), ApiAuth::user());
		return $package->json();
	}

	/**purpose
	 *   get rate
	 * args
	 *   order_group_id (required if no to_address_id)
	 *   to_address (required if no order_group_id)
	 *   from_address_id (required) (from address)
	 *   package (required) 
	 *   weight (required)
	 *   services (optional)
	 * returns
	 *   shipment
	 */
	public function doShipmentRate(Request $request) {
        $model_response = Mysql\Shipment::create((object) $request->all(), ApiAuth::user());
        return $model_response->json();
	}
	
	/**purpose
	 *   shipment rate mass
	 * args
	 *   order_group_ids (required)
	 *   from_address_id (required) (from address)
	 *   package (required)
	 *   weight (required)
	 *   services (optional)
	 *   ship_date (optional)
	 */
	public function doShipmentRateMass(Request $request) {
		$model_response = Mysql\Shipment::createMass((object) $request->all(), ApiAuth::user());
        return $model_response->json();
	}

	/**purpse
	 *   purchase a label
	 * args
	 *   shipment_id (required)
	 *   rate_id (required)                                                                                                                                                         
	 * returns
	 *   label
	 */
	public function doLabelPurchase(Request $request) {

		// initialize response
		$response = new Response;

		// get user
		$user = ApiAuth::user();

		// check to make sure account is approved
		if ($user->status != Mysql\User::$STATUS_APPROVED) return $response->jsonFailure('Account must be approved before you can purchase labels');

		// create label
        $model_response = Mysql\Label::create((object) $request->all(), $user);
        
		// if success set referral nonsense and mark order fulfilled
		if ($model_response->isSuccess())
		{
			// get validated label
			$validated_label = $model_response->get('model');

			// check for referral
			$referral = Mysql\Referral::where([
				['referred_user_id', '=', $user->id],
				['purchased_first_label', '=', 0]
			])->limit(1)->get()->first();
			if (isset($referral)) {
				$referral->purchased_first_label = 1;
				$referral->label_link = $validated_label->url;
				$referral->save();
			}

		}

		return $model_response->json();
	}

	/**purpose
	 *   send a referral email to invite someone to use software
	 * args
	 *   name (required)
	 *   email (required)
	 * returns
	 *   (none)
	 */
	public function doReferralInvite(Request $request) {
		
		// create response
		$response = new Response;

		// validate request has required fields
		if (!$response->hasRequired($request, ['name', 'email'])) return $response->jsonFailure('Missing required fields');

		// validate email
        $validated_email = Validator::validateEmail($request->get('email', ''));
        if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');

        // validate various fields to make sure they are valid
        $validated_name = Validator::validateText($request->get('name'), ['trim' => true]);
        if (!isset($validated_name)) return $response->jsonFailure('Invalid name', 'INVALID_ARGS');

		// send email
		ApiAuth::user()->sendReferralInvite($validated_name, $validated_email);
	
		// return successful response
		return $response->jsonSuccess();
	}

	
    /**purpose
     *   connect an integration
     * args
     *   type (required) (SHOPIFY)
	 *   name (required) (name of integration)
     * returns
     *   link (shopify)
     */
    public function doIntegrationConnect(Request $request) {

        // set response 
        $response = new Response;
        
        // check required fields 
        if (!$response->hasRequired($request, ['type', 'name'])) return $response->jsonFailure('Missing required fields');

		// validate name
        $validated_name = Validator::validateText($request->get('name'), ['trim' => true]);
		if (!isset($validated_name)) return $response->jsonFailure('Invalid name');

		// get valid type
		$valid_type = Integrations\Integration::validateType($request->get('type'));
		if (!isset($valid_type)) return $response->jsonFailure('Invalid type', 'INVALID_TYPE');

		// create integration instance
		$integration = new Integrations\Integration($valid_type);

		// connect integration
		$connect_response = $integration->connect($request);

		// return response json
		return $connect_response->json();
    }

	/**purpose
	 *   download an integration file
	 * args
	 *   id (required)
	 * returns
	 *   file
	 */
	public function doIntegrationDownload(Request $request) {

        // set response 
        $response = new Response;

		// get the integration
		$integration = Mysql\Integration::find($request->get('id'));
		if (!isset($integration)) return $response->jsonFailure('Invalid integration');

		// validate they have access to download integration
		$user = ApiAuth::user();
		if ($user->id != $integration->user_id) return $response->jsonFailure('Invalid integration');

		// create integration instance
		$integration = new Integrations\Integration($integration->store);

		// download file
		return $integration->download($request)->json();

	}


	/**purpose
	 *   allow an integration to purchase a label on the clients behalf
	 * args
	 * 	 (dependent on specific integration)
	 * returns
	 *   (dependent on specific integration)
	 */
	public function doIntegrationPurchase(Request $request, $integration_id)  {

        // create response response
        $response = new Response;

		// get the integration
		$integration_model = Mysql\Integration::find($integration_id);
		if (!isset($integration_model)) return $response->jsonFailure('Invalid integration');

		// create integration instance
		$integration_connector = new Integrations\Integration($integration_model->store);

		// purchase a label
		return $integration_connector->purchase($integration_model, $request)->json();

	}

	/**purpose
	 *   Oauth connect
	 * args
	 *   state (required)
	 * 	 code (required)
	 * returns
	 *   (none)
	 */
	public function doOauthConnect(Request $request) {
		
		// create response
		$response = new Response;

		// get state
		if (!$response->hasRequired($request, ['state', 'code'])) return redirect('/portal/integrations');

		// get oauth state
		$oauth_state = Mysql\OauthState::find($request->get('state'));
		if (!isset($oauth_state)) return redirect('/portal/integrations');

		// integrate 
		$integration = new Integrations\Integration($oauth_state->key);
		$integration->confirmConnection($oauth_state, $request);

		// delete oauth state
		$oauth_state->delete();

		// if user id exists go to register with user id
		if ($request->path() == 'api/oauth/install') return redirect('/register?user_id=' . $oauth_state->user_id . '&store=' . $oauth_state->key);

		// return portal integrations
		return redirect('/portal/integrations');
	}

	/**purpose
	 *   sync orders
	 * args
	 *   id (required) (integration id)
	 * returns
	 *   (none)
	 */
	public function doIntegrationOrderSync(Request $request) {

        // set response 
        $response = new Response;
        
		// get user
		$user = ApiAuth::user();

        // check required fields 
        if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');

		// get integration
		$integration = Mysql\Integration::find($request->get('id'));

		// check user id
		if ($user->id != $integration->user_id) return $response->jsonFailure('Invalid integration', 'INVALID_FIELDS');

		// sync orders 
		$integration->syncOrders();

		// return success
		return $response->jsonSuccess();
	}

	/**purpose
	 *   sync all integrations
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	public function doIntegrationSyncAll(Request $request) {
		// set response 
        $response = new Response;
        
		// get user
		$user = ApiAuth::user();

		// get integration
		$integrations = Mysql\Integration::where([
			['user_id', '=', $user->id],
			['active', '=', 1]
		])->get();;
			
		// sync orders 
		foreach ($integrations as $integration) {
			$integration->syncOrders();
		}

		// return success
		return $response->jsonSuccess();
	}

	/**purpose
     *   set up a scan form
     * args
     *   from_address_id (required)
     *   label_ids (required) (string array of label ids)
     * returns
     *   model
     */
    public function doScanFormAdd(Request $request) {
        $model_response = Mysql\ScanForm::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }

	/**purpose
     *   get pending from addresses that need to be added to a scan form
     * args
	 *   get from addresses
     * returns
     *   models
     */
    public function getScanFormOptions(Request $request) {
       
		// initialize response
		$response = new Response;

		// set response
		$models = Mysql\ScanForm::getAvailableOptions(ApiAuth::user());
		$response->set('models', $models);

		// get address associated with models
		$address_ids = [];
		foreach ($models as $address_id => $value) {
			$address_ids[] = $address_id;
		}
		$addresses = Mysql\Address::whereIn('id', $address_ids)->get();
		$response->set('addresses', $addresses);

		// return successful response
		return $response->jsonSuccess();
    }
		
	/**purpose
	 *   update first time login
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	public function doUserFirstTimeLogin() {
		
		// initialize response
		$response = new Response;

		// get user
		$user = ApiAuth::user();
		$user->first_time_login = 0;
		
		// save user
		$user->save();

		// return success response
		return $response->jsonSuccess();
	}
	

	/**purpose
	 *   integration install
	 * args
	 *   store
	 * returns
	 *   (oauth redirect)
	 */
	public function getIntegrationInstall($store_type, Request $request) {

		// create response 
		$response = new Response;
 
		// create integration instance
		$integration = new Integrations\Integration($store_type);

		// get install link
		$connect_response = $integration->install($request);

		// if response has redirect link then redirect
		if ($connect_response->has('link')) return redirect($connect_response->get('link'));

		// return response json
		return $connect_response->json();
	}

	/**purpose
	 *   get the label image
	 * args
	 *   label_id (required)
	 *   size (optional) (default is 6x4)
	 * returns
	 *   url
	 */
	public function getLabelImageUrl(Request $request) {

		// create response
		$response = new Response;

		// get label
		$label = Mysql\Label::find($request->get('label_id'));
		if (!isset($label)) return $response->jsonFailure('Invalid label id');

		// check label
		if (
			$label->user_id != ApiAuth::user()->id && 
			!Validator::validateBoolean(ApiAuth::user()->admin) && 
			$label->user_id != ApiAuth::user()->parent_user_id
		) return $response->jsonFailure('Invalid label id');

		// check the type
        $type = $request->get('type', '4X6');
        if (!Mysql\Label::isValidLabelSize($type)) return $response->jsonFailure('Invalid label type');

		// get redirect url 
		$url = $label->getImageUrl($type);
		$response->set('url', $url);
		$response->set('blob', base64_encode(file_get_contents($url)));
		
		// redirect
		return $response->jsonSuccess();
	}

	/**purpose
	 *   get the packing slip image
	 * args
	 *   label_id (required)
	 *   size (optional) (default is 6x4)
	 * returns
	 *   url
	 */
	public function getLabelPackingSlipImageUrl(Request $request) {

		// create response
		$response = new Response;

		// get label
		$label = Mysql\Label::find($request->get('label_id'));
		if (!isset($label)) return $response->jsonFailure('Invalid label id');

		// check label
		if ($label->user_id != ApiAuth::user()->id && !Validator::validateBoolean(ApiAuth::user()->admin)) return $response->jsonFailure('Invalid label id');

		// check the type
        $type = $request->get('size', '4X6');
        if (!Mysql\Label::isValidLabelSize($type)) return $response->jsonFailure('Invalid label type');

		// get redirect url 
		$url = $label->getPackingSlipImageUrl($type);
		$response->set('url', $url);
		$response->set('blob', base64_encode(file_get_contents($url)));
		
		// redirect
		return $response->jsonSuccess();
	}

	/**
	 * purpose
	 *   refund a label
	 * args
	 *   label_id (required)
	 * returns
	 *   (none)
	 */
    public function doLabelRefund(Request $request) {

        // create response
        $response = new Response;
        if (!$response->hasRequired($request, ['label_id'])) return $response->jsonFailure('Missing required fields');

        // get label
        $label = Mysql\Label::find($request->get('label_id'));
        if (!isset($label)) return $response->jsonFailure('Invalid label');
		
        // return refund
        return $label->refund()->json();
    }

	/** 
	 * purpose
	 *   add a callback
	 * args
	 *   type (required) (PRICE_ADJUSTMENT)
	 *   callback_url (required)
	 * returns
	 *   model
	 */
	public function doApiCallbackAdd(Request $request)
	{
		$model_response = Mysql\ApiCallback::create((object) $request->all(), ApiAuth::user());
		return $model_response->json();
	}

	
	/**purpose
	 *   delete an api callback
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	public function doApiCallbackDelete(Request $request) {

		// create response
		$response = new Response;

		// check for name on api key
		if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

		// get api key
		$model = Mysql\ApiCallback::find($request->get('id'));
		if (!isset($model)) return $response->jsonFailure('Invalid id', 'INVALID_ID');
		
		// check to make usre it is linked to you
		$user = ApiAuth::user();
		if ($user->id != $model->user_id) return $response->jsonFailure('Invalid id', 'INVALID_ID');

		// set as deactive
		$model->active = 0;
		$model->save();

		// return successful response
		return $response->jsonSuccess();

	}

	/**purpose
	 *   export wallet transactions
	 * args
	 *   start (required)
	 *   end (required)
	 * returns 
	 *   export.csv
	 */
	public function getWalletTransactionExport(Request $request) {

		// create response 
		$response = new Response;

		// check required
		if (!$response->hasRequired($request, ['start', 'end'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

		// get logged in user
		$user = ApiAuth::user();
		
		// get all wallet transactions
		$wallet_transactions = Mysql\WalletTransaction::join('labels', 'labels.id', '=', 'wallet_transactions.label_id', 'left')
		->join('rates', 'rates.id', '=', 'labels.rate_id', 'left')
		->join('shipments', 'shipments.id', '=', 'labels.shipment_id', 'left')
		->join('packages', 'packages.id', '=', 'shipments.package_id', 'left')
		->join('addresses as from_addresses', 'from_addresses.id', '=', 'shipments.from_address_id', 'left')
		->join('addresses as to_addresses', 'to_addresses.id', '=', 'shipments.to_address_id', 'left')
		->join('payment_methods', 'payment_methods.id', '=', 'wallet_transactions.payment_method_id', 'left')
		->where([
			['wallet_transactions.user_id', '=', $user->id],
			['wallet_transactions.created_at', '>', $request->get('start')],
			['wallet_transactions.created_at', '<', $request->get('end')],
			['wallet_transactions.pending', '=', 0],
			['wallet_transactions.failed', '=', 0]
		])
		->orderBy('wallet_transactions.created_at')
		->select([
			'wallet_transactions.id as wallet_transaction_id',
			'wallet_transactions.type as wallet_transaction_type',
			'wallet_transactions.amount as wallet_transaction_amount',
			'wallet_transactions.balance as wallet_transaction_balance',
			'wallet_transactions.processing_fee as wallet_transaction_processing_fee',
			'wallet_transactions.created_at as wallet_transaction_created',
			'payment_methods.type as payment_method_type',
			'payment_methods.last_four as payment_method_last_four',
			'labels.id as label_id',
			'labels.tracking as label_tracking',
			'rates.service as rate_service',
			'shipments.weight as shipment_weight',
			'packages.type as package_type',
			'packages.length as package_length',
			'packages.width as package_width',
			'packages.height as package_height',
			'from_addresses.postal as from_address_postal',
			'to_addresses.postal as to_address_postal',
			'to_addresses.country as to_address_country'
		])
		->get();

		$map = [
			'Time (UTC)' => 'wallet_transaction_created',
			'ID' => 'wallet_transaction_id',
			'Type' => 'wallet_transaction_type',
			'Amount' => 'wallet_transaction_amount',
			'Balance' => 'wallet_transaction_balance',
			'Processing Fee' => 'wallet_transaction_processing_fee',
			'Payment Type' => 'payment_method_type',
			'Payment Last 4' => 'payment_method_last_four',
			'Label ID' => 'label_id',
			'Label Tracking' => 'label_tracking',
			'Rate Service' => 'rate_service',
			'Shipment Weight (Oz)' => 'shipment_weight',
			'Package Type' => 'package_type',
			'Package Length (In)' => 'package_length',
			'Package Width (In)' => 'package_width',
			'Package Height (In)' => 'package_height',
			'From Postal' => 'from_address_postal',
			'To Postal' => 'to_address_postal',
			'To Country' => 'to_address_country'
		];


		$datas = [];

		$headers = [];
		foreach ($map as $key => $value) {
			$headers[] = $key;
		}
		$datas[] = $headers;

		foreach($wallet_transactions as $wallet_transaction) {
			$row = [];
			foreach ($map as $key => $value) {
				$row[] = (string) $wallet_transaction->{$value};
			}
			$datas[] = $row;
		}


		// create temp filename
		$filename = tempnam('/tmp', 'export');

		// open csv
        $handle = fopen($filename, 'w');

		// write to csv
        foreach ($datas as $data) {            
            fputcsv($handle, $data);
        }

		// close file
    	fclose($handle);

		// return file
        return response()->download($filename, 'Time Clock Combined.csv');
	}

	/**purpose
	 *   get addresses associated with scan 
	 * args
	 *   (none)
	 * returns
	 *   models
	 */
	public function getPickupAddresses(Request $request) {
		
		// get response 
		$response = new Response;

		// get available addresses
		$addresses = Mysql\Pickup::getAvailableAddresses(ApiAuth::user());
		if (!isset($addresses)) dd($addresses);

		$response->set('models', $addresses);

		return $response->jsonSuccess();
	}

	/**purpose
	 *   get pickup availability from date selected
	 * args
	 *   from_address_id (required)
	 *   date (required)
	 * returns 
	 *   mdoel
	 */
	public function getPickupAvailability(Request $request) {

        // create response
        $response = new Response;

        // check api fields
        if (!$response->hasRequired($request, ['from_address_id'])) return $response->jsonFailure('Missing required fields');

        // get from address
        $from_address = Mysql\Address::find($request->get('from_address_id'));
        if (!isset($from_address)) return $response->jsonFailure('Invalid from address');

        // validate pickup
        $pickup_availability_validator = new Libraries\PickupAvailability\PickupAvailabilityValidator;
        $availability_response = $pickup_availability_validator->validatePickupAvailability(
            $from_address,
            $request->has('date') ? strtotime($request->get('date')) : time()
        );
        if ($availability_response->isFailure()) return $availability_response->json();

        // set response
        $response->set('model', $availability_response->get('availability'));

        // return success
        return $response->jsonSuccess();
	}

	
    /**purpose
     *   schedule a pickup
     * args
     *   from_address_id (required)
     *   label_ids (1 of label_ids or scan_form_ids is required)
     *   scan_form_ids (1 of label_ids or scan_form_ids is required)
     *   package_location (required) (enum) (FRONT_DOOR, BACK_DOOR, SIDE_DOOR, KNOCK_ON_DOOR, MAIL_ROOM, OFFICE, RECEPTION, IN_MAILBOX, OTHER)
     *   special_instructions (optional)
     * returns
     *   pickup
     */
    public function doPickupSchedule(Request $request) {

		// initialize response
		$response = new Response;
        
		// schedule pickup
        $model_response = Mysql\Pickup::create((object) $request->all(), ApiAuth::user());

		// return pickup response
        return $model_response->json();
    }

    /**purpose
     *   get return label
     * args
     *   label_id (required)
     * returns
     *   model
     */
    public function doLabelReturn(Request $request) {
        $model_response = Mysql\ReturnLabel::create((object) $request->all(), ApiAuth::user());
        return $model_response->json();
    }

	/**purpose
	 *   set headers of a callback
	 * args
	 *   api_callback_id (required)
	 *   headers (array of key values)
	 * returns
	 *   (none)
	 */
	public function doApiCallbackHeadersSet(Request $request) {

		// create response
		$response = new Response;

		// get the callback
		$api_callback = Mysql\ApiCallback::find($request->get('api_callback_id'));
		if (!isset($api_callback)) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');

		// check user id
		if ($api_callback->user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');

		// get headers from call
		$headers = $request->get('headers');
		if (!is_array($headers)) return $response->jsonFailure('Invalid headers', 'INVALID_ARGS');

		// loop through headers and make sure they are strings
		foreach ($headers as $key => $value) {
			if (gettype($key) != gettype('STRING')) return $response->jsonFailure('Invalid header key', 'INVALID_ARGS');
			if (gettype($value) != gettype('STRING')) return $response->jsonFailure('Invalid header value', 'INVALID_ARGS');
		}


		// create headers object
		$api_callback_headers = Dynamo\CallbackHeaders::findOrCreate($api_callback->id);
		$api_callback_headers['headers'] = $headers;

		// save to dynamo
		$api_callback_headers->updateItem();


		// return success response
		return $response->jsonSuccess();
	}
	
	/**purpose
	 *   create a test callback
	 * args
	 *   api_callback_id (required)
	 * returns
	 *   result
	 */
	public function doApiCallbackTest(Request $request) {
		
		// create response
		$response = new Response;

		// get the callback
		$api_callback = Mysql\ApiCallback::find($request->get('api_callback_id'));
		if (!isset($api_callback)) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');

		// check user id
		if ($api_callback->user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');
		
		// check to make sure that the env is sandbox 
		if (env('APP_ENV') != 'sandbox') return $response->jsonFailure('Can only test api callbacks in sandbox mode', 'NOT_COMPLIANT');

		// call api callback for user
		return $api_callback->test()->json();
	}
	
	/**purpose
	 *   get api callback headers
	 * args
	 *   api_callback_id (required)
	 * returns
	 *   model
	 */
	public function getApiCallbackHeaders(Request $request) {
		
		// create response
		$response = new Response;

		// get the callback
		$api_callback = Mysql\ApiCallback::find($request->get('api_callback_id'));
		if (!isset($api_callback)) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');

		// check user id
		if ($api_callback->user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid api callback id', 'INVALID_ARGS');

		// get callback headers from dynamo
		$api_callback_headers = Dynamo\CallbackHeaders::findOrCreate($api_callback->id);

		// set repsonse
		$response->set('model', $api_callback_headers);

		// return success response
		return $response->jsonSuccess();
	}


	/**purpose
	 *   add a sub user
	 * args
	 *   name
	 *   email
	 * return
	 *   user
	 */
	public function doSubUserAdd(Request $request) {

		// create response
		$response = new Response;

		// validate requests has all required arguments
		if (!$response->hasRequired($request, ['name', 'email'])) return $response->jsonFailure('Missing required fields');

		// validate various fields to make sure they are valid
		$validated_name = Validator::validateText($request->get('name'), ['trim' => true]);
		if (!isset($validated_name)) return $response->jsonFailure('Invalid name', 'INVALID_ARGS');

		$validated_email = Validator::validateEmail($request->get('email', ''));
		if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');

		// check to see if user exists with email already
		if (Mysql\User::where('email', '=', $validated_email)->count() > 0) return $response->jsonFailure('User already exists with email in system', 'DUPLICATE_USER');

		$parent_user = ApiAuth::user();
		if (isset($parent_user->parent_user_id)) return $response->jsonFailure('Cannot have sub users', 'NOT AUTHORIZED');

		// create user
		$user = new Mysql\User;

		// set credentials
		$user->parent_user_id = $parent_user->id;
		$user->name = $validated_name;
		$user->email = $validated_email;
		$user->first_time_login = 0;
		$user->status = Mysql\User::$STATUS_APPROVED;
		$user->phone = '';
		$user->company = '';
		
		// save the user
		$user->save();

		// send email 
		$user->sendSubUserCreated($parent_user);

		// set user in response
		$response->set('model', $user);

		// return response
		return $response->jsonSuccess();
	}

	/**purpose
	 *   get active sub users
	 * args
	 *   (none)
	 * returns
	 *   users 
	 */
	public function getSubUserSearch(Request $request) {
		// create response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['take', 'page'])) return $response->jsonFailure('Missing required fields');

		// get user from api auth
		$user = ApiAuth::user();

        // get take and page
        $take = (int) min($request->get('take'), 1000);
        $page = (int) $request->get('page');

		// validate page
		if ($page <= 0) return $response->jsonFailure('Invalid page');

        // instantiate the query class. 
        $models_query = Mysql\User::where('parent_user_id', '=', $user->id)->where('active', '=', 1);

        // generate meta information if include_meta is true
        if (Validator::validateBoolean($request->get('include_meta',  false)))
        {
        	$count = $models_query->count();
	        $response->set('total_count', $count);
	        $response->set('page_count', ceil($count / $take));
        	$response->set('take', $take);
        	$response->set('page', $page);
        }
		
        // get models and set them in response
        $models = $models_query->take($take)->offset(($page - 1) * $take)->get();
        $response->set('models', $models);

		// return successful response
		return $response->jsonSuccess();

	}

	/**purpose
	 *   delete sub user 
	 * args
	 *   id (required)
	 * returns	
	 * 	 (none)
	 */
	public function doSubUserDelete(Request $request) {
		
		// create response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');
		
		// get user
		$user = Mysql\User::find($request->get('id'));

		// check to make sure it is a valid user id
		if (!isset($user)) return $response->jsonFailure('Invalid user id');

		// check to make sure user really is a sub user
		if ($user->parent_user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid user id');

		// delete user
		$user->active = 0;
		$user->email = $user->email . '-deleted';
		$user->save();

		// return successful response
		return $response->jsonSuccess();

	}

	/**purpose
	 *   get wallet balance
	 * args
	 *   (none)
	 * returns
	 *   balance
	 */
	public function getWalletBalance(Request $request) {

		// create response
		$response = new Response;

		// get wallet balance
		$wallet_balance = ApiAuth::user()->getWalletBalance();

		// set balance in response
		$response->set('balance', $wallet_balance);

		// return response
		return $response->jsonSuccess();
	}

	
	/**purpose
	 *   export wallet transactions
	 * args
	 *   start (required)
	 *   end (required)
	 * returns 
	 *   export.csv
	 */
	public function getLabelCorrectionExport(Request $request) {

		// create response 
		$response = new Response;

		// check required
		if (!$response->hasRequired($request, ['start', 'end'])) return $response->jsonFailure('Missing required fields', 'MISSING_FIELDS');

		// get logged in user
		$user = ApiAuth::user();
		
		// get all wallet transactions
		$wallet_transactions = Mysql\LabelCorrection::join('labels', 'labels.id', '=', 'label_corrections.label_id', 'left')
		->join('shipments', 'shipments.id', '=', 'labels.shipment_id', 'left')
		->join('packages', 'packages.id', '=', 'shipments.package_id', 'left')
		->where([
			['label_corrections.user_id', '=', $user->id],
			['label_corrections.created_at', '>', $request->get('start')],
			['label_corrections.created_at', '<', $request->get('end')]
		])
		->orderBy('label_corrections.created_at')
		->select([
			'label_corrections.id as wallet_transaction_id',
			'label_corrections.external_user_id as wallet_transaction_external_user_id',
			'label_corrections.amount as label_correction_amount',
			'label_corrections.service as label_correction_service',
			'label_corrections.weight as label_correction_weight',
			'label_corrections.width as label_correction_width',
			'label_corrections.length as label_correction_length',
			'label_corrections.height as label_correction_height',
			'label_corrections.created_at as label_correction_created',
			'labels.id as label_id',
			'labels.service as label_service',
			'labels.weight as label_weight',
			'packages.width as package_width',
			'packages.length as package_length',
			'packages.height as package_height'
		])
		->get();

		$map = [
			'Time (UTC)' => 'label_correction_created',
			'ID' => 'wallet_transaction_id',
			'External User' => 'wallet_transaction_external_user_id',
			'Amount' => 'label_correction_amount',
			'Corrected Service' => 'label_correction_service',
			'Corrected Weight' => 'label_correction_weight',
			'Corrected Width' => 'label_correction_width',
			'Corrected Length' => 'label_correction_length',
			'Corrected Height' => 'label_correction_height',
			'Entered Service' => 'label_service',
			'Entered Weight' => 'label_weight',
			'Entered Width' => 'package_width',
			'Entered Length' => 'package_length',
			'Entered Height' => 'package_height',
			'Label ID' => 'label_id'
		];


		$datas = [];

		$headers = [];
		foreach ($map as $key => $value) {
			$headers[] = $key;
		}
		$datas[] = $headers;

		foreach($wallet_transactions as $wallet_transaction) {
			$row = [];
			foreach ($map as $key => $value) {
				$row[] = (string) $wallet_transaction->{$value};
			}
			$datas[] = $row;
		}


		// create temp filename
		$filename = tempnam('/tmp', 'export');

		// open csv
        $handle = fopen($filename, 'w');

		// write to csv
        foreach ($datas as $data) {            
            fputcsv($handle, $data);
        }

		// close file
    	fclose($handle);

		// return file
        return response()->download($filename, 'Label Corrections.csv');
	}
}