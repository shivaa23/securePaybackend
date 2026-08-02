<?php

namespace App\Modules\Recharge\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Recharge\Models\RechargeTxn;
use App\Modules\Recharge\Models\RechargeValidation;
use App\Modules\Service\Models\Service;
use Auth;
use App\Helper\ResponseHelper;
use App\Modules\User\Models\User;
use App\Modules\Auth\Models\Otp;
use App\Modules\Commission\Models\CommRule;
use App\Modules\Integration\Models\Recharge;
use App\Modules\Integration\Models\Mplan;
use App\Modules\Recharge\Services\RechargeService;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\Recharge\Services\RechargeResponseHandler;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Commission\Models\AcommRule;
use App\Modules\Misc\Models\Uuid;
use App\Modules\Integration\Models\Indicore;
class RechargeController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
    }

    public function recharge(Request $request)
    {
        $validator = RechargeValidation::recharge($request->all());
        if ($validator->fails()) {
            return $this->jsonResponse('1000', 'Failed', $validator->errors(), '', 422);
        }
       $dupCheck = Uuid::check($request->client_ref,'recharge',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $user = Auth::user();
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
       $mpin = OTP::verifyMpin($request->mpin);
        if(!$mpin){
             return ResponseHelper::error("invalid mpin", [], 403);
        }

        $service = Service::find($request->operator);
        // return $service;
        if(!$service){
          return ResponseHelper::error('Service not found', [], 403);
        }
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        // return $comm;
           if(!$comm){
                return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }
        $totalComm = $comm['ret'] ?? ($comm['dd'] ?? 0); // default 0 if both not set
        $totalRequired = ($request->amount + $user->lien + $totalComm) * 100;
        
        if ($user->w1 < $totalRequired) {
            return ResponseHelper::error('Insufficient W1 Balance', '', 403);
        }
     
        $a_comm = AcommRule::calculateAdminCommission($service->name, $request->amount, $service->route);
        // return $a_comm;
        if(!$a_comm){
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }
        $a_comm = AcommRule::finalCommission($a_comm, $request->amount);
        
        DB::beginTransaction();
        try {
            $rechargeTxn = RechargeService::create($user, $service, $request, $comm,$a_comm);
            // return $rechargeTxn;
            WalletService::debit($user, $request->amount, $rechargeTxn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Recharge Init Failed', $e->getMessage(), 500);
        }
        $response = Recharge::recharge($rechargeTxn, $service);
        // return $response;
        DB::beginTransaction();
        try {
            $result = RechargeResponseHandler::handle($rechargeTxn, $response, $user, $service, $comm);

            AuditService::updateResponse($auditLog, $response);

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Recharge Update Failed', $e->getMessage(), 500);
        }
    }
   public static function fetchPlan(Request $request)
{
    if (empty($request->mobile_number)) {
        return ResponseHelper::error("Mobile number is required", [], 400);
    }
    if (empty($request->operator)) {
        return ResponseHelper::error("Operator code missing", [], 400);
    }
    if (empty($request->circle)) {
        return ResponseHelper::error("Circle is required", [], 400);
    }
    // if (empty($request->client_ref)) {
    //     return ResponseHelper::error("Transaction client_ref is required", [], 400);
    // }

    $service = Service::find($request->operator);

    if (!$service) {
        return ResponseHelper::error('Service not found', [], 403);
    }

    $url = "https://eabi.indicore.net/binfo/";

    $payload = [
        'signature' => env('INDICORE_SIGNATURE'),
        // 'cack'      => $request->client_ref,
        'tel'       => $request->mobile_number,
        'operator'  => $service->indicore,
        'type'      => 'rec',
        'circle'    => strtoupper($request->circle),
    ];

    $indicoreResponse = Indicore::service($url, 'POST', $payload);

    if (!$indicoreResponse) {
        return ResponseHelper::error("Service Down or No Response from Operator", [], 500);
    }

    $response = json_decode($indicoreResponse, true);

    if (!$response) {
        return ResponseHelper::error("Invalid JSON received from Indicore", [], 500);
    }

    // success condition
    if (isset($response['status']) && $response['status'] == 1) {

        // if plan not available
        if (isset($response['rdata'][0]['status']) && $response['rdata'][0]['status'] == 0) {
            return ResponseHelper::success("Plan Not Available", ["plans" => []], 200);
        }

        return ResponseHelper::success("Plan List Retrieved Successfully", 
       $response['rdata'] ?? []
        , 200);
    }

    // catch errors returned by API
    return ResponseHelper::error("Unable to fetch plan information", $response, 400);
}


    public function getRechargeTxns(Request $request)
    {
        $user = Auth::user();
    
        $query = RechargeTxn::with('user');
    
        //  if($user->role == 'ret' || $user->role == 'dd'|| $user->role == 'api'){
        //         $query->where('user_id', $user->id);
        //     }elseif (in_array($user->role, ['md', 'di'])) {
        //         $userIds = User::getDescendantUserIds($user->id);
        //         $query->whereIn('user_id', $userIds);
        //     }
        // if ($request->filled('status')) {
        //     $query->where('status', strtoupper($request->status));
        // }
        // if ($request->filled('mobile_number')) {
        //     $query->where('mobile_number', $request->mobile_number);
        // }
        // if ($request->filled('user_id')) {
        //     $query->where('user_id', $request->user_id);
        // }
        // if ($request->filled('route')) {
        //     $query->where('route', $request->route);
        // }
        // if ($request->filled('txn_id')) {
        //     $query->where('txn_id', $request->txn_id);
        // }
        // if ($request->filled('from_date') && $request->filled('to_date')) {
        //     $query->whereDate('created_at', '>=', $request->from_date)
        //           ->whereDate('created_at', '<=', $request->to_date);
        // }
    
        // if ($request->input('export') == 1) {
        //     $txns = $query->latest()->get();
    
        //     $txns->transform(function ($txn) {
        //         $txn->establishment = $txn->user->establishment ?? 'Unknown';
        //         unset($txn->user);
        //         return $txn;
        //     });
    
        //     return ResponseHelper::success('Transactions fetched', $txns, 200);
        // }
    
        // $paginate = $request->input('paginate', 15);
        // $txns = $query->latest()->paginate($paginate);
    
        // $txns->getCollection()->transform(function ($txn) {
        //     $txn->establishment = $txn->user->establishment ?? 'Unknown';
        //     unset($txn->user);
        //     return $txn;
        // });
    
        // return ResponseHelper::success('Transactions fetched', $txns, 200);
                  switch ($user->role) {
            case 'sadm':
            case 'adm':
                if($request->boolean('export')) {
                   $query->select([
                        'id', 'txn_id', 'user_id', 'mobile_number','client_ref',
                        'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm',
                        'gst','status','created_at','updated_at'
                    ]); 
                }
                break;
            case 'md':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'mobile_number','client_ref',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','gst','status','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'di':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'mobile_number', 'client_ref',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','gst','status','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'dd':
            case 'api':
            case 'ret':
                $query->select([
                'id', 'txn_id', 'user_id', 'pf', 'mobile_number',
                'operator','operator_id','amount','ret_comm','ret_tds','gst','status','created_at','updated_at'
            ])->where('user_id', $user->id);
                break;
            default:
            return ResponseHelper::error('Unauthorized role access', [], 403);
        }
        $query
            ->when($request->filled('status'), fn($q) => 
                $q->where('status', strtoupper($request->status))
            )
            ->when($request->filled('mobile_number'), fn($q) => 
                $q->where('mobile_number', $request->mobile_number)
            )
            ->when($request->filled('account_number'), fn($q) => 
                $q->where('account_number', $request->account_number)
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
              ->when($request->filled('service'), fn($q) => 
                $q->where('operator', $request->service)
            )
            ->when($request->filled('from_date') && $request->filled('to_date'), fn($q) => 
                $q->whereBetween('created_at', [
                    $request->from_date . ' 00:00:00',
                    $request->to_date . ' 23:59:59'
                ])
            );
        if ($request->boolean('export')) {
              ini_set('memory_limit', '512M');
            $txns = $query->latest()->get()->map(function ($txn) {
                $txn->establishment = $txn->user->establishment ?? 'Unknown';
                unset($txn->user);
                return $txn;
            });
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
        $txns = $query->latest()->paginate($request->input('paginate', 15));
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ?? 'Unknown';
            unset($txn->user);
            return $txn;
        });
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }
    public static function getPlanByCircle(Request $request)
    {
        $validator = RechargeValidation::rechargePlan($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(),'Validation Failed',  422);
        }
    
        // Map numeric operator IDs to mPlan operator names
        $operatorMap = [
            1 => 'Airtel',
            2 => 'Vodafone',
            3 => 'Jio',
            4 => 'Bsnl',
        ];
    
        $operatorId = $request->operator;
        if (!isset($operatorMap[$operatorId])) {
            return ResponseHelper::error('Invalid Operator', [], 400);
        }
    
        $serviceName = $operatorMap[$operatorId];
    
        $url = 'https://www.mplan.in/api/plans.php';
        $payload = [
            'apikey'   => env('MPLANAPIKEY'),
            'cricle'   => $request->circle,
            'operator' => $serviceName,
        ];
    
        $indicoreResponse = Mplan::service($url, 'GET', $payload);
    
        if (!$indicoreResponse) {
            return ResponseHelper::error('No response from mPlan API', [], 500);
        }
    
        $responseData = json_decode($indicoreResponse, true);
        if (!$responseData) {
            return ResponseHelper::error('Invalid JSON from mPlan API', [], 500);
        }
    
        return ResponseHelper::success('Plans fetched successfully', $responseData);
    }




}