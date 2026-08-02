<?php
use App\Http\Controllers\Emp\EmployeeController;
use Illuminate\Support\Facades\Route;


$router->group(['prefix' => 'emp'], function () use ($router) {
    $router->get('getEmployee', 'EmployeeController@getEmployee'); //ok
    $router->post('createEmp', 'EmployeeController@createEmp'); //ok
});