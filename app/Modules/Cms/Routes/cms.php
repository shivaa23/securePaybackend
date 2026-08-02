<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'cms'], function () use ($router) {
    $router->post('createOrderCms', 'App\Modules\Cms\Controllers\CmsController@createOrderCms');
    $router->post('createOrderMatm', 'App\Modules\Cms\Controllers\CmsController@createOrderMatm');

});