<?php

namespace App\Services;

use App\Exceptions\GrabberException;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Detail;
use App\Models\DetailAnalogue;
use App\Models\ParsingSetting;
use App\Models\ParsingStatistic;
use App\Utils\ArrayUtils;
use App\Utils\MemoryUtils;
use Arr;
use ArrayObject;
use Carbon\Carbon;
use Log;
use phpDocumentor\Reflection\Types\Boolean;

class DetailService
{

    public $grabber;
    protected $currency_id;
    protected $parsingSetting;
    protected $attempts = 0;
    protected $max_attempts = 70;
    protected $i = 0;
    public $request_count = 0;
    public $detailsData = [];

    public function __construct(ParsingSetting $parsingSetting)
    {
        $this->grabber = new FetchingService();
        $this->parsingSetting = $parsingSetting;
        $this->currency_id = Currency::where('code', Currency::UAH_CODE)->first()->id;
    }

    public function fetchCategoriesAndDetailsInfo()
    {
        $start = microtime(true);
        Log::info('Start fetching');
        try {
            $this->attempts = 0;
            $mainCategoriesData = $this->fetchMainCategories();
            Log::info('fetched mainCategoriesData', ArrayUtils::getFirstItem($mainCategoriesData));

            $this->attempts = 0;
            $mainYearsCategoriesData = $this->fetchMainYearsCategories($mainCategoriesData);
            $this->request_count++;
            Log::info('fetched mainYearsCategoriesData', ArrayUtils::getFirstItem($mainYearsCategoriesData));
            $this->attempts = 0;
            $carModelsCategoriesData = $this->fetchCarModels($mainYearsCategoriesData);
            $this->request_count++;
            Log::info('fetched carModelsCategoriesData', ArrayUtils::getFirstItem($carModelsCategoriesData));

            $this->fetchChildCategories($carModelsCategoriesData);
            $this->attempts = 0;

            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_SUCCESS;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            Log::info('finish fetching categories');

            $detailsDataArr = $this->array2Dto1DAndAddUid($this->detailsData, false);
            Log::info('Details count' . count($detailsDataArr));
            $this->saveDetails($detailsDataArr);

            $detailsDataArrFromDB = $this->getDetails($this->detailsData);
            if (count($detailsDataArrFromDB)) {
                $this->fetchAndMergeToDetailAnalogyDetails($detailsDataArrFromDB);
                Log::info('start saving details.', $this->detailsData[0]);
                $this->saveDetails($this->detailsData);
            }
        } catch (\Exception $e) {
            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            $this->parsingSetting->detail_parsing_at = Carbon::now();

            Log::critical($e->getMessage(), $e->getTrace());
        } finally {
            $this->parsingSetting->save();

            MemoryUtils::loggingUsedMemory();
            $time = round(microtime(true) - $start, 4);
            Log::debug('Время выполнения скрипта: ' . $time . ' сек.');

            ParsingStatistic::create([
                'parsing_setting_id' => $this->parsingSetting->id,
                'parsing_status' => $this->parsingSetting->category_parsing_status,
                'request_count' => $this->request_count,
                'request_time' => $time,
                'parsing_type' => ParsingStatistic::PARSING_CATEGORY,
                'used_memory' => MemoryUtils::getUsedMemory()
            ]);
        }

        Log::info('fetching finish');
    }

