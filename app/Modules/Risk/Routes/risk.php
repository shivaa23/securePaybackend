<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'risk'], function () use ($router) {
    $router->post('createWhiteListedAccount', 'App\Modules\Risk\Controllers\RiskController@createWhiteAccount');
    $router->post('updateWhiteListedAccount', 'App\Modules\Risk\Controllers\RiskController@updateWhiteAccount');
    $router->post('deleteWhiteListedAccount', 'App\Modules\Risk\Controllers\RiskController@deleteWhiteAccount');
    $router->post('getWhiteListedAccount',    'App\Modules\Risk\Controllers\RiskController@getWhiteAccount');
    $router->post('createBlackListedAccount', 'App\Modules\Risk\Controllers\RiskController@createBlackAccount');
    $router->post('updateBlackListedAccount', 'App\Modules\Risk\Controllers\RiskController@updateBlackAccount');
    $router->post('deleteBlackListedAccount', 'App\Modules\Risk\Controllers\RiskController@deleteBlackAccount');
    $router->post('getBlackListedAccount',    'App\Modules\Risk\Controllers\RiskController@getBlackAccount');
});
