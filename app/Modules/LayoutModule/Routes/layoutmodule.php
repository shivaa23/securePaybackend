<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'layout'], function () use ($router) {

    $router->post('getSideNav', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@getSideNav');
    
    
    $router->post('getColours', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@getColours');
      $router->post('createColour', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@createColour');
        $router->post('updateColour', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@updateColour');
    $router->post('createSideNav', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@createSideNav');
    
    $router->post('colorSchema', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@colorSchema');
            $router->post('sideNavSchema', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@sideNavSchema');
    $router->post('updateSideNav', 'App\Modules\LayoutModule\Controllers\LayoutModuleController@updateSideNav');
});


