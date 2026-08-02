<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'plan'], function () use ($router) {
    $router->post('getPlans', 'App\Modules\Plan\Controllers\PlanController@getPlans');
    $router->post('createPlan', 'App\Modules\Plan\Controllers\PlanController@createPlan');
    $router->post('updatePlan', 'App\Modules\Plan\Controllers\PlanController@updatePlan');
    $router->post('asignPlan', 'App\Modules\Plan\Controllers\PlanController@asignPlan');
    $router->post('deletePlan', 'App\Modules\Plan\Controllers\PlanController@deletePlan');
    $router->post('getPlanSchema', 'App\Modules\Plan\Controllers\PlanController@getPlanSchema');
});

