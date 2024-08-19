
import WebClient from '../webclient';

class User {

    /**purpose
     *   get verified user
     * args
     *   (none)
     * returns
     *   user
     */
    static get(parameters, successCallback, failureCallback) {
        WebClient.basicPost(parameters, '/adminapi/user', successCallback, failureCallback);
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
        WebClient.basicPost(parameters, '/adminapi/user/set', successCallback, failureCallback);
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
        parameters.class = 'user';
        WebClient.basicGet(parameters, '/adminapi/search', successCallback, failureCallback);
    }


    /**purpose
     *   approve a user
     * args
     *   id (required)
     * returns
     *   (none)
     */
    static approve(parameters, successCallback, failureCallback) {
        WebClient.basicPost(parameters, '/adminapi/user/approve', successCallback, failureCallback);
    }


    /**purpose
     *   get a users tokens so that admin can login as user
     * args
     *   id (required)
     * returns 
     *   tokens
     */
    static tokens(parameters, successCallback, failureCallback) {
        WebClient.basicGet(parameters, '/adminapi/user/tokens', successCallback, failureCallback);
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
        WebClient.basicGet(parameters, '/adminapi/search', successCallback, failureCallback);
    }



    /**purpose
     *   approve a wallet transaction
     * args
     *   id (required)
     * returns
     *   (none)
     */
    static approve(parameters, successCallback, failureCallback) {
        WebClient.basicPost(parameters, '/adminapi/wallet-transaction/approve', successCallback, failureCallback);
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
    static totals(parameters, successCallback, failureCallback) {
        WebClient.basicGet(parameters, '/adminapi/wallet-transaction/totals', successCallback, failureCallback);
    }
}


class RateDiscounts {

    /**purpose
     *   get rate discounts
     * args
     *   id (required) (id of user)
     * returns
     *   rates
     */
    static get(parameters, successCallback, failureCallback) {
        WebClient.basicGet(parameters, '/adminapi/rate/discounts', successCallback, failureCallback);
    }

    /**purpose
     *   set rate discounts
     * args
     *   id (required) (id of user)
     *   rates (required) 
     * returns
     *   (none)
     */
    static set(parameters, successCallback, failureCallback) {
        WebClient.basicPost(parameters, '/adminapi/rate/discounts/set', successCallback, failureCallback);
    }

}


export default class Admin {
    static User = User;
    static WalletTransaction = WalletTransaction;
    static RateDiscounts = RateDiscounts;
}