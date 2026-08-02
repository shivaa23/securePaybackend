<?php

namespace App\Modules\Role\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Role\Models\Role;
use App\Modules\Role\Models\RoleValidation;
use Auth;
use App\Traits\CommonEmail;
use App\Mail\SendEmail;
use Illuminate\Support\Facades\Mail;
use App\Helper\ResponseHelper;
class RoleController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['getRoles']]);
    }   
    public function getRoles(){
        
        $roles = Role::where('status','active')->paginate(10);
        if($roles){
             return ResponseHelper::success('User Roles  fetched', $roles, 200);
            // return $this->jsonResponse('1000', 'Success', 'User Roles  fetched', $roles, 200);
        }else{
            return ResponseHelper::success('No Data Found', [], 200);
            // return $this->jsonResponse('1001', 'Failed', 'No Data Found', [], 200);

        }
    }
    
    public function createRole(Request $request)
    { 
  
       	$validator = RoleValidation::createRole($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
        // if(Auth::user()->role!='sadm'){
        //     return $this->jsonResponse('1001', 'Failed', 'Action not Authorised', '', 403);
        // }
        $role=new Role;
        $role->role=$request->role;
        $role->description=$request->description;
        
        if($role->save()){
            return $this->jsonResponse('1000', 'Success', 'Role Created Successfully', $role, 200);
        }else{
            return $this->jsonResponse('1001', 'Failed', 'Something Went Wrong', '', 403);

        }
    }
    
    public function updateRole(Request $request)
    {
       	$validator = RoleValidation::updateRole($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(),'', 422);
        }
        // if(Auth::user()->role!='sadm'){
            // return $this->jsonResponse('1001', 'Failed', 'Action not Authorised', '', 403);
        // }
        $role=Role::find($request->id);
        if($role){
            $role->role=$request->role??$role->role;
            $role->description=$request->description??$role->description;
            $role->status=$request->status??$role->status;
            if($role->save()){
                return $this->jsonResponse('1000', 'Success', 'Role updated Successfully', $role, 200);
            }
        }else{
            return $this->jsonResponse('1001', 'Failed', 'Something Went Wrong', '', 403);

        }
        

    }
    public function mailCheck(){
      $subject = 'Your new password from BigPet';
    $messageBody = "Hello \n\nYour password has been reset.\nYour new password is: <span style=\"color: green; font-weight: bold;\"></span>\n\nThank you,\nBigPet Team";
// Example new password
$userEmail="shivammca099@gmail.com";
if ($userEmail) {
    $emailSent = CommonEmail::sendPlainEmail($userEmail, "shivam", $subject, $messageBody);
    

    if (!$emailSent) {
        return response()->json([
            'message' => 'Password reset but failed to send email',
            'status' => false,
            'error' => 'Email sending failed',
            'new_password' => "jhgjhdg"
        ], 500);
    }
}

    }
}
