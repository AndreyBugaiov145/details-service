<?php

namespace App\Console\Commands;

use App\Services\ProxyScrape;
use Illuminate\Console\Command;

class GetAllProxies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proxy:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a full list of unverified proxies';

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
        $endpoint = new ProxyScrape([
            "timeout" => 8000,
            "protocol" => "all",
            "country" => "all",
            "ssl" => "all",
            "anonymity" => "all"
        ]);
        $proxies1 = $endpoint->get() ?: [];
        $this->info('proxies count = ' . count($proxies1));
        $time = $start->diffInSeconds(now());
        $this->comment("Processed in $time seconds");

        return 0;
    }
}
