<?php

namespace App\Modules\Bbps\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Modules\Bbps\Models\BbpsTxn;
use App\Modules\Bbps\Models\BbpsCategorie;
use App\Modules\Bbps\Models\BbpsValidation;
use App\Modules\Integration\Models\Ipay;
use Auth;
use App\Modules\Service\Models\Service;
use App\Modules\Bbps\Services\BbpsService; 
use App\Modules\Commission\Models\CommRule;
use App\Modules\Auth\Models\Otp;
use App\Modules\User\Models\User;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Bbps\Services\BbpsResponseHandler;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Misc\Models\Uuid;
class BbpsController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);

    }
    // public function getBbpsTxns(Request $request)
    // {
    //     $user = Auth::user(); // Lumen Auth
    
    //     $query =BbpsTxn::with('user'); // eager load user
    
    // //     if($user->role == 'ret' || $user->role == 'dd'|| $user->role == 'api'){
    // //             $query->where('user_id', $user->id);
    // //         }elseif (in_array($user->role, ['md', 'di'])) {
    // //             $userIds = User::getDescendantUserIds($user->id);
    // //             $query->whereIn('user_id', $userIds);
    // //     }
    // //     if ($request->filled('consumer_number')) {
    // //         $query->where('consumer_number', $request->consumer_number);
    // //     }
    // //     if ($request->filled('status')) {
    // //         $query->where('status', strtoupper($request->status));
    // //     }
    // //     if ($request->filled('user_id')) {
    // //         $query->where('user_id', $request->user_id);
    // //     }
    // //     if ($request->filled('route')) {
    // //         $query->where('route', $request->route);
    // //     }
    // //     if ($request->filled('txn_id')) {
    // //         $query->where('txn_id', $request->txn_id);
    // //     }
    // //      if ($request->filled('from_date') && $request->filled('to_date')) {
    // //      $query->whereDate('created_at', '>=', $request->from_date)
    // //           ->whereDate('created_at', '<=', $request->to_date);
    // //      }
     
    // //     if ($request->input('export') == 1) {
    // //         $txns = $query->latest()->get();
    // //         $txns->transform(function ($txn) {
    // //             $txn->establishment = $txn->user->establishment ?? null;
                
    // //             unset($txn->user);
    // //             return $txn;
    // //         });
    // //         return ResponseHelper::success('Transactions fetched', $txns, 200);
    // //     } else {
    // //         $paginate = $request->input('paginate', 15);
    // //         $txns = $query->latest()->paginate($paginate);
    // //     }
    // //     // Map user name or establishment
    // //     $txns->getCollection()->transform(function ($txn) {
    // //         $txn->establishment = $txn->user->establishment ?? $txn->user->name ?? 'Unknown';
    // //         unset($txn->user); // optional: remove full user object
    // //         return $txn;
    // //     });
    // //   return ResponseHelper::success('Transactions fetched', $txns, 200);
    //         switch ($user->role) {
    //         case 'sadm':
    //         case 'adm':
    //             if($request->boolean('export')) {
    //               $query->select([
    //                     'id', 'txn_id', 'user_id', 'consumer_number', 'biller_name', 'customer_mobile', 'biller_id ',
    //                     'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm',
    //                     'charges','gst','status','created_at','updated_at'
    //                 ]); 
    //             }
    //             break;
    //         case 'md':
    //             $userIds = User::getDescendantUserIds($user->id);
    //             $userIds = array_merge([$user->id], $userIds ?? []);
    //             $query->select([
    //                 'id', 'txn_id', 'user_id', 'pf', 'consumer_number', 'biller_name','biller_id',
    //                 'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','charges','gst','status','created_at','updated_at'
    //             ])->whereIn('user_id', $userIds);
    //             break;
    //         case 'di':
    //             $userIds = User::getDescendantUserIds($user->id);
    //             $userIds = array_merge([$user->id], $userIds ?? []);
    //             $query->select([
    //                 'id', 'txn_id', 'user_id', 'pf', 'consumer_number', 'biller_name','biller_id',
    //                 'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','charges','gst','status','created_at','updated_at'
    //             ])->whereIn('user_id', $userIds);
    //             break;
    //         case 'dd':
    //         case 'api':
    //         case 'ret':
    //             $query->select([
    //             'id', 'txn_id', 'user_id', 'pf', 'consumer_number', 'biller_name','biller_id',
    //             'operator','operator_id','amount','ret_comm','ret_tds','charges','gst','status','created_at','updated_at'
    //         ])->where('user_id', $user->id);
    //             break;
    //         default:
    //         return ResponseHelper::error('Unauthorized role access', [], 403);
    //     }
    //     $query
    //         ->when($request->filled('status'), fn($q) => 
    //             $q->where('status', strtoupper($request->status))
    //         )
    //         ->when($request->filled('mobile_number'), fn($q) => 
    //             $q->where('mobile_number', $request->mobile_number)
    //         )
    //         ->when($request->filled('account_number'), fn($q) => 
    //             $q->where('account_number', $request->account_number)
    //         )
    //         ->when($request->filled('user_id'), fn($q) => 
    //             $q->where('user_id', $request->user_id)
    //         )
    //         ->when($request->filled('route'), fn($q) => 
    //             $q->where('route', $request->route)
    //         )
    //         ->when($request->filled('txn_id'), fn($q) => 
    //             $q->where('txn_id', $request->txn_id)
    //         )
    //           ->when($request->filled('service'), fn($q) => 
    //             $q->where('operator', $request->service)
    //         )
    //         ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) => 
    //             $q->whereBetween('created_at', [
    //                 $request->from_date . ' 00:00:00',
    //                 $request->to_date . ' 23:59:59'
    //             ])
    //         );
    //     if ($request->boolean('export')) {
    //         $txns = $query->latest()->get()->map(function ($txn) {
    //             $txn->establishment = $txn->user->establishment ?? 'Unknown';
    //             unset($txn->user);
    //             return $txn;
    //         });
    //         return ResponseHelper::success('Transactions fetched', $txns, 200);
    //     }
    //     $txns = $query->latest()->paginate($request->input('paginate', 15));
    //     $txns->getCollection()->transform(function ($txn) {
    //         $txn->establishment = $txn->user->establishment ?? 'Unknown';
    //         unset($txn->user);
    //         return $txn;
    //     });
    //     return ResponseHelper::success('Transactions fetched', $txns, 200);
    //     // return response()->json([
    //     //     'status' => 'success',
    //     //     'message' => 'Transactions fetched',
    //     //     'data' => $txns
    //     // ], 200);
    // }
    public function getBbpsTxns(Request $request){
        $user = Auth::user();
    
        $query = BbpsTxn::with('user');
        $allFields = [
            'id','user_id','txn_id','client_ref','route','pf','biller_id','biller_name',
            'consumer_number','customer_mobile','amount','charges','ret_comm','di_comm',
            'md_comm','a_comm','di_tds','md_tds','operator','operator_id','gst','ret_tds',
            'status','bill_fetch_response','api_response','created_at','updated_at'
        ];
        $hideForMD = ['route','a_comm','api_response'];  
        $hideForDI = ['md_comm','md_tds','a_comm','route','api_response']; 
        $hideForRET = ['di_comm','di_tds','md_comm','md_tds','a_comm','route','api_response']; 
        switch ($user->role) {
            case 'sadm':
            case 'adm':
                $allowed = $allFields; 
                break;
    
            case 'md':
                $allowed = array_diff($allFields, $hideForMD);
                $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
                $query->whereIn('user_id', $userIds);
                break;
    
            case 'di':
                $allowed = array_diff($allFields, $hideForDI);
                $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
                $query->whereIn('user_id', $userIds);
                break;
    
            case 'dd':
            case 'api':
            case 'ret':
                $allowed = array_diff($allFields, $hideForRET);
                $query->where('user_id', $user->id);
                break;
    
            default:
                return ResponseHelper::error('Unauthorized role access', [], 403);
        }
    
        // Apply final select fields
        $query->select($allowed);
    
        // Filters
        $query
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', strtoupper($request->status))
            )
            ->when($request->filled('consumer_number'), fn($q) =>
                $q->where('consumer_number', $request->consumer_number)
            )
            ->when($request->filled('user_id'), fn($q) =>
                $q->where('user_id', $request->user_id)
            )
            ->when($request->filled('route'), fn($q) =>
                $q->where('route', $request->route)
            )
            ->when($request->filled('txn_id'), fn($q) =>
                $q->where('txn_id', $request->txn_id)
            )
             ->when($request->filled('customer_mobile'), fn($q) =>
                $q->where('customer_mobile', $request->customer_mobile)
            )
            ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) =>
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59'
                ])
            );
    
        // Export mode
        if ($request->boolean('export')) {
              ini_set('memory_limit', '512M');
            $txns = $query->latest()->get()->map(function ($txn) {
                $txn->establishment = $txn->user->establishment ?? 'Unknown';
                unset($txn->user);
                return $txn;
            });
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
    
        // Pagination
        $txns = $query->latest()->paginate($request->input('paginate', 15));
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ?? 'Unknown';
            unset($txn->user);
            return $txn;
        });
    
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }
    public function getCategories(Request $request){
        $user = Auth::user();
        $categories = BbpsCategorie::all();
        return ResponseHelper::success('Categories Fetched Successfully', $categories, 200);
    }
    public function updateCategories(){
        $user = Auth::user();
        $data['ip'] = $request->ip();
        $data['url'] = 'https://api.instantpay.in/marketplace/utilityPayments/category';
        $data['payload'] = '';
        $data['method'] = 'GET';
        $response = Ipay::service($data);
        if(isset($response)){
            if($response['statuscode'] == 'TXN'){
                foreach($response['data'] as $data){
                    $cat = BbpsCategorie::where('category_key',$data['categoryKey'])->first();
                    if(!$cat){
                        $category = new BbpsCategorie();
                        $category->category_key = $data['categoryKey'];
                        $category->category_name = $data['categoryName'];
                        $category->icon_url = $data['iconUrl'];
                        $category->biller_list = $data['billerList'];
                        $category->save();
                    }
                }
                return ResponseHelper::success('Categories Updated Successfully', $response['data'], 200);
            }else{
                return ResponseHelper::error('Service Provider Down', '', 403);
            }
        }
        return ResponseHelper::error('No Response Recieved', '', 403);
    }
    public function getBillers(Request $request){
        $rules = [
            'category_key' => 'required',
            'page_number'  => 'sometimes'
        ];
    
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::error($validator->getMessageBag()->toArray(), '', 403);
        }
    
        $data = $request->all();
        $data['ip'] = $request->ip();
    
        $outlet_id    = Auth::user()->instId ?? 243829;
        $category_key = $request->category_key;
    
        $url = 'https://api.impsguru.com/prod/v1/bbps/getBillers';
        $payload = json_encode([
            'outlet_id'    => $outlet_id,
            'categoryKey' => $category_key
        ]);
    
        $method   = 'POST';
        $response = Impsguru::guruRequest($url, $method, $payload, '');
      return $response;
        if ($response) {
            if (isset($response['statuscode']) && $response['statuscode'] === 'TXN') {
                return ResponseHelper::success('Categories Updated Successfully', $response['data'], 200);
            } else {
                return ResponseHelper::error('Service Provider Down', $response, 403);
            }
        }
    
        return ResponseHelper::error('No Response Received', '', 403);
    }
    public function getBillerDetails(Request $request){
        $rules = array('biller_id' => 'required');
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::error($validator->getMessageBag()->toArray(), '', 403);
        }
        $data = $request->all();
        $data['ip'] = $request->ip();
        $outlet_id = Auth::user()->instId ?? 243829;
        $url = 'https://api.impsguru.com/prod/v1/bbps/getBillerDetails';
        $payload = json_encode([
            'outlet_id'    => $outlet_id,
            'billerId' => $data['biller_id']
        ]);
    
        $method = 'POST';
        $response = Impsguru::guruRequest($url, $method, $payload, '');
        // return $response;
        if(isset($response)){
            // if($response['statuscode'] == 'TXN'){
                return ResponseHelper::success('Categories Updated Successfully', $response['response'], 200);
            }
           

        else{
                return ResponseHelper::error('Service Provider Down', '', 403);
            }
        }
    public function fetchBill(Request $request){
        $rules = [
                'biller_id'  => 'required',
                'param1'     => 'required',
                'client_ref' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'ip'         => 'required|ip|ipv4'
        ];
    // return "djhfgsjkdg";
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::error($validator->getMessageBag()->toArray(), '', 403);
        }
        $outlet_id = Auth::user()->instId ?? 243829;
        $user   = Auth::user();
        $param2 = $request->param2 ?? '';   // corrected (was using param1 again)
        $param3 = $request->param3 ?? '';
        $ip     = $request->ip;
    
        $url = 'https://api.impsguru.com/prod/v1/bbps/fetchBill';
    // AGT
        $payload = [
            "billerId"          => $request->biller_id,
            "initChannel"       => "AGT",
            "externalRef"       => $request->client_ref,
            "outlet_id"    => $outlet_id,
            "param1"            => $request->param1,
            "param2"            => $param2,
            "param3"            => $param3,
             "latitude"            => $request->latitude??0,
            "longitude"            => $request->longitude??0,
            "deviceInfo"        => [
                "mac" => "88-18-18-CD-82-3C",
                "ip"  => $ip
            ],
            "remarks"           => [
                "param1" => $user->mobile
            ],
            "transactionAmount" => 10
        ];
    
        $data['method']  = 'POST';
        $data['payload'] = json_encode($payload);
        // return $data['payload'] ;
        // Call the API
        $response = Impsguru::guruRequest($url, $data['method'], $data['payload'], '');
  
        if ($response && isset($response['response']['statuscode'])) {
            $statusCode = $response['response']['statuscode'];
            if($statusCode === 'TXN'){
                return ResponseHelper::success(
                    'Bill fetched successfully',
                    $response['response']['data'],
                    200
                );
            }elseif ($statusCode === 'ERR') {
                return ResponseHelper::error(
                    $response['response']['message'],
                    $response['response'],
                    403
                );
            }
            return ResponseHelper::error('Service provider downtime', [], 403);
        }

    }
    public function payBill(Request $request){
     $user = Auth::user();
     $dupCheck = Uuid::check($request->client_ref,'payOut2',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $validator = BbpsValidation::payBill($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', [], 403);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }
        
    
        $service = Service::where('code', $request->cat)->first();
        if (!$service) {
            return ResponseHelper::error('Invalid Service Category', [], 404);
        }
        if ($service->route != "IMPSGURU" && $service->route != "IMGURU") {
        return ResponseHelper::error('Invalid route', [], 403);
        }

    
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
         if(!$comm){
                return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $final_comm = CommRule::finalCommission($comm, $request->amount, $service);
  
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        if(!$a_comm){
                return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
        $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        $requiredBalance = ($request->amount + $user->lien + ($final_comm['ret'] ?? $final_comm['dd'])) * 100;
        if ($user->w1 < $requiredBalance) {
            return ResponseHelper::error('Insufficient W1 Balance', [], 403);
        }
        DB::beginTransaction();
        try {
            $bbpsTxn = BbpsService::create($user, $service, $request, $final_comm,$a_comm);
            WalletService::debit($user, $request->amount, $bbpsTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
         DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Recharge Init Failed', $e->getMessage(), 500);
        }
            $outlet_id = $user->instId ?? 24344;
            $param3 = $request->param3 ?? null;
            $location = number_format((float)$request->latitude, 4, '.', '') . ',' .
                        number_format((float)$request->longitude, 4, '.', '');
    
            $hashData = $request->param1 . $request->billerId . $request->biller_name . $request->amount . "17" . $bbpsTxn->txn_id . env("GURU_HKEY");
            $hash = hash('sha256', $hashData);
    
            $payload = json_encode([
                "billerId"            => $request->billerId,
                "externalRef"         => $bbpsTxn->txn_id,
                "outlet_id"           => $outlet_id,
                "enquiryReferenceId"  => $request->enquiryReferenceId,
                "param1"              => $request->param1,
                "param2"              => $request->param2 ?? '8447445497',
                "mobileNumber"        => $user->mobile,
                "param3"              => $param3,
                "billerName"          => $request->biller_name,
                "operatorCode"        => 17,
                "cat"                 => $request->cat,
                "client_id"           => $bbpsTxn->txn_id,
                 "pan"                => $request->pan??null,
                "latitude"            => $request->latitude,
                "longitude"           => $request->longitude,
                "env"                 => "LIVE",
                "generatedHash"       => $hash,
                "initChannel"         => "AGT",
                "deviceInfo"          => [
                    "terminalId" => "12813923",
                    "mobile"     => $user->username,
                    "postalCode" => "110085",
                    "geoCode"    => $location,
                ],
                "paymentMode"  => "Cash",
                "paymentInfo"  => ["Remarks" => "CashPayment"],
                "remarks"      => ["param1" => $user->username],
                "transferAmount" => $request->amount,
            ], JSON_UNESCAPED_SLASHES);
            $response = Impsguru::guruRequest('https://api.impsguru.com/prod/v1/bbps/payBill', 'POST', $payload, '');
            DB::beginTransaction();
             try {
   
            $result = BbpsResponseHandler::handle($bbpsTxn, $response['response'], $user, $service, $comm);
            AuditService::updateResponse($auditLog, $response);
    
            DB::commit();
            return $result;
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }
}
