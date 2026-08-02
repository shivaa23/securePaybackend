<?php

namespace App\Modules\BusinessDetail\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\BusinessDetail\Models\Business;
use App\Modules\BusinessDetail\Models\BusinessContact;
use App\Modules\BusinessDetail\Models\BusinessAddress;
use App\Modules\BusinessDetail\Models\BusinessIdentification;
use App\Modules\BusinessDetail\Models\BusinessBank;
use App\Modules\BusinessDetail\Models\BusinessDocument;
use App\Modules\BusinessDetail\Models\BusinessKyc;
use App\Modules\BusinessDetail\Models\BusinessStatus;
use App\Modules\BusinessDetail\Models\BusinessValidation;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use App\Helper\ResponseHelper;
use Auth;
use Illuminate\Support\Facades\Storage;

class BusinessController extends BaseController
{
    
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['']]);
        $this->middleware('check.active', ['except' => '']);
        $this->middleware('check.service.time',['except' => '']);

    }
    /** -------------------- BASIC -------------------- **/
    public function createBasic(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = BusinessValidation::createBasicRequest($request->all());
    
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
    
            $basic = new Business();
            $basic->user_id = $user->id;
            $basic->business_name = $request->business_name;
            $basic->business_type = $request->business_type;
            $basic->industry = $request->industry;
            $basic->date_of_incorporation = $request->date_of_incorporation;
            $basic->save();
            
            $user->status = 8;
            $user->save();
            return ResponseHelper::success('Business created successfully', $user->status, 200);
    
        } catch (\Exception $e) {
            return ResponseHelper::error('Something went wrong', ['message' => $e->getMessage()], 500);
        }
    }
     public function basicSchema(Request $request){
          $schema = BusinessValidation::basicSchema();
         return ResponseHelper::success('Schema fetched successfuly',$schema,201);
          
      }
    /** -------------------- CONTACT -------------------- **/
    public function createContact(Request $request)
    {
        $user=Auth::user();
        $validator = BusinessValidation::createContactRequest($request->all());
        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }
        $contact = new BusinessContact();
        $contact->user_id = $user->id;
        $contact->business_email = $request->mail;
        $contact->business_mobile = $request->mobile;
        $contact->alternate_mobile = $request->alternate_mobile;
        $contact->save();

        $user->status = 7;
        $user->save();
        return ResponseHelper::success('Contact created successfully', $user->status, 200);
    }
      public function contactSchema()
    {
        $schema = BusinessValidation::contactSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }
    /** -------------------- ADDRESS -------------------- **/
    public function createAddress(Request $request)
    {
        $user=Auth::user();
        $validator = BusinessValidation::createAddressRequest($request->all());
        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $address = new BusinessAddress();
        $address->user_id = $user->id;
        $address->address_line1 = $request->address_line1;
        $address->address_line2 = $request->address_line2;
        $address->city = $request->city;
        $address->state = $request->state;
        $address->pincode = $request->pincode;
        // $address->country = $request->country;
        $address->save();
        
        $user->status = 6;
        $user->save();
        return ResponseHelper::success('Address Updated successfully', $user->status, 200);
    }
    public function addressSchema()
    {
        $schema = BusinessValidation::addressSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }
    /** -------------------- IDENTIFICATION -------------------- **/
    public function createIdentification(Request $request)
    {
        $user=Auth::user();
        $validator = BusinessValidation::createIdentificationRequest($request->all());
        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $ident = new BusinessIdentification();
        $ident->user_id = $user->id;
        $ident->pan_number = $request->pan_number;
        $ident->gst_number = $request->gst_number;
        // $ident->udyam_number = $request->udyam_number;
        // $ident->cin_number = $request->cin_number;
        // $ident->tan_number = $request->tan_number;
        $ident->save();
        
        $user->status = 5;
        $user->save();
        return ResponseHelper::success('Details Updated successfully', $user->status, 200);
    }
      public function identificationSchema()
    {
        $schema = BusinessValidation::identificationSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }
    /** -------------------- BANK -------------------- **/
    public function createBank(Request $request)
    {
        $user=Auth::user();
        $validator = BusinessValidation::createBankRequest($request->all());
        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $bank = new BusinessBank();
        $bank->user_id = $user->id;
        $bank->account_holder_name = $request->account_holder_name;
        $bank->bank_name = $request->bank_name;
        $bank->branch_name = $request->branch_name;
        $bank->ifsc_code = $request->ifsc_code;
        $bank->account_number = $request->account_number;
        $bank->upi_id = $request->upi_id;
        $bank->save();

        $user->status = 4;
        $user->save();
        return ResponseHelper::success('Bank Details Updated successfully', $user->status, 200);
    }
    public function bankSchema()
    {
        $schema = BusinessValidation::bankSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }

    /** -------------------- DOCUMENTS -------------------- **/
    public function createDocuments(Request $request)
    { 
        $user=Auth::user();
        $validator = BusinessValidation::createDocumentsRequest($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error("Validation error", $validator->errors(), 422);
        }
    
        $docs = new BusinessDocument();
        $docs->user_id = $user->id;
    
        // GST Certificate
        $docs->gst_certificate = $this->saveBase64Image($request->gst_certificate, 'gst');
    
        // Udyam Certificate
        $docs->udyam_certificate = $this->saveBase64Image($request->udyam_certificate, 'udyam');
    
        // Shop License
        $docs->shop_license = $this->saveBase64Image($request->shop_license, 'shop_license');
    
        // Rent Agreement
        // $docs->rent_agreement = $this->saveBase64Image($request->rent_agreement, 'rent_agreement');
    
        // Electricity Bill
        // $docs->electricity_bill = $this->saveBase64Image($request->electricity_bill, 'electricity_bill');
    
        $docs->save();
        
        $user->status = 3;
        $user->save();
        return ResponseHelper::success('Doc Details Updated successfully', $user->status, 200);
    }

   public function documentsSchema()
    {
        $schema = BusinessValidation::documentsSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }
    /** -------------------- KYC -------------------- **/
    //  public function createKyc(Request $request)
    // {
    //     $user = Auth::user();
    //     $validator = BusinessValidation::createKycRequest($request->all());
    
    //     if ($validator->fails()) {
    //         return ResponseHelper::error(  $validator->errors(),'validation' , 422);
    //     }
    
    //     DB::beginTransaction();
    //     try {
    //         $kyc = new BusinessKyc();
    //         $kyc->user_id = $user->id;
    //         $kyc->aadhaar_front = $this->saveUploadedFilenew($request->file('aadhaar_front'), 'images');
    //         $kyc->aadhaar_back  = $this->saveUploadedFilenew($request->file('aadhaar_back'), 'images');
    //         $kyc->shop_image    = $this->saveUploadedFilenew($request->file('shop_image'), 'images');
    //         $kyc->pan_card      = $this->saveUploadedFilenew($request->file('pan_card'), 'images');
    //         $kyc->photo         = $this->saveUploadedFilenew($request->file('photo'), 'images');

    
    //         $kyc->save();
    //         $user->status = 2;
    //         $user->save();
    
    //         DB::commit();
    //         return ResponseHelper::success('KYC Details Updated successfully', $user->status, 200);
    
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return ResponseHelper::error('Failed to update KYC', ['error' => $e->getMessage()], 500);
    //     }
    // }
  public function createKyc(Request $request)
{
    $user = Auth::user();
    $userAgent = $request->header('User-Agent');

    $validator = BusinessValidation::createKycRequest($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error($validator->errors(), "Validation error", 422);
    }

    DB::beginTransaction();

    try {

        $kyc = new BusinessKyc();
        $kyc->user_id = $user->id;

        // ✅ Same pattern as GST
        // $kyc->aadhaar_front = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'aadhaar_front'): $this->saveBase64Image($request->aadhaar_front, 'aadhaar_front');
        // $kyc->aadhaar_back = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'aadhaar_back'): $this->saveBase64Image($request->aadhaar_back, 'aadhaar_back');
        // $kyc->pan_card = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'pan_card'): $this->saveBase64Image($request->pan_card, 'pan_card');
        // $kyc->photo = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'photo'): $this->saveBase64Image($request->photo, 'photo');
        // $kyc->shop_image = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'shop_image'): $this->saveBase64Image($request->shop_image, 'shop_image');
        $kyc->aadhaar_front = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'aadhaar_front')  : ($request->hasFile('aadhaar_front')? $this->saveUploadedFileNew($request->file('aadhaar_front'), 'images') : null);
        $kyc->aadhaar_back = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'aadhaar_back'): ($request->hasFile('aadhaar_back')? $this->saveUploadedFileNew($request->file('aadhaar_back'), 'images'): null);
        $kyc->pan_card = $userAgent == 'Transup/1.0' ? $this->saveBase64ImageChunks($request->all(), 'pan_card'): ($request->hasFile('pan_card')? $this->saveUploadedFileNew($request->file('pan_card'), 'images'): null);
        $kyc->shop_image = $userAgent == 'Transup/1.0' ? $this->saveBase64ImageChunks($request->all(), 'shop_image'): ($request->hasFile('shop_image')? $this->saveUploadedFileNew($request->file('shop_image'), 'images') : null);
        $kyc->photo = $userAgent == 'Transup/1.0'? $this->saveBase64ImageChunks($request->all(), 'photo'): ($request->hasFile('photo')? $this->saveUploadedFileNew($request->file('photo'), 'images'): null);
        $kyc->save();

        $user->status = 2;
        $user->save();

        DB::commit();

        return ResponseHelper::success(
            'KYC Details Updated successfully',
            $kyc,
            200
        );

    } catch (\Exception $e) {
        DB::rollBack();

        return ResponseHelper::error(
            'Failed to update KYC',
            ['error' => $e->getMessage()],
            500
        );
    }
}

     public function kycSchema()
    {
        $schema = BusinessValidation::kycSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }

    public function createStatus(Request $request)
    {
        $user=Auth::user();
        $validator = BusinessValidation::createStatusRequest($request->all());
        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $meta = new BusinessStatus();
        $meta->user_id = $user->id;
        $meta->status = $request->status;
        $meta->save();

        return response()->json(['status'=>true,'data'=>$meta],201);
    }
     public function statusSchema()
    {
        $schema = BusinessValidation::statusSchema();
        return ResponseHelper::success("Schema fetched successfully", $schema);
    }
    
      public function getByType(Request $request)
    {
        $user = Auth::user();
    
        // Validate request
        $validator = BusinessValidation::validateType($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        // Determine user ID (admins can fetch for any user)
        $userId = $user->id;
        if (in_array($user->role, ['adm', 'sadm']) && $request->filled('user_id')) {
            $userId = $request->user_id;
        }
    
        $type = $request->type;
    
        // Map types to models
        $modelMap = [
            'basic'        => Business::class,
            'user_details' => User::class,
            'address'      => BusinessAddress::class,
            'kyc'          => BusinessKyc::class,
        ];
    
        // Invalid type check
        if (!isset($modelMap[$type])) {
            return ResponseHelper::error('Invalid type', [], 422);
        }
    
        // Fetch data
        $query = $modelMap[$type]::query();
    
        if ($type === 'user_details') {
            $query->where('id', $userId);
        } else {
            $query->where('user_id', $userId);
        }
    
        $data = $query->orderByDesc('id')->first();
    
        if (!$data) {
            return ResponseHelper::error('No data found', [], 404);
        }
        if ($type === 'kyc') {
            $docKeys = ['aadhaar_front', 'aadhaar_back', 'shop_image', 'pan_card', 'photo'];
    
            foreach ($docKeys as $key) {
                if (!empty($data->$key)) {
                    $filename = basename($data->$key);
                    $data->$key = url('/secure-images/' . $filename);
                }
            }
        }
    
        return ResponseHelper::success('Data fetched successfully', $data);
    }


    private function saveBase64Image($base64Image, $prefix = 'doc')
    {
        if (!$base64Image) {
            return null;
        }
    
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
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
            Storage::disk('public')->put('images/' . $filename, $imageData);
            return 'storage/images/' . $filename;
        }
    
        return null;
    }
    public function getAllByUser(Request $request)
    {
        $authUser = Auth::user();
        $allowedRoles = ['adm', 'sadm'];
        if (in_array($authUser->role, $allowedRoles)) {
            $validator = BusinessValidation::validateUserId($request->all());
            if ($validator->fails()) {
                return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
            }
            $user_id = $request->id;
        } else {
            $user_id = $authUser->id;
        }
    
        $modelMap = [
            'basic'          => Business::class,
            // 'contact'        => BusinessContact::class,
            'address'        => BusinessAddress::class,
            // 'identification' => BusinessIdentification::class,
            // 'bank'           => BusinessBank::class,
            // 'documents'      => BusinessDocument::class,
            'kyc'            => BusinessKyc::class,
        ];
    
        $result = [];
    
        foreach ($modelMap as $key => $model) {
            $data = $model::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->first();
    
            if ($key === 'kyc' && $data) {
                $docKeys = ['aadhaar_front','aadhaar_back','shop_image','pan_card','photo'];
                foreach ($docKeys as $docKey) {
                    if (!empty($data->$docKey)) {
                        $filename = basename($data->$docKey);
                        $data->$docKey = url('/secure-images/' . $filename);
                    }
                }
            }
    
            $result[$key] = $data ?: null;
        }
    
        return ResponseHelper::success('All data fetched successfully', $result, 200);
    }

    //  public function updateByType(Request $request)
    // {
    //     $user = Auth::user();
    
    //     // Validate type
    //     $validator = BusinessValidation::validateType($request->all());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    
    //     $type = $request->type;
    
    //     $modelMap = [
    //         'basic'   => Business::class,
    //         'address' => BusinessAddress::class,
    //         'kyc'     => BusinessKyc::class,
    //     ];
    
    //     if (!isset($modelMap[$type])) {
    //         return ResponseHelper::error('Invalid type', [], 422);
    //     }
    
    //     // Determine the target user_id
    //     if (in_array($user->role, ['adm', 'sadm'])) {
    //         if (!$request->filled('user_id')) {
    //             return ResponseHelper::error('user_id field is required for admin', [], 422);
    //         }
    //         $targetUserId = $request->user_id;
    //     } else {
    //         $targetUserId = $user->id;
    //     }
    
    //     $modelClass = $modelMap[$type];
    
    //     // Check if a record exists
    //     $record = $modelClass::firstOrNew(['user_id' => $targetUserId]);
    
    //     // Validate only fillable fields
    //     $validator = BusinessValidation::validateUpdateFields($request->all(), $record->getFillable());
    //     if ($validator->fails()) {
    //         return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    //     }
    
    //     try {
    //         if ($type === 'kyc') {
    //             $docKeys = ['aadhaar_front','aadhaar_back','shop_image','pan_card','photo'];
    //             foreach ($docKeys as $docKey) {
    //                 if ($request->has($docKey) && $request->$docKey) {
    //                     $record->$docKey = $this->saveBase64Image($request->$docKey, $docKey);
    //                 }
    //             }
    //         } else {
    //             foreach ($request->all() as $field => $value) {
    //                 if ($field !== 'type' && $field !== 'id' && $record->isFillable($field)) {
    //                     $record->$field = $value;
    //                 }
    //             }
    //         }
    
    //         // Ensure user_id is set (important for new records)
    //         $record->user_id = $targetUserId;
    
    //         $record->save();
    
    //         $action = $record->wasRecentlyCreated ? 'created' : 'updated';
    //         return ResponseHelper::success(ucfirst($type) . " $action successfully", $record, 200);
    
    //     } catch (\Exception $e) {
    //         return ResponseHelper::error('Operation failed', ['message' => $e->getMessage()], 500);
    //     }
    // }
