<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Response;
use Illuminate\Http\Request;

use App\Common\Validator;
use App\Common\Functions;
use App\Models\Mysql;
use App\Models\Dynamo;

class AdminController extends Controller {

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

		// get class from class key
		$class = Mysql\Base::getClassFromClassKey($request->get('class'));
		if (!isset($class)) return $response->jsonFailure('Invalid class key');

        // get take and page
        $take = (int) min($request->get('take'), 1000);
        $page = (int) $request->get('page');

		// validate page
		if ($page <= 0) return $response->jsonFailure('Invalid page');

        // instantiate the query class. 
        $models_query = $class::whereRaw('1 = 1');

        // apply the search
        $apply_search_result = Mysql\Base::applyFilters($request, $class, $models_query, true);
        if ($apply_search_result !== true) return $response->jsonFailure($apply_search_result);

        // generate meta information if include_meta is true
        if (Validator::validateBoolean($request->get('include_meta',  false)))
        {
        	$count = $models_query->count();
	        $response->set('total_count', $count);
	        $response->set('page_count', ceil($count / $take));
        	$response->set('take', $take);
        	$response->set('page', $page);
        }
		
		// order by arguments 
        if ($request->has('order_by')) $models_query->orderByRaw($request->get('order_by'));

        // get models and set them in response
        $models = $class::getModels($models_query->take($take)->offset(($page - 1) * $take)->get(), $request);
        $response->set('models', $models);

