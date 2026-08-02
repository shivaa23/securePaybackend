<?php

namespace App\Modules\Service\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Models\ServiceValidation;
use Auth;
use App\Helper\ResponseHelper;

class ServiceController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
    }
    
    public function getServices(Request $request){
        // if (Auth::user()->role != "sadm" && Auth::user()->role != "adm") {
        //     return ResponseHelper::error('Action not Authorised', '', 403);
        // }
    
        // Check if request has sub_type filter
        $query = Service::query();
        if ($request->has('sub_type') && !empty($request->sub_type)) {
            $query->where('sub_type', $request->sub_type);
        }
        $services = $query->get();
    
        if ($services->isNotEmpty()) {
            return ResponseHelper::success('Services fetched', $services, 200);
        } else {
            return ResponseHelper::error('No Data Found', [], 200);
        }
    }

    
    public function createService(Request $request){
       	$validator = ServiceValidation::createService($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed',$validator->errors(), 422);
        }
        if(Auth::user()->role!='sadm'&&Auth::user()->role != "adm"){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service=new Service;
        $service->name=$request->name;
        $service->is_active_api=$request->is_active_api;
        $service->is_active_users=$request->is_active_users;
        $service->code=$request->code;
        $service->is_active = $request->is_active ?? 1;
        $service->route = strtoupper($request->route);
         $service->category = ($request->category);
        if($service->save()){
            return ResponseHelper::success('Service Created Successfully', $service, 200);
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);
        }
    }
    
    public function updateService(Request $request){
       	$validator = ServiceValidation::updateService($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        if(Auth::user()->role!='sadm'&&Auth::user()->role != "adm"){
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service=Service::find($request->id);
        if($service){
            $service->name=$request->name??$service->name;
            $service->code=$request->code??$service->code;
            $service->is_active_api=$request->is_active_api??$service->is_active_api;
            $service->is_active_users=$request->is_active_users??$service->is_active_users;
            $service->route = $request->route ? strtoupper($request->route) : $service->route;
            $service->category = $request->category ? ($request->category) : $service->category;
            $service->is_active=$request->is_active??$service->is_active;
            if($service->save()){
                return ResponseHelper::success('Service updated Successfully', $service, 200);
            }
        }else{
            return ResponseHelper::error('Something Went Wrong', '', 403);
        }
    }
    
    public function blockUnblock(Request $request){
        $validator = ServiceValidation::blockUnblock($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
        $service = Service::find($request->id);
        if (!$service) {
            return ResponseHelper::error('Service not found', '', 404);
        }
        $service->is_active = $request->is_active ?? $service->is_active;
        $service->is_active_api = $request->is_active_api ?? $service->is_active_api;
        $service->is_active_users = $request->is_active_users ?? $service->is_active_users;
        if ($service->save()) {
            return ResponseHelper::success('Service Status Changed Successfully', $service, 200);
        } else {
            return ResponseHelper::error('Something Went Wrong', '', 500);
        }
    }

    
}
