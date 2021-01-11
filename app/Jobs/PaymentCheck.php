<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Redis;

class PaymentCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
//        Redis::throttle('key')->block(0)->allow(1)->every(5)->then(function () {
//            info('Lock obtained...');
//            error_log('payment check is working');
//
//            // Handle job...
//        }, function () {
//            // Could not obtain lock...
//            error_log('payment check is working');
//
//            return $this->release(5);
//        });
        error_log('payment check is working');
    }
}
