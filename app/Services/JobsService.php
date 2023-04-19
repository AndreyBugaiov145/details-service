<?php

namespace App\Services;

use App\Jobs\GrabbingCategoriesAndDetails;
use App\Jobs\GrabbingDetails;
use App\Models\ParsingSetting;

class JobsService
{
    public function addGrabbingAllCategoriesAndDetailsJobs()
    {
        ParsingSetting::whereNotNull('brand')->where([
            ['detail_parsing_status', '!=', ParsingSetting::STATUS_IN_PROGRESS, 'or'],
            ['category_parsing_status', '!=', ParsingSetting::STATUS_IN_PROGRESS, 'or']
        ])->update([
            'detail_parsing_status' => ParsingSetting::STATUS_PENDING,
            'category_parsing_status' => ParsingSetting::STATUS_PENDING
        ]);

        $parsingSetting = ParsingSetting::where('category_parsing_status', ParsingSetting::STATUS_PENDING)->get();

        $jobsData = $this->getExistsJobsData();
        foreach ($parsingSetting as $setting) {
            $jobs = \Arr::where($jobsData, function ($jobData, $key) use ($setting) {
                return $jobData['job_class'] == GrabbingCategoriesAndDetails::class && $jobData['parserSetting_id'] === $setting->id;
            });
            if (count($jobs) < 1) {
                $job = new GrabbingCategoriesAndDetails($setting);
                dispatch($job);
            }
        }
    }

    public function addGrabbingAllDetailsJobs()
    {
        ParsingSetting::whereNotNull('brand')->where([
            ['detail_parsing_status', '!=', ParsingSetting::STATUS_IN_PROGRESS, 'or'],
            ['category_parsing_status', '!=', ParsingSetting::STATUS_IN_PROGRESS, 'or']
        ])->update(['detail_parsing_status' => ParsingSetting::STATUS_PENDING]);

        $parsingSetting = ParsingSetting::where('detail_parsing_status', ParsingSetting::STATUS_PENDING)->get();
        $jobsData = $this->getExistsJobsData();

        foreach ($parsingSetting as $setting) {
            $jobs = \Arr::where($jobsData, function ($jobData, $key) use ($setting) {
                return $jobData['job_class'] == GrabbingDetails::class && $jobData['parserSetting_id'] === $setting->id;
            });
            if (count($jobs) < 1) {
                $job = new GrabbingDetails($setting);
                dispatch($job);
            }
        }
    }

    protected function addGrabbingPendingCategoriesAndDetailsJobs(ParsingSetting $parsingSetting)
    {

        $jobsData = $this->getExistsJobsData();

        $jobs = \Arr::where($jobsData, function ($jobData, $key) use ($parsingSetting) {
            return $jobData['job_class'] == GrabbingCategoriesAndDetails::class && $jobData['parserSetting_id'] === $parsingSetting->id;
        });
        if (count($jobs) < 1) {
            $job = new GrabbingCategoriesAndDetails($parsingSetting);
            dispatch($job);
        }
    }

    protected function addGrabbingPendingDetailsJobs(ParsingSetting $parsingSetting)
    {
        $jobsData = $this->getExistsJobsData();

        $jobs = \Arr::where($jobsData, function ($jobData, $key) use ($parsingSetting) {
            return $jobData['job_class'] == GrabbingDetails::class && $jobData['parserSetting_id'] === $parsingSetting->id;
        });
        if (count($jobs) < 1) {
            $job = new GrabbingDetails($parsingSetting);
            dispatch($job);
        }
    }

    public function createPendingCategoriesOrDetailsJobs()
    {
        $parsingSettings = ParsingSetting::where([
            ['category_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
            ['detail_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
        ])
            ->orderBy('category_parsing_at')->orderBy('detail_parsing_at')
            ->get();

        foreach ($parsingSettings as $parsingSetting) {
            if ($parsingSetting) {
                if ($parsingSetting->category_parsing_status === ParsingSetting::STATUS_PENDING) {
                    $this->addGrabbingPendingCategoriesAndDetailsJobs($parsingSetting);
                } elseif ($parsingSetting->detail_parsing_status === ParsingSetting::STATUS_PENDING) {
                    $this->addGrabbingPendingDetailsJobs($parsingSetting);
                }
            }
        }
    }

    public function getExistsJobsData()
    {
        $jobs = \DB::table('jobs')->get();
        $jobsData = [];

        foreach ($jobs as $job) {
            $payload = json_decode($job->payload);
            $obj = unserialize($payload->data->command);
            if ($obj::class == GrabbingCategoriesAndDetails::class || $obj::class == GrabbingDetails::class) {
                $jobsData[] = [
                    'job_class' => $obj::class,
                    'parserSetting_id' => $obj->parserSetting->id
                ];
            }
        }

        return $jobsData;
    }
}
