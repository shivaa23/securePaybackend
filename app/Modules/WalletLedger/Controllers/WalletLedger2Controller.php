<?php

namespace App\Modules\WalletLedger\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\WalletLedger\Models\WalletLedger2;
use App\Modules\User\Models\User;
use App\Modules\WalletLedger\Models\WalletLedgerValidation;
use Auth;
use App\Helper\ResponseHelper;

class WalletLedger2Controller extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    //  public function getWalletLedgers(Request $request)
    // {
    //     $authUser = Auth::user(); // Lumen Auth
    
    //     if ($authUser->role != "sadm" && $authUser->role != "adm") {
    //         $query = WalletLedger2::with('user')->where("user_id", $authUser->id)->orderBy('id','desc');
    //                     if ($request->filled('user_id')) {
    //             $query->where('user_id', substr($request->user_id, 5));
    //         }
            
    //           if ($request->filled('service_name')) {
    //                 $query->where('service', $request->service_name);
    //           }
    //         // Filter by registered number
    //         if ($request->filled('mobile')) {
    //             $user = User::where('mobile', $request->mobile)->first();
    //             if(!$user){
    //                 return ResponseHelper::success('No Data Found', [], 200);
    //             }
    //             $query->where('user_id', $user->id);
    //         }
    //     } else {
    //         $query = WalletLedger2::with('user')->orderBy('id','desc');
            
    //         // Filter by user_id
    //         if ($request->filled('user_id')) {
    //             $query->where('user_id', substr($request->user_id, 5));
    //         }
            
    //           if ($request->filled('service_name')) {
    //                 $query->where('service', $request->service_name);
    //           }
    //         // Filter by registered number
    //         if ($request->filled('mobile')) {
    //             $user = User::where('mobile', $request->mobile)->first();
    //             if(!$user){
    //                 return ResponseHelper::success('No Data Found', [], 200);
    //             }
    //             $query->where('user_id', $user->id);
    //         }
    //     }
    
    //     // Date filter
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $query->whereDate('created_at', '>=', $request->from_date)
    //               ->whereDate('created_at', '<=', $request->to_date);
    //     }
    
    //     // Dynamic pagination
    //     $paginate = $request->input('paginate', 15);
    //     $walletledgers = $query->paginate((int) $paginate);
    
    //     // Map only establishment
    //     $walletledgers->getCollection()->transform(function ($ledger) {
    //         $ledger->establishment = $ledger->user->establishment ?? 'Unknown';
    //         unset($ledger->user); // remove full user object
    //         return $ledger;
    //     });
    
    //     if ($walletledgers->isNotEmpty()) {
    //         return ResponseHelper::success('Wallet Ledgers fetched', $walletledgers, 200);
    //     } else {
    //         return ResponseHelper::success('No Data Found', [], 200);
    //     }
    // }
     public function getWalletLedgers(Request $request)
    {
        $user = Auth::user();
    
        $query = WalletLedger2::with('user')->orderBy('id', 'desc');
    
        if (!in_array($user->role, ['sadm', 'adm'])) {
            $userIds = array_merge([$user->id], User::getDescendantUserIds($user->id) ?? []);
            $query->whereIn('user_id', $userIds);
        }
    
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
    
        if ($request->filled('service_name')) {
            $query->where('service', $request->service_name);
        }
    
        if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('created_at', '>=', $request->from_date)
                      ->whereDate('created_at', '<=', $request->to_date);
            }
    
        $walletLedgers = $request->filled('export') && $request->export == 1
            ? $query->get()
            : $query->paginate((int)$request->input('paginate', 15));
    
        $collection = $walletLedgers instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $walletLedgers->getCollection()
            : $walletLedgers;
    
        $collection->transform(function ($row) {
            $row->establishment = $row->user->establishment ?? 'Unknown';
            unset($row->user);
            return $row;
        });
    
        if ($collection->isNotEmpty()) {
            return ResponseHelper::success('Wallet Ledgers fetched', $walletLedgers, 200);
        } else {
            return ResponseHelper::success('No Data Found', [], 200);
        }
    }


}
