<?php

namespace App\Services;

use App\Models\ParsingSetting;
use App\Repositories\CategoryRepository;

class GrabberService
{

    public function grabbingCategoriesAndDetailsPlanned(ParsingSetting $parsingSetting)
    {
        ParsingSetting::where('category_parsing_status', ParsingSetting::STATUS_IN_PROGRESS)
            ->update(['category_parsing_status' => ParsingSetting::STATUS_FAIL]);

        ParsingSetting::where('detail_parsing_status', ParsingSetting::STATUS_IN_PROGRESS)
            ->update(['detail_parsing_status' => ParsingSetting::STATUS_FAIL]);

        $parsingSetting->update([
            'category_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
            'detail_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
        ]);

        $detailService = new DetailService($parsingSetting);
        $detailService->fetchCategoriesAndDetailsInfo();
    }

    public function grabbingDetailsPlanned(ParsingSetting $parsingSetting)
    {
        ParsingSetting::where('detail_parsing_status', ParsingSetting::STATUS_IN_PROGRESS)
            ->update(['detail_parsing_status' => ParsingSetting::STATUS_FAIL]);

        $parsingSetting->update([
            'detail_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
        ]);

        $categories = CategoryRepository::getLastChildrenCategories($parsingSetting->brand, $parsingSetting->year, $parsingSetting->car_models);

        $detailService = new DetailService($parsingSetting);
        $detailService->fetchDetailsInfo([$categories]);
    }
}
