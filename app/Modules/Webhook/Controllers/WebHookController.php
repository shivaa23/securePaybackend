<?php

namespace App\Modules\Webhook\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Modules\Payout\Models\PayoutTxn;
use App\Modules\Webhook\Models\Webhook;
use Auth;
use Carbon\Carbon;
use App\Modules\User\Models\User;
use DB;
use App\Modules\Collection\Models\Collection;
use App\Modules\Collection\Models\Collection2;
use App\Modules\Collection\Models\Collection3;
use App\Modules\Service\Models\Service;
use App\Modules\WalletLedger\Services\WalletService2;
class WebHookController extends BaseController
{
    


    public function __construct()
    {
        $this->middleware('auth', ['except' => ['guruWebhook','solitekWebhook','indicoreWebhook','callBackPaypoint','fingPayCmsApi','abhyamWebhook','utkalWebhook','digiKhataWebhook','dynamicWebhook']]);
    }
        

    public function indicoreWebhook(Request $request){
        // if ($request->ip() != '103.20.212.206') {
        //     return ResponseHelper::error('Unauthorized Action', '', 403);
        // }
        $webhook = new Webhook();
        $webhook->req_ip = $request->ip();
        $webhook->req_from = 'INDICORE';
        $webhook->response = json_encode($request->all());
        $webhook->txn_id = $request->tid;
        $webhook->save();
        return ResponseHelper::success('Processed', '', 200);
    }
    public function solitekWebhook(Request $request){
        // Optional: restrict by IP
        if ($request->ip() != '2001:df0:411:4012:3a08:9e19:a57c:12b3') {
            return ResponseHelper::error('Unauthorized Action', '', 403);
        }

    
        $solitekTxnId = $request->Data;
        $existingWebhook = Webhook::where('txn_id', $solitekTxnId)->first();
        if ($existingWebhook) {
            return ResponseHelper::success('Already Processed', '', 200);
        }
    
        // $payoutTxn = PayoutTxn::where('api_id', $solitekTxnId)->where('status', "PENDING")->first();
         $payoutTxn = PayoutTxn::where('api_id', $solitekTxnId)->first();
        $webhook = new Webhook();
        $webhook->req_ip = $request->ip();
        $webhook->req_from = 'SOLITECK';
        $webhook->response = json_encode($request->all());
        $webhook->txn_id = $payoutTxn->txn_id;
        $webhook->save();
    
        if (isset($request->ResponseCode) && $payoutTxn) {
            if ($request->ResponseCode == 200) {
                $status = 'SUCCESS';
                $payoutTxn->status = $status;
                $payoutTxn->operator_id = $request->ResponseData ?? null;
                $payoutTxn->save();
            } elseif ($request->ResponseCode == 204) {
                Http::post('https://api.transup.in/refund/refundTxn', [
                    'txn_id' => $payoutTxn->txn_id
                ]);
            } 
            return ResponseHelper::success('Processed', $request->ResponseData ?? null, 200);
        }
    
        return ResponseHelper::success('Processed', '', 200);
    }
    public function abhyamWebhook(Request $request){
        // Optional IP restriction
        // if ($request->ip() != '205.147.110.65') {
        //     return ResponseHelper::error('Unauthorized Action', '', 403);
        // }
    
        // ABHAYAM sends RequestId = Our TxnID  
        $txnId = $request->RequestId ?? null;
    
        if (!$txnId) {
            return ResponseHelper::error('Invalid Callback Data: Missing RequestId', '', 422);
        }
    
        // Prevent duplicate webhook
        $existingWebhook = Webhook::where('txn_id', $txnId)->first();
        if ($existingWebhook) {
            return ResponseHelper::success('Already Processed', '', 200);
        }
    
        // Fetch payout transaction
        $payoutTxn = PayoutTxn::where('txn_id', $txnId)->where('status', "PENDING")->first();
        if (!$payoutTxn) {
            return ResponseHelper::error('Transaction Not Found', '', 404);
        }
    
        // Save webhook log
        $webhook = new Webhook();
        $webhook->req_ip = $request->ip();
        $webhook->req_from = 'ABHAYAM';
        $webhook->response = json_encode($request->all());
        $webhook->txn_id = $txnId;
        $webhook->save();
    
        // ------------------------------
        // PROCESS STATUS
        // ------------------------------
    
        $status = $request->Status ?? null;
    
        if ($status == "Success") {
    
            // SUCCESS CASE
            $payoutTxn->status = "SUCCESS";
            $payoutTxn->operator_id = $request->OpRefId ?? null;
            $payoutTxn->save();
    
        } else if ($status == "Reverse") {
    
            // FAILED OR REVERSE → DO REFUND
            Http::post('https://api.transup.in/refund/refundTxn', [
                'txn_id' => $txnId
            ]);
        }
    
        return ResponseHelper::success('Processed', '', 200);
    }
    // public function utkalWebhook(Request $request){
    //     // Optional IP restriction
    //     if ($request->ip() != '43.230.201.26') {
    //         return ResponseHelper::error('Unauthorized Action', '', 403);
    //     }
    
