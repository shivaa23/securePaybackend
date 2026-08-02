<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'walletledger'], function () use ($router) {
    $router->post('getWalletLedgers', 'App\Modules\WalletLedger\Controllers\WalletLedgerController@getWalletLedgers');
    $router->post('getWalletLedger2', 'App\Modules\WalletLedger\Controllers\WalletLedger2Controller@getWalletLedgers');
     $router->post('getWalletLedger3', 'App\Modules\WalletLedger\Controllers\WalletLedger3Controller@getWalletLedgers');
     $router->post('getTxnData', 'App\Modules\WalletLedger\Controllers\WalletLedgerController@getTxnData');
     $router->post('getWalletLedgersDi', 'App\Modules\WalletLedger\Controllers\WalletLedgerController@getWalletLedgersDi');
     $router->post('getDebitWallateTxn', 'App\Modules\WalletLedger\Controllers\WalletLedgerController@getDebitWallateTxn');
     
});

