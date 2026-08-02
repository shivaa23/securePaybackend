<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'aeps'], function () use ($router) {
    $router->post('getAepsTxns', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@getAepsTxns');
    $router->post('initiateSignupAeps', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@initiateSignupAeps');
    $router->post('outletStatusIpay', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@outletStatusIpay');
    $router->post('merchantBiomatricKycIpay', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@merchantBiomatricKycIpay');
    $router->post('validateSignupAeps', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@validateSignupAeps');
    $router->post('getInitiateSchema', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@getInitiateSchema');
    $router->post('outletLogin', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@outletLogin');
    $router->post('outletLoginStatus', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@outletLoginStatus');
    $router->post('balanceEnquiry', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@balanceEnquiry');
    $router->post('cashWithdrawl', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@cashWithdrawl']);
    $router->post('getBanks', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@getBanks');
    $router->post('miniStatement', 'App\Modules\Aeps\Controllers\ImpsGuruAepsController@miniStatement');
    $router->post('onboardingAeps2', 'App\Modules\Aeps\Controllers\Aeps2Controller@onboardingAeps2');
    $router->post('onboardingekycFp', 'App\Modules\Aeps\Controllers\Aeps2Controller@onboardingekycFp');
    $router->post('aeps2Schema', 'App\Modules\Aeps\Controllers\Aeps2Controller@aeps2Schema');
    $router->post('validateOtpAeps2', 'App\Modules\Aeps\Controllers\Aeps2Controller@validateOtpAeps2');
    $router->post('merchantBiometricKyc', 'App\Modules\Aeps\Controllers\Aeps2Controller@merchantBiometricKyc');
    $router->post('balanceEnquiry2', 'App\Modules\Aeps\Controllers\Aeps2Controller@balanceEnquiry2');
    $router->post('cashWithdrawl2', 'App\Modules\Aeps\Controllers\Aeps2Controller@cashWithdrawl');
    $router->post('miniStatement2', 'App\Modules\Aeps\Controllers\Aeps2Controller@miniStatement');
    $router->post('getBanksFp', 'App\Modules\Aeps\Controllers\Aeps2Controller@getBanksFp');
    $router->post('getStatesFp', 'App\Modules\Aeps\Controllers\Aeps2Controller@getStatesFp');
    $router->post('twoFaAeps2', 'App\Modules\Aeps\Controllers\Aeps2Controller@twoFaAeps2');
    $router->get('threeWayReconFp', 'App\Modules\Aeps\Controllers\Aeps2Controller@threeWayReconFp');
    $router->post('addharPayFp', 'App\Modules\Aeps\Controllers\Aeps2Controller@addharPayFp');
    
});