    //     // ABHAYAM sends RequestId = Our TxnID  
    //     $txnId = $request->RequestId ?? null;
    
    //     if (!$txnId) {
    //         return ResponseHelper::error('Invalid Callback Data: Missing RequestId', '', 422);
    //     }
    
    //     // Prevent duplicate webhook
    //     $existingWebhook = Webhook::where('txn_id', $txnId)->first();
    //     if ($existingWebhook) {
    //         return ResponseHelper::success('Already Processed', '', 200);
    //     }
    
    //     // Fetch payout transaction
    //     $payoutTxn = PayoutTxn::where('txn_id', $txnId)->where('status', "PENDING")->first();
    //     if (!$payoutTxn) {
    //         return ResponseHelper::error('Transaction Not Found', '', 404);
    //     }
    
    //     // Save webhook log
    //     $webhook = new Webhook();
    //     $webhook->req_ip = $request->ip();
    //     $webhook->req_from = 'UTKAL';
    //     $webhook->response = json_encode($request->all());
    //     $webhook->txn_id = $txnId;
    //     $webhook->save();
    
    //     // ------------------------------
    //     // PROCESS STATUS
    //     // ------------------------------
    
    //     $status = $request->Status ?? null;
    
    //     if ($status == "Success") {
    
    //         // SUCCESS CASE
    //         $payoutTxn->status = "SUCCESS";
    //         $payoutTxn->operator_id = $request->OpRefId ?? null;
    //         $payoutTxn->save();
    
    //     } else if ($status == "Reverse") {
    
    //         // FAILED OR REVERSE → DO REFUND
    //         Http::post('https://api.transup.in/refund/refundTxn', [
    //             'txn_id' => $txnId
    //         ]);
    //     }
    
    //     return ResponseHelper::success('Processed', '', 200);
    // }
    public function utkalWebhook(Request $request)
    {
        $txnId    = $request->RequestId ?? null;
        $status   = $request->Status ?? null;
        $serviceId = $request->ServiceId ?? null;
    
        if (!$txnId || !$status) {
            return ResponseHelper::error('Invalid Callback Data', '', 422);
        }
    
        // Prevent duplicate webhook
        if (Webhook::where('txn_id', $txnId)->exists()) {
            return ResponseHelper::success('Already Processed', '', 200);
        }
    
        // ------------------------------
        // Create New Webhook Entry
        // ------------------------------
        $webhook = new Webhook();
        $webhook->req_ip   = $request->ip();
        $webhook->req_from = 'UTKAL';
        $webhook->response = json_encode($request->all());
        $webhook->txn_id   = $txnId;
        $webhook->save();
    
        // ===============================
        // COLLECTION FLOW
        // ===============================
        if ($serviceId == "38") {
    
            $collectionTxn = Collection2::where('txn_id', $txnId)
                ->where('status', "PENDING")
                ->first();
    
            if (!$collectionTxn) {
                return ResponseHelper::error('Transaction Not Found', '', 404);
            }
    
            if ($status === "Success") {
    
                $collectionTxn->status = "UNCLAIMED";
                $collectionTxn->operator_id = $request->OpRefId ?? null;
                $collectionTxn->save();
    
            } elseif ($status === "Reverse") {
    
                Http::post('https://api.transup.in/refund/refundTxn', [
                    'txn_id' => $txnId
                ]);
            }
    
        }
        // ===============================
        // PAYOUT FLOW
        // ===============================
        else {
    
            $payoutTxn = PayoutTxn::where('txn_id', $txnId)
                ->where('status', "PENDING")
                ->first();
    
            if (!$payoutTxn) {
                return ResponseHelper::error('Transaction Not Found', '', 404);
            }
    
            if ($status === "Success") {
    
                $payoutTxn->status = "SUCCESS";
                $payoutTxn->operator_id = $request->OpRefId ?? null;
                $payoutTxn->save();
    
            } elseif ($status === "Reverse") {
    
                Http::post('https://api.transup.in/refund/refundTxn', [
                    'txn_id' => $txnId
                ]);
            }
        }
    
        return ResponseHelper::success('Processed', '', 200);
    }


