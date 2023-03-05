<?php

namespace App\Services;

use App\Models\ParsingSetting;

class QueueService
{
    public function grabbingInNotPlanned()
    {
        $parsingSetting = ParsingSetting::where([
            ['category_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
            ['detail_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
        ])
            ->orderBy('category_parsing_at')->orderBy('detail_parsing_at')
            ->first();

        if ($parsingSetting) {
            if ($parsingSetting->category_parsing_status === ParsingSetting::STATUS_PENDING) {
                $this->grabbingCategories($parsingSetting);
            } elseif ($parsingSetting->detail_parsing_status === ParsingSetting::STATUS_PENDING) {
                $this->grabbingDetails($parsingSetting);
            }
        }

    }

    public function grabbingPlanned()
    {
        ParsingSetting::where([
            ['category_parsing_status', '=', ParsingSetting::STATUS_SUCCESS, 'or'],
            ['detail_parsing_status', '=', null, 'or'],
            ['category_parsing_status', '=', null, 'or'],
        ])
            ->orderBy('created_at')->orderBy('category_parsing_at')->orderBy('detail_parsing_at')
            ->get();
    }

    public function grabbingCategories($parsingSetting)
    {
        $detailService = new DetailService($parsingSetting);
        $detailService->fetchCategoriesAndDetailsInfo();
    }

    public function grabbingDetails($parsingSetting)
    {

    }
}
