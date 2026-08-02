<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'commission'], function () use ($router) {
    $router->post('getCommissions', 'App\Modules\Commission\Controllers\CommissionController@getCommissions');
    $router->post('createCommissionRule', 'App\Modules\Commission\Controllers\CommissionController@createCommissionRule');
    $router->post('getCommissionSchema', 'App\Modules\Commission\Controllers\CommissionController@getCommissionSchema');
    $router->post('updateCommissionRule', 'App\Modules\Commission\Controllers\CommissionController@updateCommissionRule');
    $router->post('deleteCommissionRule', 'App\Modules\Commission\Controllers\CommissionController@deleteCommissionRule');
     $router->post('getAdminCommissions', 'App\Modules\Commission\Controllers\AdminCommissionController@getCommissions');
    $router->post('createAdminCommissionRule', 'App\Modules\Commission\Controllers\AdminCommissionController@createCommissionRule');
    $router->post('getAdminCommissionSchema', 'App\Modules\Commission\Controllers\AdminCommissionController@getCommissionSchema');
    $router->post('updateAdminCommissionRule', 'App\Modules\Commission\Controllers\AdminCommissionController@updateCommissionRule');
      $router->post('getAdminCommissionSchema', 'App\Modules\Commission\Controllers\AdminCommissionController@getAdminCommissionSchema');
    $router->post('deleteAdminCommissionRule', 'App\Modules\Commission\Controllers\AdminCommissionController@deleteAdminCommissionRule');
    
    //comm disbursal
    $router->post('disburseCommRecharge', 'App\Modules\Commission\Controllers\CommissionController@disburseCommRecharge');
});