    public function guruWebhook(Request $request){
        if($request->ip() != '103.165.119.250'){
            return ResponseHelper::error('Unauthorised Action', '', 403);
        }
        $existingWebhook = Webhook::where('txn_id',$request->client_id)->first();
        if($existingWebhook){
            return ResponseHelper::success('Already Processed', '', 200);
        }
        $webhook = new Webhook();
        $webhook->req_ip = $request->ip();
        $webhook->req_from = 'IMPSGURU';
        $webhook->response = json_encode($request->all());
        $webhook->txn_id = $request->client_id;
        $webhook->save();
        $tableMap = [
        'PMT'   => 'payout_txns',
        'DMT1'   => 'dmt_txns',
        'DMT2'  => 'dmt_txns',
        'AEPS'  => 'aeps_txns',
        'AEPS2' => 'aeps_txns',
        'BBP'  => 'bbps_txns',
        'RCH'   => 'recharge_txns',
        'CARD'   => 'credit_cards'
        ];
        $results = [];
        // Determine which table the txn_id belongs to based on its prefix
       foreach ($tableMap as $prefix => $table) {
            if (str_starts_with($request->client_id, $prefix)) {
                if($request->status == 'SUCCESS'){
                    $txn = DB::table($table)
                        ->where('txn_id', $request->client_id)
                        ->update(['status' => 'SUCCESS', 'operator_id'=> $request->op_id]);
                }elseif($request->status == 'FAILED'){
                    $txn = DB::table($table)
                        ->where('txn_id', $request->client_id)
                        ->whereIn('status', ['PENDING','SUCCESS'])
                        ->first();
                        if($txn){
                            if($txn->status == 'PENDING' || $txn->status == 'SUCCESS'){
                                $response = Http::post('https://api.transup.in/refund/refundTxn', ['txn_id'=>$txn->txn_id])->json();
                            }else{
                                $response = 'No Txn To Refund';
                            }
                        }else{
                            $response = 'No Txn To Refund';
                        }
                }
            break;
            }
        }
        return ResponseHelper::success('Success', $response?? 'null', 200);
    }
//     public function getWebhook(Request $request){
//     $user = Auth::user();

//     // Only super admin or admin can access
//     if (!in_array($user->role, ['sadm', 'adm'])) {
//         return ResponseHelper::error('Action not Authorised', '', 403);
//     }

//     // Fetch paginated webhooks ordered by created_at descending
//     $data = Webhook::orderBy('created_at', 'desc')->paginate(15);

//     return ResponseHelper::success('Success', $data, 200);
// }
    public function getWebhook(Request $request)
    {
        $user = Auth::user();
    
        // Only super admin or admin can access
        if (!in_array($user->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Action not Authorised', '', 403);
        }
    
        $query = Webhook::query();
    
        // Apply filters
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
         if ($request->filled('req_from')) {
            $query->where('req_from', $request->req_from);
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        }
    
        // Order & paginate
        $data = $query->orderBy('created_at', 'desc')->paginate(15);
    
        return ResponseHelper::success('Success', $data, 200);
    }
    
        // public function callBackPaypoint(Request $request)
        // {
        //     $data = $request->all();
        //     $data['ip'] = $request->ip();
        
        //     // IP Validation
        //     if ($request->ip() != "103.216.209.250") {
        //         return ResponseHelper::error(
        //             'Unauthorised IP Access',
        //             ['ip' => $request->ip()],
        //             403
        //         );
        //     }
        
        //     // Extract actual callback data
        //     $data = $data['data'];
        //     $data['ip'] = $request->ip();
        
        //     // --------------------------
        //     // CASE 1: When refId exists
        //     // --------------------------
        //     if (isset($data['refId'])) {
        
        //         $oldWebhook = Webhook::where('txn_id', $data['refId'])->first();
        //         if ($oldWebhook) {
        //             return ResponseHelper::success('Duplicate Order', []);
        //         }
        
        //         $webHook = new Webhook();
        //         $webHook->req_from = 'PAYPOINT';
        //         $webHook->req_ip = $request->ip();
        //         $webHook->response = json_encode($data);
        //         $webHook->txn_id = $data['refId'];
        //         $webHook->save();
        
        //         // Insert into collection table
        //         $user = User::where('vqr', $data['payeeAddress'])->first();
        //         if ($user) {
        //             $collection = new Collection;
        //             $collection->user_id = $user->id;
        //             $collection->rrn = $data['rrn'];
        //             $collection->refId = $data['refId'];
        //             $collection->payerAddress = $data['payerAddress'];
        //             $collection->payerName = $data['payerName'];
        //             $collection->payerACNo = $data['payerACNo'];
        //             $collection->payerACIFSCCode = $data['payerACIFSCCode'];
        //             $collection->amount = $data['amount'];
        //             $collection->status = 'Unclaimed';
        //             $collection->payeeAddress = $data['payeeAddress'];
        //             $collection->save();
        //         }
        
        //         return ResponseHelper::success(
        //             'Received Successfully',
        //             ['refId' => $data['refId']]
        //         );
        //     }
        //     $oldWebhook = Webhook::where('txn_id', $data['refId'])->first();
        //     if ($oldWebhook) {
        //         return ResponseHelper::success('Duplicate Order', []);
        //     }
        
        //     $webHook = new Webhook();
        //     $webHook->req_from = 'PAYPOINT';
        //     $webHook->req_ip = $request->ip();
        //     $webHook->response = json_encode($data);
        //     $webHook->txn_id = $data['refId'];
        //     $webHook->save();
        
        //     return ResponseHelper::success('SUCCESS', []);
        // }
        // public function digiKhataWebhook(Request $request){
        //     $data = $request->all();
        // //     if ($request->ip() !== '103.216.209.250') {
        // //     return ResponseHelper::error('Unauthorised Access', '', 403);
        // // }
        //     $data = $data['data'];
        //     $data['ip'] = $request->ip();
        
        //     // --------------------------
        //     // CASE 1: When refId exists
        //     // --------------------------
        //     if (isset($data['refId'])) {
        
        //         $oldWebhook = Webhook::where('txn_id', $data['refId'])->first();
        //         if ($oldWebhook) {
        //             return ResponseHelper::success('Duplicate Order', []);
        //         }
        
        //         $webHook = new Webhook();
        //         $webHook->req_from = 'PAYPOINT';
        //         $webHook->req_ip = $request->ip();
        //         $webHook->response = json_encode($data);
        //         $webHook->txn_id = $data['refId'];
        //         $webHook->save();
        //         $user = User::where('vpa', $data['payeeAddress'])->first();
        //         // return $user;
        //         if ($user) {
        //             $collection = new Collection;
        //             $collection->user_id = $user->id;
        //             $collection->rrn = $data['rrn'];
        //             $collection->refId = $data['refId'];
        //             $collection->payerAddress = $data['payerAddress'];
        //             $collection->payerName = $data['payerName'];
        //             $collection->payerACNo = $data['payerACNo'];
        //             $collection->payerACIFSCCode = $data['payerACIFSCCode'];
        //             $collection->amount = $data['amount'];
        //             $collection->status = 'Unclaimed';
        //             $collection->payeeAddress = $data['payeeAddress'];
        //             $collection->save();
        //         }
        
        //         return ResponseHelper::success(
        //             'Received Successfully',
        //             ['refId' => $data['refId']]
        //         );
        //     }
        
        //     // --------------------------
        //     // CASE 2: When refId NOT present
        //     // --------------------------
        //     $oldWebhook = Webhook::where('txn_id', $data['refId'])->first();
        //     if ($oldWebhook) {
        //         return ResponseHelper::success('Duplicate Order', []);
        //     }
        
        //     $webHook = new Webhook();
        //     $webHook->req_from = 'PAYPOINT';
        //     $webHook->req_ip = $request->ip();
        //     $webHook->response = json_encode($data);
        //     $webHook->txn_id = $data['refId'];
        //     $webHook->save();
        
        //     return ResponseHelper::success('SUCCESS', []);
        // }
    public function digiKhataWebhook(Request $request)
    {
        // ---------------------------------
        // Optional IP Whitelisting
        // ---------------------------------
        /*
        if ($request->ip() !== '103.216.209.250') {
            return ResponseHelper::error('Unauthorised Access', '', 403);
        }
        */
    
        // ---------------------------------
        // Read JSON (IMPORTANT FOR LUMEN)
        // ---------------------------------
        $payload = $request->json()->all();
    
        // Handle nested data
        $data = isset($payload['data']) ? $payload['data'] : $payload;
        $data['ip'] = $request->ip();
    
        // ---------------------------------
        // Extract refId safely
        // ---------------------------------
        $refId = $data['refId'] ?? $data['id'] ?? null;
    
        // if (empty($refId)) {
        //     return ResponseHelper::error('refId missing in callback', '', 400);
        // }
    
        // // ---------------------------------
        // // Prevent duplicate webhook
        // // ---------------------------------
        // if (Webhook::where('txn_id', $refId)->exists()) {
        //     return ResponseHelper::success('Duplicate Order', ['refId' => $refId]);
        // }
    
        // ---------------------------------
        // Save Webhook
        // ---------------------------------
        $webHook = new Webhook();
        $webHook->req_from = 'PAYPOINT';
        $webHook->req_ip   = $request->ip();
        $webHook->txn_id   = $refId??876876786;
        $webHook->response = json_encode($data);
        $webHook->save();
    
        // ---------------------------------
        // Find User by VPA
        // ---------------------------------
        $user = User::where('vpa', $data['payeeAddress'] ?? null)->first();
    
        if (!$user) {
            return ResponseHelper::success(
                'Webhook saved, user not found',
                ['refId' => $refId]
            );
        }
    
        // ---------------------------------
        // Prevent duplicate collection
        // ---------------------------------
        if (Collection::where('refId', $refId)->exists()) {
            return ResponseHelper::success(
                'Collection already exists',
                ['refId' => $refId]
            );
        }
    
        // ---------------------------------
        // Save Collection (new + save)
        // ---------------------------------
        $collection = new Collection();
        $collection->user_id         = $user->id;
        $collection->rrn             = $data['rrn'] ?? null;
        $collection->refId           = $refId;
        $collection->payerAddress    = $data['payerAddress'] ?? null;
        $collection->payerName       = $data['payerName'] ?? null;
        $collection->payerACNo       = $data['payerACNo'] ?? null;
        $collection->payerACIFSCCode = $data['payerACIFSCCode'] ?? null;
        $collection->payeeAddress    = $data['payeeAddress'] ?? null;
        $collection->amount          = $data['amount'] ?? 0;
        $collection->status          = 'Unclaimed';
        $collection->remark          = $data['note'] ?? null;
        $collection->route           = 'DIGIKHATA';
        $collection->save();
    
        // ---------------------------------
        // Success Response
        // ---------------------------------
        return ResponseHelper::success(
            'Received Successfully',
            [
                'refId'  => $refId,
                'amount' => $collection->amount
            ]
        );
    }
    public function dynamicWebhook(Request $request)
    { 
        // return "djfhsjf";
        $payload = $request->json()->all();
        $data    = isset($payload['data']) ? $payload['data'] : $payload;
        $data['ip'] = $request->ip();
    
        $refId = $data['refId'] ?? $data['id'] ?? null;
    
        $webHook = new Webhook();
        $webHook->req_from = 'PAYPOINTDYNAMIC';
        $webHook->req_ip   = $request->ip();
        $webHook->txn_id   = $refId ?? null;
        $webHook->response = json_encode($data);
        $webHook->save();
    
        if (empty($refId)) {
            return ResponseHelper::success('Webhook saved but refId missing', []);
        }
    
        $collection = Collection3::where('txn_id', $refId)->first();
        if (!$collection) {
            return ResponseHelper::success('Collection not found', ['refId' => $refId],200);
        }
    
        $service = Service::find(367);
        if (!$service) {
            return ResponseHelper::error("Service not found", [], 404);
        }
    
        if ($service->is_active == 0) {
            return ResponseHelper::error("Service is not active", [], 403);
        }
    
        $user = User::find($collection->user_id);
        if (!$user) {
            return ResponseHelper::success('User not found', ['refId' => $refId],200);
        }
    
        $claimed = WalletService2::claim2($user, $collection, $service);
    
        $collection->status = "Claimed";
        $collection->payerAddress=$data['payerAddress'];
        $collection->payerName=$data['payerName'];
        $collection->payerACNo=$data['payerACNo'];
        $collection->payerACIFSCCode=$data['payerACIFSCCode'];
        $collection->payerACType=$data['payerACType'];
        $collection->operator_id=$data['rrn'];
        $collection->remark = $data['remark'] ?? null;
        $collection->save();
    
        return ResponseHelper::success(
            'Received Successfully',
            $claimed,
            200
        );
    }

public function fingPayCmsApi(Request $request)
{
    $data = $request->all();
    $merchantTxnId = Carbon::now()->timestamp . rand(100000, 999999);

    // ---------------------------------
    // Save Webhook Log
    // ---------------------------------
    $webHook = new Webhook();
    $webHook->req_from = 'FINGCMS';
    $webHook->req_ip = $request->ip();
    $webHook->response = json_encode($data);
    $webHook->txn_id = $merchantTxnId;
    $webHook->save();

    // ---------------------------------
    // Validate User
    // ---------------------------------
    $user = User::where('fingId', $data['bcLoginId'] ?? null)->first();
    if (!$user) {
        return response()->json([
            'status' => 'false',
            'message' => 'Invalid UserID'
        ]);
    }

    // ---------------------------------
    // Get Service
    // ---------------------------------
    $service = Service::where('name', 'CMS')->first();
    if (!$service) {
        return ResponseHelper::error('Service Not Found', '', 404);
    }

    // ---------------------------------
    // TRANSACTION INITIATED
    // ---------------------------------
    if ($data['transactionStatus'] == 'I') {

        // Commission Calculation
        $comm = CommRule::calculateCommission($user, $service->name, $data['amount']);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }

        $a_comm = AcommRule::calculateAdminCommission($service->name, $data['amount'], $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }

        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm = CommRule::finalCommission($comm, $data['amount'], $service);
            $a_comm = AcommRule::finalCommission($a_comm, $data['amount']);
        }

        // Balance Check
        if (($user->w1 / 100) < ($data['amount'] + $user->hold)) {
            return response()->json([
                'status' => 'false',
                'merchantTransactionId' => $merchantTxnId,
                'errorMessage' => 'Insufficient Balance'
            ]);
        }

        // Create Transaction
        $txn = CmsService::create($user, $service, $data, $comm, $a_comm);

        if (!$txn) {
            return response()->json([
                'status' => 'false',
                'merchantTransactionId' => $merchantTxnId,
                'errorMessage' => 'Transaction Failed'
            ]);
        }

        // Debit Wallet
        WalletService::debit($user, $data['amount'], $txn, $service);

        return response()->json([
            'status' => 'true',
            'merchantTransactionId' => $txn->order_id,
            'errorMessage' => ''
        ]);
    }

