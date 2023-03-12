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

        $categories = CategoryRepository::getLastChildrenCategories($parsingSetting->brand);

        $detailService = new DetailService($parsingSetting);
        $detailService->fetchDetailsInfo([$categories]);
    }

//
//    public function grabbingInNotPlanned()
//    {
//        $parsingSetting = ParsingSetting::where([
//            ['category_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
//            ['detail_parsing_status', '=', ParsingSetting::STATUS_PENDING, 'or'],
//        ])
//            ->orderBy('category_parsing_at')->orderBy('detail_parsing_at')
//            ->first();
//
//        if ($parsingSetting) {
//            if ($parsingSetting->category_parsing_status === ParsingSetting::STATUS_PENDING) {
//                $this->grabbingCategories($parsingSetting);
//            } elseif ($parsingSetting->detail_parsing_status === ParsingSetting::STATUS_PENDING) {
//                $this->grabbingDetails($parsingSetting);
//            }
//        }
//    }
//
//    public function grabbingCategories($parsingSetting)
//    {
//        $parsingSetting->update([
//            'category_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
//            'detail_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
//        ]);
//
//        $detailService = new DetailService($parsingSetting);
//        $detailService->fetchCategoriesAndDetailsInfo();
//    }
//
//    public function grabbingDetails()
//    {
//        $parsingSetting = ParsingSetting::where('detail_parsing_status', ParsingSetting::STATUS_PENDING)
//            ->orderBy('detail_parsing_at')->first();
//        if (is_null($parsingSetting)) {
//            return;
//        }
//        $parsingSetting->update([
//            'detail_parsing_status' => ParsingSetting::STATUS_IN_PROGRESS,
//        ]);
//
//        $categories = CategoryService::getLastChildrenCategories($parsingSetting->brand);
//
//        $detailService = new DetailService($parsingSetting);
//        $detailService->fetchDetailsInfo([$categories]);
//    }
}
