<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class TestJob implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    public function handle()
    {
        Log::info('TestJob executed successfully at ' . now());
    }
}