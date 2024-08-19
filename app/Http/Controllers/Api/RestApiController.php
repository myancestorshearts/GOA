<?php namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Response;

use App\Libraries;

use App\Models\Mysql;

use App\Common\Validator;
use App\Common\Functions;

use ApiAuth;


class RestApiController extends Controller {

    /* Unique Errors */
    /**purpose
     *   generate tokens
     * args
     *   (none)
     * returns
     *   tokens
     */
    public function doTokensGenerate(Request $request) {
        
        // create response 
        $response = new Response;

        // get user and api key
        $user = ApiAuth::user();
        $api_key = ApiAuth::apiKey();

        // generate tokens
        $tokens = $user->generateTokens($api_key);

        // set tokens in response
        $response->set('model', $tokens);

        // return response
        return $response->jsonSuccess();
    }

    /* Unique Errors */
    /**purpose
     *   refresh tokens
     * args
     *   (none)
     * returns
     *   tokens
     */
    public function doTokensRefresh(Request $request) {
        
        // create response 
        $response = new Response;

        // get user and api key
        $user = ApiAuth::user();
        $api_key_id = ApiAuth::apiKeyId();

        // get api key
        $api_key = Mysql\ApiKey::find($api_key_id);

        // generate tokens
        $tokens = $user->generateTokens($api_key);

        // set tokens in response
        $response->set('model', $tokens);

        // return response
        return $response->jsonSuccess();
    }


    /* Unique Errors */
    // purpose
    //   create and verify address
    // args
    //   name (required)
    //   company (optional)
    //   email (optional)
    //   phone (optional) (required if from is set to true)
    //   street_1 (required)
    //   street_2 (optional)
    //   city (required)
    //   state (required)
    //   country (required)
    //   postal (required)
    //   from (optional) (default is false)
    // returns
    //   model
    public function doAddressCreate(Request $request) {
        $model_response = Mysql\Address::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }


    /* Unique Errors */
    // purpose
    //   save a parcel
    // args
    //   type (required) (Listed in Libraries/Parcels.php)
    //   length (required based on package)
    //   width (required based on package)
    //   height (required based on package)
    // returns 
    //   model
    public function doPackageCreate(Request $request) {
        $model_response = Mysql\Package::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }

    /* Unique Errors */
    // purpose
    //   create a shipment
    // args
    //   ref (optional) (default is '')
    //   from_address_id (required)
    //   to_address (required)
    //   parcel (required)
    //   external_user_id (required)
    //   ship_date (required)
    // returns
    //   model 
    public function doShipmentCreate(Request $request) {
        
        // create response
        $response = new Response;
        
        // required for usps
        if (!$request->has('external_user_id')) return $response->jsonFailure('Missing external user id', 'INVALID_PROPERTY', 'MISSING_EXTERNAL_USER_ID');
        if ($request->get('external_user_id') == null) return $response->jsonFailure('Missing external user id', 'INVALID_PROPERTY', 'INVALID_EXTERNAL_USER_ID');

        // get shipment response
        $model_response = Mysql\Shipment::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }

    
    /* Unique Errors */
    /**purpose
     *   rate shipment only that does not save 
     * args
     *   from_address_id (optional)
     *   from_postal (optional) (required if no from_address_id) 
     *   to_postal (required)
     *   package (required)
     *   services (optional) (SIGNATURE, ADULT_SIGNATURE)
     * returns
     *   shipment
     */
    public function doShipmentRateOnly(Request $request) {
        $model_response = Mysql\Shipment::createRateOnly((object) $request->all(), ApiAuth::user());
        return $model_response->json();
    }

    /* Unique Errors */
    // purpose
    //   purchase a label
    // args
    //   shipment_id (required)
    //   rate_id (required)
    // returns
    //   label 
    public function doLabelPurchase(Request $request) {
        $model_response = Mysql\Label::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }

