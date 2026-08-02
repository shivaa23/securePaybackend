<?php

$router->group(['prefix' => 'lien'], function () use ($router) {
        $router->post('addLien',   'App\Modules\LienModule\Controllers\LienController@addLien');
        $router->post('removeLien',   'App\Modules\LienModule\Controllers\LienController@removeLien');
        $router->post('getLien',   'App\Modules\LienModule\Controllers\LienController@getLien');

});
