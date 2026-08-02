<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'userDevice'], function () use ($router) {
    $router->post('getUserDevice', 'App\Modules\UserDevice\Controllers\UserDeviceController@getUserDevice');
    $router->post('createUserDevice', 'App\Modules\UserDevice\Controllers\UserDeviceController@createUserDevice');
    $router->post('updateBank', 'App\Modules\Bank\Controllers\BankController@updateBank');
    $router->post('deleteBank', 'App\Modules\Bank\Controllers\BankController@deleteBank');
});
