
import WebClient from './webclient';
import Storage from './storage';

class Authenticate {

	/**purpose
	 *   login user and get tokens
	 * args
	 *   email (required)
	 *   password (required)
	 * returns
	 *   user
	 *   token 
	 */
	static login(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/login', (success) => {
			// set local storage to pull user
			Storage.set('goa-tokens', success.data.tokens);
			Storage.set('goa-user', success.data.user);

			// call success callback
			if (successCallback) successCallback(success);
		}, failureCallback);
	}

	/**purpose
	 *   logout
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	static logout() {
		Storage.remove('goa-tokens');
		Storage.remove('goa-loginasuser-tokens');
		Storage.remove('goa-user');
	}

	/**purpose
	 *   register a user
	 * args
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
	static register(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/register', successCallback, failureCallback);
	}

	/**purpose 
	 *   verify email
	 * args
	 *   key (required)
	 * returns
	 *   result
	 */
	static verifyEmail(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/verify/email', successCallback, failureCallback);
	}

	/**purpose
	 *   request a forgotton password
	 * args
	 *   email (required)
	 * returns
	 *   result
	 */
	static passwordRequest(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/password/request', successCallback, failureCallback);
	}

	/**purpose
	 *   set password
	 * args
	 *   key (required)
	 *   password (required)
	 * returns
	 *   (none)
	 */
	static passwordSet(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/password/set', successCallback, failureCallback);
	}
}

class User {

	/**purpose
	 *   get verified user
	 * args
	 *   (none)
	 * returns
	 *   user
	 */
	static get(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/user', successCallback, failureCallback);
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
	static set(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/user/set', successCallback, failureCallback);
	}

	/**purpose
	 *   allow logged in user to set password
	 * args
	 *   current_password (required)
	 *   new_password (required)
	 * returns
	 *   (none)
	 */
	static passwordSet(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/user/password/set', successCallback, failureCallback);
	}

	/**purpose
	 *   get user preferences
	 * args
	 *   (none)
	 * returns
	 *   preferences
	 */
	static preferences(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/user/preferences', successCallback, failureCallback);
	}

	/**purpose
	 *   set user preference
	 * args
	 *   key (key of preference)
	 *   value (value) 
	 * returns
	 *   (none)
	 */
	static setPreference(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/user/preference/set', successCallback, failureCallback);
	}

	/**purpose
	 *   update first time login
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	static firstTimeLogin(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/user/first/time/login', successCallback, failureCallback);
	}

	/**purpose
	 *   get wallet balance
	 * args
	 *   (none)
	 * returns
	 *   balance
	 */
	static walletBalance(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/user/wallet/balance', successCallback, failureCallback);
	}
}


class WalletTransaction {

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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'wallettransaction';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   export wallet transactions
	 * args
	 *   start (required)
	 *   end (required)
	 * returns 
	 *   export
	 */
	static export(parameters) {
		WebClient.basicDownload(parameters, '/api/wallet/transaction/export');
	}
}

class Wallet {

	/**purpose
	 *   refill wallet
	 * args
	 *   type (required)
	 *   amount (required)
	 *   processing_fee (required if type = cc)
	 * returns
	 *   wallet_transaction
	 */
	static refill(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/wallet/refill', successCallback, failureCallback);
	}
}

class PaymentMethod {

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
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/payment/method/add', successCallback, failureCallback);
	}

	/**purpose 
	 *   delete payment method
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	*/
	static delete(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/payment/method/delete', successCallback, failureCallback)
	}

	/**purpose
	 *   get payment method
	 * args
	 *   type (required)
	 * returns
	 *   payment_method
	 */
	static get(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/payment/method', successCallback, failureCallback)
	}

	/**purpose 
	 *   get payment methods
	 * args
	 *   (none)
	 * returns
	 *   payment_methods
	 */
	static all(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/payment/methods', successCallback, failureCallback)
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
	static set(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/payment/method/set', successCallback, failureCallback)
	}
}

class ApiKey {

	/**purpose
	 *   add an api key
	 * args
	 *   name (required)
	 * returns
	 *   api_key
	 */
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/key/add', successCallback, failureCallback);
	}

