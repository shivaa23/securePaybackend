<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateDailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan summary:live
     */
    protected $signature = 'summary:daily';

    /**
     * The console command description.
     */
    protected $description = 'Update live service summaries incrementally (only new txns since last run)';

    public function handle()
{
    $date = Carbon::now()->subDay()->toDateString(); // yesterday’s data
    $services = [
        'dmt'      => \App\Modules\Dmt\Models\DmtTxn::class,
        'recharge' => \App\Modules\Recharge\Models\RechargeTxn::class,
        'payout'   => \App\Modules\Payout\Models\PayoutTxn::class,
        'aeps'     => \App\Modules\Aeps\Models\AepsTxn::class,
        'bbps'     => \App\Modules\Bbps\Models\BbpsTxn::class,
        'matm'     => \App\Modules\Matm\Models\MatmTxn::class,
        'Credit Card'     => \App\Modules\CreditCard\Models\CreditCard::class,
    ];

    foreach ($services as $serviceName => $model) {
        // Fetch all relevant transactions for that date
        $txns = $model::whereDate('created_at', $date)
            ->whereIn('status', ['SUCCESS','PENDING'])
            ->get();

        foreach ($txns->groupBy('user_id') as $userId => $userTxns) {
            $user = User::find($userId);
            if (!$user) continue;

            // ---- Role-based commission calculations ----
            $role = $user->role;
            $amount = $userTxns->sum('amount');

            // handle commission column variations
            $comm = $userTxns->sum(fn($t) => $t->comm ?? $t->commission ?? $t->ret_comm ?? 0);
            $tds  = $userTxns->sum(fn($t) => $t->tds ?? $t->ret_tds ?? 0);
            $charge  = $userTxns->sum(fn($t) => $t->charges ?? $t->charges ?? 0);
            $di_comm = $userTxns->sum('di_comm');
            $di_tds  = $userTxns->sum('di_tds');
            $md_comm = $userTxns->sum('md_comm');
            $md_tds  = $userTxns->sum('md_tds');
            $a_comm = $userTxns->sum('a_comm');

            $profit = $comm + $tds - $charge;

            // ---- Upsert into summary table ----
            \DB::table('service_summaries')->updateOrInsert(
                [
                    'summary_date' => $date,
                    'service_name' => $serviceName,
                    'role'         => $role,
                    'user_id'      => $userId,
                ],
                [
                    'total_amount' => round($amount, 2),
                    'a_comm'    => round($a_comm, 2),
                    'total_comm'   => round($comm, 2),
                    'total_tds'    => round($tds, 2),
                    'di_comm'    => $di_comm ? round($di_comm, 2) : 0,
                    'di_tds'    => $di_tds ? round($di_tds, 2) : 0,
                    'md_comm'    => $md_comm ? round($md_comm, 2) : 0,
                    'md_tds'    => $md_tds ? round($md_tds, 2) : 0,
                    'total_charges'=> round($charge, 2),
                    'profit'       => round($profit, 2),
                    'updated_at'   => Carbon::now(),
                ]
            );
        }
    }

    $this->info("Daily summary generated for {$date}");
}
}