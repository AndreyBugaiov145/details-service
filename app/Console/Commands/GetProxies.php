<?php

namespace App\Console\Commands;

use App\Services\ProxyService;
use Illuminate\Console\Command;

class GetProxies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get proxies list';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start = now();
        $this->comment('Processing');
        $JobsService = new ProxyService();
        $r = $JobsService->getProxies();
        $this->info('proxies count = ' . count($r));
        $time = $start->diffInSeconds(now());
        $this->comment("Processed in $time seconds");

        return 0;
    }
}
