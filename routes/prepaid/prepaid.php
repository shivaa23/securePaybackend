<?php
use App\Http\Controllers\PrepaidController;
use App\Http\Controllers\TramoController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'prepaid'], function () use ($router) {
    $router->post('getNumberInfo', 'PrepaidController@getNumberInfo'); //ok
    $router->post('getPrepaidPlans', 'PrepaidController@getPrepaidPlans'); //ok
    $router->post('getOperators', 'PrepaidController@getOperators'); //ok
    $router->post('getOperator', 'PrepaidController@getOperator'); //ok
    $router->get('recent', 'PrepaidController@recent'); //ok
    //$router->post('prepaidRecharge', 'PrepaidController@prepaidRecharge');//ok
    $router->post('prepaidRecharge', [
        'middleware' => 'throttle:1,0.25',
        'uses' => 'PrepaidController@prepaidRecharge',
    ]); //ok
    $router->post('getDistricts', 'PrepaidController@getDistricts'); //ok
    $router->get('getCategories', 'PrepaidController@getCategories');
    $router->post('getBillers', 'PrepaidController@getBillers');
    $router->post('getBillerDetails', 'PrepaidController@getBillerDetails');

    $router->post('fetchBillInst', 'PrepaidController@fetchBill');
    //$router->post('payBillInst', 'PrepaidController@payBillInst');
    $router->post('payBillInst', [
        'middleware' => 'throttle:1,0.5',
        'uses' => 'PrepaidController@payBillInst',
    ]); //ok
    $router->post('fetchBill', 'TramoController@fetchBill');
    //$router->post('payBill', 'TramoController@payBill');
    $router->post('payBill', [
        'middleware' => 'throttle:1,0.5',
        'uses' => 'TramoController@payBill',
    ]); //ok
    $router->post('payBillApi', 'TramoController@payBill'); //
});