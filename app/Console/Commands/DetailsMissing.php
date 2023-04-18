<?php

namespace App\Console\Commands;

use App\Repositories\CategoryRepository;
use Illuminate\Console\Command;

class DetailsMissing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'detail:missing {brand} {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get number of categories missing details';

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
        $categoriesDB = CategoryRepository::getLastChildrenCategories($this->argument('brand'),$this->argument('year'));
        $categoriesDBIds = collect($categoriesDB)->pluck('id')->toArray();
        $categoriesCount =  \App\Models\Category::doesntHave('details')->whereIn('id',$categoriesDBIds)->count();
        $this->info('categories count = ' .$categoriesCount);
        $this->comment("Processed");

        return 0;
    }
}
