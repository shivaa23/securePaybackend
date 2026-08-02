<?php

namespace App\Jobs;

use App\Modules\Dmt\Models\DmtBeneficiarie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessBeneficiary implements ShouldQueue
{
    protected $mobile;
    protected $bn;

    public function __construct($mobile, $bn)
    {
        $this->mobile = $mobile;
        $this->bn = $bn;
    }

    public function handle()
    {

        try {
            $bene = DmtBeneficiarie::where('sender', $this->mobile)
                ->where('account_number', $this->bn['bene_acc'])
                ->first();

            if (!$bene) {
                DmtBeneficiarie::create([
                    'pipe' => 'DMT1',
                    'sender' => $this->mobile,
                    'beneficiary_name' => $this->bn['bene_name'],
                    'bene_id' => $this->bn['benid'],
                    'account_number' => $this->bn['bene_acc'],
                    'ifsc_code' => $this->bn['ifsc'],
                    'bank_name' => $this->bn['bank'],
                    'mobile_number' => $this->mobile,
                    'is_verified' => $this->bn['last_success_date'],
                ]);
            } else {
                Log::info('Beneficiary already exists: ' . $this->bn['bene_acc']);
            }
        } catch (\Exception $e) {
            Log::error('Error processing beneficiary ' . $this->bn['bene_acc'] . ': ' . $e->getMessage());
        }
    }
}