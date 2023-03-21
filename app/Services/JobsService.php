<?php

namespace App\Services;

use App\Jobs\GrabbingCategoriesAndDetails;
use App\Jobs\GrabbingDetails;
use App\Models\ParsingSetting;

class JobsService
{
    public function addGrabbingAllCategoriesAndDetailsJobs()
    {
        ParsingSetting::whereNotNull('brand')->update([
            'detail_parsing_status' => ParsingSetting::STATUS_PENDING,
            'category_parsing_status' => ParsingSetting::STATUS_PENDING
        ]);

        $parsingSetting = ParsingSetting::get();
        foreach ($parsingSetting as $setting) {
            $job = new GrabbingCategoriesAndDetails($setting);
            dispatch($job);
        }
    }

    public function addGrabbingAllDetailsJobs()
    {
        ParsingSetting::whereNotNull('brand')->update(['detail_parsing_status' => ParsingSetting::STATUS_PENDING]);

        $parsingSetting = ParsingSetting::get();
        foreach ($parsingSetting as $setting) {
            $job = new GrabbingDetails($setting);
            dispatch($job);
        }
    }

    protected function addGrabbingPendingCategoriesAndDetailsJobs(ParsingSetting $parsingSetting)
    {
        $job = new GrabbingCategoriesAndDetails($parsingSetting);
        dispatch($job);

    }

    protected function addGrabbingPendingDetailsJobs(ParsingSetting $parsingSetting)
    {
        $job = new GrabbingDetails($parsingSetting);
        dispatch($job);
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
}