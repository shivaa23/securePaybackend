<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'signup'], function () use ($router) {
    $router->post('createUser', 'App\Modules\Signup\Controllers\SignupController@signup');
      $router->post('getSignupSchema', 'App\Modules\Signup\Controllers\SignupController@getSignupSchema');
      $router->post('getState', 'App\Modules\Signup\Controllers\SignupController@getState');
      $router->post('getDistrict', 'App\Modules\Signup\Controllers\SignupController@getDistrict');
});
