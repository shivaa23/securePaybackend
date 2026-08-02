<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'collections'], function () use ($router) {
    $router->post('getCollections', 'App\Modules\Collection\Controllers\CollectionController@getCollections');
     $router->post('claim', 'App\Modules\Collection\Controllers\CollectionController@claim');
     $router->post('getCollections2', 'App\Modules\Collection\Controllers\CollectionController@getCollections2');
     $router->post('claim2', 'App\Modules\Collection\Controllers\CollectionController@claim2');
     $router->post('markUnclaimed2', 'App\Modules\Collection\Controllers\CollectionController@markUnclaimed2');
});
$router->group(['prefix' => 'qr'], function () use ($router) {
    $router->post('initiateDynamicQrCollection', 'App\Modules\Collection\Controllers\CollectionController@initiateDynamicQrCollection');
    $router->post('claim3', 'App\Modules\Collection\Controllers\CollectionController@claim3');
    $router->post('getCollections3', 'App\Modules\Collection\Controllers\CollectionController@getCollections3');
    $router->get('expirePending', 'App\Modules\Collection\Controllers\CollectionController@expirePending');
    $router->post('checkTxnStatus', 'App\Modules\Collection\Controllers\CollectionController@checkTxnStatus');
});