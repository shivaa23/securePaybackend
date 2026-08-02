<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// ------------------ Payout Routes ------------------
$router->group(['prefix' => 'payout', 'middleware' => 'check.blacklist'], function () use ($router) {
    $router->post('get_payout_senderName', 'App\Modules\Payout\Controllers\PayoutController@get_payout_senderName');
    $router->post('update_payout_sender', 'App\Modules\Payout\Controllers\PayoutController@update_payout_sender');
    $router->post('delete_payout_sender', 'App\Modules\Payout\Controllers\PayoutController@delete_payout_sender');
    $router->post('getPayoutTxns', 'App\Modules\Payout\Controllers\PayoutController@getPayoutTxns');
    $router->post('registerSender', 'App\Modules\Payout\Controllers\SenderController@registerSender');
    $router->post('getSenders', 'App\Modules\Payout\Controllers\SenderController@getSenders');
    $router->post('getSenderByAcc', 'App\Modules\Payout\Controllers\SenderController@getSenderByAcc');
    $router->post('verifySenderSchema', 'App\Modules\Payout\Controllers\SenderController@verifySenderSchema');
    $router->post('registerSenderSchema', 'App\Modules\Payout\Controllers\SenderController@registerSenderSchema');
    $router->post('getSender', 'App\Modules\Payout\Controllers\SenderController@getSender');
    $router->post('verifySender', 'App\Modules\Payout\Controllers\SenderController@verifySender');
    $router->get('updateSenderLimit', 'App\Modules\Payout\Controllers\SenderController@updateSenderLimit');
    //$router->post('otpExp', 'App\Modules\Payout\Controllers\PayoutController@otpExp');
    $router->post('remitterLogin', 'App\Modules\Payout\Controllers\SoliteckController@remitterLogin');
    $router->post('remitterRegistration', 'App\Modules\Payout\Controllers\SoliteckController@remitterRegistration');
    $router->post('getBanks', 'App\Modules\Payout\Controllers\SoliteckController@getBanks');
    $router->post('getIfsc', 'App\Modules\Payout\Controllers\SoliteckController@getIfsc');
    $router->post('addBeneficiary', 'App\Modules\Payout\Controllers\SoliteckController@addBeneficiary');
    $router->post('verifyBeneficiary', 'App\Modules\Payout\Controllers\SoliteckController@verifyBeneficiary');
    $router->post('getSender2', 'App\Modules\Payout\Controllers\SoliteckController@getSender2');
    $router->post('getSender3', 'App\Modules\Payout\Controllers\SoliteckController@getSender3');
    $router->post('deleteBeneficiary2', 'App\Modules\Payout\Controllers\SoliteckController@deleteBeneficiary');
    $router->post('verifyRemitterLogin', 'App\Modules\Payout\Controllers\SoliteckController@verifyRemitterLogin');
    $router->post('remitterLogin2', 'App\Modules\Payout\Controllers\SoliteckController@remitterLogin2');
    $router->post('registerremitter3', 'App\Modules\Payout\Controllers\AbhayamController@remitterLogin3');
    $router->post('verifyRemitterLogin3', 'App\Modules\Payout\Controllers\AbhayamController@verifyRemitterLogin3');
    $router->post('getSender4', 'App\Modules\Payout\Controllers\AbhayamController@getSender4');
    $router->post('addBeneficiaryAbhayam', 'App\Modules\Payout\Controllers\AbhayamController@addBeneficiaryAbhayam');
    $router->post('getAbhayamBanks', 'App\Modules\Payout\Controllers\AbhayamController@getAbhayamBanks');
    $router->post('deleteBeneficiaryAbhayam', 'App\Modules\Payout\Controllers\AbhayamController@deleteBeneficiaryAbhayam');
    $router->post('registerRemitter4', 'App\Modules\Payout\Controllers\UtkalController@registerRemitter4');
    $router->post('verifyRemitterLogin4', 'App\Modules\Payout\Controllers\UtkalController@verifyRemitterLogin4');
    $router->post('getSender5', 'App\Modules\Payout\Controllers\UtkalController@getSender5');
    $router->post('getSenderUpi2', 'App\Modules\Payout\Controllers\UtkalController@getSenderUpi2');
    $router->post('addBeneficiaryUtkal', 'App\Modules\Payout\Controllers\UtkalController@addBeneficiaryUtkal');
    $router->post('createBeneficiaryUpi2', 'App\Modules\Payout\Controllers\UtkalController@createBeneficiaryUpi2');
    $router->post('getUtkalBanks', 'App\Modules\Payout\Controllers\UtkalController@getUtkalBanks');
    $router->post('deleteBeneficiaryUtkal', 'App\Modules\Payout\Controllers\UtkalController@deleteBeneficiaryUtkal');
    $router->post('upiVerification', 'App\Modules\Payout\Controllers\UtkalController@upiVerification');
    $router->post('getAllVpa', 'App\Modules\Payout\Controllers\UtkalController@getAllVpa');
  
    
    // $router->post('payOut2', 'App\Modules\Payout\Controllers\SoliteckController@payOut2');
     $router->post('payOut2', [ 'middleware' => 'throttle:1,0.5', 'uses' => 'App\Modules\Payout\Controllers\SoliteckController@payOut2']);
    $router->post('checkTransactionStatus', 'App\Modules\Payout\Controllers\SoliteckController@checkTransactionStatus');
    // Beneficiary Routes

    $router->post('getBeneficiaries', 'App\Modules\Payout\Controllers\BeneficiaryController@getBeneficiaries');
    $router->post('getBeneficiarySchema', 'App\Modules\Payout\Controllers\BeneficiaryController@getBeneficiarySchema');
    $router->post('createBeneficiary', 'App\Modules\Payout\Controllers\BeneficiaryController@createBeneficiary');
    $router->post('deleteBeneficiary', 'App\Modules\Payout\Controllers\BeneficiaryController@deleteBeneficiary');
    $router->post('getBeneficiarySchemaUpi', 'App\Modules\Payout\Controllers\BeneficiaryController@getBeneficiarySchemaUpi');
    $router->post('upiVerifiaction', 'App\Modules\Payout\Controllers\PayoutController@upiVerifiaction');
    $router->post('createPurpose', 'App\Modules\Payout\Controllers\PayoutController@createPurpose');
    $router->post('getPurpose', 'App\Modules\Payout\Controllers\PayoutController@getPurpose');
});

