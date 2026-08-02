<?php

namespace App\Modules\User\Controllers;

use App\Modules\User\Models\User;
use App\Modules\User\Models\UserValidation;
use App\Modules\User\Models\Permission;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helper\ResponseHelper;
use Illuminate\Http\Request;
use App\Modules\Auth\Models\Otp;
use Auth;
use App\Modules\Plan\Models\Plan;
use App\Modules\Utils\RoleHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessAddress;
class UserController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['syncCustomersToIMS']]);
         $this->middleware('check.active', ['except' => ['syncCustomersToIMS']]);
    }
    // public function getUsers(Request $request)
    // {
    //     $user = Auth::user();
 
    //     $query = User::with('permissions')->orderBy('id', 'desc');
    //     if (!in_array($user->role, ['adm', 'sadm'])) {
    //         $userIds = User::getDescendantUserIds($user->id);
    //         $query->whereIn('id', $userIds);
    //     }
    //     if ($request->filled('mobile')) {
    //         $query->where('mobile', $request->mobile);
    //     }
    //     if ($request->filled('id')) {
    //         $query->where('id', $request->id);
    //     }
    //     if ($request->filled('role')) {
    //         $query->where('role', $request->role);
    //     }
    //     if ($request->filled('parent')) {
    //         $query->where('parent', $request->parent);
    //     }
    //     if ($request->filled('establishment')) {
    //         $query->where('establishment', $request->establishment);
    //     }
    //     if ($request->filled('role')) {
    //         $query->where('role', $request->role);
    //     }
    //     $perPage = $request->filled('paginate') ? $request->paginate : 15;
    //     $users = $query->paginate($perPage);
    
    //     return ResponseHelper::success("Users fetched successfully", $users, 200);
    // }
    // public function getUsers(Request $request)
    // {
    //     $user = Auth::user();
    
    //     $query = User::with('permissions')->orderBy('id', 'desc');
    //     if (!in_array($user->role, ['adm', 'sadm'])) {
    //         $userIds = User::getDescendantUserIds($user->id);
    //         $query->whereIn('id', $userIds);
    //     }
    //     if ($request->filled('mobile')) {
    //         $query->where('mobile', $request->mobile);
    //     }
    //     if ($request->filled('id')) {
    //         $query->where('id', $request->id);
    //     }
    //     if ($request->filled('role')) {
    //         $query->where('role', $request->role);
    //     }
    //     if ($request->filled('parent_id')) {
    //         $query->where('parent', $request->parent_id);
    //     }
    //     if ($request->filled('establishment')) {
    //         $query->where('establishment', 'like', '%' . $request->establishment . '%');
    //     }
    //     if ($request->filled('export') && $request->export == 1) {
    //         $users = $query->get(); 
    //     } else {
    //         $perPage = $request->filled('paginate') ? $request->paginate : 15;
    //         $users = $query->paginate($perPage);
    //     }
    
    //     foreach ($users as $u) {
    //         if ($u->parent) {
    //             $parentUser = User::find($u->parent);
    //             $u->parent_establishment = $parentUser ? $parentUser->establishment : null;
    //         } else {
    //             $u->parent_establishment = null;
    //         }
    //     }
    
    //     return ResponseHelper::success("Users fetched successfully", $users, 200);
    // }
    public function getUsers(Request $request)
    {
        $user = Auth::user();
    
        $query = User::with('permissions')->orderBy('id', 'desc');
    
        // if (!in_array($user->role, ['adm', 'sadm'])) {
        //     $userIds = User::getDescendantUserIds($user->id);
        //     $query->whereIn('id', $userIds);
        // }
         
        if (in_array($user->role, ['md', 'di'])) {
            
            $query->where('parent', $user->id);
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', $request->mobile);
        }
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('parent_id')) {
            $query->where('parent', $request->parent_id);
        }
        if ($request->filled('establishment')) {
            $query->where('establishment', 'like', '%' . $request->establishment . '%');
        }
    
        if ($request->filled('export') && $request->export == 1) {
            $users = $query->get();
        } else {
            $perPage = $request->filled('paginate') ? $request->paginate : 15;
            $users = $query->paginate($perPage);
        }
    
        foreach ($users as $u) {
            
              if ($u->plan_id) {
                $planName = Plan::find($u->plan_id);
                //   return $planName;
                $u->plan_name = $planName ? $planName->name : null;
            } 
            if ($u->parent) {
                $parentUser = User::find($u->parent);
                $u->parent_establishment = $parentUser ? $parentUser->establishment : null;
            } else {
                $u->parent_establishment = null;
            }
            if ($u->permissions) {
                $permissions = $u->permissions->toArray();
    
                $allowed = [];
                if ($u->role === 'ret'||$u->role === 'dd') {
                    $allowed = [
                        'dmt1','super','vendor','upi','utkal_upi','recharge','aeps1','aeps2',
                        'bbps','credit_card','travel','electricity','fund_req','txn','soliteck','abhayam','utkal','bbps_offline','qr1','qr2','qr3','claimqr2'
                    ];
                } elseif ($u->role === 'adm') {
                    $allowed = [
                        'fund_req_actions','txn_actions','user_actions','banking','fund_req',
                        'account','bank','unclaimed','settings','add_lien','remove_lien','txn','users'
                    ];
                }
              elseif ($u->role == 'di'||$u->role == 'md') {
                    $allowed = [
                        'users','txn','w_txn','fund_req','user_actions'
                        
                    ];
                }
                 elseif ($u->role == 'asm'||$u->role == 'zsm') {
                    $allowed = [
                        'users','txn','w_txn','fund_req',
                        
                    ];
                }
                // keep only allowed keys
                $filtered = array_intersect_key($permissions, array_flip($allowed));
    
                // optional: keep id & user_id for debugging
                $filtered['id'] = $permissions['id'];
                $filtered['user_id'] = $permissions['user_id'];
    
                // replace relation
                $u->setRelation('permissions', collect($filtered));
            }
        }
    
        return ResponseHelper::success("Users fetched successfully", $users, 200);
    }
