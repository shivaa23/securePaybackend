<?php

namespace App\Modules\CreditCard\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Modules\CreditCard\Models\CreditCard;
use App\Modules\CreditCard\Models\CreditCardValidation;
use App\Modules\Integration\Models\Ipay;
use Auth;
use App\Modules\Service\Models\Service;
use App\Modules\CreditCard\Services\CreditCardService; 
use App\Modules\Commission\Models\CommRule;
use App\Modules\Auth\Models\Otp;
use App\Helper\ResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\CreditCard\Services\CreditCardResponseHandler;
use App\Modules\Bbps\Models\BbpsTxn;
use App\Modules\Integration\Models\Impsguru;
use App\Modules\Bbps\Services\BbpsService; 
use App\Modules\User\Models\User;
use App\Modules\Misc\Models\Uuid;
class CreditCardController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.active');
    }

     public function getCreditCard(Request $request)
    {
        $user = app('auth')->user();
    
        $query = CreditCard::with('user');
    
        // if (!in_array($user->role, ['sadm', 'adm'])) {
        //     $query->where('user_id', $user->id);
        // }
        // if ($request->filled('from_date') && $request->filled('to_date')) {
        //     $query->whereDate('created_at', '>=', $request->from_date)
        //           ->whereDate('created_at', '<=', $request->to_date);
        // }
    
        // if ($request->filled('status')) {
        //     $query->where('status', strtoupper($request->status));
        // }
        // if ($request->filled('user_id')) {
        //     $query->where('user_id', $request->user_id);
        // }
        // if ($request->filled('card_number')) {
        //     $query->where('cnumber', $request->card_number);
        // }
        // if ($request->filled('number')) {
        //     $query->where('number', $request->number);
        // }
        // if ($request->filled('route')) {
        //     $query->where('route', $request->route);
        // }
        // if ($request->filled('txn_id')) {
        //     $query->where('txn_id', $request->txn_id);
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
                        'id', 'txn_id', 'user_id', 'number', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
                        'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm',
                        'charges','gst','status','created_at','updated_at'
                    ]); 
                }
                break;
            case 'md':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'number', 'name',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','charges','gst','status','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'di':
                $userIds = User::getDescendantUserIds($user->id);
                $userIds = array_merge([$user->id], $userIds ?? []);
                $query->select([
                    'id', 'txn_id', 'user_id', 'pf', 'number', 'name',
                    'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','charges','gst','status','created_at','updated_at'
                ])->whereIn('user_id', $userIds);
                break;
            case 'dd':
            case 'api':
            case 'ret':
                $query->select([
                'id', 'txn_id', 'user_id', 'pf', 'number', 'name', 
                'operator','operator_id','amount','ret_comm','ret_tds','charges','gst','status','created_at','updated_at'
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
            ->when($request->filled('number'), fn($q) => 
                $q->where('number', $request->number)
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


     public function payCreditCard(Request $request)
    {
        $user = Auth::user();
    
        // Validate request
        $validator = CreditCardValidation::payCreditCard($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
      $dupCheck = Uuid::check($request->client_ref,'payCreditCard',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        // Role check
        if (!in_array($user->role, ["api", "dd", "ret"])) {
            return ResponseHelper::error('Action not Authorised', [], 403);
        }
    
        // Fetch service
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Invalid Service Category', [], 404);
        }
        $comm = CommRule::calculateCommission($user, $service->name, $request->amount);
        $final_comm = CommRule::finalCommission($comm, $request->amount, $service);
        $requiredBalance = ($request->amount + $user->lien ($final_comm['ret'] ?? $final_comm['dd'])) * 100;
        if ($user->w1 < $requiredBalance ) {
            return ResponseHelper::error('Insufficient W1 Balance', [], 403);
        }
    
        // First DB Transaction: create txn, debit wallet, log request
        DB::beginTransaction();
        try {
            $txn = CreditCardService::create($user, $service, $request, $final_comm);
            //   $txn = BbpsService::create($user, $service, $request, $final_comm);
    
            WalletService::debit($user, $request->amount, $txn, $service);
            $auditLog = AuditService::logRequest($user, $request, $service);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Init Failed', $e->getMessage(), 500);
        }      
        $data=$request->all();
        	$hashData = $data['number'].$request->cnumber.$request->name.$data['amount']."14".$txn->txn_id.env('GURU_HKEY');
			$hash = hash('sha256', $hashData);
   
        $payload = [
            // "payer" => [
                "bankId" => "0",
                "bankProfileId" => "0",
                "accountNumber" => "9999442202",
                 "mobileNumber" => $data['number'],
            // ],
            // "payee" => [
                "beneficiaryName" => $request->name,
                "beneficiaryAccount" => $request->cnumber,
            // ],
            "transferMode" => "CREDITCARD",
             "operatorCode" => 14,
            "transferAmount" => $request->amount,
            "externalRef" => $txn->txn_id,
            "client_id" => $txn->txn_id,
             "env" => "LIVE",
            "latitude" => $request->latitude ?? null,
            "longitude" => $request->longitude ?? null,
            "remarks" => "Credit Card Bill",
            "alertEmail" => "paisaonmobile@gmail.in",
            "generatedHash"=>$hash,
        ];
    
        // Call external API
        $url = 'https://api.impsguru.com/prod/v1/payments/creditCard';
        $response = Impsguru::guruRequest($url, 'POST', json_encode($payload), '');
    //   return $response;
        // Second DB Transaction: handle API response
        DB::beginTransaction();
        try {
            $result = CreditCardResponseHandler::handle($txn, $response['response'], $user, $service, $final_comm);
            AuditService::updateResponse($auditLog, $response);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }


}
