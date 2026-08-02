<?php
$router->get('/secure-images/{filename}', function ($filename) {
    $path = storage_path('app/public/images/' . $filename);

    if (!file_exists($path)) {
        abort(404, 'Image not found');
    }

    $mimeType = mime_content_type($path);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimeTypes)) {
        abort(403, 'Invalid image type');
    }

    return response()->file($path, [
        'Content-Type' => $mimeType
    ]);
});


/** @var \Laravel\Lumen\Routing\Router $router */
$router->group(['prefix' =>'business'], function () use ($router) {
    $router->post('createBasic','App\Modules\BusinessDetail\Controllers\BusinessController@createBasic');          
    $router->post('createContact', 'App\Modules\BusinessDetail\Controllers\BusinessController@createContact');  
    $router->post('createAddress','App\Modules\BusinessDetail\Controllers\BusinessController@createAddress');          
    $router->post('createIdentification', 'App\Modules\BusinessDetail\Controllers\BusinessController@createIdentification'); 
    $router->post('createBank','App\Modules\BusinessDetail\Controllers\BusinessController@createBank');          
    $router->post('createDocuments', 'App\Modules\BusinessDetail\Controllers\BusinessController@createDocuments'); 
    $router->post('createKyc', 'App\Modules\BusinessDetail\Controllers\BusinessController@createKyc'); 
    $router->post('createStatus', 'App\Modules\BusinessDetail\Controllers\BusinessController@createStatus');  
    $router->post('basicSchema','App\Modules\BusinessDetail\Controllers\BusinessController@basicSchema');          
    $router->post('contactSchema', 'App\Modules\BusinessDetail\Controllers\BusinessController@contactSchema');  
    $router->post('addressSchema','App\Modules\BusinessDetail\Controllers\BusinessController@addressSchema');          
    $router->post('identificationSchema', 'App\Modules\BusinessDetail\Controllers\BusinessController@identificationSchema'); 
    $router->post('bankSchema','App\Modules\BusinessDetail\Controllers\BusinessController@bankSchema');          
    $router->post('documentsSchema', 'App\Modules\BusinessDetail\Controllers\BusinessController@documentsSchema'); 
    $router->post('kycSchema', 'App\Modules\BusinessDetail\Controllers\BusinessController@kycSchema'); 
    $router->post('statusSchema', 'App\Modules\BusinessDetail\Controllers\BusinessController@statusSchema');  
    $router->post('getByType', 'App\Modules\BusinessDetail\Controllers\BusinessController@getByType');  
    $router->post('updateByType', 'App\Modules\BusinessDetail\Controllers\BusinessController@updateByType');
    $router->post('getAllByUser', 'App\Modules\BusinessDetail\Controllers\BusinessController@getAllByUser');  
    $router->post('approveReject', 'App\Modules\BusinessDetail\Controllers\BusinessController@approveReject');  
});