//     public function getUsers(Request $request)
// {
//     $user = Auth::user();

//     $query = User::with('permissions')->orderBy('id', 'desc');

//     // -------------------------------
//     // ROLE ACCESS CONTROL
//     // -------------------------------
//     if (!in_array($user->role, ['adm', 'sadm'])) {
//         $userIds = User::getDescendantUserIds($user->id);
//         $query->whereIn('id', $userIds);
//     }

//     // -------------------------------
//     // APPLY FILTERS
//     // -------------------------------
//     if ($request->filled('mobile')) {
//         $query->where('mobile', $request->mobile);
//     }
//     if ($request->filled('id')) {
//         $query->where('id', $request->id);
//     }

//     // -------------------------------------------
//     // ✔️ If ROLE filter comes → return ALL results
//     // -------------------------------------------
//     if ($request->filled('role')) {
//         $query->where('role', $request->role);
//         $users = $query->get();       // <-- NO PAGINATION
//     } 
//     else {
//         // normal behaviour
//         if ($request->filled('parent_id')) {
//             $query->where('parent', $request->parent_id);
//         }
//         if ($request->filled('establishment')) {
//             $query->where('establishment', 'like', '%' . $request->establishment . '%');
//         }
    
//         if ($request->filled('export') && $request->export == 1) {
//             $users = $query->get();
//         } else {
//             $perPage = $request->filled('paginate') ? $request->paginate : 15;
//             $users = $query->paginate($perPage);
//         }
//     }

//     // -----------------------------------------------------
//     // ADD PLAN NAME, PARENT DETAILS & FILTER PERMISSIONS
//     // -----------------------------------------------------
//     foreach ($users as $u) {

//         // plan
//         if ($u->plan_id) {
//             $plan = Plan::find($u->plan_id);
//             $u->plan_name = $plan ? $plan->name : null;
//         }

//         // parent establishment
//         if ($u->parent) {
//             $parentUser = User::find($u->parent);
//             $u->parent_establishment = $parentUser ? $parentUser->establishment : null;
//         } else {
//             $u->parent_establishment = null;
//         }

//         // permissions filter
//         if ($u->permissions) {
//             $permissions = $u->permissions->toArray();
//             $allowed = [];

//             if (in_array($u->role, ['ret', 'dd'])) {
//                 $allowed = [
//                     'dmt1','super','vendor','upi','recharge','aeps1','aeps2',
//                     'bbps','credit_card','travel','electricity',
//                     'fund_req','txn','soliteck','bbps_offline'
//                 ];
//             } 
//             elseif ($u->role === 'adm') {
//                 $allowed = [
//                     'fund_req_actions','txn_actions','user_actions','banking',
//                     'fund_req','account','bank','unclaimed','settings',
//                     'add_lien','remove_lien','txn','users'
//                 ];
//             } 
//             elseif (in_array($u->role, ['di','md'])) {
//                 $allowed = [
//                     'users','txn','w_txn','fund_req','user_actions'
//                 ];
//             } 
//             elseif (in_array($u->role, ['asm','zsm'])) {
//                 $allowed = [
//                     'users','txn','w_txn','fund_req'
//                 ];
//             }

