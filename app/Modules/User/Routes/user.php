<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'user'], function () use ($router) {
    $router->post('createUser', 'App\Modules\User\Controllers\UserController@createUser');
    $router->post('getUsers', 'App\Modules\User\Controllers\UserController@getUsers');
    $router->get('getUser', 'App\Modules\User\Controllers\UserController@getUser');
    $router->post('updateUser', 'App\Modules\User\Controllers\UserController@updateUser');
    $router->post('updateUserPermissions', 'App\Modules\User\Controllers\UserController@updateUserPermissions');
    $router->post('blockAndUnblock', 'App\Modules\User\Controllers\UserController@blockAndUnblock');
    $router->post('changeLayout', 'App\Modules\User\Controllers\UserController@changeLayout');
    $router->post('getParent', 'App\Modules\User\Controllers\UserController@getParent');
    $router->post('getRole', 'App\Modules\User\Controllers\UserController@getRole');
    $router->post('getUserDebounce', 'App\Modules\User\Controllers\UserController@getUserDebounce');
    $router->post('syncCustomersToIMS', 'App\Modules\User\Controllers\UserController@syncCustomersToIMS');
});