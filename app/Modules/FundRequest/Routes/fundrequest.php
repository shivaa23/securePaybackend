<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/**
 * Secure Image Route
 * Access images stored in storage/app/images
 */
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
/**
 * FundRequest Routes
 */
$router->group(['prefix' => 'fundrequest'], function () use ($router) {

    $router->post('getFundRequests', 'App\Modules\FundRequest\Controllers\FundRequestController@getFundRequests');
    $router->post('getFundRequestsSchema', 'App\Modules\FundRequest\Controllers\FundRequestController@getFundRequestsSchema');
    $router->post('test', 'App\Modules\FundRequest\Controllers\FundRequestController@test');
    $router->post('createFundRequest', 'App\Modules\FundRequest\Controllers\FundRequestController@createFundRequest');
    $router->post('getTxnId', 'App\Modules\FundRequest\Controllers\FundRequestController@getTxnId');
    $router->post('pendingCount', 'App\Modules\FundRequest\Controllers\FundRequestController@pendingCount');
      $router->post('createFundRequestnew', 'App\Modules\FundRequest\Controllers\FundRequestController@createFundRequestnew');
    $router->post('updateFundRequest', 'App\Modules\FundRequest\Controllers\FundRequestController@updateFundRequest');
    $router->post('editFundRequest', 'App\Modules\FundRequest\Controllers\FundRequestController@editFundRequest');
    $router->post('deleteFundRequest', 'App\Modules\FundRequest\Controllers\FundRequestController@deleteFundRequest');
});
