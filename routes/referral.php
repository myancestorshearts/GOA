<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/**
 * Closed Apis by Access Token
 */

// generics
Route::get('labels/export', 'ReferralController@getLabelsExport');
Route::get('labels/latest/export', 'ReferralController@getLatestLabelsExport');
Route::get('totals/export', 'ReferralController@getTotalsExport');




