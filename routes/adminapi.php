<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/**
 * Closed Apis by Access Token
 */

// generics
Route::get('search', 'AdminController@getSearch');

// user 
Route::post('user/approve', 'AdminController@doUserApprove');
Route::post('user/set', 'AdminController@doUserSet');
Route::get('user/tokens', 'AdminController@getUserTokens');
Route::get('user', 'AdminController@getUser');

// rate discounts
Route::get('rate/discounts', 'AdminController@getRateDiscounts');
Route::post('rate/discounts/set', 'AdminController@doRateDiscountsSet');

// wallet transactions
Route::post('wallet-transaction/approve', 'AdminController@doWalletTransactionApprove');
Route::get('wallet-transaction/totals', 'AdminController@getWalletTransactionTotals');




