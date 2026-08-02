<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('signIn', 'AuthController@signIn'); //ok
    $router->post('secureLogin', 'AuthController@secureLogin'); //ok
    $router->post('signRequest', 'AuthController@signRequest'); //ok
    $router->post('resendLoginOtp', ['middleware' => 'throttle:5,1','uses' => 'AuthController@resendLoginOtp',]); //ok
    $router->get('getUser', 'AuthController@getUser'); //ok
    $router->post('getDocs', 'AuthController@getDocs'); //ok
    $router->post('changePassword', 'AuthController@changePassword'); //ok
    $router->post('resetPassword', 'AuthController@resetPassword');
    $router->post('changeMpin', 'AuthController@changeMpin'); //ok
    $router->post('resetMpin', 'AuthController@resetMpin'); //ok
    $router->post('resetMpinByUsername', 'AuthController@resetMpinByUsername'); //ok
    // $router->post('forgetPassword', 'AuthController@forgetPassword');//ok
    $router->post('forgetPassword', [
        'middleware' => 'throttle:2,1',
        'uses' => 'AuthController@forgetPassword',
    ]); //ok
    $router->get('getUserByUsername', 'AuthController@getUserByUsername'); //ok
    $router->post('getUserByRole', 'AuthController@getUserByRole'); //ok
    $router->post('getUserData', 'AuthController@getUserData'); //ok
    $router->post(
        'twoFactorAuthentication',
        'AuthController@twoFactorAuthentication'
    ); //ok
    $router->post('verifyMobile', 'WalletController@verifyMobile'); //ok
    $router->post('logOut', 'AuthController@logOut'); //ok
    $router->post('mobileUpdate', 'AuthController@mobileUpdate'); //ok
    $router->post(
        'mobileUpdateValidate',
        'AuthController@mobileUpdateValidate'
    ); //ok
});