// ------------------ Payments v2 Routes ------------------
$router->group(['prefix' => 'payments/v2', 'middleware' => 'check.blacklist'], function () use ($router) {
    $router->post('payOut', ['middleware' => 'throttle:1,0.5','uses'=>'App\Modules\Payout\Controllers\PayoutController@payOut']);
    $router->post('getPayoutSchema', 'App\Modules\Payout\Controllers\PayoutController@getPayoutSchema');
     $router->post('payOutAbhayam', ['middleware' => 'throttle:1,0.5','uses'=>'App\Modules\Payout\Controllers\AbhayamController@payOutAbhayam']);
    $router->post('upiUtkal', ['middleware' => 'throttle:1,0.5','uses'=>'App\Modules\Payout\Controllers\UtkalController@upiUtkal']);
   
     $router->post('payOutUtkal', ['middleware' => 'throttle:1,0.5','uses'=>'App\Modules\Payout\Controllers\UtkalController@payOutUtkal']);
    // For API user payouts
    $router->post('userPayOut', ['middleware' => 'throttle:1,0.5','uses'=>'App\Modules\Payout\Controllers\PayoutController@payOut']); // for ret
});
$router->group(['prefix' => 'qr', 'middleware' => ['check.blacklist']], function () use ($router) {
    $router->post('initiateDynamic', [
        'uses' => 'App\Modules\Payout\Controllers\UtkalController@initiateDynamicQrCollection'
    ]);
});

