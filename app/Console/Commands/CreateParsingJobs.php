<?php

namespace App\Console\Commands;

use App\Services\JobsService;
use Illuminate\Console\Command;

class CreateParsingJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parsing:job';

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
        $this->comment('Processing');
        $JobsService = new JobsService();
        $JobsService->createPendingCategoriesOrDetailsJobs();
        $this->comment("Processed");

        return 0;
    }
}
