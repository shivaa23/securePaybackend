<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'news'], function () use ($router) {
    $router->post('getNews', 'App\Modules\News\Controllers\NewsController@getNews');
    $router->post('createNews', 'App\Modules\News\Controllers\NewsController@createNews');
    $router->post('deleteNews', 'App\Modules\News\Controllers\NewsController@deleteNews');

});
