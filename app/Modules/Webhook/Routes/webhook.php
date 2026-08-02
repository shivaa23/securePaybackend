<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'webhook'], function () use ($router) {
    $router->post('guruWebhook', 'App\Modules\Webhook\Controllers\WebHookController@guruWebhook');
    $router->post('solitekWebhook', 'App\Modules\Webhook\Controllers\WebHookController@solitekWebhook');
    $router->post('indicoreWebhook', 'App\Modules\Webhook\Controllers\WebHookController@indicoreWebhook');
    $router->post('getWebhook', 'App\Modules\Webhook\Controllers\WebHookController@getWebhook');
    $router->post('fingPayCmsApi', 'App\Modules\Webhook\Controllers\WebHookController@fingPayCmsApi');
    $router->post('digiKhataWebhook', 'App\Modules\Webhook\Controllers\WebHookController@digiKhataWebhook');
    $router->post('abhyamWebhook', 'App\Modules\Webhook\Controllers\WebHookController@abhyamWebhook');
    $router->post('utkalWebhook', 'App\Modules\Webhook\Controllers\WebHookController@utkalWebhook'); 
    $router->post('dynamicWebhook', 'App\Modules\Webhook\Controllers\WebHookController@dynamicWebhook');
});