public function updateByType(Request $request)
{
    $user = Auth::user();

    // Validate type field
    $validator = BusinessValidation::validateType($request->all());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    }

    $type = $request->type;

    // Map types to models
    $modelMap = [
        'basic'        => Business::class,
        'user_details' => User::class,
        'address'      => BusinessAddress::class,
        'user_details' => User::class,
        'kyc'          => BusinessKyc::class,
    ];

    if (!isset($modelMap[$type])) {
        return ResponseHelper::error('Invalid type', [], 422);
    }

    // Determine target user
    if (in_array($user->role, ['adm', 'sadm'])) {
        if (!$request->filled('user_id')) {
            return ResponseHelper::error('user_id field is required for admin', [], 422);
        }
        $targetUserId = $request->user_id;
    } else {
        $targetUserId = $user->id;
    }

    $modelClass = $modelMap[$type];

    // Fetch or create record
    if ($type === 'user_details') {
        $record = $modelClass::find($targetUserId);
        if (!$record) {
            return ResponseHelper::error('User not found', [], 404);
        }
    } elseif ($type === 'address') {
        // Update or create address
        $addressData = array_filter($request->only((new BusinessAddress())->getFillable()));
        $record = $modelClass::updateOrCreate(
            ['user_id' => $targetUserId],
            $addressData
        );
    } else {
        $record = $modelClass::firstOrNew(['user_id' => $targetUserId]);
    }

    // Validate update fields
    $validator = BusinessValidation::validateUpdateFields($request->all(), $record->getFillable());
    if ($validator->fails()) {
        return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
    }

    try {
        if ($type === 'kyc') {
            if (in_array($user->role, ['adm', 'sadm'])) {
                $userNew = User::find($targetUserId);
                if ($userNew) {
                    // $userNew->status = 2; 
                    // $userNew->save();
                }
            }

            $docKeys = ['aadhaar_front', 'aadhaar_back', 'shop_image', 'pan_card', 'photo'];
            foreach ($docKeys as $docKey) {
                if ($request->has($docKey) && $request->$docKey) {
                    // $record->$docKey = $this->saveBase64Image($request->$docKey, $docKey);
                        $record->$docKey = $this->saveUploadedFileNew($request->$docKey, "images");
                }
            }
        } elseif ($type !== 'address') {
            // Only update allowed fields for other types
            $restrictedFields = ['id', 'api_token', 'password', 'email_verified_at', 'created_at', 'updated_at'];
            foreach ($request->all() as $field => $value) {
                if (!in_array($field, array_merge(['type', 'user_id'], $restrictedFields)) && $record->isFillable($field)) {
                    $record->$field = $value;
                }
            }
        }

        // Ensure user_id is set for non-user_details types
        if (!in_array($type, ['user_details', 'address'])) {
            $record->user_id = $targetUserId;
        }

        $record->save();

        $action = $record->wasRecentlyCreated ? 'created' : 'updated';
        return ResponseHelper::success(ucfirst($type) . " $action successfully", $record, 200);

    } catch (\Exception $e) {
        return ResponseHelper::error('Operation failed', ['message' => $e->getMessage()], 500);
    }
}
   protected function saveUploadedFileNew(UploadedFile $file, string $folder = 'images')
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
        private function saveBase64ImageChunks(array $requestData, string $prefix)
{
    $chunks = [];

    // ✅ Collect: aadhaar_front1, aadhaar_front2 ... OR receipt1 ...
    foreach ($requestData as $key => $value) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '\d+$/', $key)) {
            $chunks[$key] = $value;
        }
    }

    if (empty($chunks)) {
        logger("No base64 chunks found for prefix: " . $prefix);
        return null;
    }

    // Sort chunks: aadhaar_front1, aadhaar_front2...
    ksort($chunks);

    // Merge base64 chunks
    $base64Data = implode('', $chunks);

    // Decode
    $imageData = base64_decode($base64Data);
    if ($imageData === false) {
        logger("Base64 decode failed for prefix: " . $prefix);
        return null;
    }

    // Max 2MB
    if (strlen($imageData) > (2 * 1024 * 1024)) {
        logger("Image size exceeded for prefix: " . $prefix);
        return null;
    }

    // Detect mime
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_buffer($finfo, $imageData);
    finfo_close($finfo);

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png'
    ];

    if (!isset($extensionMap[$mime])) {
        logger("Invalid mime type {$mime} for prefix: {$prefix}");
        return null;
    }

    $extension = $extensionMap[$mime];

    // Save file
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    $path = 'images/' . $filename;

    if (!Storage::disk('public')->exists('images')) {
        Storage::disk('public')->makeDirectory('images');
    }

    Storage::disk('public')->put($path, $imageData);

    return 'storage/' . $path;
}

    public function approveReject(Request $request)
    {
        $authUser = Auth::user();
        if (!in_array($authUser->role, ['adm', 'sadm'])) {
            return ResponseHelper::error('Unauthorized', [], 403);
        }
        $validator = BusinessValidation::approveReject($request->all());
        if ($validator->fails()) {
            return ResponseHelper::error('Validation Failed', $validator->errors(), 422);
        }
    
        $user = User::find($request->user_id);
        if ($user->status != 2) {
            return ResponseHelper::error('Action not allowed: User status is not pending KYC approval', [], 403);
        }
    
        if ($request->action === 'approve') {
            $user->status = 1;
            $user->save();
    
            return ResponseHelper::success('User approved successfully', $user->status, 200);
        }
    
        if ($request->action === 'reject') {
            DB::transaction(function () use ($user) {
                $user->status = 3;
                $user->save();
                $models = [
                    Business::class,
                    // BusinessContact::class,
                    BusinessAddress::class,
                    // BusinessIdentification::class,
                    // BusinessBank::class,
                    // BusinessDocument::class,
                    BusinessKyc::class,
            
                ];
    
                foreach ($models as $model) {
                    $model::where('user_id', $user->id)->delete();
                }
            });
    
            return ResponseHelper::success('User rejected and all related data deleted', $user->status, 200);
        }
    }





}
