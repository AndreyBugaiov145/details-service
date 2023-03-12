<?php

namespace App\Console\Commands;

use App\Services\DetailService;
use App\Services\JobsService;
use Illuminate\Console\Command;

class ScrapeDetailsInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $JobsService = new JobsService();
        $JobsService->cre();

        return 0;
    }
}
