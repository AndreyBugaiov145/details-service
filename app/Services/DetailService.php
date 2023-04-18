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
    public $categoriesData = [];
    protected $rejectedUuids = [];

    public function __construct(ParsingSetting $parsingSetting)
    {
        MemoryUtils::monitoringMemory();
        $this->grabber = new FetchingService();
        $this->parsingSetting = $parsingSetting;
        $this->currency_id = Currency::where('code', Currency::UAH_CODE)->first()->id;
    }

    public function fetchCategoriesAndDetailsInfo()
    {
        $start = microtime(true);
        Log::info($this->parsingSetting->brand . '--Start fetching');
        try {
            $this->attempts = 0;
            $mainCategoriesData = $this->fetchMainCategories();
            Log::info($this->parsingSetting->brand . '-fetched mainCategoriesData', ArrayUtils::getFirstItem($mainCategoriesData));
            MemoryUtils::monitoringMemory();
            $this->attempts = 0;
            $mainYearsCategoriesData = $this->fetchMainYearsCategories($mainCategoriesData);
            $this->request_count++;
            Log::info($this->parsingSetting->brand . '-fetched mainYearsCategoriesData', ArrayUtils::getFirstItem($mainYearsCategoriesData));
            MemoryUtils::monitoringMemory();
            $this->attempts = 0;
            $carModelsCategoriesData = $this->fetchCarModels($mainYearsCategoriesData);
            $this->request_count++;
            Log::info($this->parsingSetting->brand . '-fetched carModelsCategoriesData', ArrayUtils::getFirstItem($carModelsCategoriesData));
            MemoryUtils::monitoringMemory();

            $this->fetchChildCategories($carModelsCategoriesData);
            $this->attempts = 0;

            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_SUCCESS;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            Log::info($this->parsingSetting->brand . '-finish fetching categories');
            MemoryUtils::monitoringMemory();

            $detailsDataArr = $this->array2Dto1DAndAddUid($this->detailsData, false);
            Log::info($this->parsingSetting->brand . '-Details count' . count($detailsDataArr));
            $this->saveDetails($detailsDataArr);
            unset($detailsDataArr);
            gc_collect_cycles();

            MemoryUtils::monitoringMemory();

            $detailsDataArrFromDB = $this->getDetails($this->detailsData);
            if (count($detailsDataArrFromDB)) {
                $this->fetchAndMergeToDetailAnalogyDetails($detailsDataArrFromDB);
                Log::info($this->parsingSetting->brand . '-start saving details.', $this->detailsData[0]);
                MemoryUtils::monitoringMemory();
                $this->saveDetails($this->detailsData);
            }
        } catch (\Exception $e) {
            $this->parsingSetting->category_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->category_parsing_at = Carbon::now();
            $this->parsingSetting->detail_parsing_at = Carbon::now();

            Log::critical($this->parsingSetting->brand . '-' . $e->getMessage(), $e->getTrace());
        } finally {
            $this->parsingSetting->save();

            MemoryUtils::loggingUsedMemory();
            $time = round(microtime(true) - $start, 4);
            Log::debug($this->parsingSetting->brand . '-Время выполнения скрипта: ' . $time . ' сек.');

            ParsingStatistic::create([
                'parsing_setting_id' => $this->parsingSetting->id,
                'parsing_status' => $this->parsingSetting->category_parsing_status,
                'request_count' => $this->request_count,
                'request_time' => $time,
                'parsing_type' => ParsingStatistic::PARSING_CATEGORY,
                'used_memory' => MemoryUtils::getUsedMemory()
            ]);
        }

        Log::info($this->parsingSetting->brand . '-fetching finish');
    }

    public function fetchDetailsInfo($categoriesData)
    {
        MemoryUtils::monitoringMemory();
        $start = microtime(true);
        Log::info($this->parsingSetting->brand . '-Start fetching Details Only');
        try {
            $this->fetchChildCategories($categoriesData);
            $this->attempts = 0;
            Log::info($this->parsingSetting->brand . '-fetched Details Only', $this->detailsData[0]);
            MemoryUtils::monitoringMemory();

            //save details
            $allDetailsDataArr = $this->array2Dto1DAndAddUid($this->detailsData, false);
            Log::info($this->parsingSetting->brand . '-Details count' . count($allDetailsDataArr));
            $this->saveDetails($allDetailsDataArr);

            $detailsDataArr = $this->getDetails($this->detailsData);
            Log::info($this->parsingSetting->brand . '-Details count need fetch Analogy Details' . count($detailsDataArr));
            if (count($detailsDataArr)) {
                $this->fetchAndMergeToDetailAnalogyDetails($detailsDataArr);
                Log::info($this->parsingSetting->brand . '-start saving details.', $this->detailsData[0]);
                $this->saveDetails($this->detailsData);
            }

        } catch (\Exception $e) {
            $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_FAIL;
            $this->parsingSetting->detail_parsing_at = Carbon::now();
            $this->parsingSetting->save();

            Log::critical($this->parsingSetting->brand . '-' . $e->getMessage(), $e->getTrace());
        } finally {
            MemoryUtils::loggingUsedMemory();
            $time = round(microtime(true) - $start, 4);
            Log::debug($this->parsingSetting->brand . '-Время выполнения скрипта: ' . $time . ' сек.');

            ParsingStatistic::create([
                'parsing_setting_id' => $this->parsingSetting->id,
                'parsing_status' => $this->parsingSetting->detail_parsing_status,
                'request_count' => $this->request_count,
                'request_time' => $time,
                'parsing_type' => ParsingStatistic::PARSING_DETAIL,
                'used_memory' => MemoryUtils::getUsedMemory()
            ]);
        }

        Log::info($this->parsingSetting->brand . '-fetching finish');
    }

    public function array2Dto1DAndAddUid(array $data, bool $addUid = true): array
    {
        MemoryUtils::monitoringMemory();
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
        MemoryUtils::monitoringMemory();

        return $result;
    }

    protected function fetchMainCategories()
    {
        Log::info($this->parsingSetting->brand . '-start fetching main categories');
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

        Log::info($this->parsingSetting->brand . '-main categories data', $sliceCategoriesData);
        return $sliceCategoriesData;
    }

    protected function fetchMainYearsCategories(array $data)
    {
        Log::info($this->parsingSetting->brand . '-start fetching years by main categories.count requests = ' . count($data));
        $categoriesData = [];
        $result = $this->fetchRequestCategories($data);
        Log::info($this->parsingSetting->brand . '-fetching years by main categories result', $result);

        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical($this->parsingSetting->brand . '-Faeil max_attempts', $data);
        }

        while (count($result['rejected'])) {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info($this->parsingSetting->brand . '- fetching Main Years rejected count' . count($result['rejected']));
        }

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
        Log::info($this->parsingSetting->brand . '-finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function fetchCarModels(array $data)
    {
        Log::info($this->parsingSetting->brand . '-start fetching  Car Models categories.count requests = ' . count($data));
        $data = $this->array2Dto1DAndAddUid($data);
        $categoriesData = [];

        $result = $this->fetchRequestCategories($data);
        Log::info($this->parsingSetting->brand . '-start fetching  Car Models categories result', $result);

        if ($this->attempts > $this->max_attempts) {
            throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            Log::critical($this->parsingSetting->brand . '-Faeil max_attempts', $data);
        }

        while (count($result['rejected'])) {
            $this->attempts++;
            if ($this->attempts > $this->max_attempts) {
                throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
            }
            $rez = $this->fetchRequestCategories($result['rejected']);
            $result['rejected'] = $rez['rejected'];
            $result['success'] = array_replace($result['success'], $rez['success']);
            Log::info($this->parsingSetting->brand . '- fetching Car Models categories rejected count' . count($result['rejected']));
        };

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

        Log::info($this->parsingSetting->brand . '-finish fetching years by main categories', $categoriesData);

        return $categoriesData;
    }

    protected function parseAndSaveYearsCategoryItems(array $info): array
    {
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();
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

        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

        return $sliceCategoriesData;
    }

    protected function parseAndSaveCarModelsCategoryItems(array $info): array
    {
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();
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

        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

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
        MemoryUtils::monitoringMemory();
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncChildCategories($data);
        $rejected = [];
        foreach ($result as $key => $responseArr) {
            if ($responseArr['state'] === 'rejected') {
                $rejected[] = $responseArr;
                $rejectedCategoryData[] = Arr::first($data, function ($item) use ($key) {
                    $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                    return $uid == $key;
                });
            } else {
                $successCategoryData[$key] = $responseArr;
            }
        }

        if (count($rejected)) {
            $count_rejected_log = 5 < count($rejected)  ? 5 : count($rejected);
            Log::error('rejected _rend', Arr::random($rejected, $count_rejected_log));
        }
        unset($rejected);
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

        return [
            'success' => $successCategoryData,
            'rejected' => $rejectedCategoryData,
        ];
    }

    protected function fetchChildCategories(array $dataArr)
    {
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

        $this->categoriesData = [];
        $dataAll = $this->array2Dto1DAndAddUid($dataArr);
        Log::info($this->parsingSetting->brand . '-start fetching child categories count = ' . count($dataAll));

        $chunks = array_chunk($dataAll, 3000);
        foreach ($chunks as $i => $data) {
            $this->attempts = 0;
            $successRequestCount = 0;

            Log::info($this->parsingSetting->brand . '-start fetching child categories', ArrayUtils::getFirstItem($data));
            Log::info($this->parsingSetting->brand . '-start fetching child categories chunk count = ' . count($data));

            $result = $this->fetchRequestCategories($data);
            if (count($result['success'])) {
                $successRequestCount += count($result['success']);
                $rejected = $this->getChildCategoriesFromStreamResponses($result, $data);
                $result['rejected'] = array_merge($result['rejected'], $rejected);
                unset($rejected);
            }
            unset($result['success']);
            gc_collect_cycles();

            if ($this->attempts > $this->max_attempts) {
                Log::critical($this->parsingSetting->brand . '-Faeil max_attempts', $data);
                throw new GrabberException("Failed fetchMainYearsCategories. attempts > $this->attempts");
            }

            while (count($result['rejected'])) {
                MemoryUtils::monitoringMemory();
                gc_collect_cycles();
                $this->attempts++;

                if ($this->attempts > $this->max_attempts) {
                    throw new GrabberException("Failed fetching main categories. attempts > $this->attempts");
                }

                $rez = $this->fetchRequestCategories($result['rejected']);
                $result['rejected'] = $rez['rejected'];
                $result['success'] = $rez['success'];
                if (count($result['success'])) {
                    $successRequestCount += count($result['success']);
                    $rejected = $this->getChildCategoriesFromStreamResponses($result, $data);
                    foreach ($rejected as $key => $rejectedItem) {
                        $this->rejectedUuids[$rejectedItem['uid']] = isset($this->rejectedUuids[$rejectedItem['uid']]) ? ($this->rejectedUuids[$rejectedItem['uid']] + 1) : 1;
                        if ($this->rejectedUuids[$rejectedItem['uid']] > 5){
                           unset ($rejected[$key]);
                        }
                    }
                    $result['rejected'] = array_merge($result['rejected'], $rejected);
                }

                $rejectedCount = isset($rejected) ? count($rejected) : 0;
                $chunk_count = count($data) ?: 1;
                Log::info($this->parsingSetting->brand . '- fetching child categories progress chunk = ' . 100 * ($successRequestCount - $rejectedCount) / $chunk_count);

                MemoryUtils::monitoringMemory();
                unset($rez);
                unset($rejected);
                unset($result['success']);
                gc_collect_cycles();
            };

            $this->rejectedUuids =[];
            $all_count = count($chunks) ?: 1;
            Log::info($this->parsingSetting->brand . '- fetching categories child progress = ' . 100 * ($i + 1) / $all_count);
            gc_collect_cycles();
        }

        gc_collect_cycles();
        Log::info($this->parsingSetting->brand . '-finish fetching child categories');
        MemoryUtils::monitoringMemory();

        if (count($this->categoriesData) > 0) {
            $this->fetchChildCategories($this->categoriesData);
        }
    }

    protected function getChildCategoriesFromStreamResponses($result, $data)
    {
        Log::info($this->parsingSetting->brand . '- start ParserService   ' . count($result['success']));

        $rejected = [];
        foreach ($result['success'] as $key => $responseArr) {
            $html = $this->getCategoryHtmlFromStream($responseArr['value']);
            $item = Arr::first($data, function ($item) use ($key) {
                return $item['uid'] == $key;
            });
            if (empty($html)) {
                Log::error('REJECTED empty($html)');
                $rejected[] = Arr::first($data, function ($item) use ($key) {
                    $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                    return $uid == $key;
                });
                continue;
            }
            $parser = new ParserService($html);
            if ($parser->isFinalCategory()) {
                $detailsData = $parser->getDetails();
                if (count($detailsData) < 1) {
                    Log::error('REJECTED NO DETAILS');
                    $rejected[] = Arr::first($data, function ($item) use ($key) {
                        $uid = isset($item['uid']) ? $item['uid'] : $item['title'];
                        return $uid == $key;
                    });
                    continue;
                }

                foreach ($detailsData as $i => $detail) {
                    $detailsData[$i]['category_id'] = $item['id'];
                    $detailsData[$i]['currency_id'] = $this->currency_id;
                }
                $this->detailsData[] = $detailsData;
                unset($detailsData);
            } else {
                $categories = $parser->getAllChildCategoriesWithJns();

                $categories = array_map(function ($category) use ($item) {
                    $category['parent_id'] = $item['id'];
                    return $category;
                }, $categories);
                $this->saveCategory($categories);
                $this->categoriesData[] = $categories;
                unset($categories);
            }
            MemoryUtils::monitoringMemory();
            unset($parser);
            unset($html);
            unset($item);
            gc_collect_cycles();
        }
        MemoryUtils::monitoringMemory();
        unset($parser);
        unset($result);
        gc_collect_cycles();

        return $rejected;
    }

    protected function saveCategory(&$data)
    {
        MemoryUtils::monitoringMemory();
        $parentIds = [0];
        $categoryData = [];
        foreach ($data as $key => $item) {
            $category = ['title' => $item['title']];
            if (isset($item['parent_id'])) {
                $category['parent_id'] = $item['parent_id'];
                $parentIds[] = $item['parent_id'];
            }

            if (isset($item['jsn'])) {
                $category['jsn'] = json_encode($item['jsn']);
            }

            $categoryData[] = $category;
        }
        $parentIds = array_unique($parentIds);

        $chunks = array_chunk($categoryData, 1000);

        foreach ($chunks as $chunk) {
            Category::upsert($chunk, ['title', 'parent_id'], ['jsn']);
        }
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

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
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();
    }

    protected function saveDetails(array $detailsData)
    {
        MemoryUtils::monitoringMemory();
        Log::info($this->parsingSetting->brand . '-Saving details', ArrayUtils::getFirstItem($detailsData));
        $chunks = array_chunk($detailsData, 1000);

        foreach ($chunks as $chunk) {
            Detail::upsert($chunk, ['title', 'category_id']);
        }
        MemoryUtils::monitoringMemory();
        gc_collect_cycles();
        $this->parsingSetting->detail_parsing_status = ParsingSetting::STATUS_SUCCESS;
        $this->parsingSetting->detail_parsing_at = Carbon::now();
        $this->parsingSetting->save();

        unset($chunks);
        unset($detailsData);
        gc_collect_cycles();

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

        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

        return $detailsArr;
    }

    protected function fetchAndMergeToDetailAnalogyDetails(array $detailsArray)
    {
        Log::info($this->parsingSetting->brand . '-start fetching Analogy Details', [$detailsArray[0]]);
        $this->attempts = 0;
        $this->detailsData = [];

        MemoryUtils::monitoringMemory();
        gc_collect_cycles();

        //        grouping details by partkey
        $details = collect($detailsArray);
        $dataDetails = $details->groupBy('partkey');
        unset($details);
        //        saving existing analog parts by key partkey
        $existsDetails = Detail::whereIn('partkey', array_keys($dataDetails->toArray()))
            ->where('is_parsing_analogy_details', true)
            ->get();

        $existsDetails = $existsDetails->groupBy('partkey');
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
        MemoryUtils::monitoringMemory();
        unset($existsDetails);
        gc_collect_cycles();
        $data = [];
        foreach ($dataDetails->toArray() as $groupKey => $group) {
            $group['partkey'] = $groupKey;
            $data[$groupKey] = $group;

        }
        MemoryUtils::monitoringMemory();
        unset($dataDetails);
        gc_collect_cycles();
        Log::info($this->parsingSetting->brand . '-start fetching Analogy Details count' . count($data));


        $chunks = array_chunk($data, 3000);
        foreach ($chunks as $i => $chunk) {
            Log::info($this->parsingSetting->brand . '-start fetching Analogy Details chunk count' . count($chunk));
            $this->attempts = 0;
            $successRequestCount = 0;
            $result = $this->fetchRequestAnalogyDetails($chunk);
            if (count($result['success'])) {
                $successRequestCount += count($result['success']);
                $this->getAnalogyDetailsData($result['success'], $data);
            }

            unset($result['success']);
            gc_collect_cycles();

            if ($this->attempts > $this->max_attempts) {
                Log::critical($this->parsingSetting->brand . '-Faeil max_attempts', $chunk);
                throw new GrabberException("Failed fetchAndMergeToDetailAnalogyDetails. attempts > $this->attempts");
            }

            while (count($result['rejected'])) {
                MemoryUtils::monitoringMemory();
                gc_collect_cycles();
                $this->attempts++;
                if ($this->attempts > $this->max_attempts) {
                    throw new GrabberException("Failed fetching AnalogyDetails. attempts > $this->attempts");
                }
                $result = $this->fetchRequestAnalogyDetails($result['rejected']);
                if (count($result['success'])) {
                    $successRequestCount += count($result['success']);
                    $this->getAnalogyDetailsData($result['success'], $data);
                }

                $chunk_count = count($chunk) ?: 1;
                Log::info($this->parsingSetting->brand . '- fetching AnalogyDetails progress chunk = ' . 100 * $successRequestCount / $chunk_count);

                MemoryUtils::monitoringMemory();
                unset($result['success']);
                gc_collect_cycles();
            }

            $all_count = count($chunks) ?: 1;
            Log::info($this->parsingSetting->brand . '- fetching AnalogyDetails  progress = ' . 100 * ($i + 1) / $all_count);
            gc_collect_cycles();
        }
        unset($chunks);
        unset($data);
        gc_collect_cycles();
    }

    public function getAnalogyDetailsData($result, $data)
    {
        foreach ($result as $key => $responseArr) {
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
            MemoryUtils::monitoringMemory();
            unset($parser);
            unset($analogyDetailsData);
        }
    }


    protected function fetchRequestAnalogyDetails($data)
    {
        MemoryUtils::monitoringMemory();
        $this->request_count += count($data);
        $rejectedCategoryData = [];
        $successCategoryData = [];
        $result = $this->grabber->getAsyncDetailsBuyersGuid($data);
        MemoryUtils::monitoringMemory();
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
}
