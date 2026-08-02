<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateLiveSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan summary:live
     */
    protected $signature = 'summary:live';

    /**
     * The console command description.
     */
    protected $description = 'Update live service summaries incrementally (only new txns since last run)';

    public function handle()
    {
        $services = [
            'dmt'      => \App\Modules\Dmt\Models\DmtTxn::class,
            'recharge' => \App\Modules\Recharge\Models\RechargeTxn::class,
            'payout'   => \App\Modules\Payout\Models\PayoutTxn::class,
            'aeps'     => \App\Modules\Aeps\Models\AepsTxn::class,
            'bbps'    => \App\Modules\Bbps\Models\BbpsTxn::class,
            'matm'    => \App\Modules\Matm\Models\MatmTxn::class,
            'Credit Card' => \App\Modules\CreditCard\Models\CreditCard::class,
        ];

        foreach ($services as $serviceName => $model) {
            // $checkpoint = DB::table('summary_checkpoints')
            //     ->where('service', $serviceName)
            //     ->first();
        
            // $lastId = $checkpoint->last_txn_id ?? 0;
        
            // $newTxns = $model::where('id', '>', $lastId)
            //     ->whereIn('status',['SUCCESS','PENDING'])
            //     ->whereDate('created_at', Carbon::today())
            //     ->get();
            $newTxns = $model::whereIn('status',['SUCCESS','PENDING'])
                ->whereDate('created_at', Carbon::today())
                ->get();
        
            if ($newTxns->isEmpty()) {
                $this->info("No new $serviceName txns since last run.");
                continue;
            }
        
            // Group by user_id
            $grouped = $newTxns->groupBy('user_id');
        
            foreach ($grouped as $userId => $txns) {
                $totalAmount  = $txns->sum('amount');
                $totalComm    = $txns->sum(function ($t) {
                    return $t->comm ?? $t->commission ?? $t->ret_comm ?? 0;
                });
                $totalTds     = $txns->sum(function ($t) {
                    return $t->tds ?? $t->ret_tds ?? 0;
                });
                $totalCharges = $txns->sum('charges') ?? 0;
                $di_comm = $txns->sum('di_comm') ?? 0;
                $di_tds = $txns->sum('di_tds') ?? 0;
                $md_comm = $txns->sum('md_comm') ?? 0;
                $md_tds = $txns->sum('md_tds') ?? 0;
                $a_comm = $txns->sum('a_comm') ?? 0;
                $profit       = $totalComm - $totalCharges;
        
                DB::table('service_summaries')->updateOrInsert(
                    [
                        'summary_date' => Carbon::today()->format('Y-m-d'),
                        'service_name' => $serviceName,
                        'user_id'      => $userId,
                    ],
                    [
                        'total_amount'  => round($totalAmount, 2),
                        'total_comm'    => round($totalComm, 2),
                        'total_tds'     => round($totalTds, 2),
                        'di_comm'     => round($di_comm, 2),
                        'di_tds'     => round($di_tds, 2),
                        'md_comm'     => round($md_comm, 2),
                        'md_tds'     => round($md_tds, 2),
                        'a_comm'     => round($a_comm, 2),
                        'total_charges' => round($totalCharges, 2),
                        'profit'        => round($profit, 2),
                        'updated_at'    => Carbon::now(),
                    ]
                );
            }
        
            $lastProcessedId = $newTxns->max('id');
        
            // DB::table('summary_checkpoints')->updateOrInsert(
            //     ['service' => $serviceName],
            //     ['last_txn_id' => $lastProcessedId, 'updated_at' => Carbon::now()]
            // );
        
            $this->info("$serviceName processed, last txn id = $lastProcessedId");
        }
    }
}