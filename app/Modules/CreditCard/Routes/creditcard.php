<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'creditcard'], function () use ($router) {
    $router->post('payCreditCard', 'App\Modules\CreditCard\Controllers\CreditCardController@payCreditCard');
    $router->post('getCreditCard', 'App\Modules\CreditCard\Controllers\CreditCardController@getCreditCard');
});
