<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('signIn', ['middleware' => 'throttle:50,1','uses'=>'App\Modules\Auth\Controllers\AuthController@signIn']);
    $router->post('secureLogin', ['middleware' => 'throttle:5,1','uses'=>'App\Modules\Auth\Controllers\AuthController@secureLogin']);
    $router->post('Logout', 'App\Modules\Auth\Controllers\AuthController@Logout');
    $router->post('getMobile', 'App\Modules\Auth\Controllers\AuthController@getMobile');
    
    $router->post('changePassword', ['middleware' => 'throttle:1,1','uses'=>'App\Modules\Auth\Controllers\AuthController@changePassword']);
    $router->post('forgetPassword', ['middleware' => 'throttle:5,1','uses'=>'App\Modules\Auth\Controllers\AuthController@forgetPassword']);
    $router->post('changeMpin', ['middleware' => 'throttle:5,1','uses'=>'App\Modules\Auth\Controllers\AuthController@changeMpin']);
    $router->post('resetMpin', ['middleware' => 'throttle:5,1','uses'=>'App\Modules\Auth\Controllers\AuthController@resetMpin']);
    $router->post('changeTwoFa', ['middleware' => 'throttle:1,0.667','uses'=>'App\Modules\Auth\Controllers\AuthController@changeTwoFa']);
    $router->post('initiateChangeNumber', ['middleware' => 'throttle:5,1','uses'=>'App\Modules\Auth\Controllers\AuthController@initiateChangeNumber']);
    $router->post('verifyChangeNumber', 'App\Modules\Auth\Controllers\AuthController@verifyChangeNumber');
    $router->post('resendOtp', 'App\Modules\Auth\Controllers\AuthController@resendOtp');
    $router->post('changeRole', 'App\Modules\Auth\Controllers\AuthController@changeRole');
    $router->post('changeParent', 'App\Modules\Auth\Controllers\AuthController@changeParent');
    $router->post('generateQr','App\Modules\Auth\Controllers\AuthController@generateQr');
    $router->post('qrStatus', ['middleware' => 'throttle:50,1','uses'=>'App\Modules\Auth\Controllers\AuthController@checkQr']);
    $router->post('approveQr', ['middleware' => 'throttle:50,1','uses'=>'App\Modules\Auth\Controllers\AuthController@approveQr']);
});