	/**purpose
	 *   delete an api key
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static delete(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/key/delete', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'apikey';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}
}

class OrderGroup {
	/**purpose
	 *   add an order
	 * args
	 *   name (required)
	 *   email (optional)
	 *   company (optional)
	 *   phone (optional)
	 *   address (optional)
	 *   order_products: array (
	 *     name (required)
	 *     sku (optional)
	 *     quantity (required)
	 *     length (optional)
	 *     width (optional)
	 *     height (optional)
	 *     weight (optional)
	 *   )
	 */
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/order/group/add', successCallback, failureCallback);
	}

	/**purpose 
	 *   delete an order group
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static delete(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/order/group/delete', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'ordergroup';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}
}

class Package {
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
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/package/add', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'package';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   deactivate a model
	 * args
	 *   class (required)
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static deactivate(parameters, successCallback, failureCallback) {
		parameters.class = 'package';
		WebClient.basicPost(parameters, '/api/deactivate', successCallback, failureCallback);
	}
}


class Address {

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
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/address/add', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'address';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   deactivate a model
	 * args
	 *   class (required)
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static deactivate(parameters, successCallback, failureCallback) {
		parameters.class = 'address';
		WebClient.basicPost(parameters, '/api/deactivate', successCallback, failureCallback);
	}

	/**purpose
	 *   set an address
	 * args
	 *   id (required)
	 *   default (optional)
	 * returns
	 *   (none)
	 */
	static set(parameters, successCallback, failureCallback) {
		parameters.class = 'address';
		WebClient.basicPost(parameters, '/api/set', successCallback, failureCallback);
	}
}

class Shipment {

	/**purpose
	 *   get rate
	 * args
	 *   order_group_id (required)
	 *   from_address_id (required) (from address)
	 *   package (required) 
	 *   weight (required)
	 * returns
	 *   shipment
	 */
	static rate(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/shipment/rate', successCallback, failureCallback);
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
	static rateMass(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/shipment/rate/mass', successCallback, failureCallback);
	}

}

class Label {

	/**purpse
	 *   purchase a label
	 * args
	 *   order_group_id (required)
	 *   shipment_id (required)
	 *   rate_id (required)
	 * returns
	 *   label
	 */
	static purchase(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/label/purchase', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'label';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   get the label image
	 * args
	 *   label_id (required)
	 *   size (optional) (default is 6x4)
	 * returns
	 *   url
	 */
	static imageUrl(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/label/image/url', successCallback, failureCallback);
	}

	/**purpose
	 *   get the label image
	 * args
	 *   label_id (required)
	 *   size (optional) (default is 6x4)
	 * returns
	 *   url
	 */
	static packingSlipImageUrl(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/label/packingslip/image/url', successCallback, failureCallback);
	}

	/**
	 * purpose
	 *   refund a label
	 * args
	 *   label_id (required)
	 * returns
	 *   (none)
	 */
	static refund(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/label/refund', successCallback, failureCallback);
	}

	/**purpose
	 *   get return label
	 * args
	 *   label_id (required)
	 * returns
	 *   model
	 */
	static return(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/label/return', successCallback, failureCallback);
	}
}

class Referral {

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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'referral';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   send a referral email to invite someone to use software
	 * args
	 *   name (required)
	 *   email (required)
	 * returns
	 *   (none)
	 */
	static invite(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/referral/invite', successCallback, failureCallback);
	}
}

class Integration {

	/**purpose
	 *   connect an integration
	 * args
	 *   type (required) (SHOPIFY)
	 * returns
	 *   integration
	 */
	static connect(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/integration/connect', successCallback, failureCallback);
	}

	/**purpose
	 *   download an integration file
	 * args
	 *   id (required)
	 * returns
	 *   file
	 */
	static download(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/integration/download', successCallback, failureCallback);
	}

	/**purpose
	 *   sync orders
	 * args
	 *   id
	 * returns
	 *   (none)
	 */
	static syncOrders(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/integration/order/sync', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'integration';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   sync all integrations
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	static syncAll(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/integration/sync/all', successCallback, failureCallback);
	}

}

class ScanForm {

	/**purpose
	 *   get pending scan form options
	 * args
	 *   (none)
	 * returns
	 *   models
	 */
	static options(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/scan/form/options', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'scanform';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}


	/**purpose
	 *   set up a scan form
	 * args
	 *   from_address_id (required)
	 *   label_ids (required) (string array of label ids)
	 * returns
	 *   model
	 */
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/scan/form/add', successCallback, failureCallback);
	}



}


class Pickup {

