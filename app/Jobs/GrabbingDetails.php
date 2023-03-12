<?php

namespace App\Jobs;

use App\Models\ParsingSetting;
use App\Services\GrabberService;
use App\Services\ProxyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class GrabbingDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 17000;

    public $parserSetting;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ParsingSetting $parserSetting)
    {
        $this->parserSetting = $parserSetting;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $grabberService = new GrabberService();
        $grabberService->grabbingDetailsPlanned($this->parserSetting);
    }

    /**
     *
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        Log::warning($exception->getMessage(),$exception->getTrace());

        $this->parserSetting->update([
            'detail_parsing_status' => ParsingSetting::STATUS_FAIL,
        ]);
    }
}
