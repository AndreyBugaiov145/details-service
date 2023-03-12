<?php

namespace App\Console\Commands;

use App\Services\DetailService;
use App\Services\JobsService;
use Illuminate\Console\Command;

class ScrapePendingCategoriesInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create tasks for parsing categories and details';

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
        $JobsService->addGrabbingAllCategoriesAndDetailsJobs();

        return 0;
    }
}
