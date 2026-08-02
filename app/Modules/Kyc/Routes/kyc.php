<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'kyc'], function () use ($router) {
    $router->get('getKycs', 'App\Modules\Kyc\Controllers\KycController@getKycs');
    $router->post('createKyc', 'App\Modules\Kyc\Controllers\KycController@createKyc');
    $router->post('updateKyc', 'App\Modules\Kyc\Controllers\KycController@updateKyc');

});
