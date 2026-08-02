<?php

namespace App\Modules\FundRequest\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Modules\FundRequest\Models\FundRequest;
use App\Modules\Account\Models\Account;
use App\Modules\User\Models\User;
use App\Modules\FundRequest\Models\FundRequestValidation;
use App\Traits\ApiResponser;
use App\Helper\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Auth;
use App\Modules\Auth\Models\Otp;
use App\Modules\FundRequest\Models\FundTxnId;
use App\Modules\AuditLog\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use App\Modules\Account\Models\AccountStatement;
use Illuminate\Support\Facades\DB;
use App\Modules\WalletLedger\Services\WalletService;
use App\Modules\Template\Models\Template;
use App\Modules\Message\Models\Sms;
use App\Modules\Misc\Models\Uuid;
use Carbon\Carbon;
class FundRequestController extends BaseController
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
    }
    public function getTxnId(Request $request)
    {
        $user = Auth::user();
        $txn = "TXN" . time() . rand(1000, 9999);
        $txnId = new FundTxnId();
        $txnId->user_id = $user->id;
        $txnId->txn_id = $txn;
        $txnId->save();
        return ResponseHelper::success('Transaction ID generated successfully', [
            'txn_id' => $txnId->txn_id,
            'user_id' => $txnId->user_id
        ], 200);
    }
    public function getFundRequestsSchema()
    {
        $schema=FundRequestValidation::getSchema();
        return ResponseHelper::success('FundRequests fetched', $schema, 200);
    }
    // public function createFundRequestnew(Request $request)
    // {
    //     $user = Auth::user();
    //     $validator = FundRequestValidation::createFundRequest1($request->all());
    
    //     if ($validator->fails()) {
    //         return ResponseHelper::error("Validation error", $validator->errors(), 422);
    //     }
    //     $account = Account::where("user_id", $user->id)->first();
    //     if (!$account) {
    //         return ResponseHelper::error('Not Authorised. Please contact your administrator.', [], 403);
    //     }
    //     if (!$user || !in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
    //         return ResponseHelper::error('Operation Not Authorised', [], 403);
    //     }
    //     $fundRequest = new FundRequest();
    //     $fundRequest->bank_name   = $request->bank_name;
    //     $fundRequest->receipt     = $this->saveBase64Image($request->receipt, '');
    //     $fundRequest->status      = "pending";
    //     $fundRequest->asm_id      = $user->parent_id;
    //     $fundRequest->user_id     = $user->id;
    //     $fundRequest->username     = $user->username;
    //     $fundRequest->role     = $user->role;
    //     $fundRequest->name        = $user->establishment;
    //     $fundRequest->mode        = $request->mode;
    //     $fundRequest->bank_ref_id = $request->bank_ref_id;
    //     $fundRequest->date        = $request->date;
    //     $fundRequest->amount      = $request->amount;
    //     $fundRequest->remark      = $request->remark;
    //     $fundRequest->client_ref  = $request->client_ref;
        
        
    
    //     if ($fundRequest->save()) {
    
    //         AuditLog::create([
    //             'user_id'      => $user->id,
    //             'role'         => $user->role,
    //             'action'       => 'CREATE_FUND_REQUEST',
    //             'service_name' => 'fund_request',
    //             'request_data' => json_encode($request->all()),
    //             'response_data'=> json_encode($fundRequest),
    //             'ip_address'   => $request->ip(),
    //             'user_agent'   => $request->header('User-Agent'),
    //             'status'       => 'Success'
    //         ]);
    
    //         return ResponseHelper::success('FundRequest Created Successfully', $fundRequest, 200);
    //     } else {
    //         return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
    //     }
    // }
// public function createFundRequestnew(Request $request)
// {
//     $user = Auth::user();

//     $validator = FundRequestValidation::createFundRequest1($request->all());
//     if ($validator->fails()) {
//         return ResponseHelper::error("Validation error", $validator->errors(), 422);
//     }

//     $account = Account::where("user_id", $user->id)->first();
//     if (!$account) {
//         return ResponseHelper::error(
//             'Not Authorised. Please contact your administrator.',
//             [],
//             403
//         );
//     }

//     if (!in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
//         return ResponseHelper::error('Operation Not Authorised', [], 403);
//     }

//     // ðŸ”¹ Merge receipt chunks â†’ save ONE image
//     $receiptPath = $this->saveBase64ImageChunks($request->all(), 'receipt');

//     $fundRequest = new FundRequest();
//     $fundRequest->bank_name   = $request->bank_name;
//     $fundRequest->receipt     = $receiptPath;
//     $fundRequest->status      = "pending";
//     $fundRequest->asm_id      = $user->parent_id;
//     $fundRequest->user_id     = $user->id;
//     $fundRequest->username   = $user->username;
//     $fundRequest->role       = $user->role;
//     $fundRequest->name       = $user->establishment;
//     $fundRequest->mode       = $request->mode;
//     $fundRequest->bank_ref_id= $request->bank_ref_id;
//     $fundRequest->date       = $request->date;
//     $fundRequest->amount     = $request->amount;
//     $fundRequest->remark     = $request->remark;
//     $fundRequest->client_ref = $request->client_ref;

//     if ($fundRequest->save()) {
//         return ResponseHelper::success(
//             'FundRequest Created Successfully',
//             $fundRequest,
//             200
//         );
//     }

