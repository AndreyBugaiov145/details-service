<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Detail;
use App\Models\ParsingSetting;
use Arr;
use Log;

class DetailService
{

    public $grabber;
    protected $currency_id;

    public function __construct()
    {
        $this->grabber = new GrabberService();
        $this->currency_id = Currency::where('code', Currency::UAH_CODE)->first()->id;
    }

    public function fetchDetailsInfo()
    {
        function convert($size)
        {
            $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
            return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
        }

        Log::info('start fetching');
        dump(convert(memory_get_usage(true)));

        $mainCategoriesData = $this->fetchMainCategories();
        $mainYearsCategoriesData = $this->fetchMainYearsCategories($mainCategoriesData);

        foreach ($mainYearsCategoriesData as $mainYearsCategoryData) {
            $this->fetchChildCategories($mainYearsCategoryData);
        }


        Log::info('fetching finish');
        dump(convert(memory_get_usage(true)));
    }

    public function fetchMainCategories()
    {

        Log::info('start fetching main categories');

        $html = $this->grabber->getMainPage();
        $parser = new ParserService($html);
        $categoriesData = $parser->getAllChildCategoriesWithJns();
        unset($parser);

        $searchedBrandsTitle = $this->getSearchedBrands()->pluck('brand')->toArray();

        $sliceCategoriesData = array_filter($categoriesData, function ($item) use ($searchedBrandsTitle) {
            return in_array($item['title'], $searchedBrandsTitle);
        });
        $this->saveCategory($sliceCategoriesData);

        Log::info('main categories data', $sliceCategoriesData);
        return $sliceCategoriesData;
    }

    public function fetchMainYearsCategories(array $data)
    {
        Log::info('start fetching years by main categories');
        $searchedBrands = $this->getSearchedBrands();


        /////////////////////
        $categoriesData = [];

        $rejectedCategoryRequest = [];
        $result = $this->grabber->getAsyncChildCategories($data);
        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $categoriesData[] = current(Arr::where($data, function ($item) use ($key) {
                    return $item['title'] == $key;
                }));
            } else {
                $html = $this->getCategoryHtmlFromStream($responseArr['value']);
                $item = current(Arr::where($data, function ($item) use ($key) {
                    return $item['title'] == $key;
                }));
                $parser = new ParserService($html);
                $categories = $parser->getAllChildCategoriesWithJns();
                unset($parser);
                $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $searchedBrands) {
                    $brand = $searchedBrands->where('brand', $item['title'])->first();
                    return intval($category['title']) >= intval($brand->year_from) && intval($category['title']) <= intval($brand->year_to);
                });

                $sliceCategoriesData = array_map(function ($category) use ($item) {
                    $category['parent_id'] = $item['id'];
                    return $category;
                }, $sliceCategoriesData);


                $this->saveCategory($sliceCategoriesData);

                $categoriesData[] = $sliceCategoriesData;
            }
        }

        return $categoriesData;
        ///////////////////////////
        foreach ($data as $item) {
            $html = $this->grabber->getChildCategories($item['jsn']);
            $parser = new ParserService($html);
            $categories = $parser->getAllChildCategoriesWithJns();
            unset($parser);
            $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $searchedBrands) {
                $brand = $searchedBrands->where('brand', $item['title'])->first();
                return intval($category['title']) >= intval($brand->year_from) && intval($category['title']) <= intval($brand->year_to);
            });

            $sliceCategoriesData = array_map(function ($category) use ($item) {
                $category['parent_id'] = $item['id'];
                return $category;
            }, $sliceCategoriesData);


            $this->saveCategory($sliceCategoriesData);

            $categoriesData[] = $sliceCategoriesData;
        }
        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    public function getCategoryHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['html_fill_sections']) && is_array($data['html_fill_sections'])) {
            return reset($data['html_fill_sections']);
        }

        return '';
    }

    public function fetchChildCategories(array $data)
    {
        Log::info('start fetching child categories');
        foreach ($data as $item) {
            $html = $this->grabber->getChildCategories($item['jsn']);
            $parser = new ParserService($html);
            if ($parser->isFinalCategory()) {
                $detailsData = $parser->getDetails();
                $this->saveDetails($detailsData, $item['id']);
            } else {
                $categoriesData = $parser->getAllChildCategoriesWithJns();
                $categoriesData = array_map(function ($category) use ($item) {
                    $category['parent_id'] = $item['id'];

                    return $category;
                }, $categoriesData);

                $this->saveCategory($categoriesData);
                $this->fetchChildCategories($categoriesData);
            }

            unset($parser);
        }
        Log::info('finish fetching child categories', $data);
    }

    public function saveCategory(&$data)
    {
        $categories = collect();

        foreach ($data as $key => $item) {
            $categoryData = ['title' => $item['title']];
            if (isset($item['parent_id'])) {
                $categoryData['parent_id'] = $item['parent_id'];
            }
            $category = Category::firstOrCreate($categoryData);
            $data[$key]['id'] = $category->id;
        }
    }

    public function saveDetails(array $detailsData, $category_id)
    {
        $category = Category::find($category_id);

        $details = array_map(function ($detail) {
            $detail['currency_id'] = $this->currency_id;
            return Detail::firstOrNew($detail);
        }, $detailsData);

        $category->details()->saveMany($details);
    }

    protected function getSearchedBrands()
    {
        return ParsingSetting::get();
    }
}
