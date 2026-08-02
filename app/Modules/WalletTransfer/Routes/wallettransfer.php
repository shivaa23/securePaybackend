<?php

$router->group(['prefix' => 'wallet'], function () use ($router) {
        $router->post('getW2WTransactions',   'App\Modules\WalletTransfer\Controllers\WalletTransferController@getW2WTransactions');
    $router->post('getReceiver',   'App\Modules\WalletTransfer\Controllers\WalletTransferController@getReceiver');
    $router->post('getW2WTransaction',   'App\Modules\WalletTransfer\Controllers\WalletTransferController@getW2WTransaction');
    $router->post('createW2W', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\WalletTransfer\Controllers\WalletTransferController@createW2W']);
    $router->post('createW2W1', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\WalletTransfer\Controllers\WalletTransferController@createW2W1']);
    $router->post('createW3W1', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\WalletTransfer\Controllers\WalletTransferController@createW3W1']);
    $router->post('createDebit', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\WalletTransfer\Controllers\WalletTransferController@createDebit']);
     $router->post('getDebitWallateTxn', 'App\Modules\WalletTransfer\Controllers\WalletTransferController@getDebitWallateTxn');
       $router->post('getCreditDebitReport', 'App\Modules\WalletTransfer\Controllers\WalletTransferController@getCreditDebitReport');
});
