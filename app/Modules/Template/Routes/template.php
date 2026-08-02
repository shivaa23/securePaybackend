<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'template'], function () use ($router) {
    $router->post('getTemplates', 'App\Modules\Template\Controllers\TemplateController@getTemplates');
    $router->post('createTemplate', 'App\Modules\Template\Controllers\TemplateController@createTemplate');
    $router->post('updateTemplate', 'App\Modules\Template\Controllers\TemplateController@updateTemplate');
    $router->post('deleteTemplate', 'App\Modules\Template\Controllers\TemplateController@deleteTemplate');
});