    // ---------------------------------
    // SUCCESS CALLBACK
    // ---------------------------------
    elseif ($data['transactionStatus'] == 'S') {

        $txn = CmsTxn::where('txn_id', $data['merchantTransactionId'])->first();
        if (!$txn) {
            return response()->json(['status' => 'false', 'message' => 'Txn Not Found']);
        }

        $txn->status = 'SUCCESS';
        $txn->api_response = json_encode($data);
        $txn->op_id = $data['errorMessage'] ?? null;
        $txn->save();

        return response()->json([
            'status' => 'true',
            'merchantTransactionId' => $txn->order_id,
            'errorMessage' => ''
        ]);
    }

    // ---------------------------------
    // FAILURE CALLBACK
    // ---------------------------------
    elseif ($data['transactionStatus'] == 'F') {

        $txn = CmsTxn::where('order_id', $data['merchantTransactionId'])->first();
        if (!$txn) {
            return response()->json(['status' => 'false', 'message' => 'Txn Not Found']);
        }

        $op_id = $data['errorMessage'] ?? 'FAILED';
        CmsTxn::reverseByCallback($txn, $op_id);

        return response()->json([
            'status' => 'true',
            'merchantTransactionId' => $txn->order_id,
            'errorMessage' => ''
        ]);
    }