//             $filtered = array_intersect_key($permissions, array_flip($allowed));

//             $filtered['id'] = $permissions['id'];
//             $filtered['user_id'] = $permissions['user_id'];

//             $u->setRelation('permissions', collect($filtered));
//         }
//     }

//     return ResponseHelper::success("Users fetched successfully", $users, 200);
// }

     public function getUserDebounce(Request $request)
    {
        $user = Auth::user();
        $query = $request->input('establishment', '');
           if (in_array($user->role, ['ret',])) {
           return ResponseHelper::error('unathorised', [], 403);
        }
        $usersQuery = User::query();
        if (in_array($user->role, ['di', 'md'])) {
            $usersQuery->where('parent', $user->id);
        }
        if (!empty($query)) {
            $usersQuery->where('establishment', 'like', "%{$query}%");
        }
        $users = $usersQuery->select('id', 'establishment')->get();
    
        if ($users->isEmpty()) {
            return ResponseHelper::error('No users found', [], 404);
        }
    
        return ResponseHelper::success('Users fetched successfully', $users, 200);
    }
    public function getUsersDi(Request $request)
    {
        $query = User::where('parent',Auth::user()->id)->orderBy('id', 'desc');
    
        if ($request->filled('mobile')) {
            $query->where('mobile', $request->mobile);
        }
        if ($request->filled('establishment')) {
            $query->where('establishment', $request->establishment);
        }
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        if ($request->filled('paginate')) {
            //$perPage = (int) $request->get('per_page', 15);
            $users = $query->paginate($request->paginate);
        } else {
            $users = $query->paginate(15);
        }
        return ResponseHelper::success("Users fetched successfully", $users, 200);
    }
    
    public function getDi(Request $request)
    {
        if(Auth::user()->role === 'md'){
            $query = User::where('parent',Auth::user()->id)->where('role','di')->orderBy('id', 'desc');
        }if(Auth::user()->role === 'asm'){
            $md = User::select('id')->where('parent',Auth::user()->id)->where('role','md')->get();
            $query = User::whereIn('parent',$md)->where('role','di')->orderBy('id', 'desc');
        }if(Auth::user()->role === 'zsm'){
            $asms = User::select('id')->where('parent',Auth::user()->id)->where('role','asm')->get();
            $md = User::select('id')->where('parent',Auth::user()->id)->where('role','md')->get();
            $query = User::whereIn('parent',$md)->where('role','di')->orderBy('id', 'desc');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile', $request->mobile);
        }
        if ($request->filled('establishment')) {
            $query->where('establishment', $request->establishment);
        }
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }
        if ($request->filled('paginate')) {
            //$perPage = (int) $request->get('per_page', 15);
            $users = $query->paginate($request->paginate);
        } else {
            $users = $query->paginate(15);
        }
        return ResponseHelper::success("Users fetched successfully", $users, 200);
    }
    
    public function getUser(Request $request)
    {
        $user = User::with('permissions')->where("api_token", $request->api_token)->first();

        if (!$user) {
            return ResponseHelper::error("User not found", [], 403);
        }

        return ResponseHelper::success("User fetched successfully", $user, 200);
    }
    public function getRole(Request $request)
    {
        $roles = RoleHelper::getRoles();
        return ResponseHelper::success("Roles fetched successfully", $roles, 200);
    }
    public function blockAndUnblock(Request $request)
    {
        $authUser = Auth::user();
        
        if (!in_array($authUser->role, ["sadm", "adm"])) {
            return ResponseHelper::error("Unauthorized", [], 401);
        }
        $validator = UserValidation::activeInactive($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $mpin=OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        $user = User::find($request->id);
        if (!$user) {
            return ResponseHelper::error("User not found", [], 404);
        }
    
        if ($user->is_active == $request->is_active) {
            $statusMsg = $user->is_active == 1 ? "User already active" : "User already inactive";
            return ResponseHelper::error($statusMsg, [], 409);
        }
        $user->is_active = $request->is_active;
        $user->save();
        return ResponseHelper::success("User status updated successfully", $user, 200);
       }
    public function changeLayout(Request $request)
    {
            $authUser = Auth::user();
        
            // optional role check
            // if (!in_array($authUser->role, ["sadm", "adm"])) {
            //     return ResponseHelper::error("Unauthorized", [], 401);
            // }
        
            $validator = UserValidation::changeLayout($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
            if (!OTP::verifyMpin($request->mpin)) {
                return ResponseHelper::error("Invalid MPIN", [], 403);
            }
            $authUser->is_layout = $request->is_layout;
            $authUser->save();
            return ResponseHelper::success("User layout updated successfully", $authUser, 200);
        }
    // public function updateUserPermissions(Request $request)
    // {
    //         $user=Auth::user();
    //       if (!in_array($user->role, ["sadm", "adm"])) {
    //         return ResponseHelper::error("Unauthorized", [], 403);
    //     }
        
    //         $permissions = Permission::where("user_id", $request->user_id)->first();
        
    //         if (!$permissions) {
    //             return ResponseHelper::error("User not found", [], 403);
    //         }
    //         foreach ($request->all() as $key => $value) {
    //             if (in_array($key, $permissions->getFillable())) {
    //                 $permissions->$key = $value;
    //             }
    //         }
        
    //         $permissions->save();
        
    //         return ResponseHelper::success("Permissions updated successfully", $permissions, 200);
    //     }
        public function updateUserPermissions(Request $request)
            {
                $key = null;
                $value = null;
            
                // FT1 jaisa dynamic key nikaalna
                foreach ($request->all() as $k => $v) {
                    if ($k !== 'user_id' && $k !== 'api_token') {
                        $key = $k;
                        $value = $v;
                    }
                }
            
                if (!$key) {
                    return response()->json(['message' => 'Permission key missing'], 422);
                }
            
                // Check column exists
                if (!\Schema::hasColumn('permissions', $key)) {
                    return response()->json(['message' => 'Invalid permission key'], 422);
                }
            
                if ($request->filled('user_id')) {
            
                    // ðŸ”¹ Single user update
                    \DB::table('permissions')
                        ->where('user_id', $request->user_id)
                        ->update([$key => $value]);
            
                    $msg = "User {$request->user_id} {$key} updated";
            
                } else {
            
                    // ðŸ”¥ Bulk update (sab users)
                    \DB::table('permissions')
                        ->update([$key => $value]);
            
                    $msg = "All users {$key} updated";
                }
            
                return response()->json([
                    'status' => true,
                    'message' => $msg
                ]);
            }

        public function syncCustomersToIMS(Request $request)
        {
         
                $companyKey = env('IMS_COMPANY_KEY');
                $imsUrl     = env('IMS_SYNC_URL');
        
                if (!$companyKey) {
                    return ResponseHelper::error(
                        'IMS company key not configured',
                        [],
                        500
                    );
                }
        
                if (!$imsUrl) {
                    return ResponseHelper::error(
                        'IMS sync URL not configured',
                        [],
                        500
                    );
                }
        
             
                $users = User::with([
                        'businessAddresses:id,user_id,address_line1,city,state,pincode,country'
                    ])
                    ->select('id', 'name', 'mobile', 'email', 'gst', 'pan')
                    ->get();
     
                if ($users->isEmpty()) {
                    return ResponseHelper::success(
                        'No active users found',
                        ['customers' => []]
                    );
                }
        
         
                $customers = $users->map(function ($user) use ($companyKey) {
                    $address = $user->businessAddresses->first();
        
                    return [
                        'user_id' => $user->id,
                        'company_key'      => $companyKey,
                        'name'             => $user->name,
                        'mobile'           => $user->mobile,
                        'email'            => $user->email,
                        'gst'              => $user->gst,
                        'pan'              => $user->pan,
                        'address_line1'    => $address->address_line1 ?? null,
                        'city'             => $address->city ?? null,
                        'state'            => $address->state ?? null,
                        'pincode'          => $address->pincode ?? null,
                        'country'          => $address->country ?? null,
                        'status'           => 'active',
                    ];
                })->values();
        
                try {
                 
                    $imsResponse = Http::timeout(15)
                        ->retry(2, 500)
                        ->withHeaders([
                            'X-KEY'  => $companyKey,
                            'Accept' => 'application/json',
                        ])
                        ->post($imsUrl, [
                            'customers' => $customers
                        ]);
        
                    if (!$imsResponse->successful()) {
                        return ResponseHelper::error(
                            'IMS sync failed',
                            $imsResponse->json() ?? ['body' => $imsResponse->body()],
                            $imsResponse->status()
                        );
                    }
        
                    return ResponseHelper::success(
                        'Customers synced to IMS successfully',
                        [
                            'total_customers' => $customers->count(),
                            'ims_response'    => $imsResponse->json(),
                        ]
                    );
        
                } catch (\Throwable $e) {
                    return ResponseHelper::error(
                        'IMS sync exception',
                        ['message' => $e->getMessage()],
                        500
                    );
                }
            }

}