		// return successful repsonse
		return $response->jsonSuccess();
	}

    /**purpose
     *   Set a user
     * args
     *   id (required)
     *   email (optional) 
     *   name (optional)
     *   company (optional)
     *   is_verified (optional)
     *   phone (optional)
     *   referral_program (optional) (true/false)
     *   referral_program_type (optional) (sendFirstLabel)
     *   admin (optional)
     *   usps_webtools_evs (optional)
     *   usps_webtools_returns (optional)
     * returns
     *   user
     */
    public function doUserSet(Request $request) {
    
        // create response
        $response = new Response;

        // validate request fields
        if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');

        // get user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');

        // email
        $validated_email = Validator::validateEmail($request->get('email', $user->email));
        if (!isset($validated_email)) return $response->jsonFailure('Invalid email', 'INVALID_ARGS');
        $user->email = $validated_email;

        // first name
        $validated_name = Validator::validateText($request->get('name', $user->name), ['trim' => true]);
        if (!isset($validated_name)) return $response->jsonFailure('Invalid name', 'INVALID_ARGS');
        $user->name = $validated_name;
        
        // company name
        $validated_company = Validator::validateText($request->get('company', $user->company), ['trim' => true, 'clearable' => true]);
        if (!isset($validated_company)) return $response->jsonFailure('Invalid company', 'INVALID_ARGS');
        $user->company = $validated_company;
        
        // phone
        $validated_phone = Validator::validatePhone($request->get('phone', $user->phone), ['clearable' => true]);
        if (!isset($validated_phone)) return $response->jsonFailure('Invalid phone', 'INVALID_ARGS');
        $user->phone = $validated_phone;

        // is verified
        $validated_verified = Validator::validateBoolean($request->get('verified', $user->verified));
        if (!isset($validated_verified)) return $response->jsonFailure('Invalid is verified', 'INVALID_ARGS');
        $user->verified = $validated_verified;

        // ach auto
        $validated_ach_auto = Validator::validateBoolean($request->get('ach_auto', $user->ach_auto));
        if (!isset($validated_ach_auto)) return $response->jsonFailure('Invalid ach auto', 'INVALID_ARGS');
        $user->ach_auto = $validated_ach_auto;
        
        // referral program
        $validated_referral_program = Validator::validateBoolean($request->get('referral_program', $user->referral_program));
        if (!isset($validated_referral_program)) return $response->jsonFailure('Invalid referral program', 'INVALID_ARGS');
        $user->referral_program = $validated_referral_program;

        // referral program type
        $validated_referral_program_type = Validator::validateEnum($request->get('referral_program_type', $user->referral_program_type), ['enums' => [
            Mysql\User::$REFERRAL_PROGRAM_SEND_FIRST_LABEL,
            Mysql\User::$REFERRAL_PROGRAM_COMMISSION
        ]]);
        if (!isset($validated_referral_program_type)) return $response->jsonFailure('Invalid referral program type', 'INVALID_ARGS');
        $user->referral_program_type = $validated_referral_program_type;
        
        // admin
        $validated_admin = Validator::validateBoolean($request->get('admin', $user->admin));
        if (!isset($validated_admin)) return $response->jsonFailure('Invalid admin', 'INVALID_ARGS');
        $user->admin = $validated_admin;

        // usps webtools evs
        $validated_usps_webtools_evs = Validator::validateText($request->get('usps_webtools_evs', $user->usps_webtools_evs), ['trim' => true, 'clearable' => true]);
        if (!isset($validated_usps_webtools_evs)) return $response->jsonFailure('Invalid usps_webtools_evs', 'INVALID_ARGS');
        $user->usps_webtools_evs = $validated_usps_webtools_evs;

        // usps webtools returns
        $validated_usps_webtools_returns = Validator::validateText($request->get('usps_webtools_returns', $user->usps_webtools_returns), ['trim' => true, 'clearable' => true]);
        if (!isset($validated_usps_webtools_returns)) return $response->jsonFailure('Invalid usps_webtools_returns', 'INVALID_ARGS');
        $user->usps_webtools_returns = $validated_usps_webtools_returns;

        // save model
        $user->save();

        // set response
        $response->set('user', $user);
        
        // return successful response
        return $response->jsonSuccess();
    }

    /**purpose
     *   get user 
     * args
     *   id (required)
     * returns
     *   user
     */
    public function getUser(Request $request) {

        // create response
        $response = new Response;

        // validate request fields
        if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');

        // get user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');

        // set user
        $response->set('user', $user);

        // response successful response
        return $response->jsonSuccess();
    }
	
    /**purpose
     *   approve a user
     * args
     *   id (required)
     * returns
     *   (none)
     */
    public function doUserApprove(Request $request) {
        
        // set initial response
        $response = new Response;

        // validate request fields
        if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');

        // get user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');

        // set status
        $user->approveUser();

        // return successful response
        return $response->jsonSuccess();
    }

    /**purpose
     *   approve a wallet transaction
     * args
     *   id (required)
     * returns
     *   (none)
     */
    public function doWalletTransactionApprove(Request $request) {

        // set initial response
        $response = new Response;

        // validate request fields
        if (!$response->hasRequired($request, ['id'])) return $response->jsonFailure('Missing required fields');

        // get wallet
        $wallet_transaction = Mysql\WalletTransaction::find($request->get('id'));
        if (!isset($wallet_transaction)) return $response->jsonFailure('Invalid wallet transaction id');

		return $wallet_transaction->approve()->json();
    }

    /**purpose
     *   get a users tokens so that admin can login as user
     * args
     *   id (required)
     * returns 
     *   tokens
     */
    public function getUserTokens(Request $request) {
        
        $response = new Response;

        // find user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');

		// create access token
		$tokens = $user->generateTokens();

		// set response
		$response->set('tokens', $tokens);

        // return success response
        return $response->jsonSuccess();
    }

    /**purpose
     *   get rate discounts
     * args
     *   id (required) (id of user)
     * returns
     *   rates
     */
    public function getRateDiscounts(Request $request) {

        // create response
        $response = new Response;

        // find user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');
        
        // get rates associated with user
        $model = Dynamo\RateDiscounts::findOrCreate($user->id);
        
        // set response
        $response->set('model', $model);

        // return successful response
        return $response->jsonSuccess();
    }
    
    /**purpose
     *   set rate discounts
     * args
     *   id (required) (id of user)
     *   rates (required) 
     * returns
     *   (none)
     */
    public function doRateDiscountsSet(Request $request) {

        // create response
        $response = new Response;

        // find user
        $user = Mysql\User::find($request->get('id'));
        if (!isset($user)) return $response->jsonFailure('Invalid user id');

        $rates = $request->get('rates');
        if (!Dynamo\RateDiscounts::validateRates($rates)) return $response->jsonFailure('Invalid rates');
        
        // get rates associated with user
        $model = Dynamo\RateDiscounts::findOrCreate($user->id);
        $model->rates = $rates;
        $model->updateItem();

        // return successful response
        return $response->jsonSuccess();
    }


    /**purpose
     *   get wallet transaction totals
     * args
     *   take
     *   page
     *   created_after
     *   created_before
     * returns
     *   models
     */
    public function getWalletTransactionTotals(Request $request) {
        
		// create response
		$response = new Response;

		// check to make sure we have fields
		if (!$response->hasRequired($request, ['take', 'page', 'created_after', 'created_before'])) return $response->jsonFailure('Missing required fields');

        // get take and page
        $take = (int) min($request->get('take'), 1000);
        $page = (int) $request->get('page');

		// validate page
		if ($page <= 0) return $response->jsonFailure('Invalid page');
        // instantiate the query class. 
        $models_query = Mysql\WalletTransaction::join('users', 'wallet_transactions.user_id', '=', 'users.id', 'LEFT')
            ->where('wallet_transactions.type', '=', 'Label')
            ->where('wallet_transactions.created_at', '>=', Functions::convertTimeToMysql(strtotime($request->get('created_after'))))
            ->where('wallet_transactions.created_at', '<=', Functions::convertTimeToMysql(strtotime($request->get('created_before'))))
            ->selectRaw('
                SUM(wallet_transactions.amount) * -1 as SPEND,
                SUM(wallet_transactions.profit) as PROFIT,
                SUM(wallet_transactions.profit_calculated) as PROFIT_CALCULATED,
                COUNT(*) as COUNT,
                wallet_transactions.user_id as USER_ID,
                users.email as USER_EMAIL,
                users.phone as USER_PHONE,
                users.name as USER_NAME,
                users.company as USER_COMPANY
            ')
            ->groupBy('wallet_transactions.user_id');



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



        // get all user id in response
        $user_ids = [];
        foreach($models as $model) {
            $model->setSubModel('SPEND_USPS_FIRST_CLASS', 0);
            $model->setSubModel('SPEND_USPS_PRIORITY_EXPRESS', 0);
            $model->setSubModel('SPEND_USPS_PARCEL_SELECT', 0);
            $model->setSubModel('SPEND_USPS_PRIORITY', 0);
            $model->setSubModel('SPEND_USPS_CUBIC', 0);
            $user_ids[] = $model->USER_ID;
        }

        $service_query = Mysql\WalletTransaction::join('labels', 'wallet_transactions.label_id', '=', 'labels.id', 'LEFT')
        ->where('wallet_transactions.type', '=', 'Label')
        ->where('wallet_transactions.created_at', '>=', Functions::convertTimeToMysql(strtotime($request->get('created_after'))))
        ->where('wallet_transactions.created_at', '<=', Functions::convertTimeToMysql(strtotime($request->get('created_before'))))
        ->selectRaw('
            SUM(wallet_transactions.amount) * -1 as SPEND,
            SUM(wallet_transactions.profit) as PROFIT,
            SUM(wallet_transactions.profit_calculated) as PROFIT_CALCULATED,
            COUNT(*) as COUNT,
            wallet_transactions.user_id as USER_ID,
            wallet_transactions.user_id as ID,
            labels.service as LABEL_SERVICE
        ')
        ->groupBy(['wallet_transactions.user_id', 'labels.service']);

        $service_models = $service_query->get();
        $service_map = [
            'Priority' => 'SPEND_USPS_PRIORITY',
            'Cubic' => 'SPEND_USPS_CUBIC',
            'First Class' => 'SPEND_USPS_FIRST_CLASS',
            'Priority Express' => 'SPEND_USPS_PRIORITY_EXPRESS',
            'Parcel Select' => 'SPEND_USPS_PARCEL_SELECT'
        ];

        foreach($models as $model) {
            foreach ($service_models as $service_model) {
                if ($model->USER_ID == $service_model->USER_ID) {
                    if (isset($service_map[$service_model->LABEL_SERVICE])) {
                        $model->setSubModel($service_map[$service_model->LABEL_SERVICE], $service_model->SPEND);
                    }
                }
            }
        }

        $response->set('models', $models);

		// return successful repsonse
		return $response->jsonSuccess();
    }
}