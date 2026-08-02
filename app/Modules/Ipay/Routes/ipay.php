<?php

/** @var \Laravel\Lumen\Routing\Router $router */
$router->group(['prefix' => 'ipay'], function () use ($router) {

    $router->post('getIpay', [
        'uses' => 'App\Modules\Ipay\Controllers\IpayController@getIpay'
    ]);

    $router->post('updateIpay', [
        'uses' => 'App\Modules\Ipay\Controllers\IpayController@updateIpay'
    ]);
});

