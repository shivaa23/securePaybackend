<?php

namespace App\Modules\WalletTransfer\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Modules\WalletTransfer\Models\WalletTransaction;
use App\Modules\WalletTransfer\Models\WalletTransferValidation;
use App\Modules\User\Models\User;
use App\Helper\ResponseHelper;
use App\Modules\Integration\Models\Recharge;
use App\Modules\WalletTransfer\Services\WalletTransferService;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\WalletLedger\Services\WalletService2;
use App\Modules\WalletLedger\Services\WalletService3;
use App\Modules\Recharge\Services\RechargeResponseHandler;
use App\Modules\AuditLog\Services\AuditService;
use App\Modules\Service\Models\Service;
use App\Modules\Commission\Models\CommRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Modules\Auth\Models\Otp;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\AccountStatement;
use App\Modules\FundRequest\Models\FundRequest;
use App\Modules\Misc\Models\Uuid;
use App\Modules\Template\Models\Template;
use App\Modules\Message\Models\Sms;
class WalletTransferController extends BaseController
{
      public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
//   public function getW2WTransaction(Request $request){
//     $user = Auth::user();
//     $query = WalletTransaction::query();
    
//     // Role-based filtering
//     switch ($user->role) {
//         case 'sadm':
//         case 'adm':
//             // No additional filters for admin roles
//             break;
//         case 'md':
//         case 'di':
//         case 'dd':
//         case 'api':
//         case 'ret':
//             $query->where(function($q) use ($user) {
//                 $q->where('user_id', $user->id)
//                   ->orWhere('reciever_id', $user->id);
//             });
//             break;
//         default:
//             return ResponseHelper::error('Unauthorized role access', [], 403);
//     }

//     // Apply filters
//     $query->when($request->filled('status'), function($q) use ($request) {
//         $q->where('status', strtoupper($request->status));
//     })
//     ->when($request->filled('mobile_number'), function($q) use ($request) {
//         $q->where('mobile_number', 'LIKE', '%' . $request->mobile_number . '%');
//     })
    
//      ->when($request->filled('operator'), function($q) use ($request) {
//         $q->where('operator' , $request->operator );
//     })
//     ->when($request->filled('account_number'), function($q) use ($request) {
//         $q->where('account_number', 'LIKE', '%' . $request->account_number . '%');
//     })
//     ->when($request->filled('user_id'), function($q) use ($request) {
//         $q->where('user_id', $request->user_id);
//     })
//     ->when($request->filled('route'), function($q) use ($request) {
//         $q->where('route', $request->route);
//     })
//     ->when($request->filled('txn_id'), function($q) use ($request) {
//         $q->where('txn_id', $request->txn_id );
//     })
//     ->when($request->filled('from_date') && $request->filled('to_date'), function($q) use ($request) {
//         $q->whereBetween('created_at', [
//             $request->from_date . ' 00:00:00',
//             $request->to_date . ' 23:59:59'
//         ]);
//     });

//     // Debug: Check the final SQL query
//     // Log::info('Final Query: ' . $query->toSql());
//     // Log::info('Query Parameters: ', $query->getBindings());

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
// }
// public function getW2WTransaction(Request $request)
// {
//     $user = Auth::user();
//     $query = WalletTransaction::query();

//     /* -----------------------------------
//         ROLE BASED FILTERING
//     -------------------------------------*/
//     if (!in_array($user->role, ['sadm', 'adm', 'md', 'di', 'dd', 'api', 'ret'])) {
//         return ResponseHelper::error('Unauthorized role access', [], 403);
//     }

//     // Non-admins can see only their own transactions
//     if (!in_array($user->role, ['sadm', 'adm'])) {
//         $query->where(function ($q) use ($user) {
//             $q->where('user_id', $user->id)
//               ->orWhere('reciever_id', $user->id);
//         });
//     }

//     /* -----------------------------------
//         TYPE FILTER (CR/DR) â€” FIXED
//         (Now works with all other filters)
//     -------------------------------------*/
//     if ($request->filled('type')) {
//         $type = strtoupper($request->type);

//         $query->when($type === "CR", function ($q) use ($user) {
//             $q->where('reciever_id', $user->id);
//         });

//         $query->when($type === "DR", function ($q) use ($user) {
//             $q->where('user_id', $user->id);
//         });
//     }
    
//     /* -----------------------------------
//         APPLY ALL OPTIONAL FILTERS
//     -------------------------------------*/
//     $query->when($request->filled('status'), fn($q) =>
//         $q->where('status', strtoupper(request('status')))
//     );

//     $query->when($request->filled('sender_id'), fn($q) =>
//         $q->where('user_id', request('sender_id'))
//     );
//      $query->when($request->filled('reciever_id'), fn($q) =>
//         $q->where('reciever_id', request('reciever_id'))
//     );
//      $query->when($request->filled('mobile_number'), fn($q) =>
//         $q->where('mobile_number', 'LIKE', '%' . request('mobile_number') . '%')
//     );

//     $query->when($request->filled('operator'), fn($q) =>
//         $q->where('operator', request('operator'))
//     );

//     $query->when($request->filled('account_number'), fn($q) =>
//         $q->where('account_number', 'LIKE', '%' . request('account_number') . '%')
//     );

//     // ðŸ‘‰ This filter was causing conflict; now fixed
//     $query->when($request->filled('user_id'), fn($q) =>
//         $q->where('user_id', request('user_id'))
//     );

//     $query->when($request->filled('route'), fn($q) =>
//         $q->where('route', request('route'))
//     );

//     $query->when($request->filled('txn_id'), fn($q) =>
//         $q->where('txn_id', request('txn_id'))
//     );

//     $query->when($request->filled('from_date') && $request->filled('to_date'), fn($q) =>
//         $q->whereBetween('created_at', [
//             request('from_date') . " 00:00:00",
//             request('to_date') . " 23:59:59"
//         ])
//     );

//     /* -----------------------------------
//         EXPORT RESULT
//     -------------------------------------*/
//     if ($request->boolean('export')) {
//         $txns = $query->latest()->get()->map(function ($txn) {
//             $txn->establishment = $txn->user->establishment ?? 'Unknown';
//             unset($txn->user);
//             return $txn;
//         });

//         return ResponseHelper::success('Transactions fetched', $txns, 200);
//     }

//     /* -----------------------------------
//         PAGINATION RESULT
//     -------------------------------------*/
//     $txns = $query->latest()->paginate($request->input('paginate', 15));

//     $txns->getCollection()->transform(function ($txn) {
//         $txn->establishment = $txn->user->establishment ?? 'Unknown';
//         unset($txn->user);
//         return $txn;
//     });

//     return ResponseHelper::success('Transactions fetched', $txns, 200);
// }
    public function getW2WTransaction(Request $request)
    {
        $user = Auth::user();
    
        // Base Query
        $query = WalletTransaction::with('user');
    
        /* -----------------------------------
            ROLE BASED FILTERING
        -------------------------------------*/
        if (!in_array($user->role, ['sadm', 'adm', 'md', 'di', 'dd', 'api', 'ret'])) {
            return ResponseHelper::error('Unauthorized role access', [], 403);
        }
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('reciever_id', $user->id);
            });
        }
    
        /* -----------------------------------
            TYPE FILTER
        -------------------------------------*/
        if ($request->filled('type')) {
            $type = strtoupper($request->type);
    
            if ($type === "CR") {
                $query->where('reciever_id', $user->id);
            }
    
            if ($type === "DR") {
                $query->where('user_id', $user->id);
            }
        }
    
        /* -----------------------------------
            OPTIONAL FILTERS
        -------------------------------------*/
        $filters = [
            'status'         => fn($q, $v) => $q->where('status', strtoupper($v)),
            'sender_id'      => fn($q, $v) => $q->where('user_id', $v),
            'reciever_id'    => fn($q, $v) => $q->where('reciever_id', $v),
            'mobile_number'  => fn($q, $v) => $q->where('mobile_number', 'LIKE', "%$v%"),
            'operator'       => fn($q, $v) => $q->where('operator', $v),
            'account_number' => fn($q, $v) => $q->where('account_number', 'LIKE', "%$v%"),
            'user_id'        => fn($q, $v) => $q->where('user_id', $v),
            'route'          => fn($q, $v) => $q->where('route', $v),
            'txn_id'         => fn($q, $v) => $q->where('txn_id', $v),
            'sender_est'         => fn($q, $v) => $q->where('sender_est', 'LIKE', $v),
            'receiver_est'         => fn($q, $v) => $q->where('receiver_est', 'LIKE', $v),
        ];
    
        foreach ($filters as $key => $callback) {
            if ($request->filled($key)) {
                $callback($query, $request->$key);
            }
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . " 00:00:00",
                $request->to_date   . " 23:59:59"
            ]);
        }
    
        /* -----------------------------------
            CORRECT SUMMARY BASED ON FILTERS
        -------------------------------------*/
        $summaryQuery = clone $query;
    
        $summary = $summaryQuery
            ->selectRaw("COUNT(*) as total_count, SUM(amount) as total_amount")
            ->first();
    
        $summary_total_count  = $summary->total_count ?? 0;
        $summary_total_amount = $summary->total_amount ?? 0;
    
        /* -----------------------------------
            EXPORT RESULT
        -------------------------------------*/
        if ($request->boolean('export')) {
    
            $txns = $query->latest()->get()->map(function ($txn) use ($query, $summary_total_count, $summary_total_amount) {
    
                // Clone full filtered query for receiver stats
                $statsQuery = clone $query;
    
                $stats = $statsQuery
                    ->where('user_id', $txn->user_id)
                    ->where('reciever_id', $txn->reciever_id)
                    ->selectRaw('reciever_id, COUNT(*) as total_count, SUM(amount) as total_amount')
                    ->groupBy('reciever_id')
                    ->first();
    
                $txn->receiver_stats       = $stats;
                $txn->summary_total_count  = $summary_total_count;
                $txn->summary_total_amount = $summary_total_amount;
    
                $txn->establishment = $txn->user->establishment ?? 'Unknown';
                unset($txn->user);
    
                return $txn;
            });
    
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
    
        /* -----------------------------------
            PAGINATED RESULT
        -------------------------------------*/
        $txns = $query->latest()->paginate($request->input('paginate', 15));
    
        $txns->getCollection()->transform(function ($txn) use ($query, $summary_total_count, $summary_total_amount) {
    
            // Clone filtered query for receiver stats
            $statsQuery = clone $query;
    
            $stats = $statsQuery
                ->where('user_id', $txn->user_id)
                ->where('reciever_id', $txn->reciever_id)
                ->selectRaw('reciever_id, COUNT(*) as total_count, SUM(amount) as total_amount')
                ->groupBy('reciever_id')
                ->first();
    
            $txn->receiver_stats       = $stats;
            $txn->summary_total_count  = $summary_total_count;
            $txn->summary_total_amount = $summary_total_amount;
    
            $txn->establishment = $txn->user->establishment ?? 'Unknown';
            unset($txn->user);
    
            return $txn;
        });
    
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }
    public function getCreditDebitReport(Request $request)
    {
        $user = Auth::user();
    
        // Base Query
        $query = WalletTransaction::query();
    
        /* -----------------------------------
            ROLE BASED FILTERING
        -------------------------------------*/
        if (!in_array($user->role, ['sadm', 'adm', 'md', 'di', 'dd', 'api', 'ret'])) {
            return ResponseHelper::error('Unauthorized role access', [], 403);
        }
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $query->where(function ($q) use ($user) {
                $q->where('wallet_transactions.user_id', $user->id)
                  ->orWhere('wallet_transactions.reciever_id', $user->id);
            });
        }
    
        /* -----------------------------------
            TYPE FILTER
        -------------------------------------*/
        if ($request->filled('type')) {
            $type = strtoupper($request->type);
    
            if ($type === "CR") {
                $query->where('wallet_transactions.reciever_id', $user->id);
            }
    
            if ($type === "DR") {
                $query->where('wallet_transactions.user_id', $user->id);
            }
        }
    
        /* -----------------------------------
            OPTIONAL FILTERS
        -------------------------------------*/
        $filters = [
            'status'         => fn($q, $v) => $q->where('wallet_transactions.status', strtoupper($v)),
            'sender_id'      => fn($q, $v) => $q->where('wallet_transactions.user_id', $v),
            'reciever_id'    => fn($q, $v) => $q->where('wallet_transactions.reciever_id', $v),
            'mobile_number'  => fn($q, $v) => $q->where('wallet_transactions.mobile_number', 'LIKE', "%$v%"),
            'operator'       => fn($q, $v) => $q->where('wallet_transactions.operator', $v),
            'account_number' => fn($q, $v) => $q->where('wallet_transactions.account_number', 'LIKE', "%$v%"),
            'user_id'        => fn($q, $v) => $q->where('wallet_transactions.user_id', $v),
            'route'          => fn($q, $v) => $q->where('wallet_transactions.route', $v),
            'txn_id'         => fn($q, $v) => $q->where('wallet_transactions.txn_id', $v),
        ];
    
        foreach ($filters as $key => $callback) {
            if ($request->filled($key)) {
                $callback($query, $request->$key);
            }
        }
    
        /* -----------------------------------
            DATE FILTER
        -------------------------------------*/
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('wallet_transactions.created_at', [
                $request->from_date . " 00:00:00",
                $request->to_date   . " 23:59:59"
            ]);
        }
    
        /* -----------------------------------
            SENDER + RECEIVER WISE SUMMARY
        -------------------------------------*/
    
        $summary = $query
            ->join('users as sender', 'sender.id', '=', 'wallet_transactions.user_id')
            ->join('users as receiver', 'receiver.id', '=', 'wallet_transactions.reciever_id')
            ->selectRaw("
                wallet_transactions.user_id as sender_id,
                sender.establishment as sender_establishment,
    
                wallet_transactions.reciever_id as receiver_id,
                receiver.establishment as receiver_establishment,
                receiver.mobile as receiver_mobile,
    
                COUNT(*) as total_txn,
    
                SUM(CASE WHEN wallet_transactions.operator = 'W2W Transfer' THEN 1 ELSE 0 END) as transfer_count,
                SUM(CASE WHEN wallet_transactions.operator = 'W2W Transfer' THEN wallet_transactions.amount ELSE 0 END) as transfer_amount,
    
                SUM(CASE WHEN wallet_transactions.operator = 'Wallet Debit' THEN 1 ELSE 0 END) as debit_count,
                SUM(CASE WHEN wallet_transactions.operator = 'Wallet Debit' THEN wallet_transactions.amount ELSE 0 END) as debit_amount
            ")
            ->groupBy(
                'wallet_transactions.user_id',
                'sender.establishment',
                'wallet_transactions.reciever_id',
                'receiver.establishment',
                'receiver.mobile'
            )
            ->get()
            ->map(function ($row) {
                $row->net_amount = $row->transfer_amount - $row->debit_amount;
                return $row;
            });
    
        return ResponseHelper::success('Sender & Receiver wise summary fetched', $summary, 200);
    }
    public function getW2WTransaction1(Request $request)
    {
            $user = app('auth')->user();
        
            if (!$request->filled('service')) {
                return ResponseHelper::error('Service field required', [], 403);
            }
        
            $query = WalletTransaction::query();
        
            // For non-admin users, fetch transactions where they are sender or receiver
            if (!in_array($user->role, ["sadm", "adm"])) {
                $userIds = User::getDescendantUserIds($user->id);
        
                // Include current user in the list
                $userIds[] = $user->id;
        
                $query->where(function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds)
                      ->orWhereIn('reciever_id', $userIds);
                });
            }
        
            // Filter by service
            $query->where('operator', $request->service_name);
        
            // Optional filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        
            if ($request->filled('txn_id')) {
                $query->where('txn_id', $request->txn_id);
            }
        
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            }
        
            // Export all results if requested
            if ($request->input('export') == 1) {
                $transactions = $query->latest()->get();
                return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
            }
        
            // Paginate otherwise
            $paginate = $request->input('paginate', 15);
            $transactions = $query->latest()->paginate($paginate);
        
            return ResponseHelper::success('Transactions fetched successfully', $transactions, 200);
        }
    public function getDebitWallateTxn(Request $request)
    {
            $user = Auth::user();
        
            $query = WalletTransaction::query();
            
            // Filter only Debit wallet transactions
            $query->where("operator", "Wallet Debit");
        
            // If NOT admin or super admin → show only user's own transactions
            if (!in_array($user->role, ['adm', 'sadm'])) {
                $query->where("user_id", $user->id);
            }
        
            // Optional: add ordering
            $query->orderBy('id', 'desc');
        
            return ResponseHelper::success("Success", $query->get());
        }
    public function getReceiver(Request $request)
    {
            $validator = WalletTransferValidation::findUser($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
        
            $user_id = $request->user_id;
        
            // Check prefix
            if (!str_starts_with($user_id, 'TRANS')) {
                return ResponseHelper::error('User not found', [], 422);
            }
        
            // Extract numeric ID
            $id = substr($user_id, 5);
        
            // Query user properly
            $user = User::where('id', $id)->where('status', 1)
                ->whereIn('role', ['ret','dd','di','md'])
                ->select('id', 'name', 'role', 'mobile', 'establishment','w1')
                ->first();
        
            if (!$user) {
                return ResponseHelper::error('User not found', [], 403);
            }
        
            return ResponseHelper::success('User fetched', $user, 200);
        }
    //   public function createW2W(Request $request)
    // {   
    //     $user=Auth::user();
    //     // Validation
    //     $validator = WalletTransferValidation::createTransfer($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    //       $mpin=OTP::verifyMpin($request->mpin);
    //     if(!$mpin){
    //          return ResponseHelper::error("invalid mpin", [], 403);
    //     }
    //   $sender = Auth::user(); 
    //     if (!$sender) {
    //         return ResponseHelper::error('Unauthorized user', [], 401);
    //     }
    //     $receiver = User::find($request->reciever_id);
    //     if (!$receiver) {
    //         return ResponseHelper::error('Receiver not found', [], 404);
    //     }
    //         $account = Account::where("user_id", $receiver->user_id)->lockForUpdate()->first();
    //             if (!$account) {
    //                 return ResponseHelper::error('Admin Account Not Found', [], 403);
    //             }
    //     if ($sender->id == $receiver->id) {
    //         return ResponseHelper::error('You cannot send money to yourself', [], 403);
    //     }
    //     $netAmount = $request->amount+$user->lien;
    //     if (!in_array($user->role, ["adm", "sadm"])) {
    //     if ($sender->w1 < $netAmount ) {
    //         return ResponseHelper::error('Insufficient W1 Balance1', [], 403);
    //     }
    //     }
    //     // Get service
    //     $service = Service::find($request->operator);
    //     if (!$service) {
    //         return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
    //     }
    
    //     // Commission
    //     $comm = CommRule::calculateCommission($sender, $service->name, $request->amount);
    // //   return $comm;
    //     DB::beginTransaction();
    //     try {
    //         $transfer = WalletTransferService::create($sender, $receiver, $service, $request);
    //          if (!in_array($user->role, ["adm", "sadm"])) {
    //           WalletService::debit($sender, $request->amount, $transfer, $service);
    //          }
    //          WalletService::credit($receiver, $request->amount, $transfer, $service);
    //           if (!in_array($user->role, ["adm", "sadm"])) {
    //           $fundrequest=new FundRequest();
    //           $fundrequest->user_id=$receiver->id;
    //           $fundrequest->asm_id=$receiver->parent;
    //           $fundrequest->role=$receiver->role;
    //           $fundrequest->name=$receiver->name;
    //           $fundrequest->username=$receiver->mobile;
    //           $fundrequest->remark="Admin transfer";
    //           $fundrequest->admin_remark="W2W transfer by Admin";
    //           $fundrequest->bank_ref_id=$transfer->txn_id;
    //           $fundrequest->amount=$request->amount;
    //           $fundrequest->approved_by=$sender->name;
    //           $fundrequest->status="approved";
    //           $fundrequest->client_ref=$transfer->client_ref;
    //           $fundrequest->save();
    //             $account->balance += $request->amount;
    //             $account->save();
    //             $accountStatement = new AccountStatement();
    //             $accountStatement->credit = $request->amount;
    //             $accountStatement->account_id = $account->id;
    //             $accountStatement->balance = $account->balance;
    //             $accountStatement->remarks = "Fund Request Approved By {$user->name}";
    //             $accountStatement->particulars = "Fund Request Approved By {$user->name}";
    //             $accountStatement->claimed_by = $user->id;
    //             $accountStatement->save();
    //           }
    //         AuditService::logRequest($sender, $request, $service);
    
    //         DB::commit();
    
    //         return ResponseHelper::success('Transaction successfully', $transfer, 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
    //     }
    // }
    public function createW2W(Request $request)
    {
        $user = Auth::user();
        $validator = WalletTransferValidation::createTransfer($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $dupCheck = Uuid::check($request->client_ref,'createW2W',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if (!$mpin) {
            return ResponseHelper::error("Invalid MPIN", [], 403);
        }
    
        $sender = Auth::user();
        if (!$sender) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
        $receiver = User::find($request->reciever_id);
        if (!$receiver) {
            return ResponseHelper::error('Receiver not found', [], 404);
        }
    
        $account = Account::where("user_id", $receiver->id)
            ->lockForUpdate()
            ->first();
            
          if (in_array($user->role, ["adm", "sadm"])) {   
        if (!$account) {
            return ResponseHelper::error('Admin Account Not Found', [], 403);
        }
          }
        if ($sender->id == $receiver->id) {
            return ResponseHelper::error('You cannot send money to yourself', [], 403);
        }
        $netAmount = $request->amount + $user->lien;
        if (!in_array($user->role, ["adm", "sadm"])) {
            if ($sender->w1/100 < $netAmount) {
                return ResponseHelper::error('Insufficient W1 Balance', [], 403);
            }
        }
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
        }
        $comm = CommRule::calculateCommission($sender, $service->name, $request->amount);
        // return $comm; // (for debugging if needed)
    
        DB::beginTransaction();
        try {
            $transfer = WalletTransferService::create($sender, $receiver, $service, $request);
            if (!in_array($user->role, ["adm", "sadm"])) {
                WalletService::debit($sender, $request->amount, $transfer, $service);
            }
            WalletService::credit($receiver, $request->amount, $transfer, $service);
            if (in_array($user->role, ["adm", "sadm"])) {
                $fundrequest = new FundRequest();
                $fundrequest->user_id     = $receiver->id;
                $fundrequest->asm_id      = $receiver->parent;
                $fundrequest->role        = $receiver->role;
                $fundrequest->name        = $receiver->establishment;
                $fundrequest->username    = $receiver->mobile;
                $fundrequest->remark      = "Admin transfer";
                $fundrequest->admin_remark= $request->remark??"W2W transfer by Admin";
                $fundrequest->bank_ref_id = $transfer->txn_id;
                $fundrequest->amount      = $request->amount;
                $fundrequest->bank_name      = $request->bank_name;
                $fundrequest->approved_by = $sender->name;
                $fundrequest->status      = "approved";
                $fundrequest->client_ref  = $transfer->client_ref;
                $fundrequest->save();
    
                // Update account balance
                $account->balance += $request->amount;
                $account->save();
    
                // Account statement
                $accountStatement = new AccountStatement();
                $accountStatement->credit      = $request->amount;
                $accountStatement->account_id  = $account->id;
                $accountStatement->balance     = $account->balance;
                $accountStatement->remarks     = "Fund Request Approved By {$user->name}";
                $accountStatement->particulars = "Fund Request Approved By {$user->name}";
                $accountStatement->claimed_by  = $user->id;
                $accountStatement->save();
                
                  $template = Template::where('name','CREDITED_AMOUNT')->first();
                
                $message = $template->message;
                  $receivernew = User::find($request->reciever_id);
                $message = str_replace('{$amount}', $request->amount, $message);
                $message = str_replace('{$user_bal}', $receivernew->w1/100, $message);
                
                $data = [
                    "number"  => $receiver->mobile,
                    "message" => $message,
                    "tempid"  => $template->temp_id
                ];
                
                $res = Sms::send($data, $user);
            }
    
            // Audit log
            AuditService::logRequest($sender, $request, $service);
    
            DB::commit();
            return ResponseHelper::success('Transaction successful', $transfer, 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }

    public function createW2W1(Request $request)
    {
        $user=Auth::user();
        // Validation
        $validator = WalletTransferValidation::createTransferW2W1($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $dupCheck = Uuid::check($request->client_ref,'createW2W1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
            //   $mpin=OTP::verifyMpin($request->mpin);
        // if(!$mpin){
        //      return ResponseHelper::error("invalid mpin", [], 403);
        // }
       $sender = Auth::user(); 
        if (!$sender) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
   
        $amount = $request->amount;
        if ($sender->w2/100 < ($amount+$user->lien) ) {
            return ResponseHelper::error('Insufficient W2 Balance', [], 403);
        }
        // Get service
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
        }
    
        // Commission
        $comm = CommRule::calculateCommission($sender, $service->name, $amount);
    
        DB::beginTransaction();
        try {
            $transfer = WalletTransferService::createW2W1($sender, $service, $request, $comm);
            WalletService2::debit($sender, $amount, $transfer, $service);
            WalletService::credit($sender, $amount, $transfer, $service);
            AuditService::logRequest($sender, $request, $service);
    
            DB::commit();
    
            return ResponseHelper::success('Transaction successfully', $transfer, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }
    public function createW3W1(Request $request)
    {
        $user=Auth::user();
        $dupCheck = Uuid::check($request->client_ref,'createW3W1',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        // Validation
        $validator = WalletTransferValidation::createTransferW2W1($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
            //   $mpin=OTP::verifyMpin($request->mpin);
        // if(!$mpin){
        //      return ResponseHelper::error("invalid mpin", [], 403);
        // }
       $sender = Auth::user(); 
        if (!$sender) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
   
        $amount = $request->amount;
        if ($sender->w3/100 < ($amount +$user->lien)) {
            return ResponseHelper::error('Insufficient W3 Balance', [], 403);
        }
        // Get service
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
        }
    
        // Commission
        $comm = CommRule::calculateCommission($sender, $service->name, $amount);
    
        DB::beginTransaction();
        try {
            $transfer = WalletTransferService::createW3W1($sender, $service, $request, $comm);
            WalletService3::debit($sender, $amount, $transfer, $service);
            WalletService::credit($sender, $amount, $transfer, $service);
            AuditService::logRequest($sender, $request, $service);
    
            DB::commit();
    
            return ResponseHelper::success('Transaction successfully', $transfer, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }

    public function updateTransfer(Request $request)
    {
        $validator = WalletTransferValidation::updateTransfer($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        if (!in_array(Auth::user()->role, ['sadm', 'adm'])) {
            return ResponseHelper::error('Unauthorised Action', [], 403);
        }

        $transfer = WalletTransfer::find($request->id);
        if (!$transfer) {
            return ResponseHelper::error('Transfer not found', [], 404);
        }

        $transfer->amount     = $request->amount     ?? $transfer->amount;
        $transfer->status     = $request->status     ?? $transfer->status;
        $transfer->client_ref = $request->client_ref ?? $transfer->client_ref;
        $transfer->order_id   = $request->order_id   ?? $transfer->order_id;

        if ($transfer->save()) {
            return ResponseHelper::success('Wallet transfer updated successfully', $transfer, 200);
        }

        return ResponseHelper::error('Something went wrong', [], 500);
    }
    // public function createDebit(Request $request)
    // {
    //     $user = Auth::user();
    //     $validator = WalletTransferValidation::createDebit($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    //     $mpin = OTP::verifyMpin($request->mpin);
    //     if (!$mpin) {
    //         return ResponseHelper::error("Invalid MPIN", [], 403);
    //     }
    
    //     $sender = Auth::user();
    //     if (!in_array($sender->role,['adm','sadm'])) {
    //         return ResponseHelper::error('Unauthorized user', [], 401);
    //     }
    //     $receiver = User::find($request->reciever_id);
    //     if (!$receiver) {
    //         return ResponseHelper::error('Receiver not found', [], 404);
    //     }
    
    //     $account = Account::where("user_id", $receiver->id)
    //         ->lockForUpdate()
    //         ->first();
            
    //      if (!$account) {
    //         return ResponseHelper::error('Accound not found ', [], 403);
    //     }
    //     if ($sender->id == $receiver->id) {
    //         return ResponseHelper::error('You cannot send money to yourself', [], 403);
    //     }
    //     $netAmount = $request->amount + $user->lien;
    //     if ($receiver->w1 < $netAmount*100) {
    //             return ResponseHelper::error('Insufficient W1 Balance', [], 403);
    //     }
    //     $service = Service::find($request->operator);
    //     if (!$service) {
    //         return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
    //     }
    //     $comm = CommRule::calculateCommission($sender, $service->name, $request->amount);
    //     // return $comm; // (for debugging if needed)
    
    //     DB::beginTransaction();
    //     try {
    //         $transfer = WalletTransferService::create($sender, $receiver, $service, $request);
    //           WalletService::debit($receiver, $request->amount, $transfer, $service);
    //             $account->balance -= $request->amount;
    //             $account->save();
    //             $accountStatement = new AccountStatement();
    //             $accountStatement->credit      = $request->amount;
    //             $accountStatement->account_id  = $account->id;
    //             $accountStatement->balance     = $account->balance;
    //             $accountStatement->remarks     = "Amount debited by {$user->name}";
    //             $accountStatement->particulars = "Amount debited by {$user->name}";
    //             $accountStatement->claimed_by  = $user->id;
    //             $accountStatement->save();
         
    //         AuditService::logRequest($sender, $request, $service);
    
    //         DB::commit();
    //         return ResponseHelper::success('Transaction successful', $transfer, 200);
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
    //     }
    // }
      public function createDebit(Request $request)
    {
        $user = Auth::user();
        $validator = WalletTransferValidation::createDebit($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        $dupCheck = Uuid::check($request->client_ref,'createDebit',$request);
        if($dupCheck['code'] != '200'){
            return ResponseHelper::error('Duplicate Transaction',[],402);
        }
        $mpin = OTP::verifyMpin($request->mpin);
        if (!$mpin) {
            return ResponseHelper::error("Invalid MPIN", [], 403);
        }
    
        $sender = Auth::user();
        if (!in_array($sender->role,['adm','sadm','md','di'])) {
            return ResponseHelper::error('Unauthorized user', [], 401);
        }
        $receiver = User::find($request->reciever_id);
        if (!$receiver) {
            return ResponseHelper::error('Receiver not found', [], 404);
        }
    
        $account = Account::where("user_id", $receiver->id)
            ->lockForUpdate()
            ->first();
        if(in_array($user->role, ["adm", "sadm"])){
        if (!$account) {
            return ResponseHelper::error('Account not found ', [], 403);
        }
            }
        if ($sender->id == $receiver->id) {
            return ResponseHelper::error('You cannot send money to yourself', [], 403);
        }
        $netAmount = $request->amount + $user->lien;
        if ($receiver->w1 < $netAmount*100) {
                return ResponseHelper::error('Insufficient W1 Balance', [], 403);
        }
        $service = Service::find($request->operator);
        if (!$service) {
            return ResponseHelper::error('Wallet transfer Service Not Configured', [], 500);
        }
        $comm = CommRule::calculateCommission($sender, $service->name, $request->amount);
        // return $comm; // (for debugging if needed)
    
        DB::beginTransaction();
        try {
            $transfer = WalletTransferService::create($sender, $receiver, $service, $request);
              WalletService::debit($receiver, $request->amount, $transfer, $service);
              if(in_array($user->role, ["di", "md"])){
              WalletService::credit($sender, $request->amount, $transfer, $service);
              }
              if(in_array($user->role, ["adm", "sadm"])){
                $account->balance -= $request->amount??0;
                $account->save();
                $accountStatement = new AccountStatement();
                $accountStatement->credit      = $request->amount;
                $accountStatement->account_id  = $account->id;
                $accountStatement->balance     = $account->balance??0;
                $accountStatement->remarks     = "Amount debited by {$user->name}";
                $accountStatement->particulars = "Amount debited by {$user->name}";
                $accountStatement->claimed_by  = $user->id;
                $accountStatement->save();
                $template = Template::where('name','DEBITED_AMOUNT')->first();
                
                $message = $template->message;
                  $receivernew = User::find($request->reciever_id);
                $message = str_replace('{$amount}', $request->amount, $message);
                $message = str_replace('{$user_bal}', $receivernew->w1/100, $message);
                
                $data = [
                    "number"  => $receiver->mobile,
                    "message" => $message,
                    "tempid"  => $template->temp_id
                ];
                
                $res = Sms::send($data, $user);
              }
            AuditService::logRequest($sender, $request, $service);
    
            DB::commit();
            return ResponseHelper::success('Transaction successful', $transfer, 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Transaction Failed', $e->getMessage(), 500);
        }
    }

    // âœ… Delete transfer
    public function deleteTransfer(Request $request)
    {
        $validator = WalletTransferValidation::deleteTransfer($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }

        $transfer = WalletTransaction::find($request->id);
        if (!$transfer) {
            return ResponseHelper::error('Transfer not found', [], 404);
        }

        if ($transfer->delete()) {
            return ResponseHelper::success('Wallet transfer deleted successfully', [], 200);
        }

        return ResponseHelper::error('Wallet transfer could not be deleted', [], 500);
    }
}
