<?php

namespace App\Modules\Matm\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Modules\Matm\Models\MatmTxn;
use App\Modules\User\Models\User;
use App\Modules\Matm\Models\MatmValidation;
use Auth;
use App\Helper\ResponseHelper;

class MatmController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);

    }
    
    public function getMatmTxns(Request $request){
        $user = Auth::user();
        $query = MatmTxn::query();
        if (!in_array($user->role, ["sadm", "adm"])) {
            $userIds = User::getDescendantUserIds($user->id);
            (empty($userIds)) ? $query->where("user_id", $user->id) : $query->whereIn("user_id", $userIds);
        }
        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('route')) {
            $query->where('route', $request->route);
        }
        if ($request->filled('client_ref')) {
            $query->where('client_ref', $request->client_ref);
        }
        if ($request->filled('txn_id')) {
            $query->where('txn_id', $request->txn_id);
        }
        $paginate = $request->input('paginate', 15);
        $txns = $query->latest()->paginate($paginate);

        return ResponseHelper::success('Transactions fetched', $txns, 200);
    }
}
