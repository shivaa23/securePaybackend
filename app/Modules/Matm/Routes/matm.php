<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'matm'], function () use ($router) {
    $router->post('getMatmTxns', 'App\Modules\Matm\Controllers\MatmController@getMatmTxns');
});
