<?php

namespace App\Modules\Signup\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Permission;
use App\Traits\ApiResponser;
use App\Modules\Signup\Models\SignupValidation;
use App\Modules\Signup\Models\State;
use App\Modules\Signup\Models\District;
use App\Helper\ResponseHelper;
use App\Helper\PasswordHelper;
use Illuminate\Http\Request;
use App\Modules\Kyc\Models\Kyc;
use Illuminate\Support\Facades\Crypt;
use Auth;
use App\Modules\Signup\Models\Validator;
use App\Modules\BusinessDetail\Models\Business;
use App\Modules\BusinessDetail\Models\BusinessContact;
use App\Modules\BusinessDetail\Models\BusinessAddress;
use App\Modules\BusinessDetail\Models\BusinessIdentification;
use App\Modules\BusinessDetail\Models\BusinessBank;
use App\Modules\BusinessDetail\Models\BusinessDocument;
use App\Modules\BusinessDetail\Models\BusinessKyc;
use App\Modules\BusinessDetail\Models\BusinessStatus;
use App\Modules\BusinessDetail\Models\BusinessValidation;
use Illuminate\Support\Facades\Http;
use App\Modules\AuditLog\Models\AuditLog;
use DB;
use Illuminate\Support\Str;
class SignupController extends BaseController
{
    use ApiResponser;
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['getState','getDistrict']]);
        $this->middleware('check.active', ['except' => 'getState','getDistrict']);
    }
    //  public function signup(Request $request)
    // {
        
        
    //     $user = Auth::user();
    //       $validator = SignupValidation::signup($request->all());
    //         if ($validator->fails()) {
    //               return ResponseHelper::error($validator->errors(),"validation error",  422);
    //         }
    //     if (!in_array($user->role, ['adm', 'di', 'sadm', 'md'])) {
    //         return ResponseHelper::error("Unauthorised Access", '', 401);
    //     }
    //     if($user->role=="di"&&$request->role!="ret"){
    //          return ResponseHelper::error("Di can create only Retailer", '', 403);
    //     }
        
    //         $usermatch = User::where('pan', $request->user['pan'])
    //                  ->where('role', $request->role)
    //                  ->first();
    
    // if ($usermatch) {
    //     return ResponseHelper::error("Sorry, You Can Not Create User With Two Same Roles Using Same PAN.",'', 403);
    // }
    //       $parent=User::find($user->role == 'di' ? $user->id : $request->parent);
          
    //     try {
    //         DB::beginTransaction();
    
    //         // 1. User Table
    //         $newUser = new User();
    //         $newUser->name   = $request->user['name'];
    //         $newUser->email  = $request->user['email'];
    //         $newUser->mobile = $request->user['mobile'];
    //         $newUser->role   = $request->role;
    //         $newUser->pan = $request->user['pan'];
    //         $newUser->gst = $request->user['gst'];
    //         $newUser->w1   =  0;
    //         $newUser->api_token = Str::random(60);
    //         $newUser->w2   = 0 ;
    //         $newUser->lien   = 0 ;
    //         $newUser->establishment   = $request->business['business_name'];
    //         $newUser->status = 3;
    //         $newUser->plan_id = $parent->plan_id??1;
    //         $newUser->parent = $user->role == 'di'||  $user->role == 'md'? $user->id : $request->parent;
    //         $newUser->save();
    
    //         // 2. Permission Table
    //         $permission = new Permission();
    //         $permission->user_id  = $newUser->id;
    //         $permission->dmt1     = 0;
    //         $permission->vendor   = 0;
    //         $permission->recharge = 0;
    //         $permission->save();
    
    //         // 3. Business Table
    //         if ($request->has('business')) {
    //             $business = new Business();
    //             $business->user_id       = $newUser->id;
    //             $business->business_name = $request->business['business_name'] ?? null;
    //             $business->save();
    //         }
    
    //         // 4. Business Contact Table
    //         if ($request->has('business_contact')) {
    //             $contact = new BusinessContact();
    //             $contact->user_id         = $newUser->id;
    //             $contact->business_email  = $request->business_contact['business_email'] ?? null;
    //             $contact->business_mobile = $request->business_contact['business_mobile'] ?? null;
    //             $contact->save();
    //         }
    
    //         // 5. Business Address Table
    //         if ($request->has('business_address')) {
    //             $address = new BusinessAddress();
    //             $address->user_id       = $newUser->id;
    //             $address->address_line1 = $request->business_address['address_line1'] ?? null;
    //             $address->address_line2 = $request->business_address['address_line2'] ?? null;
    //             $address->city          = $request->business_address['city'] ?? null;
    //             $address->state         = $request->business_address['state'] ?? null;
    //             $address->pincode       = $request->business_address['pincode'] ?? null;
    //             $address->country       = "India";
    //             $address->save();
    //         }
    
    //         // 6. Business Identification Table
    //         if ($request->has('business_identification')) {
    //             $ident = new BusinessIdentification();
    //             $ident->user_id    = $newUser->id;
    //             $ident->pan_number = $request->business_identification['pan_number'] ?? null;
    //             $ident->gst_number = $request->business_identification['gst_number'] ?? null;
    //             $ident->save();
    //         }
    
    //         DB::commit();
    //       $this->syncUserToIMS($newUser, $address);

    //         return ResponseHelper::success('User Created Successfully', $newUser, 201);
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('User Cannot Be Created', ['error' => $e->getMessage()], 500);
    //     }
    // }
  
  public function signup(Request $request)
{
    $user = Auth::user();

    $validator = SignupValidation::signup($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error($validator->errors(), "validation error", 422);
    }

    if (!in_array($user->role, ['adm', 'di', 'sadm', 'md'])) {
        return ResponseHelper::error("Unauthorised Access", '', 401);
    }

    if ($user->role == "di" && $request->role != "ret") {
        return ResponseHelper::error("Di can create only Retailer", '', 403);
    }

    $usermatch = User::where('pan', $request->user['pan'])
        ->where('role', $request->role)
        ->first();

    if ($usermatch) {
        return ResponseHelper::error(
            "Sorry, You Can Not Create User With Two Same Roles Using Same PAN.",
            '',
            403
        );
    }

    $parent = User::find(
        $user->role == 'di' ? $user->id : $request->parent
    );

    try {
        DB::beginTransaction();

        // 1️⃣ USER TABLE
        $newUser = new User();
        $newUser->name   = $request->user['name'];
        $newUser->email  = $request->user['email'];
        $newUser->mobile = $request->user['mobile'];
        $newUser->role   = $request->role;
        $newUser->pan    = $request->user['pan'];
        $newUser->gst    = $request->user['gst'];
        $newUser->w1     = 0;
        $newUser->w2     = 0;
        $newUser->lien   = 0;
        $newUser->api_token = Str::random(60);
        $newUser->establishment = $request->business['business_name'];
        $newUser->status = 3;
        $newUser->plan_id = $parent->plan_id ?? 1;
        $newUser->parent  = in_array($user->role, ['di','md'])
                            ? $user->id
                            : $request->parent;
        $newUser->save();

        // 2️⃣ PERMISSION
        Permission::create([
            'user_id'  => $newUser->id,
            'dmt1'     => 0,
            'vendor'   => 0,
            'recharge' => 0
        ]);

        // 3️⃣ BUSINESS
        if ($request->has('business')) {
            Business::create([
                'user_id'       => $newUser->id,
                'business_name' => $request->business['business_name'] ?? null,
            ]);
        }

        // 4️⃣ BUSINESS CONTACT
        if ($request->has('business_contact')) {
            BusinessContact::create([
                'user_id'         => $newUser->id,
                'business_email'  => $request->business_contact['business_email'] ?? null,
                'business_mobile' => $request->business_contact['business_mobile'] ?? null,
            ]);
        }

        // 5️⃣ BUSINESS ADDRESS
        $address = null;
        if ($request->has('business_address')) {
            $address = BusinessAddress::create([
                'user_id'       => $newUser->id,
                'address_line1' => $request->business_address['address_line1'] ?? null,
                'address_line2' => $request->business_address['address_line2'] ?? null,
                'city'          => $request->business_address['city'] ?? null,
                'state'         => $request->business_address['state'] ?? null,
                'pincode'       => $request->business_address['pincode'] ?? null,
                'country'       => 'India',
            ]);
        }

        DB::commit();

     
        $this->syncUserToIMS($newUser, $address);

        return ResponseHelper::success(
            'User Created Successfully',
            $newUser,
            201
        );

    } catch (\Exception $e) {
        DB::rollBack();
        return ResponseHelper::error(
            'User Cannot Be Created',
            ['error' => $e->getMessage()],
            500
        );
    }
}

     public function EditUser(Request $request)
    {
        
        
        $user = Auth::user();
          $validator = SignupValidation::editUser($request->all());
            if ($validator->fails()) {
                  return ResponseHelper::error($validator->errors(),"validation error",  422);
            }
        if (!in_array($user->role, ['adm', 'di', 'sadm', 'md'])) {
            return ResponseHelper::error("Unauthorised Access", '', 401);
        }
    
        try {
            DB::beginTransaction();
    
            // 1. User Table
            $newUser = User::find($request->id);
            $newUser->name   = $request->user['name'];
            $newUser->email  = $request->user['email'];
            $newUser->mobile = $request->user['mobile'];
            $newUser->role   =  $request->role;
            $newUser->pan = $request->user['pan'];
            $newUser->gst = $request->user['gst'];
            $newUser->establishment   = $request->business['business_name'];
            // $newUser->status = 8;
            $newUser->parent = $request->parent;
            $newUser->save();
    
            // // 2. Permission Table
            // $permission = new Permission();
            // $permission->user_id  = $newUser->id;
            // $permission->dmt1     = 0;
            // $permission->vendor   = 0;
            // $permission->recharge = 0;
            // $permission->save();
    
            // 3. Business Table
            if ($request->has('business')) {
                $business =Business::where("user_id",$request->id)->first();
                $business->user_id       = $newUser->id;
                $business->business_name = $request->business['business_name'] ?? null;
                $business->save();
            }
    
            // 4. Business Contact Table
            // if ($request->has('business_contact')) {
            //     $contact = new BusinessContact();
            //     $contact->user_id         = $newUser->id;
            //     $contact->business_email  = $request->business_contact['business_email'] ?? null;
            //     $contact->business_mobile = $request->business_contact['business_mobile'] ?? null;
            //     $contact->save();
            // }
    
            // 5. Business Address Table
            if ($request->has('business_address')) {
                $address =BusinessAddress::where("user_id",$request->id)->first();
                $address->user_id       = $newUser->id;
                $address->address_line1 = $request->business_address['address_line1'] ?? null;
                $address->address_line2 = $request->business_address['address_line2'] ?? null;
                $address->city          = $request->business_address['city'] ?? null;
                $address->state         = $request->business_address['state'] ?? null;
                $address->pincode       = $request->business_address['pincode'] ?? null;
                $address->country       = "India";
                $address->save();
            }
    
            // 6. Business Identification Table
            if ($request->has('business_identification')) {
                $ident = new BusinessIdentification();
                $ident->user_id    = $newUser->id;
                $ident->pan_number = $request->business_identification['pan_number'] ?? null;
                $ident->gst_number = $request->business_identification['gst_number'] ?? null;
                $ident->save();
            }
    
            DB::commit();
    
            return ResponseHelper::success('User Created Successfully', $newUser, 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('User Cannot Be Created', ['error' => $e->getMessage()], 500);
        }
    }
    
//   public function signup(Request $request)
//     {
//         // Validate input
//         $validator = signupValidation::signup($request->all());
//         if ($validator->fails()) {
//             return ResponseHelper::error("Validation error", $validator->errors(), 422);
//         }

//         $user = Auth::user();
//         if(!in_array($user->role,['adm','di','sadm','md'])){
//             return ResponseHelper::error("Unauthorised Access", '', 422);
//         }
//         if($user->role === 'di'){
//             $role = 'ret';
//         }else{
//             $role = $request->role;
//         }

//         try {
//             DB::beginTransaction();

//             // Create user
//             $newUser = User::create([
//                 'name'      => $request->name,
//                 'email'     => $request->email,
//                 'mobile'    => $request->mobile,
//                 'establishment'    => $request->business_name,
//                 'role'      => $role,
//                 'status'    => 8,
//                 'parent' => $user->role == 'di' ? $user->id : $request->parent,
//             ]);

//             // Create default permissions
//             $permission = new Permission();
//             $permission->user_id = $newUser->id;
//             $permission->dmt1 = 0;
//             $permission->vendor = 0;
//             $permission->recharge = 0;
//             $permission->save();

//             // Audit log
//             AuditLog::create([
//                 'user_id'      => $user->id,
//                 'role'         => $user->role,
//                 'action'       => 'CREATE_USER',
//                 'service_name' => 'user_signup',
//                 'request_data' => json_encode($request->all()),
//                 'response_data'=> json_encode($newUser),
//                 'ip_address'   => $request->ip(),
//                 'user_agent'   => $request->header('User-Agent'),
//                 'status'       => 'Success'
//             ]);

//             DB::commit();

//             return ResponseHelper::success('User Created Successfully', $newUser, 201);

//         } catch (\Exception $e) {
//             DB::rollBack();
//             return ResponseHelper::error('User Cannot Be Created', ['error' => $e->getMessage()], 500);
//         }
//     }

            public function getSignupSchema(){
       $schema = signupValidation::getSignupSchema();
            return ResponseHelper::success('Schema fetch successfully', $schema, 200);
      
        }
            public function getState(){
           $state = State::get();
                return ResponseHelper::success('Schema fetch successfully', $state, 200);
          
            }
         public function getDistrict(Request $request)
            {
                $statuscode = $request->status_code;
            
                // If no status_code given → return all data
                if (!$statuscode) {
                    $districts = District::all();
                    return ResponseHelper::success('All district data fetched successfully', $districts, 200);
                }
            
                // If status_code is given → filter
                $districts = District::where("status_code", $statuscode)->get();
            
                // If no data found
                if ($districts->isEmpty()) {
                    return ResponseHelper::error('No district found for this status_code', [], 404);
                }
            
                // Success response
                return ResponseHelper::success('District data fetched successfully', $districts, 200);
            }

// public function getTxnId(Request $request)
// {
//     $user = Auth::user();

//     // Generate txn id
//     $txn = "TXN" . time() . rand(1000, 9999);

//     // Save txn id
//     $txnId = new FundTxnId();
//     $txnId->user_id = $user->id;
//     $txnId->txn_id = $txn;
//     $txnId->save();

//     return ResponseHelper::success('Transaction ID generated successfully', [
//         'txn_id' => $txnId->txn_id,
//         'user_id' => $txnId->user_id
//     ], 200);
// }

// public function getFundRequests(Request $request)
// {
//     $user = Auth::user();
//   $query = FundRequest::query();

//     if (!in_array($user->role, ["sadm", "adm"])) {
//         $query->where("user_id", $user->id);
//     }

  
//     if ($request->has('status') && !empty($request->status)) {
//         $query->where('status', $request->status);
//     }


//     $fundRequests = $query->orderBy('id', 'desc')->get();

//     return ResponseHelper::success('FundRequests fetched', $fundRequests, 200);
// }

// public function getFundRequestsSchema()
// {
//     $schema=FundRequestValidation::getSchema();
//     return ResponseHelper::success('FundRequests fetched', $schema, 200);
// }


//     public function createFundRequest(Request $request)
//     {
//         $user = Auth::user();
//         $validator = FundRequestValidation::createFundRequest($request->all());

//         if ($validator->fails()) {
//             return ResponseHelper::error("Validation error", $validator->errors(), 422);
//         }
          
//           $account=Account::where("user_id",$user->id)->first();
//           if (!$account) {
//           return ResponseHelper::error('Not Authorised. Please contact your administrator.', [], 403);
//             }
//     $txnData=FundTxnId::where("txn_id",$request->txn_id)->first();
//   if (!$txnData) {
//     return ResponseHelper::error('Invalid transaction ID', [], 403);
//      }

//         if (!$user || !in_array($user->role, ["ret", "dd", "asm", "ad", "md"])) {
//             return ResponseHelper::error('Operation Not Authorised', [], 403);
//         }

//         $fundRequest = new FundRequest();
//         $fundRequest->bank_name   = $request->bank_name;
//         $fundRequest->status      = "pending";
//         $fundRequest->asm_id      = $user->parent_id;
//          $fundRequest->user_id      = $user->id;
//         $fundRequest->name        = $user->name;
//         $fundRequest->mode        = $request->mode;
//         $fundRequest->bank_ref_id = $request->bank_ref_id;
//         $fundRequest->date        = $request->date;
//         $fundRequest->amount      = $request->amount;
//         $fundRequest->remark      = $request->remark;
//         $fundRequest->txn_id      = $request->txn_id;

//         if ($fundRequest->save()) {
           
//             AuditLog::create([
//                 'user_id'      => $user->id,
//                 'role'         => $user->role,
//                 'action'       => 'CREATE_FUND_REQUEST',
//                 'service_name' => 'fund_request',
//                 'request_data' => json_encode($request->all()),
//                 'response_data'=> json_encode($fundRequest),
//                 'ip_address'   => $request->ip(),
//                 'user_agent'   => $request->header('User-Agent'),
//                 'status'       => 'Success'
//             ]);

//             return ResponseHelper::success('FundRequest Created Successfully', $fundRequest, 200);
//         } else {
//             return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
//         }
//     }

// public function updateFundRequest(Request $request)
// {
//     $user = Auth::user();


//     $validator = FundRequestValidation::updateFundRequest($request->all());
//     if ($validator->fails()) {
//         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
//     }


//     if (!in_array($user->role, ["sadm", "adm"])) {
//         return ResponseHelper::error('Operation Not Authorised', '', 401);
//     }


//     $fundRequest = FundRequest::find($request->id);
//     if (!$fundRequest) {
//         return ResponseHelper::error('Request Not Found', '', 403);
//     }


//   if ($fundRequest->status !== 'pending') {
//     return ResponseHelper::error(
//     'FundRequest Already ' . strtoupper($fundRequest->status),
//     '',
//     400
// );

// }

   
//     $userData = User::find($fundRequest->user_id);
//     if (!$userData) {
//         return ResponseHelper::error('User Not Found', [], 403);
//     }

   
//     if ($request->status === "approved") {
//         $userData->w1 += $fundRequest->amount; 
//         $userData->save();
//         $fundRequest->status = "approved";
//     } elseif ($request->status === "rejected") {
//         $fundRequest->status = "rejected";
//     }


//     if ($fundRequest->save()) {
//         AuditLog::create([
//             'user_id'      => $user->id,
//             'role'         => $user->role,
//             'action'       => 'UPDATE_FUND_REQUEST',
//             'service_name' => 'fund_request',
//             'request_data' => json_encode($request->all()),
//             'response_data'=> json_encode($fundRequest),
//             'ip_address'   => $request->ip(),
//             'user_agent'   => $request->header('User-Agent'),
//             'status'       => 'Success'
//         ]);

//         return ResponseHelper::success('FundRequest Updated Successfully', $fundRequest, 200);
//     } else {
//         return ResponseHelper::error('FundRequest Cannot Be Updated', '', 500);
//     }
// }

    private function syncUserToIMS($user, $address = null)
    {
        try {
    
            $payload = [
                'customers' => [
                    [
                        'user_id'       => $user->id,
                        'name'          => $user->name,
                        'mobile'        => $user->mobile,
                        'email'         => $user->email,
                        'gst'           => $user->gst,
                        'pan'           => $user->pan,
                        'address_line1' => $address->address_line1 ?? null,
                        'city'          => $address->city ?? null,
                        'state'         => $address->state ?? null,
                        'pincode'       => $address->pincode ?? null,
                        'status'        => 'active',
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'X-KEY' => env('IMS_COMPANY_KEY'),
                'Accept' => 'application/json',
            ])->post(env('IMS_SYNC_URL'), $payload);
    
       
    
        } catch (\Exception $e) {
            \Log::error('IMS SYNC FAILED', [
                'error' => $e->getMessage()
            ]);
        }
    }

}
