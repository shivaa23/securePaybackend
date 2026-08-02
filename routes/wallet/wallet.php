<?php
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'wallet'], function () use ($router) {
    $router->post('w2ToW1Transfer', [
        'middleware' => 'throttle:1,0.25',
        'uses' => 'WalletController@w2ToW1Transfer',
    ]);
    $router->post('w1ToW1Transfer', [
        'middleware' => 'throttle:1,0.25',
        'uses' => 'WalletController@w1ToW1Transfer',
    ]); //ok
    $router->post('createCrRequest', 'WalletController@createCrRequest'); //ok
    $router->post('createCrRequestApp', 'WalletController@createCrRequestApp'); //ok
    $router->get('recentCrRequest', 'WalletController@recentCrRequest'); //ok
    $router->get(
        'getBanksForCrRequest',
        'WalletController@getBanksForCrRequest'
    ); //ok
    $router->get('transactions', 'WalletController@getTransactionsUser'); //ok
    $router->post('transactions', 'WalletController@getTransactionsUser'); //ok
    $router->get('getRetailers', 'WalletController@getRetailers'); //ok
    $router->post('editRetByAd', 'WalletController@editRetByAd'); //ok
    $router->post('userRegistration', 'WalletController@userRegistration'); //ok
    $router->post('addPersonalInfo', 'WalletController@addPersonalInfo'); //ok
    $router->post('addBusinessInfo', 'WalletController@addBusinessInfo'); //ok
    $router->post('resendOtp', 'WalletController@resendOtp'); //ok
    $router->post('createOrderPg', 'WalletController@createOrderPg'); //ok
    $router->get('test', 'WalletController@test'); //ok
    $router->get('updateWalletRblAlert', 'WalletController@updateWalletRblAlert'); //ok
});