    public function fetchDetailsInfo($categoriesData)
    {
        $start = microtime(true);
        Log::info('Start fetching Details Only');
        try {
            $this->fetchChildCategories($categoriesData);
            $this->attempts = 0;
            Log::info('fetched Details Only', $this->detailsData[0]);

            //save details
//            $detailsDataArr = $this->array2Dto1DAndAddUid($this->detailsData, false);
            $this->saveDetails($this->array2Dto1DAndAddUid($this->detailsData, false));

            $detailsDataArr = $this->getDetails($this->detailsData);
            Log::info('Details count' . count($detailsDataArr));
            if (count($detailsDataArr)) {
                $this->fetchAndMergeToDetailAnalogyDetails($detailsDataArr);
                Log::info('start saving details.', $this->detailsData[0]);
                $this->saveDetails($this->detailsData);
            }


//
//            $result = $this->saveDetails($this->array2Dto1DAndAddUid($this->detailsData, false));
//            if ($result) {
//                $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
//                $this->parsingSetting->detail_parsing_at = Carbon::now();
//
//                $details = $this->getDetails($this->detailsData);
//                $analogyDetailsData = $this->fetchAndMergeToDetailAnalogyDetails($details);
//                if ($this->saveAnalogyDetails($analogyDetailsData)) {
//                    Detail::whereIn('id', array_unique(array_map(function ($item) {
//                        return $item['detail_id'];
//                    }, $analogyDetailsData)))->update(['is_parsing_analogy_details' => true]);
//                };
//            }
        } catch (\Exception $e) {
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_at = Carbon::now();
            $this->parsingSetting->save();

            Log::critical($e->getMessage(), $e->getTrace());
        } finally {
            MemoryUtils::loggingUsedMemory();
            $time = round(microtime(true) - $start, 4);
            Log::debug('Время выполнения скрипта: ' . $time . ' сек.');

            ParsingStatistic::create([
                'parsing_setting_id' => $this->parsingSetting->id,
                'parsing_status' => $this->parsingSetting->detail_parsing_status,
                'request_count' => $this->request_count,
                'request_time' => $time,
                'parsing_type' => ParsingStatistic::PARSING_DETAIL,
                'used_memory' => MemoryUtils::getUsedMemory()
            ]);
        }

        Log::info('fetching finish');
    }

    public function array2Dto1DAndAddUid(array $data, bool $addUid = true): array
    {
        $uidArr = [];
        $result = [];
        if (is_array($data)) {
            foreach ($data as $category) {
                if (is_array($category)) {
                    foreach ($category as $item) {
                        do {
                            $uid = \Str::random(25);
                        } while (in_array($uid, $uidArr));
                        $uidArr[] = $uid;

                        $newItem = $item;
                        if ($addUid) {
                            $newItem['uid'] = $uid;
                        }
                        $result[] = $newItem;
                    }
                }
            }
        }

        return $result;
    }

    protected function fetchMainCategories()
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


        $sliceCategoriesData = array_filter($categoriesData, function ($item) {
            return $item['title'] == $this->parsingSetting->brand;
        });
        $this->saveCategory($sliceCategoriesData);

