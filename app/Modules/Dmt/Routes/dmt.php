<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// ------------------ DMT Routes ------------------
$router->group(['prefix' => 'dmt', 'middleware' => 'check.blacklist'], function () use ($router) {
    $router->post('getDmtTxns', 'App\Modules\Dmt\Controllers\DmtController@getDmtTxns');
    $router->post('getaccVerifyTxns', 'App\Modules\Dmt\Controllers\DmtController@getaccVerifyTxns');
});

// ------------------ DMT1 Routes ------------------
$router->group(['prefix' => 'dmt1', 'middleware' => 'check.blacklist'], function () use ($router) {
    $router->post('getBanks', 'App\Modules\Dmt\Controllers\ImpsGuruController@getBanks');
    $router->post('getBeneficiarySchema', 'App\Modules\Dmt\Controllers\ImpsGuruController@getBeneficiarySchema');
    $router->post('remitterStatus', 'App\Modules\Dmt\Controllers\ImpsGuruController@remitterStatus');
    $router->post('registerBeneficiary', 'App\Modules\Dmt\Controllers\ImpsGuruController@registerBeneficiary');
    $router->post('VerifyRegisterBeneficiary', 'App\Modules\Dmt\Controllers\ImpsGuruController@VerifyRegisterBeneficiary');
    $router->post('removeBeneficiary', 'App\Modules\Dmt\Controllers\ImpsGuruController@removeBeneficiary');
    $router->post('VerifyRemoveBeneficiary', 'App\Modules\Dmt\Controllers\ImpsGuruController@VerifyRemoveBeneficiary');
    $router->post('registerRemitter', 'App\Modules\Dmt\Controllers\ImpsGuruController@registerRemitter');
    $router->post('validateRemitter', 'App\Modules\Dmt\Controllers\ImpsGuruController@validateRemitter');
    $router->post('kycRemitter', 'App\Modules\Dmt\Controllers\ImpsGuruController@kycRemitter');
    // $router->post('dmtTxn', 'App\Modules\Dmt\Controllers\ImpsGuruController@dmtTxn');
    $router->post('dmtTxn', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Dmt\Controllers\ImpsGuruController@dmtTxn']);
    $router->post('transactionOtpDmt1', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Dmt\Controllers\ImpsGuruController@transactionOtpDmt1']);

    // $router->post('transactionOtpDmt1', 'App\Modules\Dmt\Controllers\ImpsGuruController@transactionOtpDmt1');
    $router->post('accountVerification', 'App\Modules\Dmt\Controllers\ImpsGuruController@accountVerification');
    $router->post('getRegisterRemitterSchema', 'App\Modules\Dmt\Controllers\ImpsGuruController@getRegisterRemitterSchema');
});


// ------------------ DMT2 Routes ------------------
$router->group(['prefix' => 'dmt2', 'middleware' => 'check.blacklist'], function () use ($router) {
    $router->post('getDmtTxns', 'App\Modules\Dmt\Controllers\PaySprintController@getDmtTxns');
    $router->post('getBanksDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@getBanksDmt2');
    $router->post('getBeneficiarySchemaDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@getBeneficiarySchemaDmt2');
    $router->post('remitterStatusDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@remitterStatusDmt2');
    $router->post('registerBeneficiaryDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@registerBeneficiaryDmt2');
    $router->post('VerifyRegisterBeneficiary', 'App\Modules\Dmt\Controllers\PaySprintController@VerifyRegisterBeneficiary');
    $router->post('removeBeneficiaryDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@removeBeneficiaryDmt2');
    $router->post('VerifyRemoveBeneficiary', 'App\Modules\Dmt\Controllers\IpayController@VerifyRemoveBeneficiary');
    $router->post('registerRemitter', 'App\Modules\Dmt\Controllers\IpayController@registerRemitter');
    $router->post('kycRemitter', 'App\Modules\Dmt\Controllers\IpayController@kycRemitter');
    $router->post('dmt2Txn', 'App\Modules\Dmt\Controllers\PaySprintController@dmt2Txn');
    $router->post('transactionOtpDmt2', 'App\Modules\Dmt\Controllers\PaySprintController@transactionOtpDmt2');
    $router->post('accountVerification', 'App\Modules\Dmt\Controllers\IpayController@accountVerification');
    
    $router->get('queue-test', function () {
        dispatch(new \App\Jobs\TestJob());
        return 'Job dispatched!';
    });
});
