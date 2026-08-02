<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'service'], function () use ($router) {
    $router->post('getServices', 'App\Modules\Service\Controllers\ServiceController@getServices');
    $router->post('createService', 'App\Modules\Service\Controllers\ServiceController@createService');
    $router->post('updateService', 'App\Modules\Service\Controllers\ServiceController@updateService');
    $router->post('blockUnblock', 'App\Modules\Service\Controllers\ServiceController@blockUnblock');
});
