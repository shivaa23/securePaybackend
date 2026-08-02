<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'notification'], function () use ($router) {
    $router->post('getNotification', 'App\Modules\Notification\Controllers\NotificationController@getNotification');
      $router->post('getNotificationSchema', 'App\Modules\Notification\Controllers\NotificationController@getNotificationSchema');
    $router->post('createNotification', 'App\Modules\Notification\Controllers\NotificationController@createNotification');
    $router->post('updateNotification', 'App\Modules\Notification\Controllers\NotificationController@updateNotification');
    $router->post('deleteNotification', 'App\Modules\Notification\Controllers\NotificationController@deleteNotification');
});
