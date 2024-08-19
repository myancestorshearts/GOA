<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|

/**
 * Open Apis
 */

// authentication
Route::post('login', 'ApiController@doLogin');
Route::post('register', 'ApiController@doRegister');
Route::post('verify/email', 'ApiController@doVerifyEmail');
Route::post('password/request', 'ApiController@doPasswordRequest');
Route::post('password/set', 'ApiController@doPasswordSet');

// oauth 
Route::get('oauth/connect', 'ApiController@doOauthConnect');
Route::get('oauth/install', 'ApiController@doOauthConnect');

// integration install
Route::get('integration/install/{store}', 'ApiController@getIntegrationInstall');

/**
 * Closed Apis by Refresh Token
 */

// token refresh
Route::post('token/refresh', 'ApiController@doTokenRefresh');

/**
 * Closed Apis by Access Token
 */

// generic search
Route::get('search', 'ApiController@getSearch');
Route::post('deactivate', 'ApiController@doDeactivate');
Route::post('set', 'ApiController@doSet');

// wallet transactions
Route::post('wallet/refill', 'ApiController@doWalletRefill');
Route::get('wallet/transaction/export', 'ApiController@getWalletTransactionExport');

// payment method
Route::post('payment/method/add', 'ApiController@doPaymentMethodAdd');
Route::post('payment/method/delete', 'ApiController@doPaymentMethodDelete');
Route::get('payment/method', 'ApiController@getPaymentMethod');
Route::get('payment/methods', 'ApiController@getPaymentMethods');
Route::post('payment/method/set', 'ApiController@doPaymentMethodSet');

// user
Route::post('user', 'ApiController@getUser');
Route::post('user/set', 'ApiController@doUserSet');
Route::post('user/password/set', 'ApiController@doUserPasswordSet');
Route::get('user/preferences', 'ApiController@getUserPreferences');
Route::post('user/preference/set', 'ApiController@doUserPreferenceSet');
Route::post('user/first/time/login', 'ApiController@doUserFirstTimeLogin');
Route::get('user/wallet/balance', 'ApiController@getWalletBalance');

// api key
Route::post('key/add', 'ApiController@doApiKeyAdd');
Route::post('key/delete', 'ApiController@doApiKeyDelete');

// order group
Route::post('order/group/add', 'ApiController@doOrderGroupAdd');
Route::post('order/group/delete', 'ApiController@doOrderGroupDelete');

// package 
Route::post('package/add', 'ApiController@doPackageAdd');

// address
Route::post('address/add', 'ApiController@doAddressAdd');

// rate
Route::post('shipment/rate', 'ApiController@doShipmentRate');
Route::post('shipment/rate/mass', 'ApiController@doShipmentRateMass');

// label
Route::post('label/purchase', 'ApiController@doLabelPurchase');
Route::get('label/image/url', 'ApiController@getLabelImageUrl');
Route::get('label/packingslip/image/url', 'ApiController@getLabelPackingSlipImageUrl');
Route::post('label/refund', 'ApiController@doLabelRefund');
Route::post('label/return', 'ApiController@doLabelReturn');

// referral
Route::post('referral/invite', 'ApiController@doReferralInvite');

// integrations
Route::post('integration/connect', 'ApiController@doIntegrationConnect');
Route::get('integration/download', 'ApiController@doIntegrationDownload');
Route::post('integration/purchase/{integration_id}', 'ApiController@doIntegrationPurchase');
Route::post('integration/order/sync', 'ApiController@doIntegrationOrderSync');
Route::post('integration/sync/all', 'ApiController@doIntegrationSyncAll');

// scan forms
Route::get('scan/form/options', 'ApiController@getScanFormOptions');
Route::post('scan/form/add', 'ApiController@doScanFormAdd');

// pickup
Route::get('pickup/addresses', 'ApiController@getPickupAddresses');
Route::get('pickup/availability', 'ApiController@getPickupAvailability');
Route::post('pickup/schedule', 'ApiController@doPickupSchedule');

// api callback
Route::post('apicallback/add', 'ApiController@doApiCallbackAdd');
Route::post('apicallback/delete', 'ApiController@doApiCallbackDelete');
Route::post('apicallback/headers/set', 'ApiController@doApiCallbackHeadersSet');
Route::get('apicallback/headers', 'ApiController@getApiCallbackHeaders');
Route::post('apicallback/test', 'ApiController@doApiCallbackTest');

// sub user
Route::post('sub/user/add', 'ApiController@doSubUserAdd');
Route::get('sub/user/search', 'ApiController@getSubUserSearch');
Route::post('sub/user/delete', 'ApiController@doSubUserDelete');

// label correction
Route::get('label/correction/export', 'ApiController@getLabelCorrectionExport');
