<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'auditlog'], function () use ($router) {
    $router->post('getAuditLogs', 'App\Modules\AuditLog\Controllers\AuditLogController@getAuditLogs');
    $router->post('getAuditLog', 'App\Modules\AuditLog\Controllers\AuditLogController@getAuditLog');
    $router->post('deleteAuditLog', 'App\Modules\AuditLog\Controllers\AuditLogController@deleteAuditLog');
});
