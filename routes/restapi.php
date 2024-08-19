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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

// Generic
Route::get('/search', 'RestApiController@getSearch');

// Tokens
Route::post('/tokens/generate', 'RestApiController@doTokensGenerate');
Route::post('/tokens/refresh', 'RestApiController@doTokensRefresh');

// Address
Route::post('/address/create', 'RestApiController@doAddressCreate');

// Package
Route::post('/package/create', 'RestApiController@doPackageCreate');

// Shipment
Route::post('/shipment/create', 'RestApiController@doShipmentCreate');
Route::post('/shipment/rate/only', 'RestApiController@doShipmentRateOnly');
Route::post('/shipment/rate/mass', 'RestApiController@doShipmentRateMass');

// Label
Route::post('/label/purchase', 'RestApiController@doLabelPurchase');
Route::post('/label/refund', 'RestApiController@doLabelRefund');
Route::get('/label/image/url', 'RestApiController@getLabelImageUrl');
Route::post('/label/return', 'RestApiController@doLabelReturn');
Route::get('/label', 'RestApiController@getLabel');

// Scan Form
Route::post('scanform/create', 'RestApiController@doScanFormCreate');
Route::get('scanform/options', 'RestApiController@getScanFormOptions');

// Pickup
Route::post('pickup/schedule', 'RestApiController@doPickupSchedule');
Route::get('pickup/availability', 'RestApiController@getPickupAvailability');
Route::post('pickup/cancel', 'RestApiController@doPickupCancel');

// Api Callback Instance
Route::get('apicallback/instance/validate', 'RestApiController@getApiCallbackInstanceValidate');

// Callback Handlers
Route::post('apicallback/handle/tracking', 'RestApiController@doApiCallbackHandleTracking');
Route::post('apicallback/handle/correction', 'RestApiController@doApiCallbackHandleCorrection');
Route::post('apicallback/handle/cancellation', 'RestApiController@doApiCallbackHandleCancellation');
Route::post('apicallback/handle/return', 'RestApiController@doApiCallbackHandleRefresh');

