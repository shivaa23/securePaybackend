<?php
use App\Http\Controllers\IrctcController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'uat/irctc'], function () use ($router) {
    $router->post('paymentRequest', 'IrctcController@paymentUat'); //ok
    $router->post('verificationRequest', 'IrctcController@verificationUat'); //ok
    $router->get('paymentRequest', 'IrctcController@paymentUat'); //ok
    $router->get('verificationRequest', 'IrctcController@verificationUat'); //ok
    $router->post('authPayment', 'IrctcController@authPaymentUat'); //ok
});

$router->group(['prefix' => 'prod/irctc'], function () use ($router) {
    $router->post('paymentRequest', 'IrctcController@payment'); //ok
    $router->post('verificationRequest', 'IrctcController@verification'); //ok
    $router->get('paymentRequest', 'IrctcController@payment'); //ok
    $router->get('verificationRequest', 'IrctcController@verification'); //ok
    $router->post('authPayment', 'IrctcController@authPayment'); //ok
});