    /* Unique Errors */
    // purpose
    //   refund a label
    // args
    //   label_id (required)
    // returns
    //   (none)
    public function doLabelRefund(Request $request) {

        // create response
        $response = new Response;
        if (!$response->hasRequired($request, ['label_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_REQUIRED_FIELDS', 'MISSING_LABEL_ID');

        // get label
        $label = Mysql\Label::find($request->get('label_id'));
        if (!isset($label)) return $response->jsonFailure('Invalid label', 'INVALID_LABEL_ID', 'INVALID_LABEL_ID');

        // return refund
        return $label->refund()->json();
    }

    /* Unique Errors */
    /**purpose
     *   set up a scan form
     * args
     *   from_address_id (required)
     *   ship_date (required) 
     *   external_user_id (required)
     *   label_ids (required) (string array of label ids)
     * returns
     *   scan_form
     */
    public function doScanFormCreate(Request $request) {

		// initialize response
		$response = new Response;
        
        // check external user id
        if (!$response->hasRequired($request, ['external_user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_REQUIRED_FIELDS', 'MISSING_EXTERNAL_USER_ID');

        $model_response = Mysql\ScanForm::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId(), $request->get('external_user_id'));
        return $model_response->json();
    }

    /* Unique Errors */
    /**purpose
     *   get pending from addresses that need to be added to a scan form
     * args
	 *   external_user_id (required)
     * returns
     *   models
     */
    public function getScanFormOptions(Request $request) {
       
		// initialize response
		$response = new Response;
        
        // check external user id
        if (!$response->hasRequired($request, ['external_user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_REQUIRED_FIELDS', 'MISSING_EXTERNAL_USER_ID');

		// set response
		$response->set('models', Mysql\ScanForm::getAvailableOptions(ApiAuth::user(), $request->get('external_user_id')));

		// return successful response
		return $response->jsonSuccess();
    }

    /* Unique Errors */ 
    /**purpose
     *   schedule a pickup
     * args
     *   external_user_id (required)
     *   from_address_id (required)
     *   label_ids (1 of label_ids or scan_form_ids is required)
     *   scan_form_ids (1 of label_ids or scan_form_ids is required)
     *   package_location (required) (enum) (FRONT_DOOR, BACK_DOOR, SIDE_DOOR, KNOCK_ON_DOOR, MAIL_ROOM, OFFICE, RECEPTION, IN_MAILBOX, OTHER)
     *   special_instructions (optional)
     *   date (required)
     * returns
     *   pickup
     */
    public function doPickupSchedule(Request $request) {

		// initialize response
		$response = new Response;
        
        // check external user id
        if (!$response->hasRequired($request, ['external_user_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_REQUIRED_FIELDS', 'MISSING_EXTERNAL_USER_ID');

        $model_response = Mysql\Pickup::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId(), $request->get('external_user_id'));
        return $model_response->json();
    }

    /* Unique Errors */ 
    /**purpose
     *   get pickup availability
     * args
     *   from_address_id (required)
     *   date (optional) (default is today)
     * returns
     *   availability
     */
    public function getPickupAvailability(Request $request) {

        // create response
        $response = new Response;

        // check api fields
        if (!$response->hasRequired($request, ['from_address_id'])) return $response->jsonFailure('Missing required fields', 'MISSING_REQUIRED_FIELDS', 'MISSING_FROM_ADDRESS_ID');

        // get from address
        $from_address = Mysql\Address::find($request->get('from_address_id'));
        if (!isset($from_address)) return $response->jsonFailure('Invalid from address', 'INVALID_FROM_ADDRESS', 'INVALID_FROM_ADDRESS_ID');

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

        // instantiate the query class. 
        $models_query = $class::where('user_id', '=', $user->id);

        // apply the search
        $apply_search_result = Mysql\Base::applyFilters($request, $class, $models_query);
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
	 *   get the label image
	 * args
	 *   label_id (required)
	 *   label_size (optional) (default is 4X6)
     *   file_type (optional) (default is JPG)
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
		if ($label->user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid label id');

		// check the size
        $size = $request->get('label_size', Mysql\Label::IMAGE_SIZE_4X6);
        if (!Mysql\Label::isValidLabelSize($size)) return $response->jsonFailure('Invalid label size');

        // check the file type
        $file_type = $request->get('file_type', Mysql\Label::FILE_TYPE_JPG);
        if (!Mysql\Label::isValidFileType($file_type)) return $response->jsonFailure('Invalid label file type');

		// get redirect url 
		$url = $label->getImageUrl($size, $file_type);
		$response->set('model', $url);
		
		// redirect
		return $response->jsonSuccess();
	}


    /* Unique Errors */ 
    /**purpose
     *   get return label
     * args
     *   label_id (required)
     * returns
     *   model
     */
    public function doLabelReturn(Request $request) {
        $model_response = Mysql\ReturnLabel::create((object) $request->all(), ApiAuth::user(), ApiAuth::apiKeyId());
        return $model_response->json();
    }

    /* Unique Errors */ 
    /**purpose
     *   cancel a pickup
     * args
     *   pickup_id (required)
     * returns
     *   result
     */
    public function doPickupCancel(Request $request) {
        
		// create response
		$response = new Response;

        // find pickup
        $pickup = Mysql\Pickup::find($request->get('pickup_id'));
        if (!isset($pickup)) return $response->jsonFailure('Invalid pickup id', 'INVALID_ARGS', 'INVALID_PICKUP_ID');
        if (ApiAuth::user()->id != $pickup->user_id) return $response->jsonFailure('Invalid pickup id', 'INVALID_ARGS', 'INVALID_PICKUP_ID');
    
        // cancel pickup
        $cancel_response = $pickup->cancel();
        if ($cancel_response->isFailure()) return $cancel_response->json();
        
        $response->set('model', $pickup);

        return $response->jsonSuccess();
    }

    /* Unique Errors */ 
    /**purpose
     *   get a label
     * args
     *   id (required)
     * returns
     *   model
     */
    public function getLabel(Request $request) {

        // create response 
        $response = new Response;

		// get label
		$label = Mysql\Label::find($request->get('id'));
		if (!isset($label)) return $response->jsonFailure('Invalid label id', 'INVALID_LABEL_ID', 'INVALID_LABEL_ID');

		// check label
		if ($label->user_id != ApiAuth::user()->id) return $response->jsonFailure('Invalid label id');

        // set label model
        $response->set('model', $label->getModel($request));

        // return successful response
        return $response->jsonSuccess();
    }


    /* Unique Errors */ 
    /**purpose
     *   validate an api callback instance came from goa
     * args
     *   id (required)
     * returns
     *   (none)
     */
    public function getApiCallbackInstanceValidate(Request $request) {

        // create response
        $response = new Response;

        // get api callback instance
        $api_callback_instance = Mysql\ApiCallbackInstance::find($request->get('id'));
        if (!isset($api_callback_instance)) return $response->jsonFailure('Not validated', 'INVALID_CALLBACK_INSTANCE', 'INVALID_CALLBACK_INSTANCE_ID');

        // check callback to make sure it belongs to user
        $api_callback = Mysql\ApiCallback::find($api_callback_instance->api_callback_id);
        if (!isset($api_callback)) return $response->jsonFailure('Not validated', 'INVALID_CALLBACK_INSTANCE', 'INVALID_CALLBACK_ID');

        // check to make sure user is correct user id
		if ($api_callback->user_id != ApiAuth::user()->id) return $response->jsonFailure('Not validated', 'INVALID_CALLBACK_INSTANCE', 'INVALID_CALLBACK_INSTANCE_ID');

        // return successful response
        return $response->jsonSuccess();
    }

	/**purpose
	 *   shipment rate mass
	 * args
	 *   to_addresses (required)
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

}   