	/**purpose
	 *   get addresses associated with scan 
	 * args
	 *   (none)
	 * returns
	 *   models
	 */
	static addresses(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/pickup/addresses', successCallback, failureCallback);
	}

	/**purpose
	 *   get search
	 * args
	 *   class (required)
	 *   include_meta (optional) (includes "take, page, total_count, page_count" in respo	nse)
	 *   order_by (sorts results by this)
	 * returns
	 *   models
	 *   take
	 *   page
	 *   total_count
	 *   page_count
	 */
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'pickup';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   get pickup availability from date selected
	 * args
	 *   from_address_id (required)
	 *   date (required)
	 * returns 
	 *   mdoel
	 */
	static availability(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/pickup/availability', successCallback, failureCallback);
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
	static schedule(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/pickup/schedule', successCallback, failureCallback);
	}
}


class IntegrationFailedOrder {



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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'integrationfailedorder';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}


	/**purpose
	 *   deactivate a model
	 * args
	 *   class (required)
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static deactivate(parameters, successCallback, failureCallback) {
		parameters.class = 'integrationfailedorder';
		WebClient.basicPost(parameters, '/api/deactivate', successCallback, failureCallback);
	}
}


class ApiCallback {


	/** 
	 * purpose
	 *   add a callback
	 * args
	 *   type (required) (PRICE_ADJUSTMENT)
	 *   callback_url (required)
	 * returns
	 *   model
	 */
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/apicallback/add', successCallback, failureCallback);
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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'apicallback';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}

	/**purpose
	 *   delete an api callback
	 * args
	 *   id (required)
	 * returns
	 *   (none)
	 */
	static delete(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/apicallback/delete', successCallback, failureCallback);
	}

	/**purpose
	 *   set headers of a callback
	 * args
	 *   api_callback_id (required)
	 *   headers (array of key values)
	 * returns
	 *   (none)
	 */
	static headersSet(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/apicallback/headers/set', successCallback, failureCallback);
	}


	/**purpose
	 *   get api callback headers
	 * args
	 *   api_callback_id (required)
	 * returns
	 *   model
	 */
	static headers(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/apicallback/headers', successCallback, failureCallback);
	}

	/**purpose
	 *   test a callback
	 * args
	 *   api_callback_id (required)
	 *   headers (array of key values)
	 * returns
	 *   (none)
	 */
	static test(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/apicallback/test', successCallback, failureCallback);
	}

}

class SubUser {

	/**purpose
	 *   add a sub user
	 * args
	 *   name
	 *   email
	 * return
	 *   user
	 */
	static add(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/sub/user/add', successCallback, failureCallback);
	}


	/**purpose
	 *   get active sub users
	 * args
	 *   (none)
	 * returns
	 *   users 
	 */
	static search(parameters, successCallback, failureCallback) {
		WebClient.basicGet(parameters, '/api/sub/user/search', successCallback, failureCallback);
	}


	/**purpose
	 *   delete sub user 
	 * args
	 *   user_id (required)
	 * returns	
	 * 	 (none)
	 */
	static delete(parameters, successCallback, failureCallback) {
		WebClient.basicPost(parameters, '/api/sub/user/delete', successCallback, failureCallback);
	}
}

class LabelCorrection {

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
	static search(parameters, successCallback, failureCallback) {
		parameters.class = 'labelcorrection';
		WebClient.basicGet(parameters, '/api/search', successCallback, failureCallback);
	}


	/**purpose
	 *   export wallet transactions
	 * args
	 *   start (required)
	 *   end (required)
	 * returns 
	 *   export
	 */
	static export(parameters) {
		WebClient.basicDownload(parameters, '/api/label/correction/export');
	}

}

export default class Api {
	static Authenticate = Authenticate;
	static User = User;
	static Wallet = Wallet;
	static WalletTransaction = WalletTransaction;
	static PaymentMethod = PaymentMethod;
	static ApiKey = ApiKey;
	static OrderGroup = OrderGroup;
	static Package = Package;
	static Address = Address;
	static Shipment = Shipment;
	static Label = Label;
	static Referral = Referral;
	static Integration = Integration;
	static ScanForm = ScanForm;
	static IntegrationFailedOrder = IntegrationFailedOrder;
	static ApiCallback = ApiCallback;
	static Pickup = Pickup;
	static SubUser = SubUser;
	static LabelCorrection = LabelCorrection;
}