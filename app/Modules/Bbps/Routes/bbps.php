<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'bbps'], function () use ($router) {
    $router->post('getBbpsTxns', 'App\Modules\Bbps\Controllers\BbpsController@getBbpsTxns');
    $router->post('getCategories', 'App\Modules\Bbps\Controllers\BbpsController@getCategories');
    $router->post('getBillers', 'App\Modules\Bbps\Controllers\BbpsController@getBillers');
    $router->post('getBillerDetails', 'App\Modules\Bbps\Controllers\BbpsController@getBillerDetails');
    $router->post('fetchBill', 'App\Modules\Bbps\Controllers\BbpsController@fetchBill');
    // $router->post('payBill', 'App\Modules\Bbps\Controllers\BbpsController@payBill');
       $router->post('payBill', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Bbps\Controllers\BbpsController@payBill']);
    $router->post('getCategoriesBillPayment', 'App\Modules\Bbps\Controllers\BillpaymentController@getCategoriesBillPayment');
    $router->post('billPayment', ['uses' => 'App\Modules\Bbps\Controllers\BillpaymentController@billPayment','middleware' => 'throttle:1,0.167']);
    $router->post('partPayment', ['uses' => 'App\Modules\Bbps\Controllers\BillpaymentController@partPayment','middleware' => 'throttle:1,0.167']);

});
