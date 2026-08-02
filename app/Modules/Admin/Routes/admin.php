<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'admin'], function () use ($router) {
    $router->post('getRoutes', 'App\Modules\Admin\Controllers\AdminController@getRoutes');
    $router->post('getAllTxn', 'App\Modules\Admin\Controllers\AdminController@getAllTxn');
    $router->post('userCommssionReport', 'App\Modules\Admin\Controllers\AdminController@userCommssionReport');
});
