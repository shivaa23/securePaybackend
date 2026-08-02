<?php

namespace App\Modules\LayoutModule\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\LayoutModule\Models\SideNav;
use App\Modules\LayoutModule\Models\Colour;
use Illuminate\Http\Request;
use App\Modules\LayoutModule\Models\SideNavValidation;
use App\Modules\LayoutModule\Models\ColorsValidator;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use App\Helper\ResponseHelper;

class LayoutModuleController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ["getColours"]]);
    }
public function getSideNav()
{
    $user = Auth::user();

    $roleMap = [
        'sadm' => 'sadm',
        'adm' => 'adm',
        'ret'  => 'ret',
        'dd'   => 'dd',
    ];

    $role = $roleMap[$user->role] ?? null;

    if (!$role) {
        return ResponseHelper::error('Invalid role', [], 403);
    }

    $sidenav = SideNav::select( 'name', 'url', 'title', 'status')
        ->where('status', 1)
        ->where(function ($query) use ($role) {
            $query->where('role', $role) 
                  ->orWhereJsonContains('role', $role); 
        })
        ->get();

    return ResponseHelper::success('SideNav fetched', $sidenav, 200);
}


 public function createSideNav(Request $request)
{
    $user = Auth::user();

    // if ($user->role !== 'sadm') {
    //     return ResponseHelper::error('Operation not authorized', [], 403);
    // }

    $validator = SideNavValidation::createSideNav($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }

    // Check for duplicate name
    $exists = SideNav::where('name', $request->name)->first();
    if ($exists) {
        return ResponseHelper::error('SideNav with this name already exists', [], 409);
    }

    $sidenav = new SideNav();
    $sidenav->name = $request->name;
    $sidenav->title = $request->title;
    $sidenav->url  = $request->url;
    $sidenav->role = $request->role;
    $sidenav->save();

    return ResponseHelper::success('SideNav created successfully', $sidenav, 201);
}

public function updateSideNav(Request $request)
{
    $user = Auth::user();
    $id = $request->id;

    if ($user->role !== 'sadm') {
        return ResponseHelper::error('Operation not authorized', [], 403);
    }

    $data = $request->all();
    $data['id'] = $id;

    $validator = SideNavValidation::updateSideNav($data);
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }

    $sidenav = SideNav::findOrFail($id);

    // Check for duplicate name excluding current record
    if ($request->has('name')) {
        $exists = SideNav::where('name', $request->name)
                          ->where('id', '!=', $id)
                          ->first();
        if ($exists) {
            return ResponseHelper::error('SideNav with this name already exists', [], 409);
        }
        $sidenav->name = $request->name;
    }

    if ($request->has('url')) {
        $sidenav->url = $request->url;
    }
    if ($request->has('title')) {
        $sidenav->title = $request->title;
    }
    if ($request->has('role')) {
        $sidenav->role = $request->role;
    }

    $sidenav->save();

    return ResponseHelper::success('SideNav updated successfully', $sidenav, 200);
}

public function getColours(){
    $color=Colour::all();
     return ResponseHelper::success('color fetch successfully', $color, 200);
    
}
public function createColour(Request $request)
{
    $validator = ColorsValidator::createColour($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }

  
    $exists = Colour::where('element_type', $request->element_type)->exists();
    if ($exists) {
        return ResponseHelper::error('Duplicate color not allowed for this element type', [], 409);
    }

    $color = Colour::create($request->all());
    return ResponseHelper::success('Color created successfully', $color, 201);
}


public function updateColour(Request $request)
{
    $id = $request->id;

    $validator = ColorsValidator::updateColour($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation failed', $validator->errors(), 422);
    }

    $color = Colour::find($id);
    if (!$color) {
        return ResponseHelper::error('Color not found', [], 404);
    }

  
    $exists = Colour::where('element_type', $request->element_type)
                    ->where('id', '!=', $id)
                    ->exists();

    if ($exists) {
        return ResponseHelper::error('Duplicate color not allowed for this element type', [], 409);
    }

    $color->update($request->all());
    return ResponseHelper::success('Color updated successfully', $color, 200);
}


public function deleteColor($id)
{
    $color = Colour::find($id);

    if (!$color) {
        return ResponseHelper::error('Color not found', [], 404);
    }

    $color->delete();
    return ResponseHelper::success('Color deleted successfully', [], 200);
}
public function colorSchema()
{
    $schema = ColorsValidator::colorSchema(); // static call
    return ResponseHelper::success('Schema fetched', $schema, 200);
}
public function sideNavSchema()
{
    $schema = SideNavValidation::sideNavSchema(); // static call
    return ResponseHelper::success('Schema fetched', $schema, 200);
}


    // public function deleteColor($id)
    // {
    //     $color = Colour::find($id);
    //     if (!$color) {
    //         return ResponseHelper::error('Color not found', [], 404);
    //     }

    //     $color->delete();
    //     return ResponseHelper::success('Color deleted successfully', [], 200);
    // }
}
