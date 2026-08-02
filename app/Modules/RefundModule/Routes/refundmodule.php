<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'refund'], function () use ($router) {
    $router->post('refundTxn', 'App\Modules\RefundModule\Controllers\RefundController@refundTxn');
    $router->post('successTxn', 'App\Modules\RefundModule\Controllers\RefundController@successTxn');
    $router->post('failedTxn', 'App\Modules\RefundModule\Controllers\RefundController@failedTxn');
    $router->post('successAepsTxn', 'App\Modules\RefundModule\Controllers\RefundController@successAepsTxn');
});