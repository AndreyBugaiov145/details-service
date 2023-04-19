<?php

namespace App\Console;

use App\Models\Proxy;
use App\Services\CurrencyService;
use App\Services\JobsService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Log;

class Kernel extends ConsoleKernel
{


    protected function scheduleTimezone()
    {
        return 'Europe/Istanbul';
    }

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->call(function () {
            Log::info('test schedule');
        })->everyMinute();
        // Update UAH currency
        $schedule->call(function () {
            $currencyService = new CurrencyService();
            $currencyService->updateUAHRate();
        })->twiceDaily(5, 14);

        // Update UAH currency
        $schedule->call(function () {
           Proxy::where('fail_count', '>',15)->withoutGlobalScopes()->delete();
        })->monthly();

//        Grabbing
//        $schedule->call(function () {
//            $jobsService = new JobsService();
//            $jobsService->addGrabbingAllCategoriesAndDetailsJobs();
//        })->monthly();
//
//        $schedule->call(function () {
//            $jobsService = new JobsService();
//            $jobsService->addGrabbingAllDetailsJobs();
//        })->twiceMonthly(10, 20, '23:59');

        $schedule->call(function () {
            $jobsService = new JobsService();
            $jobsService->createPendingCategoriesOrDetailsJobs();
        })->daily();

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
