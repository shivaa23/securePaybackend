<?php

namespace App\Modules\Dmt\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helper\ResponseHelper;
use App\Modules\Dmt\Models\DmtTxn;
use App\Modules\Dmt\Models\VerificationTxn;
use App\Modules\User\Models\User;
use Auth;
class DmtController extends BaseController
{
    

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);

    }
    
    // public function getDmtTxns(Request $request)
    // {
    //     $user = app('auth')->user();
    
    //     $query = DmtTxn::with('user');
    
    //     // if (in_array($user->role, ['ret', 'dd', 'api'])) {
    //     //     $query->where('user_id', $user->id);
    //     // } elseif (in_array($user->role, ['md', 'di'])) {
    //     //     $userIds = User::getDescendantUserIds($user->id);
    //     //     $query->whereIn('user_id', $userIds);
    //     // }
    
    //     // if ($request->filled('from_date') && $request->filled('to_date')) {
    //     //     $query->whereDate('created_at', '>=', $request->from_date)
    //     //           ->whereDate('created_at', '<=', $request->to_date);
    //     // }
    
    //     // if ($request->filled('sender_mobile')) {
    //     //     $query->where('sender_mobile', $request->sender_mobile);
    //     // }
    //     // if ($request->filled('status')) {
    //     //     $query->where('status', strtoupper($request->status));
    //     // }
    //     // if ($request->filled('account_number')) {
    //     //     $query->where('account_number', $request->account_number);
    //     // }
    //     // if ($request->filled('user_id')) {
    //     //     $query->where('user_id', $request->user_id);
    //     // }
    //     // if ($request->filled('route')) {
    //     //     $query->where('route', $request->route);
    //     // }
    //     // if ($request->filled('txn_id')) {
    //     //     $query->where('txn_id', $request->txn_id);
    //     // }
    
    //     // if ($request->input('export') == 1) {
    //     //     $txns = $query->latest()->get();
    //     //     $txns->transform(function ($txn) {
    //     //         $txn->establishment = $txn->user->establishment ?? null;
                
    //     //         unset($txn->user);
    //     //         return $txn;
    //     //     });
    //     //     return ResponseHelper::success('Transactions fetched', $txns, 200);
    //     // }
    
    //     // $paginate = $request->input('paginate', 15);
    //     // $txns = $query->latest()->paginate($paginate);
    //     // $txns->getCollection()->transform(function ($txn) {
    //     //     $txn->establishment = $txn->user->establishment ??null;
    //     //     unset($txn->user);
    //     //     return $txn;
    //     // });
    
    //     // return ResponseHelper::success('Transactions fetched', $txns, 200);
    //      switch ($user->role) {
    //         case 'sadm':
    //         case 'adm':
    //             if($request->boolean('export')) {
    //               $query->select([
    //                     'id', 'txn_id', 'user_id', 'sender_mobile', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
    //                     'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm',
    //                     'charges','gst','status','mop','created_at','updated_at'
    //                 ]); 
    //             }
    //             break;
    //         case 'md':
    //             $userIds = User::getDescendantUserIds($user->id);
    //             $userIds = array_merge([$user->id], $userIds ?? []);
    //             $query->select([
    //                 'id', 'txn_id', 'user_id', 'pf', 'sender_mobile', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
    //                 'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','charges','gst','status','mop','created_at','updated_at'
    //             ])->whereIn('user_id', $userIds);
    //             break;
    //         case 'di':
    //             $userIds = User::getDescendantUserIds($user->id);
    //             $userIds = array_merge([$user->id], $userIds ?? []);
    //             $query->select([
    //                 'id', 'txn_id', 'user_id', 'pf', 'sender_mobile', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
    //                 'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','charges','gst','status','mop','created_at','updated_at'
    //             ])->whereIn('user_id', $userIds);
    //             break;
    //         case 'dd':
    //         case 'api':
    //         case 'ret':
    //             $query->select([
    //             'id', 'txn_id', 'user_id', 'pf', 'sender_mobile', 'beneficiary_name', 'account_number', 'ifsc_code','bank_name',
    //             'operator','operator_id','amount','ret_comm','ret_tds','charges','gst','status','mop','created_at','updated_at'
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
    // }
    public function getDmtTxns(Request $request)
    {
        $user = Auth::user();
        $query = DmtTxn::with('user');
    
        // ---------------- FULL FIELD LIST ----------------
        $allFields = [
            'id','txn_id','user_id','pf','sender_mobile','beneficiary_name','account_number','ifsc_code','bank_name',
            'operator','operator_id','amount','ret_comm','ret_tds','di_comm','di_tds','md_comm','md_tds','a_comm',
            'charges','gst','status','mop','route','created_at','updated_at'
        ];
    
        // ---------------- ROLE-WISE HIDE FIELDS ----------------
        $hideForMD  = ['route'];  
        $hideForDI  = ['md_comm','md_tds','a_comm']; 
        $hideForRET = ['di_comm','di_tds','md_comm','md_tds','a_comm','route'];
    
        // ---------------- DETERMINE FIELDS ----------------
        switch ($user->role) {
    
            case 'sadm':
            case 'adm':
                $allowed = $allFields;
                break;
    
            case 'md':
                $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
                $query->whereIn('user_id', $userIds);
    
                $allowed = array_diff($allFields, $hideForMD);
                break;
    
            case 'di':
                $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
                $query->whereIn('user_id', $userIds);
    
                $allowed = array_diff($allFields, $hideForDI);
                break;
    
            case 'dd':
            case 'api':
            case 'ret':
                $query->where('user_id', $user->id);
    
                $allowed = array_diff($allFields, $hideForRET);
                break;
    
            default:
                return ResponseHelper::error('Unauthorized role access', [], 403);
        }
    
        // APPLY final select
        $query->select($allowed);
    
        // ---------------- FILTERS ----------------
        $query
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', strtoupper($request->status))
            )
            ->when($request->filled('sender_mobile'), fn($q) =>
                $q->where('sender_mobile', $request->sender_mobile)
            )
            ->when($request->filled('account_number'), fn($q) =>
                $q->where('account_number', $request->account_number)
            )

             ->when($request->filled('beneficiary_name'), fn($q) =>
                $q->where('beneficiary_name', $request->beneficiary_name)
            )
            ->when($request->filled('sender_mobile'), fn($q) =>
                $q->where('sender_mobile', $request->sender_mobile)
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
                    $request->to_date   . ' 23:59:59'
                ])
            );
    
        // ---------------- EXPORT ----------------
        if ($request->boolean('export')) {
              ini_set('memory_limit', '512M');
            $txns = $query->latest()->get()->map(function ($txn) {
                $txn->establishment = $txn->user->establishment ?? null;
                unset($txn->user);
                return $txn;
            });
    
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
    
        // ---------------- PAGINATION ----------------
        $paginate = $request->input('paginate', 15);
    
        $txns = $query->latest()->paginate($paginate);
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ?? null;
            unset($txn->user);
            return $txn;
        });
    
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }

        
    public function getaccVerifyTxns(Request $request)
    {
        $user = app('auth')->user();
    
        $query = VerificationTxn::with('user');
    
        if (in_array($user->role, ['ret', 'dd', 'api'])) {
            $query->where('user_id', $user->id);
        } elseif (in_array($user->role, ['md', 'di'])) {
            $userIds = User::getDescendantUserIds($user->id);
            $query->whereIn('user_id', $userIds);
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereDate('created_at', '>=', $request->from_date)
                  ->whereDate('created_at', '<=', $request->to_date);
        }
    
        if ($request->filled('sender_mobile')) {
            $query->where('sender_mobile', $request->sender_mobile);
        }
        
        if ($request->filled('beneficiary_name')) {
            $query->where('beneficiary_name', $request->beneficiary_name);
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }
        if ($request->filled('account_number')) {
            $query->where('account_number', $request->account_number);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('route')) {
            $query->where('route', $request->route);
        }
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->input('export') == 1) {
            $txns = $query->latest()->get();
            $txns->transform(function ($txn) {
                $txn->establishment = $txn->user->establishment ?? null;
                
                unset($txn->user);
                return $txn;
            });
            return ResponseHelper::success('Transactions fetched', $txns, 200);
        }
    
        $paginate = $request->input('paginate', 15);
        $txns = $query->latest()->paginate($paginate);
        $txns->getCollection()->transform(function ($txn) {
            $txn->establishment = $txn->user->establishment ??null;
            unset($txn->user);
            return $txn;
        });
    
        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }


}
