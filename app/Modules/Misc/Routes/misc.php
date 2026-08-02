<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'misc'], function () use ($router) {
    $router->post('apiBalance', 'App\Modules\Misc\Controllers\MiscController@apiBalance');
    $router->post('apiBalanceNew', 'App\Modules\Misc\Controllers\MiscController@apiBalanceNew');
    $router->get('logoutAll', 'App\Modules\Misc\Controllers\MiscController@logoutAll');
    $router->get('getUuid', [ 'middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Misc\Controllers\MiscController@getUuid']);
    $router->post('chkUuid', 'App\Modules\Misc\Controllers\MiscController@chkUuid');
});
