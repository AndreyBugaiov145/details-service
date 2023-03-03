<?php

namespace App\Services;

use App\Exceptions\GrabberException;
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
    protected $attempts = 0;
    protected $max_attempts = 25;
    protected $i = 0;
    public $request_count = 0;

    public function __construct()
    {
        $this->grabber = new GrabberService();
        $this->currency_id = Currency::where('code', Currency::UAH_CODE)->first()->id;
    }

    public function fetchDetailsInfo()
    {
        Log::info('Start fetching');
        try {
            $this->attempts = 0;
            $mainCategoriesData = $this->fetchMainCategories();
            dump($mainCategoriesData);
            $this->attempts = 0;
            $mainYearsCategoriesData = $this->fetchMainYearsCategories($mainCategoriesData);
            $this->request_count += count($mainYearsCategoriesData);
//            $mainYearsCategoriesData = $this->array2Dto1DAndAddUid($mainYearsCategoriesData);
            dump($mainYearsCategoriesData);
            $this->fetchChildCategories($mainYearsCategoriesData);
            $this->attempts = 0;
            foreach ($mainYearsCategoriesData as $mainYearsCategoryData) {
                $this->attempts = 0;
                $this->fetchChildCategories([$mainYearsCategoryData]);
            }
//            $rez = $this->fetchChildCategories($mainYearsCategoriesData);
//            dump($rez);
//            foreach ($mainYearsCategoriesData as $mainYearsCategoryData) {
//                $this->attempts = 0;
//                $this->fetchChildCategories($mainYearsCategoryData);
//            }

        } catch (GrabberException $e) {
            Log::warning($e->getMessage(), $e->getTrace());
            dump('GrabberException');
            dd($e->getMessage());
        } catch (\Exception $e) {

            Log::critical($e->getMessage(), $e->getTrace());
            dump('Exception');
            dd($e->getMessage());
        }

        Log::info('fetching finish');
    }

    public function array2Dto1DAndAddUid(array $data): array
    {
        $uidArr = [];
        $result = [];

        foreach ($data as $category) {
            if (is_array($category)) {
                foreach ($category as $item) {
                    do {
                        $uid = \Str::random(25);
                    } while (in_array($uid, $uidArr));
                    $uidArr[] = $uid;
                    $newItem = $item;
                    $newItem['uid'] = $uid;
                    $result[] = $newItem;
                }
            }
        }

        return $result;
    }

    public function fetchMainCategories()
    {
        Log::info('start fetching main categories');
        do {
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $html = $this->grabber->getMainPage();
            $this->attempts++;
        } while (!$html);
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
        $categoriesData = [];
        $rejectedCategoryData = [];
        $result = $this->grabber->getAsyncChildCategories($data);

        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['title'] == $key;
                });
            } else {
                $sliceCategoriesData = $this->parseAndSaveYearsCategoryItems([
                    'data' => $data,
                    'key' => $key,
                    'responseArr' => $responseArr,
                    'searchedBrands' => $searchedBrands,
                ]);

                $categoriesData[] = $sliceCategoriesData;
            }
        }
        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
        }

        if (count($rejectedCategoryData)) {
            $this->attempts++;
            $categoriesData2 = $this->fetchMainYearsCategories($rejectedCategoryData);
            $categoriesData = array_merge($categoriesData, $categoriesData2);
        }

        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    public function parseAndSaveYearsCategoryItems(array $info): array
    {
        $html = $this->getCategoryHtmlFromStream($info['responseArr']['value']);
        $item = Arr::first($info['data'], function ($item) use ($info) {
            return $item['title'] == $info['key'];
        });
        $parser = new ParserService($html);
        $categories = $parser->getAllChildCategoriesWithJns();
        unset($parser);
        if (isset($info['searchedBrands'])) {
            $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $info) {
                $brand = $info['searchedBrands']->where('brand', $item['title'])->first();
//                return intval($category['title']) == intval($brand->year_from);
                return intval($category['title']) >= intval($brand->year_from) && intval($category['title']) <= intval($brand->year_to);
            });
        } else {
            $sliceCategoriesData = $categories;
        }

        $sliceCategoriesData = array_map(function ($category) use ($item) {
            $category['parent_id'] = $item['id'];
            return $category;
        }, $sliceCategoriesData);


        $this->saveCategory($sliceCategoriesData);

        return $sliceCategoriesData;
    }

    public function getCategoryHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['html_fill_sections']) && is_array($data['html_fill_sections'])) {
            return reset($data['html_fill_sections']);
        }

        return '';
    }

    public function fetchRequestCategories($data)
    {
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncChildCategories($data);
        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['uid'] == $key;
                });
            } else {
                $successCategoryData[$key] = $responseArr;
            }
        }

        return [
            'success' => $successCategoryData,
            'rejected' => $rejectedCategoryData,
        ];
    }

    public function fetchChildCategories(array $data)
    {
        $this->i++;
        if ($this->i > 5) {
            return;
        }

        Log::info('start fetching child categories', $data);
        $this->attempts = 0;
        $newAllCategoriesData = [];
        $data = $this->array2Dto1DAndAddUid($data);
        $result = $this->fetchRequestCategories($data);
        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getCategoryHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['uid'] == $key;
            });
            $parser = new ParserService($html);
            if ($parser->isFinalCategory()) {
                $detailsData = $parser->getDetails();
                $this->saveDetails($detailsData, $item['id']);
                unset($parser);
            } else {
                $categories = $parser->getAllChildCategoriesWithJns();
                unset($parser);

                $categories = array_map(function ($category) use ($item) {
                    $category['parent_id'] = $item['id'];
                    return $category;
                }, $categories);
                $this->saveCategory($categories);
                $newAllCategoriesData[] = $categories;
            }

        }

        dump($newAllCategoriesData);
        Log::info('finish fetching child categories', $newAllCategoriesData);
        if (count($newAllCategoriesData) > 0) {
            $this->fetchChildCategories($newAllCategoriesData);
        }

        return;

    }

    public function saveCategory(&$data)
    {
        foreach ($data as $key => $item) {
            $categoryData = ['title' => $item['title']];
            if (isset($item['parent_id'])) {
                $categoryData['parent_id'] = $item['parent_id'];
            }
            if (isset($item['jsn'])) {
                $categoryData['jsn'] = json_encode($item['jsn']);
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
