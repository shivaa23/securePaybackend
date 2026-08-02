<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'account'], function () use ($router) {
    $router->post('getAccounts', 'App\Modules\Account\Controllers\AccountController@getAccounts');
    $router->post('getAccountSchema', 'App\Modules\Account\Controllers\AccountController@getAccountSchema');
    $router->post('createAccount', 'App\Modules\Account\Controllers\AccountController@createAccount');
    $router->post('updateAccount', 'App\Modules\Account\Controllers\AccountController@updateAccount');
    $router->post('deleteAccount', 'App\Modules\Account\Controllers\AccountController@deleteAccount');
    $router->post('encryptPayload', 'App\Modules\Account\Controllers\AccountController@encryptPayload');
    $router->post('deleteLastTxn', 'App\Modules\Account\Controllers\AccountController@deleteLastTxn');
    $router->post('getAccounOthertSchema', 'App\Modules\Account\Controllers\AccountController@getAccounOthertSchema');
});
$router->group(['prefix' => 'accountStatement'], function () use ($router) {
    $router->post('getAccountStatement', 'App\Modules\Account\Controllers\AccountStatementController@getAccountStatement');
    $router->post('getAccountStatementSchema', 'App\Modules\Account\Controllers\AccountStatementController@getAccountStatementSchema');
    $router->post('createAccountStatement', 'App\Modules\Account\Controllers\AccountStatementController@createAccountStatement');
    $router->post('updateAccountStatement', 'App\Modules\Account\Controllers\AccountStatementController@updateAccountStatement');
    $router->post('deleteAccountStatement', 'App\Modules\Account\Controllers\AccountStatementController@deleteAccountStatement');
    $router->post('encryptPayload', 'App\Modules\Account\Controllers\AccountStatementController@encryptPayload');
});