//     return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
// }

  public function createFundRequestnew(Request $request)
    {
        $user = Auth::user();
    
    
        $validator = FundRequestValidation::createFundRequest1($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
    
        $account = Account::where("user_id", $user->id)->first();
        if (!$account) {
            return ResponseHelper::error(
                'Not Authorised. Please contact your administrator.',
                [],
                403
            );
        }
    
        if (!in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
            return ResponseHelper::error('Operation Not Authorised', [], 403);
        }
    
    
        $dupCheck = Uuid::check($request->client_ref, 'fund_request', $request->all());
    
        if ($dupCheck['code'] !== '200') {
            return ResponseHelper::success(
                'Duplicate Fund Request',
                $dupCheck['data'],
                200
            );
        }
    
    
        Uuid::lock(
            $request->client_ref,
            'fund_request',
            $user->id,
            $request->all()
        );
    
       
        DB::beginTransaction();
        try {
    
    
            $receiptPath = $this->saveBase64ImageChunks($request->all(), 'receipt');
    
            $fundRequest = new FundRequest();
            $fundRequest->bank_name     = $request->bank_name;
            $fundRequest->receipt      = $receiptPath;
            $fundRequest->status       = "pending";
            $fundRequest->asm_id       = $user->parent_id;
            $fundRequest->user_id      = $user->id;
            $fundRequest->username    = $user->username;
            $fundRequest->role        = $user->role;
            $fundRequest->name        = $user->establishment;
            $fundRequest->mode        = $request->mode;
            $fundRequest->bank_ref_id = $request->bank_ref_id;
            $fundRequest->date        = $request->date;
            $fundRequest->amount      = $request->amount;
            $fundRequest->remark      = $request->remark;
            $fundRequest->client_ref  = $request->client_ref;
    
            $fundRequest->save();
    
            Uuid::markCompleted(
                $request->client_ref,
                'fund_request',
                $fundRequest->id,
                $fundRequest
            );
    
            DB::commit();
    
            return ResponseHelper::success(
                'FundRequest Created Successfully',
                $fundRequest,
                200
            );
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            /* ================= UUID FAILED ================= */
            Uuid::markFailed(
                $request->client_ref,
                'fund_request',
                $e->getMessage()
            );
    
            return ResponseHelper::error(
                'FundRequest Cannot Be Created',
                $e->getMessage(),
                500
            );
        }
    }
    
    // public function createFundRequest(Request $request)
    // {
    //     $user = Auth::user();
    //     $validator = FundRequestValidation::createFundRequest($request->all());
    // //   return $request->receipt;
    //     if ($validator->fails()) {
    //         return ResponseHelper::error($validator->errors(), "Validation error", 422);
    //     }
    
    //     $account = Account::where("user_id", $user->id)->first();
    //     if (!$account) {
    //         return ResponseHelper::error('Not Authorised. Please contact your administrator.', [], 403);
    //     }
    
    //     if (!$user || !in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
    //         return ResponseHelper::error('Operation Not Authorised', [], 403);
    //     }
    //     $receiptPath = null;
    //     if ($request->hasFile('receipt')) {
    //         $receiptPath = $this->saveUploadedFile($request->file('receipt'), 'receipt');
    //     }
    
    //     $fundRequest = new FundRequest();
    //     $fundRequest->bank_name   = $request->bank_name;
    //     $fundRequest->receipt     = $receiptPath;
    //     $fundRequest->status      = "pending";
    //     $fundRequest->asm_id      = $user->parent_id;
    //     $fundRequest->user_id     = $user->id;
    //     $fundRequest->username    = $user->username;
    //     $fundRequest->role        = $user->role;
    //     $fundRequest->name        = $user->establishment;
    //     $fundRequest->mode        = $request->mode;
    //     $fundRequest->bank_ref_id = $request->bank_ref_id;
    //     $fundRequest->date        = $request->date;
    //     $fundRequest->amount      = $request->amount;
    //     $fundRequest->remark      = $request->remark;
    //     $fundRequest->client_ref  = $request->client_ref;
    
    //     if ($fundRequest->save()) {
    //         AuditLog::create([
    //             'user_id'      => $user->id,
    //             'role'         => $user->role,
    //             'action'       => 'CREATE_FUND_REQUEST',
    //             'service_name' => 'fund_request',
    //             'request_data' => json_encode($request->except('receipt')), // donâ€™t log file binary
    //             'response_data'=> json_encode($fundRequest),
    //             'ip_address'   => $request->ip(),
    //             'user_agent'   => $request->header('User-Agent'),
    //             'status'       => 'Success'
    //         ]);
    
    //         return ResponseHelper::success('FundRequest Created Successfully', $fundRequest, 200);
    //     } else {
    //         return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
    //     }
    // }

    // public function createFundRequest(Request $request)
    // {
    //     $user = Auth::user();
       
    //     // Validate request data
    //     $userAgent = $request->header('User-Agent');
    //     // if($user->id==82){
    //     //     return $userAgent;
    //     // }
    //     // if($userAgent=='Transup/1.0'&&$user->id==82){
    //     // $validator = FundRequestValidation::createFundRequest1($request->all());
            
    //     // }
    //     // else{
    //          $validator = FundRequestValidation::createFundRequest($request->all());
            
    //     // }
    //     if ($validator->fails()) {
    //         return ResponseHelper::error($validator->errors(), "Validation error", 422);
    //     }

    //     // Check if account exists
    //     $account = Account::where("user_id", $user->id)->first();
    //     if (!$account) {
    //         return ResponseHelper::error('Not Authorised. Please contact your administrator.', [], 403);
    //     }

    //     // Check if user role is allowed
    //     if (!$user || !in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
    //         return ResponseHelper::error('Operation Not Authorised', [], 403);
    //     }
    //     $receiptPath = null;
    //     if ($request->hasFile('receipt')) {
    //         $receiptPath = $this->saveUploadedFile($request->file('receipt'), 'images');
    //         if (!$receiptPath) {
    //             return ResponseHelper::error('Invalid receipt file. Must be jpg, jpeg, png and <=2MB.', [], 422);
    //         }
    //     }
    //     // elseif($userAgent=='Transup/1.0'){
    //     //   $receiptPath =  $this->saveBase64ImageChunks($request->all(), 'receipt');
    //     //   if (!$receiptPath) {
    //     //         return ResponseHelper::error('Invalid receipt file. Must be jpg, jpeg, png and <=2MB.', [], 422);
    //     //     }
    //     // }

    //     // Create FundRequest
    //     $fundRequest = new FundRequest();
    //     $fundRequest->bank_name   = $request->bank_name;
    //     $fundRequest->receipt     = $receiptPath;
    //     $fundRequest->status      = "pending";
    //     $fundRequest->asm_id      = $user->parent_id;
    //     $fundRequest->user_id     = $user->id;
    //     $fundRequest->username    = $user->username;
    //     $fundRequest->role        = $user->role;
    //     $fundRequest->name        = $user->establishment;
    //     $fundRequest->mode        = $request->mode;
    //     $fundRequest->bank_ref_id = $request->bank_ref_id;
    //     $fundRequest->date        = $request->date;
    //     $fundRequest->amount      = $request->amount;
    //     $fundRequest->remark      = $request->remark;
    //     $fundRequest->client_ref  = $request->client_ref;

    //     if ($fundRequest->save()) {
    //         // Create audit log
    //         AuditLog::create([
    //             'user_id'      => $user->id,
    //             'role'         => $user->role,
    //             'action'       => 'CREATE_FUND_REQUEST',
    //             'service_name' => 'fund_request',
    //             'request_data' => json_encode($request->except('receipt')), // avoid logging file binary
    //             'response_data'=> json_encode($fundRequest),
    //             'ip_address'   => $request->ip(),
    //             'user_agent'   => $request->header('User-Agent'),
    //             'status'       => 'Success'
    //         ]);

    //         return ResponseHelper::success('FundRequest Created Successfully', $fundRequest, 200);
    //     }

    //     return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
    // }
       public function createFundRequest(Request $request)
    {
        $user = Auth::user();
        $currentTime = Carbon::now('Asia/Kolkata')->format('H:i');

        $startTime = '08:00';
        $endTime   = '23:30';

        if ($currentTime < $startTime || $currentTime > $endTime) {
            return response()->json([
                'status'  => false,
                'message' => 'Fund request allowed only between 8:00 AM to 11:30 PM.'
            ], 403);
        }

        // Validate request data
        $userAgent = $request->header('User-Agent');
        // if($user->id==82){
        //     return $userAgent;
        // }
        if($userAgent=='Transup/1.0'){
        $validator = FundRequestValidation::createFundRequest1($request->all());
            
        }
        else{
             $validator = FundRequestValidation::createFundRequest($request->all());
            
        }
        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors(), "Validation error", 422);
        }

        // Check if account exists
        $account = Account::where("user_id", $user->id)->first();
        if (!$account) {
            return ResponseHelper::error('Not Authorised. Please contact your administrator.', [], 403);
        }

        // Check if user role is allowed
        if (!$user || !in_array($user->role, ["ret", "dd", "asm", "di", "md"])) {
            return ResponseHelper::error('Operation Not Authorised', [], 403);
        }
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $this->saveUploadedFile($request->file('receipt'), 'images');
            if (!$receiptPath) {
                return ResponseHelper::error('Invalid receipt file. Must be jpg, jpeg, png and <=2MB.', [], 422);
            }
        }
        elseif($userAgent=='Transup/1.0'){
          $receiptPath =  $this->saveBase64ImageChunks($request->all(), 'receipt');
        //   if (!$receiptPath) {
        //         return ResponseHelper::error('Invalid receipt file. Must be jpg, jpeg, png and <=2MB.', [], 422);
        //     }
        }

        // Create FundRequest
        $fundRequest = new FundRequest();
        $fundRequest->bank_name   = $request->bank_name;
        $fundRequest->receipt     = $receiptPath;
        $fundRequest->status      = "pending";
        $fundRequest->asm_id      = $user->parent_id;
        $fundRequest->user_id     = $user->id;
        $fundRequest->username    = $user->username;
        $fundRequest->role        = $user->role;
        $fundRequest->name        = $user->establishment;
        $fundRequest->mode        = $request->mode;
        $fundRequest->bank_ref_id = $request->bank_ref_id;
        $fundRequest->date        = $request->date;
        $fundRequest->amount      = $request->amount;
        $fundRequest->remark      = $request->remark;
        $fundRequest->client_ref  = $request->client_ref;

        if ($fundRequest->save()) {
            // Create audit log
            AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'CREATE_FUND_REQUEST',
                'service_name' => 'fund_request',
                'request_data' => json_encode($request->except('receipt')), // avoid logging file binary
                'response_data'=> json_encode($fundRequest),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Success'
            ]);

            return ResponseHelper::success('FundRequest Created Successfully', $fundRequest, 200);
        }

        return ResponseHelper::error('FundRequest Cannot Be Created', [], 500);
    }


    /**
     * Save uploaded file to storage and return public path
     */
    protected function saveUploadedFile(UploadedFile $file, string $folder = 'images'): string|null
    {
        // Validate file type
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            return null;
        }

        // Validate file size (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            return null;
        }

        // Create folder if not exists
        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        // Generate unique filename
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

        // Store the file
        $path = $folder . '/' . $filename;
        Storage::disk('public')->put($path, file_get_contents($file));

        // Return public storage path
        return 'storage/' . $path;
    }
    // private function saveUploadedFile($file, $prefix = 'file')
    // {
    //     if (!$file || !$file->isValid()) {
    //         return null;
    //     }
    
    //     $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf'];
    //     $extension = strtolower($file->getClientOriginalExtension());
    
    //     if (!in_array($extension, $allowedExtensions)) {
    //         return null;
    //     }
    //     if (!Storage::disk('public')->exists('images')) {
    //         Storage::disk('public')->makeDirectory('images');
    //     }
    //     $filename = $prefix . '_' . uniqid() . '.' . $extension;
    //     $path = 'images/' . $filename;
    //     if (Storage::disk('public')->putFileAs('images', $file, $filename)) {
            
    //         return 'storage/' . $path;
    //     }
    
    //     return null;
    // }
    // public function getFundRequests(Request $request)
    // {
    //     $user = Auth::user();
    //     $query = FundRequest::query();
    //     if (!in_array($user->role, ["sadm", "adm"])) {
    //         $query->where("user_id", $user->id);
    //     }
    //     if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
    //         $query->where('status', $request->status);
    //     }
    //       if ($request->has('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }
    //     if ($request->filled('from_date') && $request->filled('to_date')) {
    //         $query->whereDate('created_at', '>=', $request->from_date)
    //               ->whereDate('created_at', '<=', $request->to_date);
    //     }
    //   $fundRequests = $request->filled('export') && $request->export == 1
    //       ? $query->orderBy('id', 'desc')->get()
    //      : $query->orderBy('id', 'desc')->paginate($request->input('paginate', 15));

    
    //     $fundRequests->transform(function ($item) {
    //         if ($item->receipt) {
    //             $filename = basename($item->receipt);
    //             $item->receipt = url('/secure-images/' . $filename);
    //         }
    //         return $item;
    //     });
    
    //     return ResponseHelper::success('FundRequests fetched', $fundRequests, 200);
    // }
    public function getFundRequests(Request $request)
{
    $user = Auth::user();
    $query = FundRequest::query();

    if (!in_array($user->role, ["sadm", "adm"])) {
        $query->where("user_id", $user->id);
    }

    if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereDate('created_at', '>=', $request->from_date)
              ->whereDate('created_at', '<=', $request->to_date);
    }

    // Fetch Data
    $fundRequests = $request->filled('export') && $request->export == 1
        ? $query->orderBy('id', 'desc')->get()
        : $query->orderBy('id', 'desc')->paginate($request->input('paginate', 15));

    // Transform receipt URL
    $fundRequests->transform(function ($item) {
        if ($item->receipt) {
            $filename = basename($item->receipt);
            $item->receipt = url('/secure-images/' . $filename);
        }
        return $item;
    });

    /*
    |--------------------------------------------------------------------------
    | EXPORT LOGIC
    |--------------------------------------------------------------------------
    */
    if ($request->filled('export') && $request->export == 1) {

        // If status = approved â†’ show only limited fields
        if ($request->status == 'approved') {
            $fundRequests = $fundRequests->map(function ($item) {
                return [
                    'created_at'   => $item->created_at,
                    'updated_at'   => $item->updated_at,
                    'user_id'      => $item->user_id,
                    'shop_name'    => $item->name,
                    'bank_name'    => $item->bank_name,
                    'mode'         => $item->mode,
                    'bank_ref_id'  => $item->bank_ref_id,
                    'amount'       => $item->amount,
                    'remark'       => $item->remark,
                    'admin_remark' => $item->admin_remark,
                ];
            });
        }
        // If admin but no approved filter
        else if (in_array($user->role, ['adm', 'sadm'])) {
            $fundRequests = $fundRequests->map(function ($item) {
                return [
                    'created_at'   => $item->created_at,
                    'updated_at'   => $item->updated_at,
                    'user_id'      => $item->user_id,
                    'shop_name'    => $item->name,
                    'bank_name'    => $item->bank_name,
                    'mode'         => $item->mode,
                    'bank_ref_id'  => $item->bank_ref_id,
                    'amount'       => $item->amount,
                    'remark'       => $item->remark,
                    'admin_remark' => $item->admin_remark,
                ];
            });
        }
    }

    return ResponseHelper::success('FundRequests fetched', $fundRequests, 200);
}

    // public function updateFundRequest(Request $request)
    // {
    //     $user = Auth::user();
    
    //     // Validation
    //     $validator = FundRequestValidation::updateFundRequest($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    //     if (!in_array($user->role, ["adm", "sadm"])) {
    //         return ResponseHelper::error('Operation Not Authorised', '', 403);
    //     }
    //     // $mpin = OTP::verifyMpin($request->mpin);
    //     // if (!$mpin) {
    //     //     return ResponseHelper::error("Invalid MPIN", [], 403);
    //     // }
    //     $fundRequest = FundRequest::find($request->id);
    //     if (!$fundRequest) {
    //         return ResponseHelper::error('Request Not Found', '', 403);
    //     }
    
    //     if ($fundRequest->status !== 'pending') {
    //         return ResponseHelper::error(
    //             'FundRequest Already ' . strtoupper($fundRequest->status),
    //             '',
    //             400
    //         );
    //     }
    
    //     $userData = User::find($fundRequest->user_id);
    //     if (!$userData) {
    //         return ResponseHelper::error('User Not Found', [], 403);
    //     }
    
    //     try {
    //         DB::beginTransaction();
    
    //         if ($request->status === "approved") {
    //             $account = Account::where("user_id", $fundRequest->user_id)->lockForUpdate()->first();
    //             if (!$account) {
    //                 return ResponseHelper::error('Admin Account Not Found', [], 403);
    //             }
    //     if ($user->role != 'sadm') {
    //         // return $account;
    //     if (($account->balance + $fundRequest->amount) > $account->credit_limit) {
    //         return ResponseHelper::error('Credit Limit Exceeded for this User', [], 400);
    //         }
    //     }

    //             // $userData->w1 += ($fundRequest->amount * 100);
    //              WalletService::creditFundRequest($userData, $fundRequest->amount, $fundRequest);
    //             $userData->save();
    //             $account->balance += $fundRequest->amount;
    //             $account->save();
    //             $accountStatement = new AccountStatement();
    //             $accountStatement->credit = $fundRequest->amount;
    //             $accountStatement->account_id = $account->id;
    //             $accountStatement->balance = $account->balance;
    //             $accountStatement->remarks = "Fund Request Approved By {$user->name}";
    //             $accountStatement->particulars = "Fund Request Approved By {$user->name}";
    //             $accountStatement->claimed_by = $user->id;
    //             $accountStatement->save();
    //             $fundRequest->status = "approved";
    //             $fundRequest->approved_by = $user->name??$user->id;
    //             $fundRequest->admin_remark = $request->admin_remark;
    //             $template = Template::where('name','CREDITED_AMOUNT')->first();
    //             $message = $template->message;
    //             $receivernew = User::find($fundRequest->user_id);
    //             $message = str_replace('{$amount}', $request->amount, $message);
    //             $message = str_replace('{$user_bal}', $receivernew->w1/100, $message);
                
    //             $data = [
    //                 "number"  => $receivernew->mobile,
    //                 "message" => $message,
    //                 "tempid"  => $template->temp_id
    //             ];
                
    //             $res = Sms::send($data, $user);
    //         } elseif ($request->status === "rejected") {
    //             $fundRequest->status = "rejected";
    //              $template = Template::where('name','REJECTED_FUND_REQUEST')->first();
    //             $message = $template->message;
    //             $message = str_replace('{$client_ref}', $fundRequest->client_ref, $message);
    //             $message = str_replace('{$admin_remark}', $request->admin_remark, $message);
    //             $data = [
    //                 "number"  => $userData->mobile,
    //                 "message" => $message,
    //                 "tempid"  => $template->temp_id
    //             ];
                
    //             $res = Sms::send($data, $user);
    //             $fundRequest->admin_remark = $request->admin_remark;
    //             $fundRequest->approved_by = $user->name??$user->id;
    //         }
    
    //         $fundRequest->save();
    
    //         DB::commit();
    
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
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('Something went wrong: ' . $e->getMessage(), [], 500);
    //     }
    // }
    public function updateFundRequest(Request $request)
    {
        $user = Auth::user();
    
        // Validation
        $validator = FundRequestValidation::updateFundRequest($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        if (!in_array($user->role, ["adm", "sadm"])) {
            return ResponseHelper::error('Operation Not Authorised', '', 403);
        }
    
        $fundRequest = FundRequest::find($request->id);
        if (!$fundRequest) {
            return ResponseHelper::error('Request Not Found', '', 403);
        }
    
        if ($fundRequest->status !== 'pending') {
            return ResponseHelper::error(
                'FundRequest Already ' . strtoupper($fundRequest->status),
                '',
                400
            );
        }
    
        $userData = User::find($fundRequest->user_id);
        if (!$userData) {
            return ResponseHelper::error('User Not Found', [], 403);
        }
    
        try {
            DB::beginTransaction();
    
            if ($request->status === "approved") {
                $account = Account::where("user_id", $fundRequest->user_id)->lockForUpdate()->first();
                if (!$account) {
                    return ResponseHelper::error('Admin Account Not Found', [], 403);
                }
    
                if ($user->role != 'sadm' && ($account->balance + $fundRequest->amount) > $account->credit_limit) {
                    return ResponseHelper::error('Credit Limit Exceeded for this User', [], 400);
                }
    
                WalletService::creditFundRequest($userData, $fundRequest->amount, $fundRequest);
                $userData->save();
    
                $account->balance += $fundRequest->amount;
                $account->save();
    
                $accountStatement = new AccountStatement();
                $accountStatement->credit = $fundRequest->amount;
                $accountStatement->account_id = $account->id;
                $accountStatement->balance = $account->balance;
                $accountStatement->remarks = "Fund Request Approved By {$user->name}";
                $accountStatement->particulars = "Fund Request Approved By {$user->name}";
                $accountStatement->claimed_by = $user->id;
                $accountStatement->save();
    
                $fundRequest->status = "approved";
                $fundRequest->approved_by = $user->name ?? $user->id;
                $fundRequest->admin_remark = $request->admin_remark;
    
            } elseif ($request->status === "rejected") {
                $fundRequest->status = "rejected";
                $fundRequest->approved_by = $user->name ?? $user->id;
                $fundRequest->admin_remark = $request->admin_remark;
            }
    
            $fundRequest->save();
            DB::commit();
    
            // --- SEND SMS ONLY ONCE ---
            $message = '';
            $data = [];
    
            if ($fundRequest->status == 'approved') {
                $template = Template::where('name','CREDITED_AMOUNT')->first();
                $receivernew = User::find($fundRequest->user_id);
    
                $message = str_replace('{$amount}', $fundRequest->amount, $template->message);
                $message = str_replace('{$user_bal}', $receivernew->w1/100, $message);
    
                $data = [
                    "number"  => $receivernew->mobile,
                    "message" => $message,
                    "tempid"  => $template->temp_id
                ];
    
            } elseif ($fundRequest->status == 'rejected') {
                $template = Template::where('name','REJECTED_FUND_REQUEST')->first();
    
                $message = str_replace('{$client_ref}', $fundRequest->client_ref, $template->message);
                $message = str_replace('{$admin_remark}', $fundRequest->admin_remark, $message);
    
                $data = [
                    "number"  => $userData->mobile,
                    "message" => $message,
                    "tempid"  => $template->temp_id
                ];
            }
    
            if (!empty($data)) {
                Sms::send($data, $user);
            }
    
            // --- AUDIT LOG ---
            AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'UPDATE_FUND_REQUEST',
                'service_name' => 'fund_request',
                'request_data' => json_encode($request->all()),
                'response_data'=> json_encode($fundRequest),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Success'
            ]);
    
            return ResponseHelper::success('FundRequest Updated Successfully', $fundRequest, 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error('Something went wrong: ' . $e->getMessage(), [], 500);
        }
    }
    // public function editFundRequest(Request $request)
    // {
    //     $user = Auth::user();
    //     $validator = FundRequestValidation::editFundRequest($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
      
    //     if (!in_array($user->role, ["ret", "dd","md","di"])) {
    //         return ResponseHelper::error('Operation Not Authorised', '', 403);
    //     }
    //     // if (!OTP::verifyMpin($request->mpin)) {
    //     //     return ResponseHelper::error("Invalid MPIN", [], 403);
    //     // }
    //     $fundRequest = FundRequest::find($request->id);
    //     if (!$fundRequest) {
    //         return ResponseHelper::error('Request Not Found', '', 403);
    //     }
    //     if ($fundRequest->status != "pending") {
    //         return ResponseHelper::error(
    //             'Cannot update a request with status: ' . strtoupper($fundRequest->status),
    //             [],
    //             400
    //         );
    //     }   if ($request->has('receipt') && $request->receipt) {
    //             if ($fundRequest->receipt) {
    //                 $oldPath = str_replace('storage/images/', 'public/images/', $fundRequest->receipt);
    //                 if (Storage::exists($oldPath)) {
    //                     Storage::delete($oldPath);
    //                 }
    //             }
    //             $fundRequest->receipt = $this->saveBase64Image($request->receipt, 'receipt');
    //         }

    //     $fundRequest->bank_name   = $request->bank_name;
    //     $fundRequest->mode        = $request->mode;
    //     $fundRequest->bank_ref_id = $request->bank_ref_id;
    //     $fundRequest->date        = $request->date;
    //     $fundRequest->remark      = $request->remark;
    //     if ($fundRequest->save()) {
    //         // Audit log
    //         AuditLog::create([
    //             'user_id'      => $user->id,
    //             'role'         => $user->role,
    //             'action'       => 'EDIT_FUND_REQUEST',
    //             'service_name' => 'fund_request',
    //             'request_data' => json_encode($request->all()),
    //             'response_data'=> json_encode($fundRequest),
    //             'ip_address'   => $request->ip(),
    //             'user_agent'   => $request->header('User-Agent'),
    //             'status'       => 'Success'
    //         ]);
    
    //         return ResponseHelper::success('FundRequest Updated Successfully', $fundRequest, 200);
    //     }
    
    //     return ResponseHelper::error('FundRequest Cannot Be Updated', '', 500);
    // }
     public function editFundRequest(Request $request)
    {
        $user = Auth::user();
            $userAgent = $request->header('User-Agent');

        $validator = FundRequestValidation::editFundRequest($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
      
        if (!in_array($user->role, ["ret", "dd","md","di"])) {
            return ResponseHelper::error('Operation Not Authorised', '', 403);
        }
        // if (!OTP::verifyMpin($request->mpin)) {
        //     return ResponseHelper::error("Invalid MPIN", [], 403);
        // }
        $fundRequest = FundRequest::find($request->id);
        if (!$fundRequest) {
            return ResponseHelper::error('Request Not Found', '', 403);
        }
        if ($fundRequest->status != "pending") {
            return ResponseHelper::error(
                'Cannot update a request with status: ' . strtoupper($fundRequest->status),
                [],
                400
            );
        } 
        // if ($request->has('receipt') && $request->receipt) {
        //         if ($fundRequest->receipt) {
        //             $oldPath = str_replace('storage/images/', 'public/images/', $fundRequest->receipt);
        //             if (Storage::exists($oldPath)) {
        //                 Storage::delete($oldPath);
        //             }
        //         }
        //         $fundRequest->receipt = $this->saveBase64Image($request->receipt, 'receipt');
        //     }
        if (
    $userAgent == 'Transup/1.0' ||
    $request->has('receipt') ||
    $request->hasFile('receipt')
)
{

    // 🧹 delete old receipt if exists
    if ($fundRequest->receipt) {
        $oldPath = str_replace('storage/', 'public/', $fundRequest->receipt);
        if (Storage::exists($oldPath)) {
            Storage::delete($oldPath);
        }
    }
  if ($userAgent == 'Transup/1.0' && $user->id == 15) {
    $fundRequest->receipt = $this->saveBase64ImageChunks($request->all(), 'receipt');
} elseif ($request->hasFile('receipt')) {
    $fundRequest->receipt = $this->saveUploadedFileNew($request->file('receipt'), 'images');
} else {
    $fundRequest->receipt = $this->saveBase64Image($request->receipt, 'receipt');
}

}


        $fundRequest->bank_name   = $request->bank_name;
        $fundRequest->mode        = $request->mode;
        $fundRequest->bank_ref_id = $request->bank_ref_id;
        $fundRequest->date        = $request->date;
        $fundRequest->remark      = $request->remark;
        if ($fundRequest->save()) {
            // Audit log
            AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'EDIT_FUND_REQUEST',
                'service_name' => 'fund_request',
                'request_data' => json_encode($request->all()),
                'response_data'=> json_encode($fundRequest),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Success'
            ]);
    
            return ResponseHelper::success('FundRequest Updated Successfully', $fundRequest, 200);
        }
    
        return ResponseHelper::error('FundRequest Cannot Be Updated', '', 500);
    }
    public function deleteFundRequest(Request $request)
    {
        $user = Auth::user();
        $validator = FundRequestValidation::deleteFundRequest($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
        if (in_array($user->role, ["ret", "dd", "md", "di"])) {
            return ResponseHelper::error('Operation Not Authorised', '', 401);
        }
        if (!OTP::verifyMpin($request->mpin)) {
            return ResponseHelper::error("Invalid MPIN", [], 403);
        }
        $fundRequest = FundRequest::find($request->id);
        if (!$fundRequest) {
            return ResponseHelper::error('FundRequest Not Found', '', 404);
        }
        if ($fundRequest->status != "pending") {
            return ResponseHelper::error(
                'Cannot delete a request with status: ' . strtoupper($fundRequest->status),
                [],
                400
            );
        }
        if ($fundRequest->receipt) {
            $oldPath = str_replace('storage/images/', 'public/images/', $fundRequest->receipt);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        if ($fundRequest->delete()) {
            AuditLog::create([
                'user_id'      => $user->id,
                'role'         => $user->role,
                'action'       => 'DELETE_FUND_REQUEST',
                'service_name' => 'fund_request',
                'request_data' => json_encode($request->all()),
                'response_data'=> json_encode($fundRequest),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->header('User-Agent'),
                'status'       => 'Success'
            ]);
    
            return ResponseHelper::success('FundRequest Deleted Successfully', [], 200);
        }
    
        return ResponseHelper::error('FundRequest Cannot Be Deleted', '', 500);
    }

    
    private function saveBase64ImageChunks(array $requestData, string $prefix)
    {
        $chunks = [];
    
        // Collect receipt1, receipt2, receipt3...
        foreach ($requestData as $key => $value) {
            if (preg_match('/^receipt\d+$/', $key)) {
                $chunks[$key] = $value;
            }
        }
    
        if (empty($chunks)) {
            return null;
        }
    
        // Sort by receipt1, receipt2, receipt3
        ksort($chunks);
    
        // Concatenate raw base64
        $base64Data = implode('', $chunks);
    
        // Decode base64
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return null;
        }
      if (strlen($imageData) > (2 * 1024 * 1024)) {
        return null;
    }
        // Detect image type from binary data
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
    
        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png'
        ];
    
        if (!isset($extensionMap[$mime])) {
            return null;
        }
    
        $extension = $extensionMap[$mime];
    
        // Save image
        $filename = $prefix . '_' . uniqid() . '.' . $extension;
        $path = 'images/' . $filename;
    
        if (!Storage::disk('public')->exists('images')) {
            Storage::disk('public')->makeDirectory('images');
        }
    
        Storage::disk('public')->put($path, $imageData);
    
        // âœ… Only file path saved
        return 'storage/' . $path;
    }

  private function saveBase64Image($base64Image, $prefix = 'doc')
  {
    if (!$base64Image) {
        return null;
    }
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
        return null;
    }

    $extension = strtolower($matches[1]);
    $allowedExtensions = ['jpeg', 'jpg', 'png'];
    if (!in_array($extension, $allowedExtensions)) {
        return null;
    }

    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);

    $imageData = base64_decode($base64Image);
    if ($imageData === false) {
        return null;
    }
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    $path = 'images/' . $filename;
    if (!Storage::disk('public')->exists('images')) {
        Storage::disk('public')->makeDirectory('images');
    }

    if (Storage::disk('public')->put($path, $imageData)) {
        return 'storage/' . $path;
    }

    return null;
}
    public function pendingCount(Request $request)
    {
        $user = Auth::user();
        // if (!in_array($user->role, ['sadm', 'adm', 'asm', 'dist', 'retailer'])) {
        //     return ResponseHelper::error('Operation Not Authorised', '', 401);
        // }
        if (in_array($user->role, ['sadm', 'adm'])) {
            $pendingCount = FundRequest::where('status', 'pending')->count();
        } else {
            $pendingCount = FundRequest::where('status', 'pending')
                                        ->where('user_id', $user->id)
                                        ->count();
        }

        return ResponseHelper::success(
            'Pending fund request count fetched successfully',
            ['pending_count' => $pendingCount, 'role' => $user->role],
            200
        );
    }

    //     public function updateFundRequest(Request $request)
    // {
    //     $user = Auth::user();
    
    //     // Validation
    //     $validator = FundRequestValidation::updateFundRequest($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    
    //     if (!in_array($user->role, ["adm", "sadm"])) {
    //         return ResponseHelper::error('Operation Not Authorised', '', 403);
    //     }
    
    //     $fundRequest = FundRequest::find($request->id);
    //     if (!$fundRequest) {
    //         return ResponseHelper::error('Request Not Found', '', 403);
    //     }
    
    //     if ($fundRequest->status !== 'pending') {
    //         return ResponseHelper::error(
    //             'FundRequest Already ' . strtoupper($fundRequest->status),
    //             '',
    //             400
    //         );
    //     }
    
    //     $userData = User::find($fundRequest->user_id);
    //     if (!$userData) {
    //         return ResponseHelper::error('User Not Found', [], 403);
    //     }
    
    //     try {
    //         DB::beginTransaction();
    
    //         if ($request->status === "approved") {
    
    //             $amount = floatval($fundRequest->amount);
    //             $charge = floatval($request->admin_charge ?? 0);
    
    //             if ($charge < 0 || $charge > $amount) {
    //                 return ResponseHelper::error('Invalid charge amount', [], 400);
    //             }
    
    //             $netAmount = $amount - $charge;
    //             /* ========================= */
    
    //             $account = Account::where("user_id", $fundRequest->user_id)->lockForUpdate()->first();
    //             if (!$account) {
    //                 return ResponseHelper::error('Admin Account Not Found', [], 403);
    //             }
    
                
    //             if ($user->role != 'sadm' && ($account->balance + $netAmount) > $account->credit_limit) {
    //                 return ResponseHelper::error('Credit Limit Exceeded for this User', [], 400);
    //             }
    
              
    //             WalletService::creditFundRequest($userData, $netAmount, $fundRequest);
    //             $userData->save();
    
    //             $account->balance += $netAmount;
    //             $account->save();
    
    
    //             $accountStatement = new AccountStatement();
    //             $accountStatement->credit = $netAmount;
    //             $accountStatement->account_id = $account->id;
    //             $accountStatement->balance = $account->balance;
    //             $accountStatement->remarks = "Fund Request Approved By {$user->name} (Net)";
    //             $accountStatement->particulars = "Fund Request Approved By {$user->name}";
    //             $accountStatement->claimed_by = $user->id;
    //             $accountStatement->save();
    
    //             if ($charge > 0) {
    //                 $chargeStmt = new AccountStatement();
    //                 $chargeStmt->debit = $charge;
    //                 $chargeStmt->account_id = $account->id;
    //                 $chargeStmt->balance = $account->balance;
    //                 $chargeStmt->remarks = "Fund Request Charge Deducted";
    //                 $chargeStmt->particulars = "Admin Charge";
    //                 $chargeStmt->claimed_by = $user->id;
    //                 $chargeStmt->save();
    //             }
    
    
    //             $fundRequest->status = "approved";
    //             $fundRequest->approved_by = $user->name ?? $user->id;
    //             $fundRequest->admin_remark = $request->admin_remark;
    
    //             $fundRequest->admin_charge = $charge;
    //             $fundRequest->net_amount = $netAmount;
             
    
    //         } elseif ($request->status === "rejected") {
    //             $fundRequest->status = "rejected";
    //             $fundRequest->approved_by = $user->name ?? $user->id;
    //             $fundRequest->admin_remark = $request->admin_remark;
    //         }
    
    //         $fundRequest->save();
    //         DB::commit();
    
         
    //         $message = '';
    //         $data = [];
    
    //         if ($fundRequest->status == 'approved') {
    //             $template = Template::where('name','CREDITED_AMOUNT')->first();
    //             $receivernew = User::find($fundRequest->user_id);
    
    //             $charge = $fundRequest->admin_charge ?? 0;
    //             $netAmount = $fundRequest->net_amount ?? $fundRequest->amount;
               
    //             $message = str_replace('{$amount}', $fundRequest->amount, $template->message);
    //             $message = str_replace('{$charge}', $charge, $message);
    //             $message = str_replace('{$net_amount}', $netAmount, $message);
    //             $message = str_replace('{$user_bal}', $receivernew->w1/100, $message);
    
    //             $data = [
    //                 "number"  => $receivernew->mobile,
    //                 "message" => $message,
    //                 "tempid"  => $template->temp_id
    //             ];
    
    //         } elseif ($fundRequest->status == 'rejected') {
    //             $template = Template::where('name','REJECTED_FUND_REQUEST')->first();
    
    //             $message = str_replace('{$client_ref}', $fundRequest->client_ref, $template->message);
    //             $message = str_replace('{$admin_remark}', $fundRequest->admin_remark, $message);
    
    //             $data = [
    //                 "number"  => $userData->mobile,
    //                 "message" => $message,
    //                 "tempid"  => $template->temp_id
    //             ];
    //         }
    
    //         if (!empty($data)) {
    //             Sms::send($data, $user);
    //         }
    
           
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
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('Something went wrong: ' . $e->getMessage(), [], 500);
    //     }
    // }



}
