<?php

namespace App\Console;

use App\Models\Proxy;
use App\Services\CurrencyService;
use App\Services\JobsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update UAH currency
        $schedule->call(function () {
            $currencyService = new CurrencyService();
            $currencyService->updateUAHRate();
        })->timezone('Europe/Istanbul')->twiceDaily(5, 14);

        // Update UAH currency
        $schedule->call(function () {
           Proxy::where('fail_count', '>',13)->delete();
        })->timezone('Europe/Istanbul')->monthly();

        //Grabbing
        $schedule->call(function () {
            $jobsService = new JobsService();
            $jobsService->addGrabbingAllCategoriesAndDetailsJobs();
        })->timezone('Europe/Istanbul')->monthly();

        $schedule->call(function () {
            $jobsService = new JobsService();
            $jobsService->addGrabbingAllDetailsJobs();
        })->timezone('Europe/Istanbul')->weekly();

        $schedule->call(function () {
            $jobsService = new JobsService();
            $jobsService->addGrabbingAllDetailsJobs();
        })->twiceMonthly(10, 20, '22:00');

        $schedule->call(function () {
            $jobsService = new JobsService();
            $jobsService->createPendingCategoriesOrDetailsJobs();
        })->timezone('Europe/Istanbul')->daily();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
