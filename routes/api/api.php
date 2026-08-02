<?php
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\DmtController;
use App\Http\Controllers\Dmt2Controller;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\RechargeController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AepsController;
use App\Http\Controllers\BbpsController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'prod/v1/onboarding'], function () use ($router) {
    $router->post('onboarding1', 'api\OnboardingController@onboardingIpay');
    $router->post('validateOnboarding1','api\OnboardingController@validateOnboardingIpay');
});
//instantpay
$router->group(['prefix' => 'prod/v1/dmt'], function () use ($router) {
    $router->get('getBanks', 'api\DmtController@getBanks');
    $router->post('getBanks', 'api\DmtController@getBanks');
    $router->get('getRemitter', 'api\DmtController@getRemitter');
    $router->post('getRemitter', 'api\DmtController@getRemitter');
    $router->post('registerRemitter', 'api\DmtController@registerRemitter');
    $router->post('updateRemitter', 'api\DmtController@updateRemitter');
    $router->post('validateOtp', 'api\DmtController@validateOtp');
    $router->post('registerBeneficiary','api\DmtController@registerBeneficiary');
    $router->post('deleteBeneficiary', 'api\DmtController@deleteBeneficiary');
    $router->post('domesticRemittance', 'api\DmtController@domesticRemittance');
    $router->post('initiateKyc', 'api\DmtController@initiateKyc');
});
//paysprint
$router->group(['prefix' => 'prod/v1/dmt2'], function () use ($router) {
    $router->post('getBanks', 'api\Dmt2Controller@getBanks');
    $router->post('getRemitterStatus', 'api\Dmt2Controller@getRemitterStatus');
    $router->post('registerRemitter', 'api\Dmt2Controller@registerRemitter');
    $router->post('registerBeneficiary','api\Dmt2Controller@registerBeneficiary');
    $router->post('removeBeneficiary', 'api\Dmt2Controller@removeBeneficiary');
    $router->post('domesticRemittance','api\Dmt2Controller@domesticRemittance');
});
$router->group(['prefix' => 'prod/v1/payments'], function () use ($router) {
    $router->post('payOut', ['middleware' => 'throttle:100,1','uses' => 'api\PayoutController@payOut',]); //ok
    $router->post('upiPay', ['middleware' => 'throttle:100,1','uses' => 'api\PayoutController@upiPay',]); //ok
    $router->post('creditCard', ['middleware' => 'throttle:100,1','uses' => 'api\PayoutController@billPmtCreditCard',]); //ok
    $router->post('walletTransfer', 'api\PayoutController@walletTransfer');
});
$router->group(['prefix' => 'prod/v1/recharge'], function () use ($router) {
    $router->post('mobile', 'api\RechargeController@mobileRecharge');
    $router->post('dth', 'api\RechargeController@dthRecharge');
});

$router->group(['prefix' => 'prod/v1/utility'], function () use ($router) {
    $router->post('onLine', 'api\UtilityController@onLine');
    $router->post('offLine', 'api\RechargeController@offLine');
});
$router->group(['prefix' => 'prod/v1/verification'], function () use ($router) {
    $router->post('bankAccountVerification','api\VerificationController@bankAccountVerification');
    $router->post('initiateSignupEkyc','api\VerificationController@initiateSignupEkyc');
    $router->post('validateSignupEkyc','api\VerificationController@validateSignupEkyc');
    $router->post('initiateMobileChange','api\VerificationController@initiateMobileChange');
    $router->post('validateMobileChange','api\VerificationController@validateMobileChange');
    $router->post('customerList', 'api\VerificationController@customerList');
});
$router->group(['prefix' => 'prod/v1/aeps'], function () use ($router) {
    $router->post('outletLoginStatus', 'api\AepsController@outletLoginStatus');
    $router->post('outletLogin', 'api\AepsController@outletLogin');
    $router->get('getBanks', 'api\AepsController@getBanks');
    $router->post('cashWithdrawl', 'api\AepsController@cashWithdrawl');
    $router->post('balanceInquiry', 'api\AepsController@balanceInquiry');
    $router->post('miniStatement', 'api\AepsController@miniStatement');
});
$router->group(['prefix' => 'prod/v1/aeps2'], function () use ($router) {
    $router->get('getStates', 'api\AepsController@getStatesFp');
    $router->get('getBanks', 'api\AepsController@getBanksFp');
    $router->post('merchantOnboarding','api\AepsController@merchantOnboardingFp');
    $router->post('merchantEkyc', 'api\AepsController@merchantEkycFp');
    $router->post('validateOtp', 'api\AepsController@validateOtpFp');
    $router->post('merchantBiomatricKyc','api\AepsController@merchantBiomatricKyc');
    $router->post('twoFa', 'api\AepsController@twoFaFp');
});
$router->group(['prefix' => 'prod/v1/bbps'], function () use ($router) {
    $router->post('getCategories', 'api\BbpsController@getCategories');
    $router->post('getBillers', 'api\BbpsController@getBillers');
    $router->post('getBillerDetails', 'api\BbpsController@getBillerDetails');
    $router->post('fetchBill', 'api\BbpsController@fetchBill');
    $router->post('payBill', 'api\BbpsController@payBill');
});
$router->group(['prefix' => 'prod/v1/query'], function () use ($router) {
    $router->post('status', ['middleware' => 'throttle:100,1','uses' => 'api\VerificationController@queryTxn',]); //ok
    $router->post('balance', ['middleware' => 'throttle:10,1','uses' => 'api\VerificationController@balance',]); //ok
});