    return response()->json([
        'status' => 'false',
        'message' => 'Invalid Transaction Status'
    ]);
}

        public function cmsCallback(Request $request)
    {
    $event = $request->event;
    $param = $request->param;
       if ($request->ip() !== '13.201.165.211') {
        return ResponseHelper::error('Unauthorised Access', '', 403);
    }
    $refid = $param['refid'] ?? null;

    // Extract User ID from refid like: 123456789P2P1 → userId = 1
    $userId = null;
    if ($refid && strpos($refid, 'P2P') !== false) {
        $parts = explode('P2P', $refid);
        $userId = $parts[1] ?? null;
    }

    if (!$userId) {
        return response()->json([
            "status"  => "FAILED",
            "message" => "Invalid Reference ID Format"
        ], 403);
    }

    $user = User::find($userId);

    if (!$user) {
        return response()->json([
            "status"  => "FAILED",
            "message" => "User Not Found"
        ], 404);
    }

    $successResponse = [
        "status"  => 200,
        "message" => "Transaction completed successfully"
    ];

    // -------------------------------
    // WEBHOOK
    // -------------------------------
    $webHook = new Webhook();
    $webHook->req_from        = 'PAYSPRINTCMS';
    $webHook->req_ip          = $request->ip();
    $webHook->event           = $event;
    $webHook->response        = json_encode($request->all());
    $webHook->txn_id          = bin2hex(random_bytes(5));
    $webHook->final_response  = json_encode($successResponse);
    $webHook->save();

    // FIX: calculate required balance correctly
    $requiredBalance = ($user->w1 - $user->lien) / 100;

    // ------------------------------------------
    // CMS BALANCE INQUIRY
    // ------------------------------------------
    if ($event == "CMS_BALANCE_INQUIRY") {

        if ($requiredBalance < $param['amount']) {
            return response()->json([
                "status"  => "FAIL",
                "message" => "Insufficient wallet balance"
            ], 403);
        }

        return response()->json($successResponse, 200);
    }

    // ------------------------------------------
    // CMS BALANCE DEBIT
    // ------------------------------------------
    else if ($event == "CMS_BALANCE_DEBIT") {

        if ($requiredBalance < $param['amount']) {
            return response()->json([
                "status"  => "FAIL",
                "message" => "Insufficient wallet balance"
            ], 403);
        }

        $service = Service::where("paySprint_billerId", $param['biller_id'])->first();
        if (!$service) {
            return ResponseHelper::error('Service Not Found', [], 404);
        }

        $comm = CommRule::calculateCommission($user, $service->name, $param['amount']);
        if (!$comm) {
            return ResponseHelper::error('Commission Slab Not Configured', '', 403);
        }

        $a_comm = AcommRule::calculateAdminCommission($service->name, $param['amount'], $service->route);
        if (!$a_comm) {
            return ResponseHelper::error('Admin Commission Slab Not Configured', '', 403);
        }

        if (in_array($user->role, ["api", "dd", "ret"])) {
            $comm   = CommRule::finalCommission($comm, $param['amount'], $service);
            $a_comm = AcommRule::finalCommission($a_comm, $param['amount']);
        }

        DB::beginTransaction();
        try {
            $cmsTxn = CmsService::create($user, $service, $param, $comm, $a_comm);
            WalletService::debit($user, $param['amount'], $cmsTxn, $service);

            // FIX: use $cmsTxn NOT $txn (undefined earlier)
            WalletService::creditCommission($user, $cmsTxn->ret_comm, $cmsTxn, $service);

            // Distributor
            $distributor = $user->parent ? User::find($user->parent) : null;
            if ($distributor && $cmsTxn->di_comm > 0) {
                WalletService::creditCommission($distributor, $cmsTxn->di_comm, $cmsTxn, $service);
            }

            // Master Distributor
            $masterDistributor = $distributor && $distributor->parent ? User::find($distributor->parent) : null;
            if ($masterDistributor && $cmsTxn->md_comm > 0) {
                WalletService::creditCommission($masterDistributor, $cmsTxn->md_comm, $cmsTxn, $service);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('CMS Debit Failed', $e->getMessage(), 500);
        }

        return response()->json($successResponse, 200);
    }

    // ------------------------------------------
    // CMS LOW BALANCE INQUIRY
    // ------------------------------------------
    else if ($event == "CMS_LOW_BALANCE_INQUIRY") {

        if ($requiredBalance < $param['amount']) {
            return response()->json([
                "status"  => "FAIL",
                "message" => "Low wallet balance"
            ], 403);
        }

        return response()->json($successResponse, 200);
    }

    // ------------------------------------------
    // CMS POSTING
    // ------------------------------------------
    else if ($event == "CMS_POSTING") {

        $txn = CmsTxn::where("client_ref", $param['refid'])->first();

        if ($txn) {
            $txn->operator_id = $param['utr'] ?? "SUCCESS";
            $txn->status = "SUCCESS";
            $txn->save();
        }

        return response()->json($txn, 200);
    }

    return response()->json([
        "status"  => 500,
        "message" => "Unknown event type"
    ], 500);
}
   
}
