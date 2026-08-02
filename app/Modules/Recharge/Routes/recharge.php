<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'recharge',], function () use ($router) {
    //$router->post('get-recharge-txns', 'App\Modules\Recharge\Controllers\RechargeController@getRechargeTxns');
    $router->post('getRechargeTxns', 'App\Modules\Recharge\Controllers\RechargeController@getRechargeTxns');
    $router->post('recharge', ['middleware' => 'throttle:1,0.167', 'uses' => 'App\Modules\Recharge\Controllers\RechargeController@recharge']);
    $router->post('getPlanByCircle', 'App\Modules\Recharge\Controllers\RechargeController@getPlanByCircle');
    $router->post('getPlansByOperator', 'App\Modules\Recharge\Controllers\RechargePlanController@getPlansByOperator');
    $router->post('createRechargePlan', 'App\Modules\Recharge\Controllers\RechargePlanController@createRechargePlan');
    $router->post('getPlansSchema', 'App\Modules\Recharge\Controllers\RechargePlanController@getPlansSchema');
    $router->post('getRechargePlans', 'App\Modules\Recharge\Controllers\RechargePlanController@getRechargePlans');
    $router->post('fetchPlan', 'App\Modules\Recharge\Controllers\RechargeController@fetchPlan');
});
