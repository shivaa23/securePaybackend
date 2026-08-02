<?php

namespace App\Jobs;

use App\Modules\Dmt\Models\DmtBeneficiarie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ProcessBeneficiaryDmt2 implements ShouldQueue
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
                ->where('account_number', $this->bn['account'])
                ->first();

            if (!$bene) {
                DmtBeneficiarie::create([
                    'pipe' => 'DMT2',
                    'sender' => $this->mobile,
                    'beneficiary_name' => $this->bn['name'],
                    'bene_id' => $this->bn['id'],
                    'account_number' => $this->bn['account'],
                    'ifsc_code' => $this->bn['ifsc'],
                    'bank_name' => $this->bn['bank'],
                    'mobile_number' => $this->bn['beneficiaryMobileNumber'],
                    'is_verified' => $this->bn['verificationDt'],
                ]);

                //Log::info('Beneficiary inserted: ' . $this->bn['account']);
            } else {
                //Log::info('Beneficiary already exists: ' . $this->bn['account']);
            }
        } catch (\Exception $e) {
            Log::error('Error processing beneficiary ' . $this->bn['account'] . ': ' . $e->getMessage());
        }
    }
}