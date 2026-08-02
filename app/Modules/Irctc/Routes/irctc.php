<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'irctc'], function () use ($router) {
    $router->post('getIrctcTxns', 'App\Modules\Irctc\Controllers\IrctcController@getIrctcTxns');
});