        Log::info('main categories data', $sliceCategoriesData);
        return $sliceCategoriesData;
    }

    protected function fetchMainYearsCategories(array $data)
    {
        Log::info('start fetching years by main categories.count requests = ' . count($data));
        $categoriesData = [];
        $result = $this->fetchRequestCategories($data);
        Log::info('fetching years by main categories result', $result);

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
            Log::info(' fetching Main Years rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['title'] == $key;
                });
            } else {
                $sliceCategoriesData = $this->parseAndSaveYearsCategoryItems([
                    'data' => $data,
                    'key' => $key,
                    'responseArr' => $responseArr,
                ]);

                $categoriesData[] = $sliceCategoriesData;
            }
        }
        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function fetchCarModels(array $data)
    {
        Log::info('start fetching  Car Models categories.count requests = ' . count($data));
        $data = $this->array2Dto1DAndAddUid($data);
        $categoriesData = [];

        $result = $this->fetchRequestCategories($data);
        Log::info('start fetching  Car Models categories result', $result);

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
            Log::info(' fetching Car Models categories rejected count' . count($result['rejected']));
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['uid'] == $key;
                });
            } else {
                $sliceCategoriesData = $this->parseAndSaveCarModelsCategoryItems([
                    'data' => $data,
                    'key' => $key,
                    'responseArr' => $responseArr,
                ]);

                $categoriesData[] = $sliceCategoriesData;
            }
        }

        Log::info('finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function parseAndSaveYearsCategoryItems(array $info): array
    {
        $html = $this->getCategoryHtmlFromStream($info['responseArr']['value']);
        $item = Arr::first($info['data'], function ($item) use ($info) {
            return $item['title'] == $info['key'];
        });
        $parser = new ParserService($html);
        $categories = $parser->getAllChildCategoriesWithJns();
        unset($parser);

        $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $info) {
            return intval($category['title']) == intval($this->parsingSetting->year);
        });

        $sliceCategoriesData = array_map(function ($category) use ($item) {
            $category['parent_id'] = $item['id'];
            return $category;
        }, $sliceCategoriesData);


        $this->saveCategory($sliceCategoriesData);

        return $sliceCategoriesData;
    }

    protected function parseAndSaveCarModelsCategoryItems(array $info): array
    {
        $html = $this->getCategoryHtmlFromStream($info['responseArr']['value']);
        $item = Arr::first($info['data'], function ($item) use ($info) {
            return $item['uid'] == $info['key'];
        });
        $parser = new ParserService($html);
        $categories = $parser->getAllChildCategoriesWithJns();
        unset($parser);

        $sliceCategoriesData = array_filter($categories, function ($category) use ($item, $info) {
            if (is_null($this->parsingSetting->car_models) || $this->parsingSetting->car_models == '') {
                return true;
            }
            return in_array($category['title'], explode(',', $this->parsingSetting->car_models));
        });
        $sliceCategoriesData = array_map(function ($category) use ($item) {
            $category['parent_id'] = $item['id'];
            return $category;
        }, $sliceCategoriesData);

        $this->saveCategory($sliceCategoriesData);

        return $sliceCategoriesData;
    }

    protected function getCategoryHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['html_fill_sections']) && is_array($data['html_fill_sections'])) {
            return ArrayUtils::getFirstItem($data['html_fill_sections']);
        }

        return '';
    }

    protected function getBuyersGuiHtmlFromStream($stream)
    {
        $data = json_decode((string)$stream->getBody(), true);
        if (isset($data['buyersguidepieces']) && is_array($data['buyersguidepieces'])) {
            return ArrayUtils::getFirstItem($data['buyersguidepieces']);
        }

        return '';
    }

    protected function fetchRequestCategories($data)
    {
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncChildCategories($data);
        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                    return $uid == $key;
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

    protected function fetchChildCategories(array $data)
    {
        Log::info('start fetching child categories', $data[0]);
        $this->attempts = 0;
        $newAllCategoriesData = [];
        $data = $this->array2Dto1DAndAddUid($data);

        Log::info('start fetching child categories count' . count($data));
        $result = $this->fetchRequestCategories($data);

        if ($this->attempts > $this->max_attempts) {
            Log::critical('Faeil max_attempts', $data);
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);

            $all_count = count($data) ?: 1;
            Log::info(' fetching child progress ' . 100 * count($result['success']) / $all_count);
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getCategoryHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['uid'] == $key;
            });
            $parser = new ParserService($html);
            if ($parser->isFinalCategory()) {

                $detailsData = $parser->getDetails();

                foreach ($detailsData as $i => $detail) {
                    $detailsData[$i]['category_id'] = $item['id'];
                    $detailsData[$i]['currency_id'] = $this->currency_id;
                }
                $this->detailsData[] = $detailsData;
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
        Log::info('finish fetching child categories');
        if (count($newAllCategoriesData) > 0) {

            $this->fetchChildCategories($newAllCategoriesData);
        }
    }

    protected function saveCategory(&$data)
    {
        $parentIds = [0];
        $categoryData = [];
        foreach ($data as $key => $item) {
            $category = ['title' => $item['title']];
            if (isset($item['parent_id'])) {
                $category['parent_id'] = $item['parent_id'];
                $parentIds[] = $item['parent_id'];
            }

            if (isset($item['jsn'])) {
//                $categoryData['jsn'] = $item['jsn'];
                $category['jsn'] = json_encode($item['jsn']);
            }

            $categoryData[] = $category;
        }
        $parentIds = array_unique($parentIds);

        $chunks = array_chunk($categoryData, 1000);

        foreach ($chunks as $chunk) {
            Category::upsert($chunk, ['title', 'parent_id'], ['jsn']);
        }

        $categories = Category::whereIn('parent_id', $parentIds)->get();
        foreach ($data as $key => $item) {
            $category = $categories->first(function ($category) use ($item) {
                $parent_id = isset($item['parent_id']) ? $item['parent_id'] : 0;
                return $category->title == $item['title'] && $category->parent_id == $parent_id;
            });
            if (is_null($category)) {
                throw new GrabberException('parent category not found' . json_encode($item));
            }

            $data[$key]['id'] = $category->id;
        }
//
//        $category = Category::firstOrCreate($categoryData);
//        $data[$key]['id'] = $category->id;
    }

    protected function saveDetails(array $detailsData)
    {
        Log::info('Saving details', $detailsData[0]);
        $chunks = array_chunk($detailsData, 1000);

        foreach ($chunks as $chunk) {
            Detail::upsert($chunk, ['title', 'category_id']
//                , [
//                'price',
//                'short_description',
//                's_number',
//                'price',
//                'partkey',
//                'analogy_details',
//                'is_parsing_analogy_details'
//            ]
            );
        }

        $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
        $this->parsingSetting->detail_parsing_at = Carbon::now();
        $this->parsingSetting->save();

        return true;
    }

    protected function getDetails($detailsData): array
    {
        $categoryIds = array_map(function ($details) {
            if (isset($details[0])) {
                return $details[0]['category_id'];
            }
            return 0;
        }, $detailsData);

        $detailsArr = Detail::select([
            'title',
            'category_id',
            'price',
            'short_description',
            's_number',
            'price',
            'partkey',
            'currency_id',
            'is_parsing_analogy_details',
            'analogy_details',
        ])->withoutAppends()->whereIn('category_id', $categoryIds)
            ->where([['is_parsing_analogy_details', false], ['is_manual_added', false]])->get()->toArray();
        Log::debug('getDetails', $detailsArr);

        return $detailsArr;
    }

    protected function fetchAndMergeToDetailAnalogyDetails(array $detailsArray)
    {
        Log::info('start fetching Analogy Details', [$detailsArray[0]]);
        $this->attempts = 0;
        $this->detailsData = [];

        //        grouping details by partkey
        $details = collect($detailsArray);
        $dataDetails = $details->groupBy('partkey');

        //        saving existing analog parts by key partkey
        $existsDetails = Detail::whereIn('partkey', array_keys($dataDetails->toArray()))
            ->where('is_parsing_analogy_details', true)
            ->get();

        $existsDetails = $existsDetails->groupBy('partkey');
//        Log::info('grouping details by partkey' . $dataDetails->first()->toArray());

        foreach ($existsDetails as $key => $existsDetail) {
            if (isset($dataDetails[$key])) {
                foreach ($dataDetails[$key] as $dataDetail) {
                    $this->detailsData[] = array_merge($dataDetail, [
                        'analogy_details' => json_encode($existsDetail[0]->analogy_details),
                        'is_parsing_analogy_details' => true
                    ]);
                }
                $dataDetails->forget($key);
            }
        }

        $data = [];
        foreach ($dataDetails->toArray() as $groupKey => $group) {
            $group['partkey'] = $groupKey;
            $data[$groupKey] = $group;

        }

        Log::info('start fetching Analogy Details count' . count($data));

        $result = $this->fetchRequestAnalogyDetails($data);
        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchAndMergeToDetailAnalogyDetails. attempts > $this->attempts");
            Log::critical('Faeil max_attempts', $data);
        }

        do {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching AnalogyDetails. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestAnalogyDetails($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            $all_count = count($details) ?: 1;
            Log::info(' fetching  Analogy Details progress = ' . 100 * count($result['success']) / $all_count);
        } while (count($result['rejected']));

        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getBuyersGuiHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['partkey'] == $key;
            });
            unset($item['partkey']);

            $parser = new ParserService($html);
            $analogyDetailsData = $parser->getAnalogyDetails();

            foreach ($item as $detail) {
                $this->detailsData[] = array_merge($detail, [
                    'analogy_details' => json_encode($analogyDetailsData),
                    'is_parsing_analogy_details' => true
                ]);

            }

        }

    }

    protected function fetchRequestAnalogyDetails($data)
    {
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncDetailsBuyersGuid($data);

        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    return $item['partkey'] == $key;
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

    private function saveAnalogyDetails(array $analogyDetailsData)
    {
        $chunks = array_chunk($analogyDetailsData, 1000);
        foreach ($chunks as $chunk) {
            DetailAnalogue::upsert($chunk, []);
        }

        return true;
    }
}
