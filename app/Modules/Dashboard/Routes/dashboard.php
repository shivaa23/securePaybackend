<?php

/** @var \Laravel\Lumen\Routing\Router $router */
$router->group(['prefix' => 'dashboard'], function () use ($router) {
    $router->post('getRoleWiseCounts', 'App\Modules\Dashboard\Controllers\DashboardController@getRoleWiseCounts');
    $router->post('getServiceWiseSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseSummary');
    $router->post('getRetTxnSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getRetTxnSummary');
    $router->post('getServiceWiseProfitSummaryForAdmin', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForAdmin');
    
    $router->post('getServiceWiseProfitSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummary');
    $router->post('getServiceWiseProfitSummaryNew', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryNew');
    $router->post('getServiceWiseProfitSummaryForDi', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForDi');
    $router->post('getServiceWiseProfitSummaryForMd', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForMd');
    $router->post('getUserServiceWiseTxnReport', 'App\Modules\Dashboard\Controllers\DashboardController@getUserServiceWiseTxnReport');
     $router->post('getTxnSummaryServiceWise', 'App\Modules\Dashboard\Controllers\DashboardController@getTxnSummaryServiceWise');

});

// $router->group(['prefix' => 'dashboard'], function () use ($router) {
//     $router->post('getRoleWiseCounts', 'App\Modules\Dashboard\Controllers\DashboardController@getRoleWiseCounts');
//     $router->post('getServiceWiseSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseSummary');
//     $router->post('getRetTxnSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getRetTxnSummary');
//     $router->post('getServiceWiseProfitSummaryForAdmin', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForAdmin');
    
//     $router->post('getServiceWiseProfitSummary', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummary');
//     $router->post('getServiceWiseProfitSummaryNew', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryNew');
//     $router->post('getServiceWiseProfitSummaryForDi', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForDi');
//     $router->post('getServiceWiseProfitSummaryForMd', 'App\Modules\Dashboard\Controllers\DashboardController@getServiceWiseProfitSummaryForMd');
// });
