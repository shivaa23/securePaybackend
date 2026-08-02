<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'bank'], function () use ($router) {
    $router->post('getBanks', 'App\Modules\Bank\Controllers\BankController@getBanks');
    $router->post('getBankSchema', 'App\Modules\Bank\Controllers\BankController@getBankSchema');
    $router->post('getBankBalance', 'App\Modules\Bank\Controllers\BankController@getBankBalance');
    $router->post('createBank', 'App\Modules\Bank\Controllers\BankController@createBank');
    $router->post('updateBank', 'App\Modules\Bank\Controllers\BankController@updateBank');
    $router->post('deleteBank', 'App\Modules\Bank\Controllers\BankController@deleteBank');
    $router->post('deleteLastTxn', 'App\Modules\Bank\Controllers\BankController@deleteLastTxn');
});
$router->group(['prefix' => 'bankStatement'], function () use ($router) {
    $router->post('getUnclaimedEntries', 'App\Modules\Bank\Controllers\BankStatementController@getUnclaimedEntries');
    $router->post('getBankStatement', 'App\Modules\Bank\Controllers\BankStatementController@getBankStatement');
    $router->post('getBankStatementSchema', 'App\Modules\Bank\Controllers\BankStatementController@getBankStatementSchema');
    $router->post('createBankStatement', 'App\Modules\Bank\Controllers\BankStatementController@createBankStatement');
    $router->post('updateBankStatement', 'App\Modules\Bank\Controllers\BankStatementController@updateBankStatement');
    $router->post('deleteBankStatement', 'App\Modules\Bank\Controllers\BankStatementController@deleteBankStatement');
});


// $router->group([
//     'prefix' => 'bank',
//     'middleware' => ['decryptAfterLogin']
// ], function () use ($router) {
//     $router->post('getBanks', 'App\Modules\Bank\Controllers\BankController@getBanks');
//     $router->post('getBankSchema', 'App\Modules\Bank\Controllers\BankController@getBankSchema');
//     $router->post('createBank', 'App\Modules\Bank\Controllers\BankController@createBank');
//     $router->post('updateBank', 'App\Modules\Bank\Controllers\BankController@updateBank');
//     $router->post('deleteBank', 'App\Modules\Bank\Controllers\BankController@deleteBank');
    