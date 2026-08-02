<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'role','middleware' => 'decrypt',], function () use ($router) {
    $router->get('getRoles', 'App\Modules\Role\Controllers\RoleController@getRoles');
    $router->post('createRole', 'App\Modules\Role\Controllers\RoleController@createRole');
    $router->post('updateRole', 'App\Modules\Role\Controllers\RoleController@updateRole');
  $router->post('mailCheck', 'App\Modules\Role\Controllers\RoleController@mailCheck');
});
