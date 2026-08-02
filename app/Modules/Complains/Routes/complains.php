<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'complains'], function () use ($router) {
    $router->post('getComplains', 'App\Modules\Complains\Controllers\ComplainsController@getComplains');

    $router->post('createComplains', 'App\Modules\Complains\Controllers\ComplainsController@createComplains');
    $router->post('updateComplains', 'App\Modules\Complains\Controllers\ComplainsController@updateComplains');

});