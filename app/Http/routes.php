<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes...
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

// Registration routes...
Route::get('auth/register', 'Auth\AuthController@getRegister');
Route::post('auth/register', 'Auth\AuthController@postRegister');

Route::resource('profile', 'ProfileController', ['only' => ['index', 'store', 'show', 'update', 'destroy']]);

// Web payment
Route::get('payment', 'WebController@preparePayment');
Route::post('payment/process', 'WebController@processPayment');
Route::any('payment/final', 'WebController@finalPayment');
Route::post('payment/notify', 'WebController@notifyPayment');

// Backend server for mobile
Route::group(['namespace' => 'api', 'prefix' => 'api'], function() {

    // version 1
    Route::group(['namespace' => 'v1', 'prefix' => 'v1', 'middleware' => 'api:'. env('API_KEY_v1', '')], function() {

        Route::post('register', 'ApiController@register');
        Route::post('login', 'ApiController@login');

        // need authentication
        Route::group(['middleware' => 'api.auth'], function() {

            Route::post('logout', 'ApiController@logout');                                  // log out
            Route::get('account', 'ApiController@getAccount');                              // get account data
            Route::post('account', 'ApiController@updateAccount');                          // update account data

            Route::get('cities', 'ApiController@getCities');                                // get cities
            Route::get('city/{id}', 'ApiController@getCity');                               // get a city
            Route::get('hotel/{id}', 'ApiController@getHotel');                             // get a hotel

            Route::post('payment/methods', 'ApiController@postPaymentMethods');             // register a payment method
            Route::get('payment/methods', 'ApiController@getPaymentMethods');               // get payment methods of user
            Route::delete('payment/methods/{id}', 'ApiController@deletePaymentMethods');    // delete a payment method of user

            Route::post('payment/checkout', 'ApiController@postPaymentCheckout');           // payment checkout for WBE

            Route::post('bookings/info', 'ApiController@postBookingInfo');                  // record additional hotel data(from_date, to_date) from reference
            Route::get('bookings', 'ApiController@getBookings');                            // get booking history (hotel id, amount, from_date, to_date)
        });
    });
});

// Backend for WBE
Route::post('api/v1/payment/init', 'api\v1\ApiController@postPaymentInit');                   // payment init for WBE
Route::post('api/v1/payment/validate', 'api\v1\ApiController@postPaymentValidate');           // payment validate for WBE

// Backend for PayGate
Route::post('api/v1/payment/final', 'api\v1\ApiController@postPaymentFinal');
Route::post('api/v1/payment/notify', 'api\v1\ApiController@postPaymentNotify');

Route::post('api/v1/test', 'api\v1\ApiController@test');
Route::post('api/v1/test2', 'api\v1\ApiController@test2');
